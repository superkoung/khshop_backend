<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product_variant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get or create user's cart
        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        // Get cart items
        $cartItems = CartItem::query()
            ->where('cart_id', $cart->id)
            ->with([
                'variant.product',
                'variant.color',
                'variant.size',
                'variant.image',
            ])
            ->get();

        return $this->successResponse(
            [
                'cart' => $cart,
                'cart_items' => $cartItems,
            ],
            'Get cart items successfully',
            200
        );
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'color_id'   => 'required|exists:colors,id',
            'size_id'    => 'required|exists:sizes,id',
            'qty'        => 'required|integer|min:1',
        ]);

        $user = $request->user();

        // Get or create user's cart
        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        // Find selected variant
        $variant = Product_variant::with('product')
            ->where('product_id', $validatedData['product_id'])
            ->where('color_id', $validatedData['color_id'])
            ->where('size_id', $validatedData['size_id'])
            ->first();

        if (!$variant) {
            return $this->errorResponse(
                'Product variant not found',
                404
            );
        }

        // Check product status
        if (!$variant->product || !$variant->product->is_active) {
            return $this->errorResponse(
                'Product is not available',
                422
            );
        }

        // Check stock
        if ($validatedData['qty'] > $variant->stock) {
            return $this->errorResponse(
                'Not enough stock',
                422
            );
        }

        // Check if this variant already exists in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('variant_id', $variant->id)
            ->first();

        // Calculate current unit price
        $unitPrice = $variant->product->price
            + ($variant->price_modifier ?? 0);

        if ($cartItem) {

            $newQty = $cartItem->qty + $validatedData['qty'];

            // Check total quantity against stock
            if ($newQty > $variant->stock) {
                return $this->errorResponse(
                    'Not enough stock',
                    422
                );
            }

            $cartItem->update([
                'qty'       => $newQty,
                'sub_total' => $newQty * $unitPrice,
            ]);

        } else {

            $cartItem = CartItem::create([
                'cart_id'    => $cart->id,
                'variant_id' => $variant->id,
                'qty'        => $validatedData['qty'],
                'sub_total' => $validatedData['qty'] * $unitPrice,
            ]);
        }

        // Load relationships for frontend response
        $cartItem->load([
            'variant.product',
            'variant.color',
            'variant.size',
            'variant.image',
        ]);

        return $this->successResponse(
            [
                'cart_item' => $cartItem,
            ],
            'Product added to cart successfully',
            201
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'qty' => 'required|integer|min:0'
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return $this->errorResponse('Cart not found', 404);
        }

        $cart_item = CartItem::where('cart_id', $cart->id)->where('id', $id)->first();

        if (!$cart_item) {
            return $this->errorResponse('Cart item not found', 404);
        }

        if ($validatedData['qty'] == 0) {
            $cart_item->delete();

            return $this->successResponse(
                null,
                'Cart item removed successfully',
                200
            );
        }

        $variant = Product_variant::with('product')->find($cart_item->variant_id);

        if (!$variant || $validatedData['qty'] > $variant->stock) {
            return $this->errorResponse('Not enough stock', 422);
        }

        $price = $variant->product->price + ($variant->price_modifier ?? 0);
        $cart_item->update([
            'qty' => $validatedData['qty'],
            'sub_total' => $validatedData['qty'] * $price
        ]);

        return $this->successResponse(
            ['cart_item' => $cart_item],
            'Cart item updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return $this->errorResponse('Cart not found', 404);
        }

        $cart_item = CartItem::where('cart_id', $cart->id)->where('id', $id)->first();

        if (!$cart_item) {
            return $this->errorResponse('Cart item not found', 404);
        }

        $cart_item->delete();

        return $this->successResponse(null, "Cart item deleted successfully", 200);
    }

    /**
     * Clear all items in cart.
     */
    public function clear(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
        }

        return $this->successResponse(null, 'Cart items cleared successfully', 200);
    }

    public function merge(Request $request)
    {
        $validatedData = $request->validate([
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        foreach ($validatedData['items'] as $item) {

            $variant = Product_variant::with('product')
                ->find($item['variant_id']);

            // Variant not found / product not available
            if (!$variant || !$variant->product || !$variant->product->is_active) {
                continue;
            }

            // No stock
            if ($variant->stock <= 0) {
                continue;
            }

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('variant_id', $variant->id)
                ->first();

            $currentQty = $cartItem?->qty ?? 0;

            $newQty = $currentQty + $item['qty'];

            // Prevent quantity from exceeding stock
            if ($newQty > $variant->stock) {
                $newQty = $variant->stock;
            }

            $unitPrice = $variant->product->price
                + ($variant->price_modifier ?? 0);

            $subTotal = $newQty * $unitPrice;

            if ($cartItem) {

                $cartItem->update([
                    'qty' => $newQty,
                    'sub_total' => $subTotal,
                ]);

            } else {

                CartItem::create([
                    'cart_id' => $cart->id,
                    'variant_id' => $variant->id,
                    'qty' => $newQty,
                    'sub_total' => $subTotal,
                ]);
            }
        }

        $cartItems = CartItem::where('cart_id', $cart->id)
            ->with([
                'variant.product',
                'variant.color',
                'variant.size',
                'variant.image',
            ])
            ->get();

        return $this->successResponse(
            [
                'cart' => $cart,
                'cart_items' => $cartItems,
            ],
            'Guest cart merged successfully',
            200
        );
    }
}
