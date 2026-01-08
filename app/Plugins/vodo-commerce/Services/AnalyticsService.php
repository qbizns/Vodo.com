<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\InventoryItem;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Transaction;

class AnalyticsService
{
    public function __construct(
        protected Store $store
    ) {
    }

    /**
     * Get dashboard overview metrics.
     */
    public function getDashboardMetrics(?string $period = 'today'): array
    {
        $dateRange = $this->getDateRange($period);

        return [
            'revenue' => $this->getRevenue($dateRange),
            'orders' => $this->getOrdersMetrics($dateRange),
            'customers' => $this->getCustomersMetrics($dateRange),
            'products' => $this->getProductsMetrics($dateRange),
            'inventory' => $this->getInventoryMetrics(),
            'period' => $period,
            'date_range' => [
                'from' => $dateRange['from']->toDateString(),
                'to' => $dateRange['to']->toDateString(),
            ],
        ];
    }

    /**
     * Get revenue metrics.
     */
    public function getRevenue(array $dateRange): array
    {
        $orders = Order::where('store_id', $this->store->id)
            ->whereBetween('placed_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', ['cancelled', 'draft']);

        $total = $orders->sum('total');
        $count = $orders->count();
        $averageOrderValue = $count > 0 ? $total / $count : 0;

        // Previous period comparison
        $previousRange = $this->getPreviousDateRange($dateRange);
        $previousTotal = Order::where('store_id', $this->store->id)
            ->whereBetween('placed_at', [$previousRange['from'], $previousRange['to']])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('total');

        $growth = $previousTotal > 0 ? (($total - $previousTotal) / $previousTotal) * 100 : 0;

        return [
            'total' => round($total, 2),
            'count' => $count,
            'average_order_value' => round($averageOrderValue, 2),
            'growth_percentage' => round($growth, 2),
            'previous_total' => round($previousTotal, 2),
        ];
    }

    /**
     * Get orders metrics.
     */
    public function getOrdersMetrics(array $dateRange): array
    {
        $query = Order::where('store_id', $this->store->id)
            ->whereBetween('placed_at', [$dateRange['from'], $dateRange['to']]);

        $total = $query->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $processing = (clone $query)->where('status', 'processing')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();

        $fulfillmentRate = $total > 0 ? ($completed / $total) * 100 : 0;

        return [
            'total' => $total,
            'pending' => $pending,
            'processing' => $processing,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'fulfillment_rate' => round($fulfillmentRate, 2),
        ];
    }

    /**
     * Get customers metrics.
     */
    public function getCustomersMetrics(array $dateRange): array
    {
        $newCustomers = Customer::where('store_id', $this->store->id)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->count();

        $totalCustomers = Customer::where('store_id', $this->store->id)->count();

        $returningCustomers = Order::where('store_id', $this->store->id)
            ->whereBetween('placed_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotNull('customer_id')
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return [
            'total' => $totalCustomers,
            'new' => $newCustomers,
            'returning' => $returningCustomers,
        ];
    }

    /**
     * Get products metrics.
     */
    public function getProductsMetrics(array $dateRange): array
    {
        $totalProducts = Product::where('store_id', $this->store->id)
            ->where('status', 'active')
            ->count();

        $outOfStock = Product::where('store_id', $this->store->id)
            ->where('stock_quantity', '<=', 0)
            ->where('track_inventory', true)
            ->count();

        $lowStock = Product::where('store_id', $this->store->id)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', 10)
            ->where('track_inventory', true)
            ->count();

        return [
            'total' => $totalProducts,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
        ];
    }

    /**
     * Get inventory metrics.
     */
    public function getInventoryMetrics(): array
    {
        $items = InventoryItem::query()
            ->whereHas('location', function ($q) {
                $q->where('store_id', $this->store->id)->where('is_active', true);
            })
            ->get();

        $totalValue = $items->sum(function ($item) {
            return $item->quantity * ($item->unit_cost ?? 0);
        });

        return [
            'total_items' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'total_value' => round($totalValue, 2),
            'reserved_quantity' => $items->sum('reserved_quantity'),
        ];
    }

    /**
     * Get sales report by period.
     */
    public function getSalesReport(string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        $groupByMap = [
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
        ];

        $format = $groupByMap[$groupBy] ?? $groupByMap['day'];

        $sales = Order::where('store_id', $this->store->id)
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->select(
                DB::raw("DATE_FORMAT(placed_at, '{$format}') as period"),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(subtotal) as subtotal'),
                DB::raw('SUM(tax_total) as tax'),
                DB::raw('SUM(shipping_total) as shipping'),
                DB::raw('SUM(discount_total) as discounts')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'period' => $groupBy,
            'data' => $sales->toArray(),
            'summary' => [
                'total_orders' => $sales->sum('orders_count'),
                'total_revenue' => round($sales->sum('revenue'), 2),
                'average_order_value' => $sales->count() > 0 ? round($sales->sum('revenue') / $sales->sum('orders_count'), 2) : 0,
            ],
        ];
    }

    /**
     * Get best selling products.
     */
    public function getBestSellingProducts(int $limit = 10, ?array $dateRange = null): array
    {
        $query = DB::table('commerce_order_items')
            ->join('commerce_orders', 'commerce_order_items.order_id', '=', 'commerce_orders.id')
            ->join('commerce_products', 'commerce_order_items.product_id', '=', 'commerce_products.id')
            ->where('commerce_orders.store_id', $this->store->id)
            ->whereNotIn('commerce_orders.status', ['cancelled', 'draft']);

        if ($dateRange) {
            $query->whereBetween('commerce_orders.placed_at', [$dateRange['from'], $dateRange['to']]);
        }

        $products = $query->select(
                'commerce_products.id',
                'commerce_products.name',
                'commerce_products.sku',
                DB::raw('SUM(commerce_order_items.quantity) as units_sold'),
                DB::raw('SUM(commerce_order_items.total) as revenue')
            )
            ->groupBy('commerce_products.id', 'commerce_products.name', 'commerce_products.sku')
            ->orderBy('units_sold', 'desc')
            ->limit($limit)
            ->get();

        return $products->toArray();
    }

    /**
     * Get revenue by payment method.
     */
    public function getRevenueByPaymentMethod(?array $dateRange = null): array
    {
        $query = Transaction::where('store_id', $this->store->id)
            ->where('type', 'payment')
            ->where('status', 'completed');

        if ($dateRange) {
            $query->whereBetween('created_at', [$dateRange['from'], $dateRange['to']]);
        }

        $revenue = $query->join('commerce_payment_methods', 'commerce_transactions.payment_method_id', '=', 'commerce_payment_methods.id')
            ->select(
                'commerce_payment_methods.name',
                'commerce_payment_methods.slug',
                DB::raw('COUNT(*) as transactions_count'),
                DB::raw('SUM(commerce_transactions.amount) as total_amount')
            )
            ->groupBy('commerce_payment_methods.id', 'commerce_payment_methods.name', 'commerce_payment_methods.slug')
            ->orderBy('total_amount', 'desc')
            ->get();

        return $revenue->toArray();
    }

    /**
     * Get customer lifetime value report.
     */
    public function getCustomerLifetimeValue(int $limit = 10): array
    {
        $customers = Customer::where('store_id', $this->store->id)
            ->with('orders')
            ->get()
            ->map(function ($customer) {
                $orders = $customer->orders()->whereNotIn('status', ['cancelled', 'draft'])->get();
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'total_orders' => $orders->count(),
                    'total_spent' => round($orders->sum('total'), 2),
                    'average_order_value' => $orders->count() > 0 ? round($orders->sum('total') / $orders->count(), 2) : 0,
                    'first_order' => $orders->min('placed_at'),
                    'last_order' => $orders->max('placed_at'),
                ];
            })
            ->sortByDesc('total_spent')
            ->take($limit)
            ->values();

        return $customers->toArray();
    }

    /**
     * Get inventory turnover report.
     */
    public function getInventoryTurnover(?array $dateRange = null): array
    {
        $dateRange = $dateRange ?? $this->getDateRange('last_30_days');

        $soldProducts = DB::table('commerce_order_items')
            ->join('commerce_orders', 'commerce_order_items.order_id', '=', 'commerce_orders.id')
            ->where('commerce_orders.store_id', $this->store->id)
            ->whereBetween('commerce_orders.placed_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('commerce_orders.status', ['cancelled', 'draft'])
            ->select(
                'commerce_order_items.product_id',
                DB::raw('SUM(commerce_order_items.quantity) as units_sold')
            )
            ->groupBy('commerce_order_items.product_id')
            ->pluck('units_sold', 'product_id');

        $products = Product::where('store_id', $this->store->id)
            ->whereIn('id', $soldProducts->keys())
            ->get()
            ->map(function ($product) use ($soldProducts, $dateRange) {
                $unitsSold = $soldProducts[$product->id] ?? 0;
                $avgInventory = $product->stock_quantity + ($unitsSold / 2);
                $days = $dateRange['from']->diffInDays($dateRange['to']);
                $turnoverRate = $avgInventory > 0 ? ($unitsSold / $avgInventory) * (365 / $days) : 0;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => $product->stock_quantity,
                    'units_sold' => $unitsSold,
                    'turnover_rate' => round($turnoverRate, 2),
                ];
            })
            ->sortByDesc('turnover_rate')
            ->values();

        return $products->toArray();
    }

    /**
     * Get date range based on period.
     */
    public function getDateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => ['from' => $now->copy()->startOfDay(), 'to' => $now->copy()->endOfDay()],
            'yesterday' => ['from' => $now->copy()->subDay()->startOfDay(), 'to' => $now->copy()->subDay()->endOfDay()],
            'this_week' => ['from' => $now->copy()->startOfWeek(), 'to' => $now->copy()->endOfWeek()],
            'last_week' => ['from' => $now->copy()->subWeek()->startOfWeek(), 'to' => $now->copy()->subWeek()->endOfWeek()],
            'this_month' => ['from' => $now->copy()->startOfMonth(), 'to' => $now->copy()->endOfMonth()],
            'last_month' => ['from' => $now->copy()->subMonth()->startOfMonth(), 'to' => $now->copy()->subMonth()->endOfMonth()],
            'last_30_days' => ['from' => $now->copy()->subDays(30), 'to' => $now],
            'last_90_days' => ['from' => $now->copy()->subDays(90), 'to' => $now],
            'this_year' => ['from' => $now->copy()->startOfYear(), 'to' => $now->copy()->endOfYear()],
            'last_year' => ['from' => $now->copy()->subYear()->startOfYear(), 'to' => $now->copy()->subYear()->endOfYear()],
            default => ['from' => $now->copy()->startOfDay(), 'to' => $now->copy()->endOfDay()],
        };
    }

    /**
     * Get previous date range for comparison.
     */
    protected function getPreviousDateRange(array $currentRange): array
    {
        $days = $currentRange['from']->diffInDays($currentRange['to']);

        return [
            'from' => $currentRange['from']->copy()->subDays($days + 1),
            'to' => $currentRange['from']->copy()->subDay(),
        ];
    }
}
