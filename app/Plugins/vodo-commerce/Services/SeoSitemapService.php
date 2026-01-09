<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use VodoCommerce\Models\Brand;
use VodoCommerce\Models\Category;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\SeoSettings;
use VodoCommerce\Models\SeoSitemap;
use VodoCommerce\Models\Store;

class SeoSitemapService
{
    public function __construct(
        protected Store $store
    ) {
    }

    // Generate complete sitemap index
    public function generateSitemapIndex(): string
    {
        $sitemaps = [
            ['loc' => route('sitemap.products'), 'lastmod' => $this->getLastProductUpdate()],
            ['loc' => route('sitemap.categories'), 'lastmod' => $this->getLastCategoryUpdate()],
            ['loc' => route('sitemap.brands'), 'lastmod' => $this->getLastBrandUpdate()],
            ['loc' => route('sitemap.pages'), 'lastmod' => now()],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($sitemaps as $sitemap) {
            $xml .= '<sitemap>';
            $xml .= '<loc>' . htmlspecialchars($sitemap['loc']) . '</loc>';
            if ($sitemap['lastmod']) {
                $xml .= '<lastmod>' . $sitemap['lastmod']->toW3cString() . '</lastmod>';
            }
            $xml .= '</sitemap>';
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    // Generate product sitemap
    public function generateProductSitemap(): string
    {
        $products = Product::where('store_id', $this->store->id)
            ->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->generateXmlSitemap($products, function ($product) {
            return [
                'loc' => route('products.show', $product->slug ?? $product->id),
                'lastmod' => $product->updated_at,
                'changefreq' => 'daily',
                'priority' => $product->featured ? 0.9 : 0.8,
                'images' => $this->getProductImages($product),
            ];
        });
    }

    // Generate category sitemap
    public function generateCategorySitemap(): string
    {
        $categories = Category::where('store_id', $this->store->id)
            ->where('is_visible', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->generateXmlSitemap($categories, function ($category) {
            return [
                'loc' => route('categories.show', $category->slug ?? $category->id),
                'lastmod' => $category->updated_at,
                'changefreq' => 'weekly',
                'priority' => 0.9,
            ];
        });
    }

    // Generate brand sitemap
    public function generateBrandSitemap(): string
    {
        $brands = Brand::where('store_id', $this->store->id)
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->generateXmlSitemap($brands, function ($brand) {
            return [
                'loc' => route('brands.show', $brand->slug ?? $brand->id),
                'lastmod' => $brand->updated_at,
                'changefreq' => 'monthly',
                'priority' => 0.7,
            ];
        });
    }

    // Generic XML sitemap generator
    protected function generateXmlSitemap(Collection $items, callable $transformer): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        foreach ($items as $item) {
            $data = $transformer($item);

            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($data['loc']) . '</loc>';

            if (! empty($data['lastmod'])) {
                $xml .= '<lastmod>' . $data['lastmod']->toW3cString() . '</lastmod>';
            }

            if (! empty($data['changefreq'])) {
                $xml .= '<changefreq>' . $data['changefreq'] . '</changefreq>';
            }

            if (! empty($data['priority'])) {
                $xml .= '<priority>' . number_format($data['priority'], 1) . '</priority>';
            }

            // Add images if present
            if (! empty($data['images'])) {
                foreach ($data['images'] as $image) {
                    $xml .= '<image:image>';
                    $xml .= '<image:loc>' . htmlspecialchars($image['url']) . '</image:loc>';
                    if (! empty($image['title'])) {
                        $xml .= '<image:title>' . htmlspecialchars($image['title']) . '</image:title>';
                    }
                    if (! empty($image['caption'])) {
                        $xml .= '<image:caption>' . htmlspecialchars($image['caption']) . '</image:caption>';
                    }
                    $xml .= '</image:image>';
                }
            }

            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return $xml;
    }

    // Sync database sitemap entries
    public function syncSitemapEntries(): int
    {
        return DB::transaction(function () {
            $synced = 0;

            // Sync products
            $synced += $this->syncProductEntries();

            // Sync categories
            $synced += $this->syncCategoryEntries();

            // Sync brands
            $synced += $this->syncBrandEntries();

            return $synced;
        });
    }

    protected function syncProductEntries(): int
    {
        $products = Product::where('store_id', $this->store->id)
            ->where('status', 'active')
            ->get();

        $synced = 0;

        foreach ($products as $product) {
            SeoSitemap::updateOrCreate(
                [
                    'store_id' => $this->store->id,
                    'entity_type' => 'Product',
                    'entity_id' => $product->id,
                ],
                [
                    'loc' => route('products.show', $product->slug ?? $product->id),
                    'lastmod' => $product->updated_at,
                    'changefreq' => SeoSitemap::FREQ_DAILY,
                    'priority' => $product->featured ? 0.9 : 0.8,
                    'sitemap_type' => SeoSitemap::TYPE_URL,
                    'is_active' => true,
                    'images' => $this->getProductImages($product),
                ]
            );

            $synced++;
        }

        return $synced;
    }

    protected function syncCategoryEntries(): int
    {
        $categories = Category::where('store_id', $this->store->id)
            ->where('is_visible', true)
            ->get();

        $synced = 0;

        foreach ($categories as $category) {
            SeoSitemap::updateOrCreate(
                [
                    'store_id' => $this->store->id,
                    'entity_type' => 'Category',
                    'entity_id' => $category->id,
                ],
                [
                    'loc' => route('categories.show', $category->slug ?? $category->id),
                    'lastmod' => $category->updated_at,
                    'changefreq' => SeoSitemap::FREQ_WEEKLY,
                    'priority' => 0.9,
                    'sitemap_type' => SeoSitemap::TYPE_URL,
                    'is_active' => true,
                ]
            );

            $synced++;
        }

        return $synced;
    }

    protected function syncBrandEntries(): int
    {
        $brands = Brand::where('store_id', $this->store->id)
            ->where('is_active', true)
            ->get();

        $synced = 0;

        foreach ($brands as $brand) {
            SeoSitemap::updateOrCreate(
                [
                    'store_id' => $this->store->id,
                    'entity_type' => 'Brand',
                    'entity_id' => $brand->id,
                ],
                [
                    'loc' => route('brands.show', $brand->slug ?? $brand->id),
                    'lastmod' => $brand->updated_at,
                    'changefreq' => SeoSitemap::FREQ_MONTHLY,
                    'priority' => 0.7,
                    'sitemap_type' => SeoSitemap::TYPE_URL,
                    'is_active' => true,
                ]
            );

            $synced++;
        }

        return $synced;
    }

    // Helper methods

    protected function getProductImages(Product $product): array
    {
        $images = [];

        if ($product->featured_image) {
            $images[] = [
                'url' => $product->featured_image,
                'title' => $product->name,
                'caption' => $product->short_description,
            ];
        }

        if ($product->images && is_array($product->images)) {
            foreach ($product->images as $image) {
                if (is_string($image)) {
                    $images[] = ['url' => $image, 'title' => $product->name];
                } elseif (is_array($image) && isset($image['url'])) {
                    $images[] = [
                        'url' => $image['url'],
                        'title' => $image['title'] ?? $product->name,
                        'caption' => $image['caption'] ?? null,
                    ];
                }
            }
        }

        return $images;
    }

    protected function getLastProductUpdate()
    {
        return Product::where('store_id', $this->store->id)
            ->where('status', 'active')
            ->max('updated_at');
    }

    protected function getLastCategoryUpdate()
    {
        return Category::where('store_id', $this->store->id)
            ->where('is_visible', true)
            ->max('updated_at');
    }

    protected function getLastBrandUpdate()
    {
        return Brand::where('store_id', $this->store->id)
            ->where('is_active', true)
            ->max('updated_at');
    }

    // Submit sitemap to search engines
    public function submitToSearchEngines(): array
    {
        $settings = SeoSettings::getForStore($this->store->id);
        $sitemapUrl = $settings->getSitemapUrl();

        $results = [];

        // Submit to Google
        if ($settings->google_site_verification) {
            $results['google'] = $this->submitToGoogle($sitemapUrl);
        }

        // Submit to Bing
        if ($settings->bing_site_verification) {
            $results['bing'] = $this->submitToBing($sitemapUrl);
        }

        return $results;
    }

    protected function submitToGoogle(string $sitemapUrl): bool
    {
        $url = 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);
        // In production, you would make an actual HTTP request
        // For now, return true as placeholder
        return true;
    }

    protected function submitToBing(string $sitemapUrl): bool
    {
        $url = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl);
        // In production, you would make an actual HTTP request
        // For now, return true as placeholder
        return true;
    }

    // Statistics
    public function getSitemapStatistics(): array
    {
        $stats = SeoSitemap::where('store_id', $this->store->id)
            ->selectRaw('sitemap_type, COUNT(*) as count, MAX(lastmod) as last_update')
            ->groupBy('sitemap_type')
            ->get();

        return [
            'total_urls' => SeoSitemap::where('store_id', $this->store->id)->count(),
            'by_type' => $stats->pluck('count', 'sitemap_type')->toArray(),
            'last_update' => $stats->max('last_update'),
            'indexed_urls' => SeoSitemap::where('store_id', $this->store->id)->where('is_indexed', true)->count(),
        ];
    }
}
