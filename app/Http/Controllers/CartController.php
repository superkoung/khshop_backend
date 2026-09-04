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
        $cart = Cart::firstOrCreate([
            'user_id' => $user->id
        ]);

        $cart_items = CartItem::where('cart_id', $cart->id)
            ->with(['variant.product', 'variant.color', 'variant.size'])
            ->get();

        return $this->successResponse(
            ['cart_items' => $cart_items],
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

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        // Eager load product relationship
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

        // Check stock
        if ($validatedData['qty'] > $variant->stock) {
            return $this->errorResponse(
                'Not enough stock',
                422
            );
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('variant_id', $variant->id)
            ->first();

        $unitPrice = $variant->product->price + ($variant->price_modifier ?? 0);

        if ($cartItem) {
            $newQty = $cartItem->qty + $validatedData['qty'];

            if ($newQty > $variant->stock) {
                return $this->errorResponse(
                    'Not enough stock',
                    422
                );
            }

            $cartItem->update([
                'qty' => $newQty,
                'sub_total' => $newQty * $unitPrice,
            ]);
        } else {
            $cartItem = CartItem::create([
                'cart_id'    => $cart->id,
                'variant_id' => $variant->id,
                'qty'        => $validatedData['qty'],
                'sub_total'  => $validatedData['qty'] * $unitPrice,
            ]);
        }

        return $this->successResponse(
            ['cart_item' => $cartItem],
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
}
