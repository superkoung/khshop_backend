<?php

namespace App\Http\Controllers;

use App\Models\Product_variant;
use App\Services\NotificationDispatcher;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use ApiResponse;

    private const LOW_STOCK_THRESHOLD = 10;
    private const DEFAULT_PER_PAGE = 10;

    public function index(Request $request)
    {
        $query = $this->baseInventoryQuery()
            ->with([
                'product' => function ($q) {
                    $q->select('id', 'name', 'category_id', 'is_active', 'price')
                        ->with('category:id,name');
                },
                'color:id,name',
                'size:id,name',
            ]);

        $this->applyInventoryFilters($query, $request);

        // Aggregate over the full filtered set (not just the current page).
        $summaryRow = $this->summarize($query);

        $variants = $query
            ->latest('product_variants.created_at')
            ->orderBy('product_variants.id')
            ->paginate(
                $request->integer('per_page', self::DEFAULT_PER_PAGE),
                ['*'],
                'page',
                $request->integer('page', 1)
            )
            ->withQueryString();

        $variants->setCollection(
            $variants->getCollection()->map(fn ($variant) => $this->mapInventoryRow($variant))
        );

        return $this->successResponse(
            [
                'inventory' => $variants,
                'summary' => [
                    'total_variants' => (int) ($summaryRow->total_variants ?? 0),
                    'total_units' => (int) ($summaryRow->total_units ?? 0),
                    'total_inventory_value' => round((float) ($summaryRow->total_inventory_value ?? 0), 2),
                    'total_selling_value' => round((float) ($summaryRow->total_selling_value ?? 0), 2),
                ],
            ],
            'Inventory retrieved successfully',
            200
        );
    }

    public function getLowStock(Request $request)
    {
        $query = $this->baseInventoryQuery()
            ->where('product_variants.stock', '<=', self::LOW_STOCK_THRESHOLD)
            ->with([
                'product' => function ($q) {
                    $q->select('id', 'name', 'category_id', 'is_active', 'price')
                        ->with('category:id,name');
                },
                'color:id,name',
                'size:id,name',
            ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_variants.sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $summaryRow = $this->summarize($query);

        $variants = $query
            ->latest('product_variants.created_at')
            ->orderBy('product_variants.id')
            ->paginate(
                $request->integer('per_page', self::DEFAULT_PER_PAGE),
                ['*'],
                'page',
                $request->integer('page', 1)
            )
            ->withQueryString();

        $variants->setCollection(
            $variants->getCollection()->map(function ($variant) {
                $row = $this->mapInventoryRow($variant);
                $row['status'] = ((int) $variant->stock) === 0 ? 'Out of Stock' : 'Low Stock';
                return $row;
            })
        );

        return $this->successResponse(
            [
                'inventory' => $variants,
                'summary' => [
                    'total_variants' => (int) ($summaryRow->total_variants ?? 0),
                    'total_units' => (int) ($summaryRow->total_units ?? 0),
                    'total_inventory_value' => round((float) ($summaryRow->total_inventory_value ?? 0), 2),
                    'total_selling_value' => round((float) ($summaryRow->total_selling_value ?? 0), 2),
                ],
            ],
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

        $previousStock = (int) $variant->stock;
        $variant->update(['stock' => $validated['stock']]);

        // Inventory-changing event only — never on list load.
        NotificationDispatcher::notifyStockChange($variant, $previousStock);

        Cache::flush();

        $variant->load([
            'product:id,name,price',
            'color:id,name',
            'size:id,name',
        ]);

        // mapInventoryRow expects join aliases when called from list queries.
        $variant->product_price = $variant->product->price ?? null;
        $variant->cost_price = DB::table('product_suppliers')
            ->where('product_id', $variant->product_id)
            ->where('is_primary', true)
            ->value('cost_price');

        $row = $this->mapInventoryRow($variant);

        return $this->successResponse(
            ['variant' => $row],
            'Stock updated successfully',
            200
        );
    }

    private function baseInventoryQuery()
    {
        return Product_variant::query()
            ->join('products', function ($join) {
                $join->on('products.id', '=', 'product_variants.product_id')
                    ->whereNull('products.deleted_at');
            })
            ->leftJoin('product_suppliers', function ($join) {
                $join->on('product_suppliers.product_id', '=', 'products.id')
                    ->where('product_suppliers.is_primary', true);
            })
            ->whereNull('product_variants.deleted_at')
            ->select(
                'product_variants.*',
                'products.price as product_price',
                'product_suppliers.cost_price as cost_price'
            );
    }

    private function applyInventoryFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_variants.sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'in_stock') {
                $query->where('product_variants.stock', '>', self::LOW_STOCK_THRESHOLD);
            } elseif ($status === 'low_stock') {
                $query->where('product_variants.stock', '>', 0)
                    ->where('product_variants.stock', '<=', self::LOW_STOCK_THRESHOLD);
            } elseif ($status === 'out_of_stock') {
                $query->where('product_variants.stock', '=', 0);
            }
        }
    }

    /**
     * SQL aggregates over the full filtered set (clears row columns first so
     * Postgres does not reject mixed aggregate + star selects).
     */
    private function summarize($query): object
    {
        $base = (clone $query)->getQuery();
        $base->columns = null;

        return $base
            ->selectRaw('
                COUNT(*) as total_variants,
                COALESCE(SUM(product_variants.stock), 0) as total_units,
                COALESCE(SUM(COALESCE(product_suppliers.cost_price, 0) * product_variants.stock), 0) as total_inventory_value,
                COALESCE(SUM((products.price + COALESCE(product_variants.price_modifier, 0)) * product_variants.stock), 0) as total_selling_value
            ')
            ->first();
    }

    private function mapInventoryRow(Product_variant $variant): array
    {
        $stock = (int) $variant->stock;
        $status = 'In Stock';
        if ($stock === 0) {
            $status = 'Out of Stock';
        } elseif ($stock <= self::LOW_STOCK_THRESHOLD) {
            $status = 'Low Stock';
        }

        // Real cost from primary supplier pivot (product_suppliers.cost_price).
        $costPrice = null;
        if (isset($variant->cost_price) && $variant->cost_price !== null && $variant->cost_price !== '') {
            $costPrice = round((float) $variant->cost_price, 2);
        }

        $basePrice = $variant->product_price !== null && $variant->product_price !== ''
            ? (float) $variant->product_price
            : (float) ($variant->product->price ?? 0);
        $sellingPrice = round($basePrice + (float) ($variant->price_modifier ?? 0), 2);

        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'product' => $variant->product->name ?? '',
            'category' => $variant->product->category->name ?? '',
            'sku' => $variant->sku ?? '',
            'color' => $variant->color->name ?? '',
            'size' => $variant->size->name ?? '',
            'stock' => $stock,
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'inventory_value' => $costPrice !== null ? round($costPrice * $stock, 2) : null,
            'selling_stock_value' => round($sellingPrice * $stock, 2),
            'status' => $status,
            'is_active' => $variant->is_active,
        ];
    }
}
