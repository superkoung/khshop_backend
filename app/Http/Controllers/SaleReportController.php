<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

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
        unset($results);

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
        unset($results);

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

    /**
     * Product Report: date-ranged KPIs + best/worst/out-of-stock with backend pagination.
     * Sales rules mirror getSalesData / getProfitLoss: orders.status != 'cancelled',
     * filtered on orders.created_at (app timezone UTC).
     * Per-product revenue = SUM(order_items.sub_total) — same as getTopProducts.
     * Out of Stock uses current product_variants.stock (not historical sales).
     */
    public function getProductReport(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:today,week,month,custom',
            'start_date' => 'required_if:period,custom|nullable|date',
            'end_date' => 'required_if:period,custom|nullable|date|after_or_equal:start_date',
            'tab' => 'nullable|string|in:best,worst,out_of_stock',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $period = $validated['period'] ?? 'month';
        $tab = $validated['tab'] ?? 'best';
        $perPage = $request->integer('per_page', 10);

        [$start, $end] = $this->resolveProfitLossRange(
            $period,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $summary = $this->buildProductReportSummary($start, $end);
        $query = $this->buildProductReportQuery($tab, $start, $end);

        $products = $query
            ->paginate($perPage, ['*'], 'page', $request->integer('page', 1))
            ->withQueryString();

        $products->setCollection($this->mapProductReportRows($products->getCollection(), $tab));

        return $this->successResponse([
            'period' => $period,
            'tab' => $tab,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'range_label' => $this->formatRangeLabel($start, $end, $period),
            'summary' => $summary,
            'products' => $products,
        ], 'Product report retrieved successfully', 200);
    }

    private function buildProductReportQuery(string $tab, Carbon $start, Carbon $end): Builder
    {
        if ($tab === 'out_of_stock') {
            return Product::query()
                ->whereDoesntHave('variants', function ($q) {
                    $q->where('stock', '>', 0);
                })
                ->with('category:id,name')
                ->withSum('variants as total_stock', 'stock')
                ->orderBy('name');
        }

        $salesSub = OrderItem::query()
            ->select('product_variants.product_id as pid')
            ->selectRaw('COALESCE(SUM(order_items.qty), 0) as units_sold')
            ->selectRaw('COALESCE(SUM(order_items.sub_total), 0) as revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', $start)
            ->where('orders.created_at', '<=', $end)
            ->groupBy('product_variants.product_id');

        $stockSub = DB::table('product_variants')
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(stock), 0) as total_stock')
            ->whereNull('deleted_at')
            ->groupBy('product_id');

        $query = Product::query()
            ->leftJoinSub($salesSub, 'sales', function ($join) {
                $join->on('sales.pid', '=', 'products.id');
            })
            ->leftJoinSub($stockSub, 'stock_agg', function ($join) {
                $join->on('stock_agg.product_id', '=', 'products.id');
            })
            ->with('category:id,name')
            ->select('products.id', 'products.name', 'products.category_id')
            ->selectRaw('COALESCE(sales.units_sold, 0) as units_sold')
            ->selectRaw('COALESCE(sales.revenue, 0) as revenue')
            ->selectRaw('COALESCE(stock_agg.total_stock, 0) as stock')
            ->where('products.is_active', true);

        if ($tab === 'best') {
            $query->whereNotNull('sales.pid')
                ->orderByDesc('units_sold')
                ->orderByDesc('revenue')
                ->orderBy('products.name');
        } else {
            $query->orderBy('units_sold')
                ->orderBy('revenue')
                ->orderBy('products.name');
        }

        return $query;
    }

    private function mapProductReportRows($products, string $tab)
    {
        return $products->map(function ($product) use ($tab) {
            $stock = $tab === 'out_of_stock'
                ? (int) ($product->total_stock ?? 0)
                : (int) ($product->stock ?? 0);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name ?? 'Other',
                'units_sold' => $tab === 'out_of_stock' ? 0 : (int) ($product->units_sold ?? 0),
                'revenue' => $tab === 'out_of_stock' ? 0.0 : round((float) ($product->revenue ?? 0), 2),
                'stock' => $stock,
            ];
        });
    }

    private function buildProductReportSummary(Carbon $start, Carbon $end): array
    {
        $totals = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', $start)
            ->where('orders.created_at', '<=', $end)
            ->selectRaw('
                COALESCE(SUM(order_items.qty), 0) as total_units,
                COALESCE(SUM(order_items.sub_total), 0) as total_revenue
            ')
            ->first();

        $topCategory = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', $start)
            ->where('orders.created_at', '<=', $end)
            ->selectRaw("COALESCE(categories.name, 'Other') as category_name")
            ->selectRaw('COALESCE(SUM(order_items.sub_total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(order_items.qty), 0) as units')
            ->groupByRaw("COALESCE(categories.name, 'Other')")
            ->orderByDesc('revenue')
            ->orderByDesc('units')
            ->first();

        return [
            'total_units_sold' => (int) ($totals->total_units ?? 0),
            'total_revenue' => round((float) ($totals->total_revenue ?? 0), 2),
            'top_category' => $topCategory
                ? [
                    'name' => $topCategory->category_name,
                    'revenue' => round((float) $topCategory->revenue, 2),
                    'units' => (int) $topCategory->units,
                ]
                : null,
        ];
    }

    public function getProfitLoss(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:today,yesterday,week,month,year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'compare' => 'nullable|boolean',
        ]);

        $period = $validated['period'] ?? 'month';
        $compare = filter_var($validated['compare'] ?? false, FILTER_VALIDATE_BOOLEAN);

        [$start, $end] = $this->resolveProfitLossRange(
            $period,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $current = $this->buildProfitLossPayload($start, $end);

        $payload = [
            'period' => $period,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'range_label' => $this->formatRangeLabel($start, $end, $period),
            'summary' => $current['summary'],
            'statement' => $current['statement'],
            'trend' => $current['trend'],
            'expense_breakdown' => $current['expense_breakdown'],
            'comparison' => null,
        ];

        if ($compare) {
            [$prevStart, $prevEnd] = $this->previousEquivalentRange($start, $end);
            $previous = $this->buildProfitLossPayload($prevStart, $prevEnd);

            $payload['comparison'] = [
                'start_date' => $prevStart->toDateString(),
                'end_date' => $prevEnd->toDateString(),
                // Always date-based for the previous period (never "This Month").
                'range_label' => $prevStart->format('M j, Y') . ' – ' . $prevEnd->format('M j, Y'),
                'summary' => $previous['summary'],
            ];
        }

        return $this->successResponse($payload, 'Profit & Loss report retrieved successfully', 200);
    }

    /**
     * Resolve [start, end] inclusive range for the selected period.
     * Revenue rules intentionally mirror getSalesData / getSalesChart:
     * orders with status != cancelled, filtered on created_at.
     */
    private function resolveProfitLossRange(string $period, ?string $startDate, ?string $endDate): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'last30' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
            ],
            'custom' => [
                Carbon::parse($startDate ?? $now->copy()->startOfMonth()->toDateString())->startOfDay(),
                Carbon::parse($endDate ?? $now->toDateString())->endOfDay(),
            ],
            default => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
        };
    }

    private function previousEquivalentRange(Carbon $start, Carbon $end): array
    {
        $days = $start->diffInDays($end) + 1;

        return [
            $start->copy()->subDays($days)->startOfDay(),
            $start->copy()->subDay()->endOfDay(),
        ];
    }

    private function formatRangeLabel(Carbon $start, Carbon $end, string $period): string
    {
        if ($period === 'today') {
            return 'Today';
        }
        if ($period === 'yesterday') {
            return 'Yesterday';
        }
        if ($period === 'last30') {
            return 'Last 30 Days';
        }
        if ($period === 'week') {
            return 'This Week';
        }
        if ($period === 'month') {
            return 'This Month';
        }
        if ($period === 'year') {
            return 'This Year';
        }

        return $start->format('M j, Y') . ' – ' . $end->format('M j, Y');
    }

    /**
     * Authoritative P&L aggregates computed in SQL (not on the frontend).
     *
     * Revenue source: same as Sale Report — Order.status != 'cancelled',
     * SUM(net_amount) (and gross/discount components from the same set).
     * COGS source: product_suppliers.cost_price (primary supplier) × order_items.qty
     *   when cost data exists; never uses selling price as cost.
     * Operating expenses: no expense table in this database → always 0 / unavailable.
     */
    private function buildProfitLossPayload(Carbon $start, Carbon $end): array
    {
        $orderStats = Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as gross_revenue,
                COALESCE(SUM(discount_amount), 0) as discounts,
                COALESCE(SUM(net_amount), 0) as net_revenue,
                COALESCE(SUM(tax_amount), 0) as tax
            ')
            ->first();

        $returns = (float) Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', 'refunded')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->sum('net_amount');

        $cogsRow = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
            ->leftJoin('product_suppliers', function ($join) {
                $join->on('product_suppliers.product_id', '=', 'product_variants.product_id')
                    ->where('product_suppliers.is_primary', true);
            })
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', $start)
            ->where('orders.created_at', '<=', $end)
            ->selectRaw('
                COALESCE(SUM(order_items.qty * COALESCE(product_suppliers.cost_price, 0)), 0) as cogs,
                COALESCE(SUM(order_items.qty), 0) as units_sold,
                COUNT(product_suppliers.cost_price) as costed_lines
            ')
            ->first();

        $unitsSold = (float) ($cogsRow->units_sold ?? 0);
        $costedLines = (int) ($cogsRow->costed_lines ?? 0);
        $cogs = (float) ($cogsRow->cogs ?? 0);
        // Cost is only trustworthy when sold lines actually join a cost_price row.
        $cogsAvailable = $unitsSold > 0 && $costedLines > 0;

        $grossRevenue = (float) ($orderStats->gross_revenue ?? 0);
        $discounts = (float) ($orderStats->discounts ?? 0);
        $netRevenue = (float) ($orderStats->net_revenue ?? 0);
        $grossProfit = round($netRevenue - ($cogsAvailable ? $cogs : 0), 2);
        $grossMargin = $netRevenue > 0 ? round(($grossProfit / $netRevenue) * 100, 2) : 0.0;

        // Real operating expenses require an expense data source (not in schema yet).
        $operatingExpenses = 0.0;
        $expensesAvailable = false;
        $expenseBreakdown = [
            'available' => false,
            'message' => 'No expense data available. Operating expenses require an expense data source.',
            'categories' => [],
        ];

        $netProfit = round($grossProfit - $operatingExpenses, 2);

        $summary = [
            'revenue' => round($netRevenue, 2),
            'gross_revenue' => round($grossRevenue, 2),
            'discounts' => round($discounts, 2),
            'returns' => round($returns, 2),
            'cogs' => $cogsAvailable ? round($cogs, 2) : 0.0,
            'cogs_available' => $cogsAvailable,
            'gross_profit' => $grossProfit,
            'gross_margin' => $grossMargin,
            'operating_expenses' => $operatingExpenses,
            'expenses_available' => $expensesAvailable,
            'net_profit' => $netProfit,
            'orders' => (int) ($orderStats->total_orders ?? 0),
            'units_sold' => (int) $unitsSold,
        ];

        $statement = [
            'revenue' => [
                'gross_revenue' => round($grossRevenue, 2),
                'discounts' => round($discounts, 2),
                'returns' => round($returns, 2),
                'net_revenue' => round($netRevenue, 2),
            ],
            'cogs' => [
                'amount' => $cogsAvailable ? round($cogs, 2) : 0.0,
                'available' => $cogsAvailable,
            ],
            'gross_profit' => [
                'amount' => $grossProfit,
                'margin' => $grossMargin,
            ],
            'operating_expenses' => [
                'amount' => $operatingExpenses,
                'available' => $expensesAvailable,
                'categories' => [],
            ],
            'net_profit' => [
                'amount' => $netProfit,
            ],
        ];

        $useDaily = $start->diffInDays($end) <= 92;
        $trend = $useDaily
            ? $this->getProfitLossDailyTrend($start, $end)
            : $this->getProfitLossMonthlyTrend($start, $end);

        return [
            'summary' => $summary,
            'statement' => $statement,
            'trend' => $trend,
            'expense_breakdown' => $expenseBreakdown,
        ];
    }

    private function getProfitLossDailyTrend(Carbon $start, Carbon $end): array
    {
        $orderRows = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $start->copy()->startOfDay())
            ->where('created_at', '<=', $end->copy()->endOfDay())
            ->selectRaw('DATE(created_at) as day, SUM(net_amount) as revenue')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('day');

        $cogsRows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
            ->leftJoin('product_suppliers', function ($join) {
                $join->on('product_suppliers.product_id', '=', 'product_variants.product_id')
                    ->where('product_suppliers.is_primary', true);
            })
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', $start->copy()->startOfDay())
            ->where('orders.created_at', '<=', $end->copy()->endOfDay())
            ->selectRaw('
                DATE(orders.created_at) as day,
                COALESCE(SUM(order_items.qty * COALESCE(product_suppliers.cost_price, 0)), 0) as cogs
            ')
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->get()
            ->keyBy('day');

        $trend = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $dayKey = $cursor->format('Y-m-d');
            $revenue = (float) ($orderRows->get($dayKey)->revenue ?? 0);
            $cogs = (float) ($cogsRows->get($dayKey)->cogs ?? 0);
            // Expenses unavailable → 0 on the chart (not fabricated).
            $expenses = 0.0;
            $netProfit = round($revenue - $cogs - $expenses, 2);

            $trend[] = [
                'date' => $cursor->format('M j'),
                'revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'expenses' => $expenses,
                'net_profit' => $netProfit,
            ];

            $cursor->addDay();
        }

        return $trend;
    }

    private function getProfitLossMonthlyTrend(Carbon $start, Carbon $end): array
    {
        $orderRows = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $start->copy()->startOfMonth())
            ->where('created_at', '<=', $end->copy()->endOfMonth())
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month_key, SUM(net_amount) as revenue")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $cogsRows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
            ->leftJoin('product_suppliers', function ($join) {
                $join->on('product_suppliers.product_id', '=', 'product_variants.product_id')
                    ->where('product_suppliers.is_primary', true);
            })
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', $start->copy()->startOfMonth())
            ->where('orders.created_at', '<=', $end->copy()->endOfMonth())
            ->selectRaw("
                TO_CHAR(orders.created_at, 'YYYY-MM') as month_key,
                COALESCE(SUM(order_items.qty * COALESCE(product_suppliers.cost_price, 0)), 0) as cogs
            ")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $trend = [];
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $monthKey = $cursor->format('Y-m');
            $revenue = (float) ($orderRows->get($monthKey)->revenue ?? 0);
            $cogs = (float) ($cogsRows->get($monthKey)->cogs ?? 0);
            $expenses = 0.0;
            $netProfit = round($revenue - $cogs - $expenses, 2);

            $trend[] = [
                'date' => $cursor->format('M Y'),
                'revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'expenses' => $expenses,
                'net_profit' => $netProfit,
            ];

            $cursor->addMonth();
        }

        return $trend;
    }

    /**
     * Sales Report PDF export — same aggregates/chart as dashboard JSON endpoints.
     */
    public function exportSales(Request $request): Response
    {
        $validated = $request->validate([
            'period' => 'required|string|in:today,7days,30days,year',
            'chart' => 'nullable|string|in:7D,30D,3M,1Y',
        ]);

        $period = $validated['period'];
        $chartPeriod = $validated['chart'] ?? '30D';

        $query = Order::query()->where('status', '!=', 'cancelled');
        $query = $this->applyPeriodFilter($query, $period);

        $stats = $query->selectRaw('
            COUNT(DISTINCT orders.id) as total_orders,
            COALESCE(SUM(orders.net_amount), 0) as total_revenue,
            COUNT(DISTINCT orders.user_id) as total_customers
        ')->first();

        $totalOrders = (int) $stats->total_orders;
        $totalRevenue = (float) $stats->total_revenue;
        $avgOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $chartData = match ($chartPeriod) {
            '7D' => $this->getDailyChart(7),
            '30D' => $this->getDailyChart(30),
            '3M' => $this->getMonthlyChart(3),
            '1Y' => $this->getMonthlyChart(12),
            default => $this->getDailyChart(30),
        };

        $now = Carbon::now();
        [$rangeStart, $rangeEnd] = match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };

        $periodLabel = match ($period) {
            'today' => 'Today',
            '7days' => 'Last 7 Days',
            '30days' => 'Last 30 Days',
            'year' => 'This Year',
            default => $period,
        };

        $viewData = [
            'title' => 'Sales Report',
            'rangeLabel' => $periodLabel,
            'startDate' => $rangeStart->toDateString(),
            'endDate' => $rangeEnd->toDateString(),
            'generatedAt' => now()->format('M j, Y H:i'),
            'summary' => [
                'revenue' => round($totalRevenue, 2),
                'orders' => $totalOrders,
                'avgOrder' => $avgOrder,
                'customers' => (int) $stats->total_customers,
            ],
            'chart' => $chartData,
            'chartPeriod' => $chartPeriod,
        ];

        return $this->downloadReportPdf(
            'reports.sales',
            $viewData,
            $this->exportFilename('sales', $rangeStart, $rangeEnd)
        );
    }

    /**
     * Profit & Loss PDF export — reuses buildProfitLossPayload (identical to dashboard).
     */
    public function exportProfitLoss(Request $request): Response
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:today,yesterday,week,month,year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'compare' => 'nullable|boolean',
        ]);

        $period = $validated['period'] ?? 'month';
        $compare = filter_var($validated['compare'] ?? false, FILTER_VALIDATE_BOOLEAN);

        [$start, $end] = $this->resolveProfitLossRange(
            $period,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $current = $this->buildProfitLossPayload($start, $end);

        $payload = [
            'period' => $period,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'range_label' => $this->formatRangeLabel($start, $end, $period),
            'summary' => $current['summary'],
            'statement' => $current['statement'],
            'trend' => $current['trend'],
            'expense_breakdown' => $current['expense_breakdown'],
            'comparison' => null,
        ];

        if ($compare) {
            [$prevStart, $prevEnd] = $this->previousEquivalentRange($start, $end);
            $previous = $this->buildProfitLossPayload($prevStart, $prevEnd);

            $payload['comparison'] = [
                'start_date' => $prevStart->toDateString(),
                'end_date' => $prevEnd->toDateString(),
                'range_label' => $prevStart->format('M j, Y') . ' – ' . $prevEnd->format('M j, Y'),
                'summary' => $previous['summary'],
            ];
        }

        $viewData = array_merge($payload, [
            'title' => 'Profit & Loss Report',
            'rangeLabel' => $payload['range_label'],
            'startDate' => $payload['start_date'],
            'endDate' => $payload['end_date'],
            'generatedAt' => now()->format('M j, Y H:i'),
        ]);

        return $this->downloadReportPdf(
            'reports.profit-loss',
            $viewData,
            $this->exportFilename('profit-loss', $start, $end)
        );
    }

    /**
     * Product Report PDF export — full filtered dataset (not page-limited).
     */
    public function exportProductReport(Request $request): Response
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:today,week,month,custom',
            'start_date' => 'required_if:period,custom|nullable|date',
            'end_date' => 'required_if:period,custom|nullable|date|after_or_equal:start_date',
            'tab' => 'nullable|string|in:best,worst,out_of_stock',
        ]);

        $period = $validated['period'] ?? 'month';
        $tab = $validated['tab'] ?? 'best';

        [$start, $end] = $this->resolveProfitLossRange(
            $period,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $summary = $this->buildProductReportSummary($start, $end);
        $products = $this->mapProductReportRows(
            $this->buildProductReportQuery($tab, $start, $end)->get(),
            $tab
        );

        $viewData = [
            'title' => 'Product Report',
            'rangeLabel' => $this->formatRangeLabel($start, $end, $period),
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'generatedAt' => now()->format('M j, Y H:i'),
            'period' => $period,
            'tab' => $tab,
            'summary' => $summary,
            'products' => $products,
        ];

        return $this->downloadReportPdf(
            'reports.product',
            $viewData,
            $this->exportFilename('product-report', $start, $end, $tab)
        );
    }

    /**
     * Customer Report PDF export — full filtered dataset (not page-limited).
     */
    public function exportTopCustomers(Request $request): Response
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:month,last30,week,custom',
            'start_date' => 'required_if:period,custom|nullable|date',
            'end_date' => 'required_if:period,custom|nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:255',
            'min_orders' => 'nullable|integer|min:0',
            'max_orders' => 'nullable|integer|min:0',
            'min_spent' => 'nullable|numeric|min:0',
            'max_spent' => 'nullable|numeric|min:0',
        ]);

        $period = $validated['period'] ?? 'month';
        $search = trim((string) ($validated['search'] ?? ''));

        [$start, $end] = $this->resolveProfitLossRange(
            $period,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $summary = $this->buildCustomerReportSummary($start, $end);
        $growth = $this->buildCustomerGrowthSeries($start, $end);

        $customers = $this->mapTopCustomerRows(
            $this->buildTopCustomersQuery([
                'search' => $search,
                'min_orders' => $validated['min_orders'] ?? null,
                'max_orders' => $validated['max_orders'] ?? null,
                'min_spent' => $validated['min_spent'] ?? null,
                'max_spent' => $validated['max_spent'] ?? null,
            ], $start, $end)->get()
        );

        $viewData = [
            'title' => 'Customer Report',
            'rangeLabel' => $this->formatRangeLabel($start, $end, $period),
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'generatedAt' => now()->format('M j, Y H:i'),
            'period' => $period,
            'search' => $search,
            'summary' => $summary,
            'growth' => $growth,
            'customers' => $customers,
        ];

        return $this->downloadReportPdf(
            'reports.customer',
            $viewData,
            $this->exportFilename('customer-report', $start, $end)
        );
    }

    private function downloadReportPdf(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('enablePhp', true);

        return $pdf->download($filename);
    }

    private function exportFilename(string $type, Carbon $start, Carbon $end, ?string $suffix = null): string
    {
        $range = $start->isSameDay($end)
            ? $start->format('Y-m-d')
            : ($start->format('Y-m') === $end->format('Y-m')
                ? $start->format('Y-m')
                : $start->format('Y-m-d') . '_' . $end->format('Y-m-d'));

        $name = 'khshop-' . $type . '-' . $range;
        if ($suffix !== null && $suffix !== '') {
            $name .= '-' . $suffix;
        }

        return $name . '.pdf';
    }

    /**
     * Customer Report: date-ranged KPIs + growth series + paginated top customers.
     *
     * Valid order rule mirrors getSalesData / getProfitLoss / getProductReport:
     * orders.status != 'cancelled', filtered on orders.created_at.
     * Revenue/AOV use SUM(net_amount) — same centralized definition as Sales Report.
     * New Customers = users.created_at (role_id=1) within range.
     * Returning Customers = valid order in range AND prior valid order before range start.
     * LTV = all-time valid revenue / distinct customers with valid orders (real history only).
     */
    public function getTopCustomers(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:month,last30,week,custom',
            'start_date' => 'required_if:period,custom|nullable|date',
            'end_date' => 'required_if:period,custom|nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:255',
            'min_orders' => 'nullable|integer|min:0',
            'max_orders' => 'nullable|integer|min:0',
            'min_spent' => 'nullable|numeric|min:0',
            'max_spent' => 'nullable|numeric|min:0',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $period = $validated['period'] ?? 'month';
        $perPage = $request->integer('per_page', 10);
        $search = trim((string) ($validated['search'] ?? ''));
        $minOrders = $validated['min_orders'] ?? null;
        $maxOrders = $validated['max_orders'] ?? null;
        $minSpent = $validated['min_spent'] ?? null;
        $maxSpent = $validated['max_spent'] ?? null;

        [$start, $end] = $this->resolveProfitLossRange(
            $period,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $summary = $this->buildCustomerReportSummary($start, $end);
        $growth = $this->buildCustomerGrowthSeries($start, $end);

        $query = $this->buildTopCustomersQuery([
            'search' => $search,
            'min_orders' => $minOrders,
            'max_orders' => $maxOrders,
            'min_spent' => $minSpent,
            'max_spent' => $maxSpent,
        ], $start, $end);

        $customers = $query
            ->paginate($perPage, ['*'], 'page', $request->integer('page', 1))
            ->withQueryString();

        $customers->setCollection($this->mapTopCustomerRows($customers->getCollection()));

        return $this->successResponse([
            'period' => $period,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'range_label' => $this->formatRangeLabel($start, $end, $period),
            'summary' => $summary,
            'growth' => $growth,
            'customers' => $customers,
        ], 'Top customers retrieved successfully', 200);
    }

    private function buildTopCustomersQuery(array $filters, Carbon $start, Carbon $end): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $minOrders = $filters['min_orders'] ?? null;
        $maxOrders = $filters['max_orders'] ?? null;
        $minSpent = $filters['min_spent'] ?? null;
        $maxSpent = $filters['max_spent'] ?? null;

        $periodOrders = Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end);

        $aggSub = (clone $periodOrders)
            ->select('user_id')
            ->selectRaw('COUNT(*) as period_orders')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as period_spent')
            ->selectRaw('MAX(created_at) as last_order_date')
            ->groupBy('user_id');

        $query = User::query()
            ->joinSub($aggSub, 'cust_agg', function ($join) {
                $join->on('cust_agg.user_id', '=', 'users.id');
            })
            ->where('users.role_id', 1)
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->selectRaw('cust_agg.period_orders, cust_agg.period_spent, cust_agg.last_order_date');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('users.name', 'ilike', $like)
                    ->orWhere('users.email', 'ilike', $like);
            });
        }

        if ($minOrders !== null) {
            $query->where('cust_agg.period_orders', '>=', $minOrders);
        }
        if ($maxOrders !== null) {
            $query->where('cust_agg.period_orders', '<=', $maxOrders);
        }
        if ($minSpent !== null) {
            $query->where('cust_agg.period_spent', '>=', $minSpent);
        }
        if ($maxSpent !== null) {
            $query->where('cust_agg.period_spent', '<=', $maxSpent);
        }

        return $query
            ->orderByDesc('cust_agg.period_spent')
            ->orderByDesc('cust_agg.period_orders')
            ->orderBy('users.name');
    }

    private function mapTopCustomerRows($customers)
    {
        return $customers->map(function ($customer) {
            $orders = (int) ($customer->period_orders ?? 0);
            $spent = (float) ($customer->period_spent ?? 0);

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'joined' => $customer->created_at,
                'orders' => $orders,
                'totalSpent' => round($spent, 2),
                'averageOrderValue' => $orders > 0 ? round($spent / $orders, 2) : 0,
                'lastOrderDate' => $customer->last_order_date,
            ];
        });
    }

    private function buildCustomerReportSummary(Carbon $start, Carbon $end): array
    {
        $totalCustomers = User::where('role_id', 1)->count();

        $newCustomers = User::where('role_id', 1)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->count();

        // Returning: ordered in range AND had a prior valid order before range start.
        $returningCustomers = (int) Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->whereExists(function ($q) use ($start) {
                $q->select(DB::raw(1))
                    ->from('orders as prior_orders')
                    ->whereColumn('prior_orders.user_id', 'orders.user_id')
                    ->where('prior_orders.status', '!=', 'cancelled')
                    ->where('prior_orders.created_at', '<', $start);
            })
            ->distinct()
            ->count('user_id');

        $orderStats = Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(net_amount), 0) as total_revenue')
            ->first();

        $totalOrders = (int) ($orderStats->total_orders ?? 0);
        $totalRevenue = (float) ($orderStats->total_revenue ?? 0);
        $aov = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0;

        // Historical LTV from real order history (no estimates).
        $lifetime = Order::query()
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COUNT(DISTINCT user_id) as customers, COALESCE(SUM(net_amount), 0) as revenue')
            ->first();

        $lifetimeCustomers = (int) ($lifetime->customers ?? 0);
        $lifetimeRevenue = (float) ($lifetime->revenue ?? 0);
        $ltvAvailable = $lifetimeCustomers > 0 && $lifetimeRevenue > 0;
        $ltv = $ltvAvailable ? round($lifetimeRevenue / $lifetimeCustomers, 2) : null;

        $returnRate = $totalCustomers > 0
            ? round(($returningCustomers / $totalCustomers) * 100)
            : 0;

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'return_rate' => $returnRate,
            'total_orders' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'avg_order_value' => $aov,
            'ltv' => $ltv,
            'ltv_available' => $ltvAvailable,
            'ltv_note' => $ltvAvailable
                ? null
                : 'No historical paid order revenue available for LTV.',
        ];
    }

    /**
     * Aggregated new-customer counts over the selected range.
     * Daily when range <= 92 days (same convention as P&L trend); otherwise monthly.
     */
    private function buildCustomerGrowthSeries(Carbon $start, Carbon $end): array
    {
        $useDaily = $start->diffInDays($end) <= 92;

        if ($useDaily) {
            $rows = User::where('role_id', 1)
                ->where('created_at', '>=', $start->copy()->startOfDay())
                ->where('created_at', '<=', $end->copy()->endOfDay())
                ->selectRaw('DATE(created_at) as day, COUNT(*) as new_customers')
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->keyBy('day');

            $series = [];
            $cursor = $start->copy()->startOfDay();
            $last = $end->copy()->startOfDay();

            while ($cursor->lte($last)) {
                $dayKey = $cursor->format('Y-m-d');
                $series[] = [
                    'date' => $cursor->format('M j'),
                    'new_customers' => (int) ($rows->get($dayKey)->new_customers ?? 0),
                ];
                $cursor->addDay();
            }

            return $series;
        }

        $rows = User::where('role_id', 1)
            ->where('created_at', '>=', $start->copy()->startOfMonth())
            ->where('created_at', '<=', $end->copy()->endOfMonth())
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month_key, COUNT(*) as new_customers")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $series = [];
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $monthKey = $cursor->format('Y-m');
            $series[] = [
                'date' => $cursor->format('M Y'),
                'new_customers' => (int) ($rows->get($monthKey)->new_customers ?? 0),
            ];
            $cursor->addMonth();
        }

        return $series;
    }
}
