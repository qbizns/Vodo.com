<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Store;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $query = Customer::where('store_id', $store->id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $customers = $query->orderBy($sortBy, $sortDir)->paginate(20);

        return view('vodo-commerce::admin.customers.index', [
            'store' => $store,
            'customers' => $customers,
            'filters' => $request->only(['search', 'sort_by', 'sort_dir']),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $customer = Customer::where('store_id', $store->id)
            ->with(['addresses', 'defaultAddress'])
            ->findOrFail($id);

        $orders = Order::where('customer_id', $customer->id)
            ->orderBy('placed_at', 'desc')
            ->paginate(10);

        return view('vodo-commerce::admin.customers.show', [
            'store' => $store,
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    public function edit(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $customer = Customer::where('store_id', $store->id)
            ->with(['addresses'])
            ->findOrFail($id);

        return view('vodo-commerce::admin.customers.edit', [
            'store' => $store,
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $customer = Customer::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => "required|email|unique:commerce_customers,email,{$id}",
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:100',
            'accepts_marketing' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
        ]);

        $customer->update($validated);

        return redirect()
            ->route('commerce.admin.customers.show', $customer->id)
            ->with('success', 'Customer updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $customer = Customer::where('store_id', $store->id)->findOrFail($id);

        // Check if customer has orders
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Cannot delete customer with existing orders');
        }

        $customer->addresses()->delete();
        $customer->delete();

        return redirect()
            ->route('commerce.admin.customers.index')
            ->with('success', 'Customer deleted successfully');
    }

    protected function getCurrentStore(Request $request): ?Store
    {
        $tenantId = $request->user()?->tenant_id;

        if ($tenantId) {
            return Store::where('tenant_id', $tenantId)->first();
        }

        // For super_admin (no tenant_id), return the first available store
        return Store::withoutGlobalScopes()->first();
    }
}
