<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use VodoCommerce\Models\Category;
use VodoCommerce\Models\Store;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $categories = Category::where('store_id', $store->id)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('position')
            ->get();

        return view('vodo-commerce::admin.categories.index', [
            'store' => $store,
            'categories' => $categories,
        ]);
    }

    public function create(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $parentCategories = Category::where('store_id', $store->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('vodo-commerce::admin.categories.create', [
            'store' => $store,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function store(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:commerce_categories,id',
            'position' => 'integer|min:0',
            'is_visible' => 'boolean',
        ]);

        $validated['store_id'] = $store->id;

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($store->id, $validated['name']);
        }

        $category = Category::create($validated);

        return redirect()
            ->route('commerce.admin.categories.index')
            ->with('success', 'Category created successfully');
    }

    public function edit(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $category = Category::where('store_id', $store->id)->findOrFail($id);

        $parentCategories = Category::where('store_id', $store->id)
            ->whereNull('parent_id')
            ->where('id', '!=', $id)
            ->orderBy('name')
            ->get();

        return view('vodo-commerce::admin.categories.edit', [
            'store' => $store,
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $category = Category::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:255',
            'parent_id' => "nullable|exists:commerce_categories,id|not_in:{$id}",
            'position' => 'integer|min:0',
            'is_visible' => 'boolean',
        ]);

        if (isset($validated['slug']) && $validated['slug'] !== $category->slug) {
            $validated['slug'] = $this->generateUniqueSlug($store->id, $validated['slug'], $id);
        }

        $category->update($validated);

        return redirect()
            ->route('commerce.admin.categories.index')
            ->with('success', 'Category updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $category = Category::where('store_id', $store->id)->findOrFail($id);

        // Check for child categories
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories');
        }

        // Check for products
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with products');
        }

        $category->delete();

        return redirect()
            ->route('commerce.admin.categories.index')
            ->with('success', 'Category deleted successfully');
    }

    public function reorder(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|integer',
            'categories.*.position' => 'required|integer|min:0',
        ]);

        foreach ($request->input('categories') as $item) {
            Category::where('store_id', $store->id)
                ->where('id', $item['id'])
                ->update(['position' => $item['position']]);
        }

        return response()->json(['success' => true]);
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

    protected function generateUniqueSlug(int $storeId, string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        $query = Category::where('store_id', $storeId)->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;

            $query = Category::where('store_id', $storeId)->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}
