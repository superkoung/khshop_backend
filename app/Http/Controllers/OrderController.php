<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display user's orders.
     */
public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with([
                'items.variant.product',
                'items.variant.color',
                'items.variant.size',
                'items.variant.image',
            ])
            ->latest()
            ->paginate(10);

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
            'shipping_address' => 'required|string',
            'receiver_phone'   => 'required|string|max:20',
            'order_type'       => 'required|in:delivery,pickup',
            'note'             => 'nullable|string',
        ]);

        $user = $request->user();

        try {
            $order = DB::transaction(function () use ($user, $validatedData) {

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

                    // Snapshot shipping information
                    'shipping_address' => $validatedData['shipping_address'],
                    'receiver_phone'   => $validatedData['receiver_phone'],

                    'status'         => 'pending',
                    'order_type'     => $validatedData['order_type'],
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
                    | 6. Decrease stock
                    |--------------------------------------------------------------------------
                    */

                    $variant->decrement('stock', $cartItem->qty);
                }


                /*
                |--------------------------------------------------------------------------
                | 7. Clear cart
                |--------------------------------------------------------------------------
                */

                $cart->cartItems()->delete();


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

                $order = Order::where('user_id', $user->id)
                    ->with('items')
                    ->find($id);

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


                /*
                |--------------------------------------------------------------------------
                | Restore stock
                |--------------------------------------------------------------------------
                */

                foreach ($order->items as $item) {

                    $variant = $item->variant;

                    if ($variant) {
                        $variant->increment('stock', $item->qty);
                    }
                }


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
