<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Product_variant;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $now = Carbon::now();

        // Summary
        $totalSales = Order::where('status', '!=', 'cancelled')->sum('net_amount');
        $totalOrders = Order::where('status', '!=', 'cancelled')->count();
        $totalCustomers = User::where('role_id', 1)->count();
        $totalProducts = Product::where('is_active', true)->count();

        // Sales overview (last 7 days)
        $salesOverview = $this->getDailySales(7);

        // Order overview by status
        $orderOverview = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Recent orders (latest 5)
        $recentOrders = Order::with('user:id,name')
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'customer' => $order->user->name ?? '—',
                    'date' => $order->created_at,
                    'payment' => $order->payment_status,
                    'status' => $order->status,
                    'total' => (float) $order->net_amount,
                ];
            });

        // Top products (top 5 by revenue)
        $topProducts = Product::with(['category:id,name', 'variants.image:id,image_path'])
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                $variants = $product->variants;
                $totalStock = $variants->sum('stock');

                $salesData = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
                    ->where('product_variants.product_id', $product->id)
                    ->where('orders.status', '!=', 'cancelled')
                    ->selectRaw('SUM(order_items.qty) as total_sold, SUM(order_items.sub_total) as total_revenue')
                    ->first();

                $image = null;
                $firstImage = $variants->firstWhere('image');
                if ($firstImage && $firstImage->image) {
                    $image = $firstImage->image->image_path;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'categoryName' => $product->category->name ?? 'Other',
                    'image' => $image,
                    'sold' => (int) ($salesData->total_sold ?? 0),
                    'revenue' => (float) ($salesData->total_revenue ?? 0),
                    'totalStock' => $totalStock,
                ];
            })
            ->sortByDesc('sold')
            ->values()
            ->take(5);

        // Low stock variants (stock <= 10)
        $lowStock = Product_variant::with([
                'product:id,name',
                'color:id,name',
                'size:id,name',
            ])
            ->whereNull('deleted_at')
            ->where('stock', '<=', 10)
            ->where('is_active', true)
            ->orderBy('stock')
            ->limit(10)
            ->get()
            ->map(function ($variant) {
                $stock = $variant->stock;
                $status = $stock === 0 ? 'Out of Stock' : 'Low Stock';

                return [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product' => $variant->product->name ?? '',
                    'sku' => $variant->sku ?? '',
                    'color' => $variant->color->name ?? '',
                    'size' => $variant->size->name ?? '',
                    'stock' => $stock,
                    'status' => $status,
                ];
            });

        return $this->successResponse([
            'summary' => [
                'total_sales' => (float) $totalSales,
                'total_orders' => (int) $totalOrders,
                'total_customers' => (int) $totalCustomers,
                'total_products' => (int) $totalProducts,
            ],
            'sales_overview' => $salesOverview,
            'order_overview' => $orderOverview,
            'recent_orders' => $recentOrders,
            'top_products' => $topProducts,
            'low_stock' => $lowStock,
        ], 'Dashboard data retrieved successfully', 200);
    }

    private function getDailySales(int $days): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays($days - 1)->startOfDay();

        $results = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(net_amount) as sales, COUNT(*) as orders')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $resultsMap = collect($results)->keyBy('date');

        $chart = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $label = $date->format('D');

            $row = $resultsMap->get($dateStr);
            $chart[] = [
                'date' => $label,
                'sales' => $row ? (float) $row->sales : 0,
                'orders' => $row ? (int) $row->orders : 0,
            ];
        }

        return $chart;
    }
}
