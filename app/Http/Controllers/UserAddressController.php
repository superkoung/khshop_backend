<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return $this->successResponse(
            ['addresses' => $addresses],
            'Addresses retrieved successfully',
            200
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_name'  => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'address_line'   => 'required|string|max:500',
            'city_province'  => 'nullable|string|max:255',
            'is_default'     => 'nullable|boolean',
        ]);

        $user = $request->user();

        if ($user->addresses()->count() === 0) {
            $validated['is_default'] = true;
        }

        if (!empty($validated['is_default']) && $validated['is_default']) {
            $user->addresses()->where('is_default', true)->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($validated);

        return $this->successResponse(
            ['address' => $address],
            'Address created successfully',
            201
        );
    }

    public function show(Request $request, int $id)
    {
        $address = $request->user()
            ->addresses()
            ->where('id', $id)
            ->firstOrFail();

        return $this->successResponse(
            ['address' => $address],
            'Address retrieved successfully',
            200
        );
    }

    public function update(Request $request, int $id)
    {
        $address = $request->user()
            ->addresses()
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'receiver_name'  => 'sometimes|required|string|max:255',
            'receiver_phone' => 'sometimes|required|string|max:20',
            'address_line'   => 'sometimes|required|string|max:500',
            'city_province'  => 'nullable|string|max:255',
            'is_default'     => 'nullable|boolean',
        ]);

        if (!empty($validated['is_default']) && $validated['is_default']) {
            $request->user()->addresses()->where('is_default', true)->update(['is_default' => false]);
        }

        $address->update($validated);

        return $this->successResponse(
            ['address' => $address],
            'Address updated successfully',
            200
        );
    }

    public function destroy(Request $request, int $id)
    {
        $address = $request->user()
            ->addresses()
            ->where('id', $id)
            ->firstOrFail();

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $oldest = $request->user()->addresses()->latest()->first();
            if ($oldest) {
                $oldest->update(['is_default' => true]);
            }
        }

        return $this->successResponse(null, 'Address deleted successfully', 200);
    }

    public function setDefault(Request $request, int $id)
    {
        $address = $request->user()
            ->addresses()
            ->where('id', $id)
            ->firstOrFail();

        $request->user()->addresses()->where('is_default', true)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return $this->successResponse(
            ['address' => $address],
            'Default address updated successfully',
            200
        );
    }
}
