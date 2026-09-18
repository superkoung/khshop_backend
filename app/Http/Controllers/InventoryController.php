<?php

namespace App\Http\Controllers;

use App\Models\Product_variant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Product_variant::query()
            ->with([
                'product' => function ($q) {
                    $q->select('id', 'name', 'category_id', 'is_active')
                        ->with('category:id,name');
                },
                'color:id,name',
                'size:id,name',
            ])
            ->whereNull('deleted_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'in_stock') {
                $query->where('stock', '>', 10);
            } elseif ($status === 'low_stock') {
                $query->where('stock', '>', 0)->where('stock', '<=', 10);
            } elseif ($status === 'out_of_stock') {
                $query->where('stock', '=', 0);
            }
        }

        $variants = $query->latest()
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $inventory = $variants->getCollection()->map(function ($variant) {
            $stock = $variant->stock;
            $status = 'In Stock';
            if ($stock === 0) {
                $status = 'Out of Stock';
            } elseif ($stock <= 10) {
                $status = 'Low Stock';
            }

            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product' => $variant->product->name ?? '',
                'category' => $variant->product->category->name ?? '',
                'sku' => $variant->sku ?? '',
                'color' => $variant->color->name ?? '',
                'size' => $variant->size->name ?? '',
                'stock' => $stock,
                'status' => $status,
                'is_active' => $variant->is_active,
            ];
        });

        $variants->setCollection($inventory);

        return $this->successResponse(
            ['inventory' => $variants],
            'Inventory retrieved successfully',
            200
        );
    }

    public function getLowStock(Request $request)
    {
        $query = Product_variant::query()
            ->with([
                'product' => function ($q) {
                    $q->select('id', 'name')
                        ->with('category:id,name');
                },
                'color:id,name',
                'size:id,name',
            ])
            ->whereNull('deleted_at')
            ->where('stock', '<=', 10);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $variants = $query->latest()
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $inventory = $variants->getCollection()->map(function ($variant) {
            $stock = $variant->stock;
            $status = $stock === 0 ? 'Out of Stock' : 'Low Stock';

            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product' => $variant->product->name ?? '',
                'category' => $variant->product->category->name ?? '',
                'sku' => $variant->sku ?? '',
                'color' => $variant->color->name ?? '',
                'size' => $variant->size->name ?? '',
                'stock' => $stock,
                'status' => $status,
                'is_active' => $variant->is_active,
            ];
        });

        $variants->setCollection($inventory);

        return $this->successResponse(
            ['inventory' => $variants],
            'Low stock inventory retrieved successfully',
            200
        );
    }

    public function updateStock(Request $request, int $id)
    {
        $variant = Product_variant::whereNull('deleted_at')->find($id);

        if (!$variant) {
            return $this->errorResponse('Variant not found', 404);
        }

        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $variant->update(['stock' => $validated['stock']]);

        $stock = $variant->stock;
        $status = 'In Stock';
        if ($stock === 0) {
            $status = 'Out of Stock';
        } elseif ($stock <= 10) {
            $status = 'Low Stock';
        }

        $variant->load([
            'product:id,name',
            'color:id,name',
            'size:id,name',
        ]);

        return $this->successResponse(
            [
                'variant' => [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product' => $variant->product->name ?? '',
                    'sku' => $variant->sku ?? '',
                    'color' => $variant->color->name ?? '',
                    'size' => $variant->size->name ?? '',
                    'stock' => $stock,
                    'status' => $status,
                    'is_active' => $variant->is_active,
                ],
            ],
            'Stock updated successfully',
            200
        );
    }
}
