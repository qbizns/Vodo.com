<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VodoCommerce\Traits\BelongsToStore;

class SeoSettings extends Model
{
    use BelongsToStore;
    use HasFactory;

    protected $table = 'commerce_seo_settings';

    protected $fillable = [
        'store_id',
        'site_name',
        'site_description',
        'default_og_image',
        'favicon_url',
        'logo_url',
        'facebook_url',
        'twitter_handle',
        'instagram_url',
        'linkedin_url',
        'youtube_url',
        'pinterest_url',
        'organization_type',
        'organization_name',
        'organization_contact',
        'organization_description',
        'organization_founding_date',
        'organization_logo',
        'business_type',
        'opening_hours',
        'price_range',
        'geo_coordinates',
        'service_areas',
        'robots_txt',
        'allow_search_engines',
        'auto_generate_sitemap',
        'sitemap_products_per_page',
        'sitemap_categories_per_page',
        'sitemap_excluded_urls',
        'google_site_verification',
        'google_analytics_id',
        'google_tag_manager_id',
        'google_merchant_center_id',
        'bing_site_verification',
        'pinterest_site_verification',
        'yandex_verification',
        'enable_product_schema',
        'enable_breadcrumb_schema',
        'enable_organization_schema',
        'enable_review_schema',
        'enable_faq_schema',
        'canonical_domain',
        'force_trailing_slash',
        'force_lowercase_urls',
        'default_meta_title_template',
        'product_meta_title_template',
        'category_meta_title_template',
        'custom_head_code',
        'custom_body_code',
        'enable_amp',
        'enable_pwa',
    ];

    protected function casts(): array
    {
        return [
            'organization_contact' => 'array',
            'organization_founding_date' => 'date',
            'opening_hours' => 'array',
            'geo_coordinates' => 'array',
            'service_areas' => 'array',
            'allow_search_engines' => 'boolean',
            'auto_generate_sitemap' => 'boolean',
            'sitemap_products_per_page' => 'integer',
            'sitemap_categories_per_page' => 'integer',
            'sitemap_excluded_urls' => 'array',
            'enable_product_schema' => 'boolean',
            'enable_breadcrumb_schema' => 'boolean',
            'enable_organization_schema' => 'boolean',
            'enable_review_schema' => 'boolean',
            'enable_faq_schema' => 'boolean',
            'force_trailing_slash' => 'boolean',
            'force_lowercase_urls' => 'boolean',
            'custom_head_code' => 'array',
            'custom_body_code' => 'array',
            'enable_amp' => 'boolean',
            'enable_pwa' => 'boolean',
        ];
    }

    // Robots.txt generation

    public function generateRobotsTxt(): string
    {
        if ($this->robots_txt) {
            return $this->robots_txt;
        }

        $robotsTxt = [];

        // User-agent
        $robotsTxt[] = 'User-agent: *';

        // Allow or disallow all
        if ($this->allow_search_engines) {
            $robotsTxt[] = 'Allow: /';

            // Disallow common paths
            $robotsTxt[] = 'Disallow: /admin/';
            $robotsTxt[] = 'Disallow: /cart/';
            $robotsTxt[] = 'Disallow: /checkout/';
            $robotsTxt[] = 'Disallow: /account/';
            $robotsTxt[] = 'Disallow: /api/';
            $robotsTxt[] = 'Disallow: /search?';
        } else {
            $robotsTxt[] = 'Disallow: /';
        }

        // Add sitemap reference
        if ($this->auto_generate_sitemap) {
            $robotsTxt[] = '';
            $robotsTxt[] = 'Sitemap: ' . $this->getSitemapUrl();
        }

        return implode("\n", $robotsTxt);
    }

    public function getSitemapUrl(): string
    {
        $domain = $this->canonical_domain ?? config('app.url');

        return rtrim($domain, '/') . '/sitemap.xml';
    }

    // Organization Schema generation

    public function generateOrganizationSchema(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $this->organization_type ?? 'Organization',
            'name' => $this->organization_name ?? $this->site_name,
            'url' => $this->canonical_domain ?? config('app.url'),
        ];

        if ($this->organization_description) {
            $schema['description'] = $this->organization_description;
        }

        if ($this->organization_logo ?? $this->logo_url) {
            $schema['logo'] = $this->organization_logo ?? $this->logo_url;
        }

        if ($this->organization_contact) {
            $contact = $this->organization_contact;

            if (! empty($contact['phone'])) {
                $schema['telephone'] = $contact['phone'];
            }

            if (! empty($contact['email'])) {
                $schema['email'] = $contact['email'];
            }

            if (! empty($contact['address'])) {
                $schema['address'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $contact['address']['street'] ?? null,
                    'addressLocality' => $contact['address']['city'] ?? null,
                    'addressRegion' => $contact['address']['state'] ?? null,
                    'postalCode' => $contact['address']['zip'] ?? null,
                    'addressCountry' => $contact['address']['country'] ?? null,
                ];
            }
        }

        // Social media profiles
        $sameAs = array_filter([
            $this->facebook_url,
            $this->twitter_handle ? 'https://twitter.com/' . ltrim($this->twitter_handle, '@') : null,
            $this->instagram_url,
            $this->linkedin_url,
            $this->youtube_url,
            $this->pinterest_url,
        ]);

        if (! empty($sameAs)) {
            $schema['sameAs'] = $sameAs;
        }

        // Local business specific fields
        if ($this->business_type) {
            $schema['@type'] = $this->business_type;

            if ($this->opening_hours) {
                $schema['openingHoursSpecification'] = $this->opening_hours;
            }

            if ($this->price_range) {
                $schema['priceRange'] = $this->price_range;
            }

            if ($this->geo_coordinates) {
                $schema['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $this->geo_coordinates['lat'] ?? null,
                    'longitude' => $this->geo_coordinates['lng'] ?? null,
                ];
            }
        }

        return array_filter($schema);
    }

    // Meta title template processing

    public function processMetaTitleTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        // Replace {site_name} if not already replaced
        $template = str_replace('{site_name}', $this->site_name, $template);

        return $template;
    }

    public function getProductMetaTitle(string $productName): string
    {
        return $this->processMetaTitleTemplate($this->product_meta_title_template, [
            'product_name' => $productName,
        ]);
    }

    public function getCategoryMetaTitle(string $categoryName): string
    {
        return $this->processMetaTitleTemplate($this->category_meta_title_template, [
            'category_name' => $categoryName,
        ]);
    }

    // Verification codes

    public function getGoogleSiteVerificationTag(): ?string
    {
        if (! $this->google_site_verification) {
            return null;
        }

        return '<meta name="google-site-verification" content="' . htmlspecialchars($this->google_site_verification) . '" />';
    }

    public function getBingSiteVerificationTag(): ?string
    {
        if (! $this->bing_site_verification) {
            return null;
        }

        return '<meta name="msvalidate.01" content="' . htmlspecialchars($this->bing_site_verification) . '" />';
    }

    // Analytics

    public function getGoogleAnalyticsScript(): ?string
    {
        if (! $this->google_analytics_id) {
            return null;
        }

        return <<<HTML
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$this->google_analytics_id}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$this->google_analytics_id}');
</script>
HTML;
    }

    public function getGoogleTagManagerScript(): ?string
    {
        if (! $this->google_tag_manager_id) {
            return null;
        }

        return <<<HTML
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$this->google_tag_manager_id}');</script>
HTML;
    }

    // Static helpers

    public static function getForStore(int $storeId): self
    {
        return static::firstOrCreate(
            ['store_id' => $storeId],
            [
                'site_name' => 'My Store',
                'allow_search_engines' => true,
                'auto_generate_sitemap' => true,
                'sitemap_products_per_page' => 1000,
                'sitemap_categories_per_page' => 500,
                'enable_product_schema' => true,
                'enable_breadcrumb_schema' => true,
                'enable_organization_schema' => true,
                'enable_review_schema' => true,
                'enable_faq_schema' => true,
                'force_lowercase_urls' => true,
                'default_meta_title_template' => '{title} | {site_name}',
                'product_meta_title_template' => '{product_name} | {site_name}',
                'category_meta_title_template' => '{category_name} | {site_name}',
            ]
        );
    }
}
