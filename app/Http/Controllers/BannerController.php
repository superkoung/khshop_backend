<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class BannerController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Banner::with('category:id,name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active') && $request->is_active !== 'all') {
            $isActive = $request->is_active === '1' || $request->is_active === 'true';
            $query->where('is_active', $isActive);
        }

        if ($request->filled('menu_id')) {
            if ($request->menu_id === 'homepage') {
                $query->whereNull('menu_id');
            } else {
                $query->where('menu_id', $request->menu_id);
            }
        }

        $banners = $query->latest()->get();

        return $this->successResponse(
            ['banners' => $banners],
            'Banners retrieved successfully',
            200
        );
    }

    public function show(int $id)
    {
        $banner = Banner::with('category:id,name')->find($id);

        if (!$banner) {
            return $this->errorResponse('Banner not found', 404);
        }

        return $this->successResponse(
            ['banner' => $banner],
            'Banner retrieved successfully',
            200
        );
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'image'       => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'menu_id'     => 'sometimes|nullable|integer|exists:categories,id',
            'is_active'   => 'sometimes|boolean',
        ]);

        $uploadedImage = $request->file('image')->storeOnCloudinary('banner_images');
        $validatedData['image_path'] = $uploadedImage->getSecurePath();

        if (!isset($validatedData['is_active'])) {
            $validatedData['is_active'] = true;
        }

        $banner = Banner::create($validatedData);

        return $this->successResponse(
            ['banner' => $banner],
            'Banner created successfully',
            201
        );
    }

    public function update(Request $request, int $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return $this->errorResponse('Banner not found', 404);
        }

        $validatedData = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'image'       => 'sometimes|image|mimes:png,jpg,jpeg,webp|max:2048',
            'menu_id'     => 'sometimes|nullable|integer|exists:categories,id',
            'is_active'   => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image')) {
            $uploadedImage = $request->file('image')->storeOnCloudinary('banner_images');
            $validatedData['image_path'] = $uploadedImage->getSecurePath();
        }

        $banner->update($validatedData);

        return $this->successResponse(
            ['banner' => $banner],
            'Banner updated successfully',
            200
        );
    }

    public function destroy(int $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return $this->errorResponse('Banner not found', 404);
        }

        $banner->delete();

        return $this->successResponse(null, 'Banner deleted successfully', 200);
    }

    public function updateStatus(Request $request, int $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return $this->errorResponse('Banner not found', 404);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $banner->update(['is_active' => $validated['is_active']]);

        return $this->successResponse(
            ['banner' => $banner],
            'Banner status updated successfully',
            200
        );
    }
}
