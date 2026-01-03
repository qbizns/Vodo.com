<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Requests\ProductRequest;
use VodoCommerce\Models\Category;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ProductService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $productService = new ProductService($store);

        $filters = $request->only([
            'status',
            'category_id',
            'search',
            'in_stock',
            'sort_by',
            'sort_dir',
        ]);

        $products = $productService->list($filters, 20);

        $categories = Category::where('store_id', $store->id)
            ->orderBy('name')
            ->get();

        return view('vodo-commerce::admin.products.index', [
            'store' => $store,
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $categories = Category::where('store_id', $store->id)
            ->orderBy('name')
            ->get();

        return view('vodo-commerce::admin.products.create', [
            'store' => $store,
            'categories' => $categories,
        ]);
    }

    public function store(ProductRequest $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $productService = new ProductService($store);
        $product = $productService->create($request->validated());

        return redirect()
            ->route('commerce.admin.products.edit', $product->id)
            ->with('success', 'Product created successfully');
    }

    public function edit(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $productService = new ProductService($store);
        $product = $productService->find($id);

        if (!$product) {
            abort(404);
        }

        $categories = Category::where('store_id', $store->id)
            ->orderBy('name')
            ->get();

        return view('vodo-commerce::admin.products.edit', [
            'store' => $store,
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(ProductRequest $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $productService = new ProductService($store);
        $product = $productService->find($id);

        if (!$product) {
            abort(404);
        }

        $productService->update($product, $request->validated());

        return redirect()
            ->route('commerce.admin.products.edit', $product->id)
            ->with('success', 'Product updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $productService = new ProductService($store);
        $product = $productService->find($id);

        if (!$product) {
            abort(404);
        }

        $productService->delete($product);

        return redirect()
            ->route('commerce.admin.products.index')
            ->with('success', 'Product deleted successfully');
    }

    public function duplicate(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $productService = new ProductService($store);
        $product = $productService->find($id);

        if (!$product) {
            abort(404);
        }

        $data = $product->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
        $data['name'] = $product->name . ' (Copy)';
        $data['slug'] = null; // Will be auto-generated
        $data['sku'] = $product->sku ? $product->sku . '-copy' : null;
        $data['status'] = 'draft';

        $newProduct = $productService->create($data);

        return redirect()
            ->route('commerce.admin.products.edit', $newProduct->id)
            ->with('success', 'Product duplicated successfully');
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
