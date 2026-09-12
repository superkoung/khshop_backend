<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class wishlistController extends Controller
{
    use ApiResponse;

    /**
     * Display user's wishlist.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $wishlists = Wishlist::query()
            ->where('user_id', $user->id)
            ->with([
                'product',
                'product.variants' => function ($query) {
                    $query->with([
                        'color',
                        'size',
                        'image',
                    ])
                    ->orderBy('id')
                    ->limit(1);
                },
            ])
            ->latest()
            ->get();

        return $this->successResponse(
            [
                'wishlists' => $wishlists,
            ],
            'Get wishlist successfully',
            200
        );
    }

    /**
     * Add product to wishlist.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = $request->user();

        // Check product
        $product = Product::find($validatedData['product_id']);

        if (!$product || !$product->is_active) {
            return $this->errorResponse(
                'Product is not available',
                422
            );
        }

        // Check if already exists
        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($wishlist) {
            return $this->errorResponse(
                'Product already exists in wishlist',
                409
            );
        }

        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $wishlist->load([
            'product',
            'product.brand',
            'product.category',
        ]);

        return $this->successResponse(
            [
                'wishlist' => $wishlist,
            ],
            'Product added to wishlist successfully',
            201
        );
    }

    /**
     * Remove product from wishlist.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$wishlist) {
            return $this->errorResponse(
                'Wishlist item not found',
                404
            );
        }

        $wishlist->delete();

        return $this->successResponse(
            null,
            'Product removed from wishlist successfully',
            200
        );
    }

    /**
     * Clear user's wishlist.
     */
    public function clear(Request $request)
    {
        $user = $request->user();

        Wishlist::where('user_id', $user->id)->delete();

        return $this->successResponse(
            null,
            'Wishlist cleared successfully',
            200
        );
    }

    /**
     * Merge guest wishlist into user's wishlist.
     */
    public function merge(Request $request)
    {
        $validatedData = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer|exists:products,id',
        ]);

        $user = $request->user();

        foreach ($validatedData['product_ids'] as $productId) {

            // Check product
            $product = Product::find($productId);

            if (!$product || !$product->is_active) {
                continue;
            }

            // Avoid duplicate
            Wishlist::firstOrCreate([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        // Return updated wishlist
        $wishlists = Wishlist::query()
            ->where('user_id', $user->id)
            ->with([
                'product',
                'product.brand',
                'product.category',
            ])
            ->latest()
            ->get();

        return $this->successResponse(
            [
                'wishlists' => $wishlists,
            ],
            'Guest wishlist merged successfully',
            200
        );
    }
}
