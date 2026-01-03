<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ProductService;

class HomeController extends Controller
{
    public function __invoke(Request $request, string $storeSlug)
    {
        // Bypass tenant scope for public storefront - find store by slug only
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $productService = new ProductService($store);

        $featuredProducts = $productService->getFeatured(8);

        return view('vodo-commerce::storefront.home', [
            'store' => $store,
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
