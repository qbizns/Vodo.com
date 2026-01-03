<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Services\OrderService;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.settings.general')
                ->with('info', 'Please configure your store settings first');
        }

        $orderService = new OrderService(
            $store,
            app(PaymentGatewayRegistry::class),
            app(ShippingCarrierRegistry::class)
        );

        $stats = $orderService->getStats([
            'date_from' => now()->subDays(30),
        ]);

        $recentOrders = Order::where('store_id', $store->id)
            ->with('customer')
            ->orderBy('placed_at', 'desc')
            ->limit(10)
            ->get();

        $lowStockProducts = Product::where('store_id', $store->id)
            ->active()
            ->where('stock_quantity', '<=', 5)
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get();

        $revenueByDay = $orderService->getRevenueByPeriod('day', 30);

        return view('vodo-commerce::admin.dashboard', [
            'store' => $store,
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
            'revenueByDay' => $revenueByDay,
            'totalProducts' => Product::where('store_id', $store->id)->count(),
            'totalCustomers' => Customer::where('store_id', $store->id)->count(),
        ]);
    }

    protected function getCurrentStore(Request $request): ?Store
    {
        $user = $request->user();
        
        if (!$user) {
            return null;
        }

        // If user has tenant_id, get their tenant's store
        if ($user->tenant_id) {
            return Store::where('tenant_id', $user->tenant_id)->first();
        }

        // Super admin - get first available store (bypass tenant scope)
        return Store::withoutTenantScope()->first();
    }
}
