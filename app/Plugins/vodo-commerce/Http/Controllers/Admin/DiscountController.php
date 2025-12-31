<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Store;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $query = Discount::where('store_id', $store->id);

        if ($request->input('active_only')) {
            $query->valid();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $discounts = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('vodo-commerce::admin.discounts.index', [
            'store' => $store,
            'discounts' => $discounts,
            'filters' => $request->only(['search', 'active_only']),
        ]);
    }

    public function create(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        return view('vodo-commerce::admin.discounts.create', [
            'store' => $store,
        ]);
    }

    public function store(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:commerce_discounts,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:percentage,fixed_amount,free_shipping',
            'value' => 'required|numeric|min:0',
            'minimum_order' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'conditions' => 'nullable|array',
        ]);

        $validated['store_id'] = $store->id;
        $validated['code'] = strtoupper($validated['code']);

        $discount = Discount::create($validated);

        return redirect()
            ->route('commerce.admin.discounts.index')
            ->with('success', 'Discount created successfully');
    }

    public function edit(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $discount = Discount::where('store_id', $store->id)->findOrFail($id);

        return view('vodo-commerce::admin.discounts.edit', [
            'store' => $store,
            'discount' => $discount,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $discount = Discount::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'code' => "required|string|max:50|unique:commerce_discounts,code,{$id}",
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:percentage,fixed_amount,free_shipping',
            'value' => 'required|numeric|min:0',
            'minimum_order' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'conditions' => 'nullable|array',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $discount->update($validated);

        return redirect()
            ->route('commerce.admin.discounts.index')
            ->with('success', 'Discount updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $discount = Discount::where('store_id', $store->id)->findOrFail($id);
        $discount->delete();

        return redirect()
            ->route('commerce.admin.discounts.index')
            ->with('success', 'Discount deleted successfully');
    }

    public function toggleStatus(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $discount = Discount::where('store_id', $store->id)->findOrFail($id);
        $discount->update(['is_active' => !$discount->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $discount->is_active,
        ]);
    }

    protected function getCurrentStore(Request $request): ?Store
    {
        $tenantId = $request->user()?->tenant_id;

        if (!$tenantId) {
            return null;
        }

        return Store::where('tenant_id', $tenantId)->first();
    }
}
