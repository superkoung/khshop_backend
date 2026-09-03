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
        $cart = Cart::query()->firstOrCreate([
            'user_id'=>$user->id
        ]);

        $cart_items = CartItem::where('cart_id', $cart->id)->get();
        $cart_items->load('variant.product');
        return $this->successResponse(
            ['cart_items' => $cart_items],
            'Get cart items',
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

        $variant = Product_variant::where('product_id', $validatedData['product_id'])
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
                'sub_total' => $newQty *
                    ($variant->product->price + $variant->price_modifier),
            ]);
        } else {

            $price = $variant->product->price + $variant->price_modifier;

            $cartItem = CartItem::create([
                'cart_id'   => $cart->id,
                'variant_id' => $variant->id,
                'qty'       => $validatedData['qty'],
                'sub_total' => $validatedData['qty'] * $price,
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
        $validatedData=$request->validate([
            'qty'=>"required|integer|min:0"
        ]);
        $user=$request->user();
        $cart=Cart::where('user_id',$user->id)->first();

        $cart_item=CartItem::where('cart_id',$cart->id)->where('id',$id)->first();

        $variant=Product_variant::find($cart_item->variant_id);

        if ($validatedData['qty'] == 0) {
            $cart_item->delete();

            return $this->successResponse(
                null,
                'Cart item removed successfully',
                200
            );
        }
        if($validatedData['qty']>$variant->stock){
            return $this->errorResponse('Not enough stock',422);
        }
        $price=$variant->price_modifier+$variant->product->price;
        $cart_item->update([
            'qty'=>$validatedData['qty'],
            'sub_total'=>$validatedData['qty']*$price
        ]);

        return $this->successResponse(
            ['cart_item'=>$cart_item],
            'Cart item updated successfully',
            200
        );



    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request,string $id)
    {
        $user=$request->user();
        $cart=Cart::where('user_id',$user->id)->first();

        $cart_item=CartItem::where('cart_id',$cart->id)->where('id',$id)->first();
        $cart_item->delete();

        return $this->successResponse(null,"Cart item deleted successfully",200);
    }

    public function clear(Request $request){
        $user=$request->user();
        $cart=Cart::where('user_id',$user->id)->first();
        $cart_item=CartItem::where('cart_id',$cart->id)->delete();
        $cart_item->delete();

        return $this->successResponse(null,'Cart item cleared successfully',200);
    }
}
