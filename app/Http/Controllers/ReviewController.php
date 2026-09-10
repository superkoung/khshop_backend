<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;


    class ReviewController extends Controller
{
    use ApiResponse;
    public function index($productId)
    {
        $product = Product::findOrFail($productId);

        $reviews = Review::with('user:id,name')
            ->where('product_id', $product->id)
            ->latest()
            ->get();

        return $this->successResponse(
            ['reviews' => $reviews],
            'Get product reviews',
            200
        );
    }

    /**
     * Create a review
     */
    public function store(Request $request, $productId)
    {
        $validatedData = $request->validate([
            'comment' => 'required|string|min:1|max:1000',
        ]);

        $user = $request->user();

        Product::findOrFail($productId);

        // Prevent the same user from reviewing the same product twice
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();

        if ($existingReview) {
            return $this->errorResponse(
                'You have already reviewed this product.',
                422
            );
        }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'comment' => $validatedData['comment'],
        ]);

        $review->load('user:id,name');

        return $this->successResponse(
            ['review' => $review],
            'Review created successfully',
            201
        );
    }

    /**
     * Update own review
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'comment' => 'required|string|min:1|max:1000',
        ]);

        $user = $request->user();

        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $review->update([
            'comment' => $validatedData['comment'],
        ]);

        $review->load('user:id,name');

        return $this->successResponse(
            ['review' => $review],
            'Review updated successfully',
            200
        );
    }

    /**
     * Delete own review
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $review->delete();

        return $this->successResponse(
            [],
            'Review deleted successfully',
            200
        );
    }
}
