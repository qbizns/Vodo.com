<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Address;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Store;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return redirect()->route('storefront.vodo-commerce.home', $storeSlug);
        }

        $recentOrders = Order::where('customer_id', $customer->id)
            ->orderBy('placed_at', 'desc')
            ->limit(5)
            ->get();

        return view('vodo-commerce::storefront.account.dashboard', [
            'store' => $store,
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function orders(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return redirect()->route('storefront.vodo-commerce.home', $storeSlug);
        }

        $orders = Order::where('customer_id', $customer->id)
            ->orderBy('placed_at', 'desc')
            ->paginate(10);

        return view('vodo-commerce::storefront.account.orders', [
            'store' => $store,
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    public function orderDetail(Request $request, string $storeSlug, string $orderNumber)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return redirect()->route('storefront.vodo-commerce.home', $storeSlug);
        }

        $order = Order::where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->with(['items'])
            ->firstOrFail();

        return view('vodo-commerce::storefront.account.order-detail', [
            'store' => $store,
            'customer' => $customer,
            'order' => $order,
        ]);
    }

    public function addresses(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return redirect()->route('storefront.vodo-commerce.home', $storeSlug);
        }

        $addresses = $customer->addresses()->orderBy('is_default', 'desc')->get();

        return view('vodo-commerce::storefront.account.addresses', [
            'store' => $store,
            'customer' => $customer,
            'addresses' => $addresses,
        ]);
    }

    public function storeAddress(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return back()->with('error', 'Customer not found');
        }

        $validated = $request->validate([
            'type' => 'required|in:billing,shipping',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'company' => 'nullable|string|max:100',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        $address = Address::create([
            'customer_id' => $customer->id,
            ...$validated,
        ]);

        if ($request->boolean('is_default')) {
            $address->setAsDefault();
        }

        return back()->with('success', 'Address added successfully');
    }

    public function updateAddress(Request $request, string $storeSlug, int $addressId)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return back()->with('error', 'Customer not found');
        }

        $address = $customer->addresses()->findOrFail($addressId);

        $validated = $request->validate([
            'type' => 'required|in:billing,shipping',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'company' => 'nullable|string|max:100',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        $address->update($validated);

        if ($request->boolean('is_default')) {
            $address->setAsDefault();
        }

        return back()->with('success', 'Address updated successfully');
    }

    public function deleteAddress(Request $request, string $storeSlug, int $addressId)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return back()->with('error', 'Customer not found');
        }

        $address = $customer->addresses()->findOrFail($addressId);
        $address->delete();

        return back()->with('success', 'Address deleted successfully');
    }

    public function profile(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return redirect()->route('storefront.vodo-commerce.home', $storeSlug);
        }

        return view('vodo-commerce::storefront.account.profile', [
            'store' => $store,
            'customer' => $customer,
        ]);
    }

    public function updateProfile(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $customer = $this->getCustomer($request, $store);

        if (!$customer) {
            return back()->with('error', 'Customer not found');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:100',
            'accepts_marketing' => 'boolean',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Profile updated successfully');
    }

    protected function getCustomer(Request $request, Store $store)
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return \VodoCommerce\Models\Customer::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->first();
    }
}
