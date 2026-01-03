<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Category;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ProductService;

class ProductController extends Controller
{
    public function index(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $productService = new ProductService($store);

        $filters = $request->only([
            'category_id',
            'price_min',
            'price_max',
            'in_stock',
            'sort_by',
            'sort_dir',
            'search',
        ]);

        $products = $productService->listActive($filters, 12);
        $categories = Category::where('store_id', $store->id)
            ->whereNull('parent_id')
            ->where('is_visible', true)
            ->with('children')
            ->orderBy('position')
            ->get();

        return view('vodo-commerce::storefront.products.index', [
            'store' => $store,
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, string $storeSlug, string $productSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $productService = new ProductService($store);

        $product = $productService->findBySlug($productSlug);

        if (!$product) {
            abort(404);
        }

        $relatedProducts = $productService->getRelated($product, 4);

        return view('vodo-commerce::storefront.products.show', [
            'store' => $store,
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    public function category(Request $request, string $storeSlug, string $categorySlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $category = Category::where('store_id', $store->id)
            ->where('slug', $categorySlug)
            ->where('is_visible', true)
            ->firstOrFail();

        $productService = new ProductService($store);
        $products = $productService->getByCategory($category, 12);

        $categories = Category::where('store_id', $store->id)
            ->whereNull('parent_id')
            ->where('is_visible', true)
            ->with('children')
            ->orderBy('position')
            ->get();

        return view('vodo-commerce::storefront.products.category', [
            'store' => $store,
            'category' => $category,
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function search(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $productService = new ProductService($store);

        $query = $request->input('q', '');
        $products = $productService->search($query, 24);

        return view('vodo-commerce::storefront.products.search', [
            'store' => $store,
            'query' => $query,
            'products' => $products,
        ]);
    }
}
