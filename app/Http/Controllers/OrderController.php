<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product_variant;
use App\Notifications\NewOrderNotification;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display user's orders.
     */
public function index(Request $request)
    {
        $user = $request->user();

        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $orders = Order::where('user_id', $user->id)
            ->with([
                'items.variant.product',
                'items.variant.color',
                'items.variant.size',
                'items.variant.image',
                // Payment method/status — used by the UI to tell KHQR from COD.
                'payments:id,order_id,method,status,paid_at,created_at',
            ])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => $orders,
        ]);
    }


    /**
     * Create a new order from user's cart.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            // Required for delivery; optional/empty for pickup.
            'shipping_address' => 'required_if:order_type,delivery|nullable|string',
            'receiver_phone'   => 'required_if:order_type,delivery|nullable|string|max:20',
            'order_type'       => 'required|in:delivery,pickup',
            'note'             => 'nullable|string',
            // bakong: keep cart until payment is verified. cod/legacy: clear on create.
            'payment_method'   => 'nullable|in:bakong,cod',
        ]);

        $paymentMethod = $validatedData['payment_method'] ?? 'cod';
        $orderType = $validatedData['order_type'];
        // orders.shipping_address / receiver_phone are NOT NULL — store '' for pickup.
        $shippingAddress = $validatedData['shipping_address'] ?? '';
        $receiverPhone = $validatedData['receiver_phone'] ?? '';

        $user = $request->user();

        try {
            $order = DB::transaction(function () use ($user, $validatedData, $paymentMethod, $orderType, $shippingAddress, $receiverPhone) {

                /*
                |--------------------------------------------------------------------------
                | 1. Get user's cart
                |--------------------------------------------------------------------------
                */

                $cart = Cart::where('user_id', $user->id)
                    ->with([
                        'cartItems.variant.product',
                        'cartItems.variant.color',
                        'cartItems.variant.size',
                        'cartItems.variant.image',
                    ])
                    ->first();

                if (!$cart || $cart->cartItems->isEmpty()) {
                    abort(422, 'Your cart is empty.');
                }


                /*
                |--------------------------------------------------------------------------
                | 2. Check stock + calculate total
                |--------------------------------------------------------------------------
                */

                $totalAmount = 0;

                foreach ($cart->cartItems as $cartItem) {

                    $variant = $cartItem->variant;

                    // Variant no longer exists
                    if (!$variant) {
                        abort(
                            422,
                            'One of the products in your cart is no longer available.'
                        );
                    }

                    // Product no longer exists
                    if (!$variant->product) {
                        abort(
                            422,
                            'One of the products in your cart is no longer available.'
                        );
                    }

                    // Product inactive
                    if (!$variant->product->is_active) {
                        abort(
                            422,
                            "Product {$variant->product->name} is currently unavailable."
                        );
                    }

                    // Check stock
                    if ($cartItem->qty > $variant->stock) {
                        abort(
                            422,
                            "Not enough stock for {$variant->product->name}."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Calculate current unit price
                    |--------------------------------------------------------------------------
                    */

                    $unitPrice =
                        $variant->product->price +
                        ($variant->price_modifier ?? 0);

                    $subTotal = $unitPrice * $cartItem->qty;

                    $totalAmount += $subTotal;
                }


                /*
                |--------------------------------------------------------------------------
                | 3. Financial calculation
                |--------------------------------------------------------------------------
                */

                $discountAmount = 0;

                $taxAmount = 0;

                $netAmount =
                    $totalAmount
                    - $discountAmount
                    + $taxAmount;


                /*
                |--------------------------------------------------------------------------
                | 4. Create Order
                |--------------------------------------------------------------------------
                */

                $order = Order::create([
                    'user_id'          => $user->id,
                    'total_amount'     => $totalAmount,
                    'discount_amount' => $discountAmount,
                    'tax_amount'      => $taxAmount,
                    'net_amount'      => $netAmount,

                    // Snapshot shipping information (empty for pickup)
                    'shipping_address' => $shippingAddress,
                    'receiver_phone'   => $receiverPhone,

                    'status'         => 'pending',
                    'order_type'     => $orderType,
                    'payment_status' => 'unpaid',

                    'note' => $validatedData['note'] ?? null,
                ]);


                /*
                |--------------------------------------------------------------------------
                | 5. Create Order Items
                |--------------------------------------------------------------------------
                */

                foreach ($cart->cartItems as $cartItem) {

                    $variant = $cartItem->variant;
                    $product = $variant->product;

                    $unitPrice =
                        $product->price +
                        ($variant->price_modifier ?? 0);

                    $subTotal =
                        $unitPrice * $cartItem->qty;


                    /*
                    |--------------------------------------------------------------------------
                    | Variant name snapshot
                    |--------------------------------------------------------------------------
                    */

                    $variantName = collect([
                        $variant->color?->name,
                        $variant->size?->name,
                    ])
                        ->filter()
                        ->implode(' / ');


                    OrderItem::create([
                        'order_id' => $order->id,

                        'variant_id' => $variant->id,

                        'qty' => $cartItem->qty,

                        'unit_price' => $unitPrice,

                        'sub_total' => $subTotal,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | 6. Decrease stock (atomic, overselling-safe)
                    |
                    | UPDATE ... SET stock = stock - qty WHERE stock >= qty.
                    | Affected-rows guard re-validates stock against concurrent
                    | orders so stock can never go negative. Runs once here —
                    | payment verification never deducts stock again.
                    |--------------------------------------------------------------------------
                    */

                    $affected = Product_variant::whereKey($variant->id)
                        ->where('stock', '>=', $cartItem->qty)
                        ->decrement('stock', $cartItem->qty);

                    if (!$affected) {
                        abort(
                            422,
                            "Not enough stock for {$variant->product->name}."
                        );
                    }
                }

                Cache::flush();

                /*
                |--------------------------------------------------------------------------
                | 7. Clear cart (COD / legacy only)
                |
                | Bakong: order create ≠ payment success. Cart items stay until
                | BakongPaymentController verifies payment and clears them.
                |--------------------------------------------------------------------------
                */

                if ($paymentMethod !== 'bakong') {
                    $cart->cartItems()->delete();
                }


                /*
                |--------------------------------------------------------------------------
                | 8. Load Order relationships
                |--------------------------------------------------------------------------
                */

                $order->load([
                    'items.variant.product',
                    'items.variant.color',
                    'items.variant.size',
                    'items.variant.image',
                ]);

                return $order;
            });


            /*
            |--------------------------------------------------------------------------
            | Success response
            |--------------------------------------------------------------------------
            */

            // Notify after successful commit only (never inside failed txn).
            NotificationDispatcher::toAdminStaff(new NewOrderNotification(
                (int) $order->id,
                '#KH' . $order->id,
                $order->user->name ?? ''
            ));

            foreach ($order->items as $item) {
                if ($item->variant) {
                    NotificationDispatcher::notifyStockChange($item->variant);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order,
            ], 201);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Return validation/business error
            |--------------------------------------------------------------------------
            */

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }

            /*
            |--------------------------------------------------------------------------
            | Unexpected error
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Display a specific order.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->with([
                'items.variant.product',
                'items.variant.color',
                'items.variant.size',
                'items.variant.image',
                // Payment method/status — used by the UI to tell KHQR from COD.
                'payments:id,order_id,method,status,paid_at,created_at',
            ])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => $order,
        ]);
    }


    /**
     * Cancel user's order.
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        try {

            $order = DB::transaction(function () use ($user, $id) {

                // Row lock serializes against khqr:expire-pending and payment
                // verification — stock is restored by exactly one winner.
                $order = Order::where('user_id', $user->id)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    abort(404, 'Order not found.');
                }


                /*
                |--------------------------------------------------------------------------
                | Only pending orders can be cancelled
                |--------------------------------------------------------------------------
                */

                if ($order->status !== 'pending') {
                    abort(
                        422,
                        'This order can no longer be cancelled.'
                    );
                }

                // Paid orders are never cancellable here (status should already
                // be processing — defense for any paid+pending state).
                if ($order->payment_status === 'paid') {
                    abort(
                        422,
                        'This order has been paid and cannot be cancelled.'
                    );
                }

                // KHQR/Bakong orders cannot be cancelled manually: stock was
                // deducted at creation and is released only when the payment
                // window expires (khqr:expire-pending) — never twice here.
                if ($order->payments()->where('method', 'bakong')->exists()) {
                    abort(
                        422,
                        'KHQR payment orders cannot be cancelled manually. The order is cancelled automatically if the payment is not completed in time.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Restore stock (COD — once; row lock + status check above
                | make a second restore impossible)
                |--------------------------------------------------------------------------
                */

                foreach ($order->items()->with('variant')->get() as $item) {

                    $variant = $item->variant;

                    if ($variant) {
                        $variant->increment('stock', $item->qty);
                    }
                }

                Cache::flush();

                /*
                |--------------------------------------------------------------------------
                | Update order status
                |--------------------------------------------------------------------------
                */

                $order->update([
                    'status' => 'cancelled',
                ]);

                return $order;
            });


            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.',
                'data' => $order,
            ]);

        } catch (\Throwable $e) {

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
