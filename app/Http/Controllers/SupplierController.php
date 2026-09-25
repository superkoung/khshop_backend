<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    use ApiResponse;

    private const DEFAULT_PER_PAGE = 10;

    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active') && $request->is_active !== 'all') {
            $isActive = $request->is_active === '1' || $request->is_active === 'true';
            $query->where('is_active', $isActive);
        }

        $suppliers = $query->latest()
            ->paginate(
                $request->integer('per_page', self::DEFAULT_PER_PAGE),
                ['*'],
                'page',
                $request->integer('page', 1)
            )
            ->withQueryString();

        return $this->successResponse(
            ['suppliers' => $suppliers],
            'Get suppliers data',
            200
        );
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'         => 'required|string|max:255',
            'contact_name' => 'sometimes|nullable|string|max:255',
            'phone'        => 'sometimes|nullable|string|max:255',
            'email'        => 'sometimes|nullable|email|max:255',
            'address'      => 'sometimes|nullable|string',
            'is_active'    => 'sometimes|boolean',
            'notes'        => 'sometimes|nullable|string',
        ]);

        if (!isset($validatedData['is_active'])) {
            $validatedData['is_active'] = true;
        }

        $supplier = Supplier::create($validatedData);

        return $this->successResponse(
            ['supplier' => $supplier],
            'Supplier created successfully',
            201
        );
    }

    public function show(int $id)
    {
        $supplier = Supplier::with([
            'products' => function ($q) {
                $q->select(
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.price',
                    'products.sale_price',
                    'products.discount_type',
                    'products.discount_value',
                    'products.is_active'
                )->withPivot('supplier_sku', 'cost_price', 'is_primary');
            },
        ])->find($id);

        if (!$supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        return $this->successResponse(
            ['supplier' => $supplier],
            'Get supplier data',
            200
        );
    }

    public function update(Request $request, int $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        $validatedData = $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'contact_name' => 'sometimes|nullable|string|max:255',
            'phone'        => 'sometimes|nullable|string|max:255',
            'email'        => 'sometimes|nullable|email|max:255',
            'address'      => 'sometimes|nullable|string',
            'is_active'    => 'sometimes|boolean',
            'notes'        => 'sometimes|nullable|string',
        ]);

        $supplier->update($validatedData);

        return $this->successResponse(
            ['supplier' => $supplier],
            'Supplier updated successfully',
            200
        );
    }

    public function destroy(int $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        $supplier->delete();

        return $this->successResponse(null, 'Supplier deleted successfully', 200);
    }
}
