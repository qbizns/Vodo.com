<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\Brand;
use VodoCommerce\Models\Category;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\SeoSettings;
use VodoCommerce\Models\Store;

class SeoSchemaService
{
    public function __construct(
        protected Store $store
    ) {
    }

    // Product Schema (schema.org/Product)
    public function generateProductSchema(Product $product): array
    {
        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => strip_tags($product->short_description ?? $product->description ?? ''),
            'sku' => $product->sku,
            'image' => $this->getProductImages($product),
            'brand' => $this->getBrandSchema($product->brand),
            'offers' => $this->getProductOfferSchema($product),
        ];

        // Add category/breadcrumb
        if ($product->category) {
            $schema['category'] = $product->category->name;
        }

        // Add review/rating data
        $reviewData = $this->getProductReviewSchema($product);
        if ($reviewData) {
            $schema['aggregateRating'] = $reviewData;
        }

        // Add additional product details
        if ($product->weight) {
            $schema['weight'] = [
                '@type' => 'QuantitativeValue',
                'value' => $product->weight,
                'unitCode' => 'KGM',
            ];
        }

        if ($product->dimensions) {
            $dimensions = $product->dimensions;
            if (isset($dimensions['length'], $dimensions['width'], $dimensions['height'])) {
                $schema['depth'] = ['@type' => 'QuantitativeValue', 'value' => $dimensions['length'], 'unitCode' => 'CMT'];
                $schema['width'] = ['@type' => 'QuantitativeValue', 'value' => $dimensions['width'], 'unitCode' => 'CMT'];
                $schema['height'] = ['@type' => 'QuantitativeValue', 'value' => $dimensions['height'], 'unitCode' => 'CMT'];
            }
        }

        return array_filter($schema);
    }

    protected function getProductImages(Product $product): array
    {
        $images = [];

        // Main image
        if ($product->featured_image) {
            $images[] = $product->featured_image;
        }

        // Gallery images
        if ($product->images && is_array($product->images)) {
            foreach ($product->images as $image) {
                if (is_string($image)) {
                    $images[] = $image;
                } elseif (is_array($image) && isset($image['url'])) {
                    $images[] = $image['url'];
                }
            }
        }

        return array_unique(array_filter($images));
    }

    protected function getBrandSchema(?Brand $brand): ?array
    {
        if (! $brand) {
            return null;
        }

        return [
            '@type' => 'Brand',
            'name' => $brand->name,
            'logo' => $brand->logo,
            'url' => $brand->website,
        ];
    }

    protected function getProductOfferSchema(Product $product): array
    {
        $offer = [
            '@type' => 'Offer',
            'url' => route('products.show', $product->slug ?? $product->id),
            'priceCurrency' => $this->store->currency ?? 'USD',
            'price' => number_format((float) $product->price, 2, '.', ''),
            'priceValidUntil' => now()->addYear()->format('Y-m-d'),
            'availability' => $this->getAvailabilityStatus($product),
            'itemCondition' => 'https://schema.org/NewCondition',
        ];

        if ($product->compare_at_price && $product->compare_at_price > $product->price) {
            $offer['priceSpecification'] = [
                '@type' => 'UnitPriceSpecification',
                'price' => number_format((float) $product->price, 2, '.', ''),
                'priceCurrency' => $this->store->currency ?? 'USD',
            ];
        }

        return $offer;
    }

    protected function getAvailabilityStatus(Product $product): string
    {
        return match ($product->stock_status) {
            'in_stock' => 'https://schema.org/InStock',
            'out_of_stock' => 'https://schema.org/OutOfStock',
            'backorder' => 'https://schema.org/PreOrder',
            default => 'https://schema.org/InStock',
        };
    }

    protected function getProductReviewSchema(Product $product): ?array
    {
        $reviews = ProductReview::where('product_id', $product->id)
            ->where('status', 'approved')
            ->get();

        if ($reviews->isEmpty()) {
            return null;
        }

        $avgRating = $reviews->avg('rating');
        $reviewCount = $reviews->count();

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format($avgRating, 1),
            'reviewCount' => $reviewCount,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    // Breadcrumb Schema
    public function generateBreadcrumbSchema(array $breadcrumbs): array
    {
        $items = [];

        foreach ($breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'] ?? null,
            ];
        }

        return [
            '@context' => 'https://schema.org/',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    // Review Schema (for individual reviews)
    public function generateReviewSchema(ProductReview $review): array
    {
        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'Product',
                'name' => $review->product->name ?? 'Product',
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $review->rating,
                'bestRating' => 5,
                'worstRating' => 1,
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $review->customer->name ?? 'Anonymous',
            ],
            'reviewBody' => $review->comment,
            'datePublished' => $review->published_at?->toIso8601String(),
        ];

        if ($review->is_verified_purchase) {
            $schema['@type'] = 'UserReview';
        }

        return $schema;
    }

    // FAQ Schema
    public function generateFaqSchema(array $faqs): array
    {
        $items = [];

        foreach ($faqs as $faq) {
            $items[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org/',
            '@type' => 'FAQPage',
            'mainEntity' => $items,
        ];
    }

    // Organization Schema
    public function generateOrganizationSchema(SeoSettings $settings): array
    {
        return $settings->generateOrganizationSchema();
    }

    // WebSite Schema (for site-wide search)
    public function generateWebSiteSchema(string $searchUrl): array
    {
        return [
            '@context' => 'https://schema.org/',
            '@type' => 'WebSite',
            'name' => $this->store->name,
            'url' => config('app.url'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $searchUrl . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    // Category/Collection Page Schema
    public function generateCollectionSchema(Category $category, array $products): array
    {
        $items = [];

        foreach ($products as $index => $product) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'Product',
                    'name' => $product->name,
                    'url' => route('products.show', $product->slug ?? $product->id),
                    'image' => $product->featured_image,
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org/',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => $category->description,
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $items,
            ],
        ];
    }

    // Convert schema array to JSON-LD script tag
    public function toJsonLd(array $schema): string
    {
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    // Generate multiple schemas at once
    public function generateMultipleSchemas(array $schemas): string
    {
        $output = [];

        foreach ($schemas as $schema) {
            $output[] = $this->toJsonLd($schema);
        }

        return implode("\n", $output);
    }
}
