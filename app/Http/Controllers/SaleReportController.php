<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleReportController extends Controller
{
    use ApiResponse;

    public function getSalesData(Request $request, string $period)
    {
        $query = Order::query()
            ->where('status', '!=', 'cancelled');

        $query = $this->applyPeriodFilter($query, $period);

        $stats = $query->selectRaw('
            COUNT(DISTINCT orders.id) as total_orders,
            COALESCE(SUM(orders.net_amount), 0) as total_revenue,
            COUNT(DISTINCT orders.user_id) as total_customers
        ')->first();

        $totalOrders = $stats->total_orders;
        $totalRevenue = (float) $stats->total_revenue;
        $avgOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        return $this->successResponse([
            'revenue' => $totalRevenue,
            'orders' => (int) $totalOrders,
            'avgOrder' => $avgOrder,
            'customers' => (int) $stats->total_customers,
        ], 'Sales data retrieved successfully', 200);
    }

    public function getSalesChart(Request $request, string $period)
    {
        $chartData = match ($period) {
            '7D' => $this->getDailyChart(7),
            '30D' => $this->getDailyChart(30),
            '3M' => $this->getMonthlyChart(3),
            '1Y' => $this->getMonthlyChart(12),
            default => $this->getDailyChart(7),
        };

        return $this->successResponse($chartData, 'Sales chart retrieved successfully', 200);
    }

    private function applyPeriodFilter($query, string $period)
    {
        $now = Carbon::now();

        match ($period) {
            'today' => $query->whereDate('created_at', $now->toDateString()),
            '7days' => $query->where('created_at', '>=', $now->copy()->subDays(6)->startOfDay()),
            '30days' => $query->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay()),
            'year' => $query->where('created_at', '>=', $now->copy()->startOfYear()),
            default => null,
        };

        return $query;
    }

    private function getDailyChart(int $days): array
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
            $label = $days <= 7
                ? $date->format('D')
                : $date->format('M j');

            $row = $resultsMap->get($dateStr);
            $chart[] = [
                'date' => $label,
                'sales' => $row ? (float) $row->sales : 0,
                'orders' => $row ? (int) $row->orders : 0,
            ];
        }

        return $chart;
    }

    private function getMonthlyChart(int $months): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subMonths($months - 1)->startOfMonth();

        $results = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month_key, SUM(net_amount) as sales, COUNT(*) as orders")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $resultsMap = collect($results)->keyBy('month_key');

        $chart = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $label = $date->format('M');

            $row = $resultsMap->get($monthKey);
            $chart[] = [
                'date' => $label,
                'sales' => $row ? (float) $row->sales : 0,
                'orders' => $row ? (int) $row->orders : 0,
            ];
        }

        return $chart;
    }

    public function getTopProducts()
    {
        $products = Product::with(['category:id,name', 'variants.image:id,image_path'])
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                $variants = $product->variants;
                $totalStock = $variants->sum('stock');

                $salesData = OrderItem::whereHas('variant', function ($q) use ($product) {
                    $q->where('product_id', $product->id);
                })
                ->whereHas('order', function ($q) {
                    $q->where('status', '!=', 'cancelled');
                })
                ->selectRaw('SUM(qty) as total_sold, SUM(sub_total) as total_revenue, COUNT(DISTINCT order_id) as order_count')
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
                    'orderCount' => (int) ($salesData->order_count ?? 0),
                    'totalStock' => $totalStock,
                    'variantCount' => $variants->count(),
                ];
            })
            ->sortByDesc('sold')
            ->values()
            ->take(10);

        return $this->successResponse($products, 'Top products retrieved successfully', 200);
    }

    public function getTopCustomers()
    {
        $subQuery = '(SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = users.id AND o.status != \'cancelled\')';

        $customers = User::where('role_id', 1)
            ->whereHas('orders', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->selectRaw('users.id, users.name, users.email, users.created_at')
            ->selectRaw("({$subQuery}) as items_purchased")
            ->withCount(['orders as total_orders' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            }])
            ->withSum(['orders as total_spent' => function ($q) {
                $q->where('status', '!=', 'cancelled')
                  ->where('payment_status', 'paid');
            }], 'net_amount')
            ->withMax(['orders as last_order_date' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            }], 'created_at')
            ->get()
            ->map(function ($customer) {
                $totalOrders = (int) ($customer->total_orders ?? 0);
                $totalSpent = (float) ($customer->total_spent ?? 0);

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'joined' => $customer->created_at,
                    'orders' => $totalOrders,
                    'totalSpent' => round($totalSpent, 2),
                    'itemsPurchased' => (int) ($customer->items_purchased ?? 0),
                    'averageOrderValue' => $totalOrders > 0 ? round($totalSpent / $totalOrders, 2) : 0,
                    'lastOrderDate' => $customer->last_order_date,
                ];
            })
            ->sortByDesc('totalSpent')
            ->values()
            ->take(10);

        $totalCustomers = User::where('role_id', 1)->count();

        $newCustomers = User::where('role_id', 1)
            ->where('created_at', '>=', Carbon::now()->subDays(30)->startOfDay())
            ->count();

        $returningCustomers = User::where('role_id', 1)
            ->whereRaw("(SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id AND orders.status != 'cancelled') > 1")
            ->count();

        $returnRate = $totalCustomers > 0 ? round(($returningCustomers / $totalCustomers) * 100) : 0;

        return $this->successResponse([
            'summary' => [
                'total_customers' => $totalCustomers,
                'new_customers' => $newCustomers,
                'returning_customers' => $returningCustomers,
                'return_rate' => $returnRate,
            ],
            'customers' => $customers,
        ], 'Top customers retrieved successfully', 200);
    }
}
