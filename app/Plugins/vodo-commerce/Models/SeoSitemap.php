<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class SeoSitemap extends Model
{
    use BelongsToStore;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'commerce_seo_sitemaps';

    protected $fillable = [
        'store_id',
        'entity_type',
        'entity_id',
        'loc',
        'lastmod',
        'changefreq',
        'priority',
        'sitemap_type',
        'images',
        'videos',
        'news',
        'language',
        'alternate_languages',
        'is_active',
        'is_indexed',
        'indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'lastmod' => 'datetime',
            'priority' => 'decimal:1',
            'images' => 'array',
            'videos' => 'array',
            'news' => 'array',
            'alternate_languages' => 'array',
            'is_active' => 'boolean',
            'is_indexed' => 'boolean',
            'indexed_at' => 'datetime',
        ];
    }

    // Constants
    public const TYPE_URL = 'url';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_NEWS = 'news';

    public const FREQ_ALWAYS = 'always';
    public const FREQ_HOURLY = 'hourly';
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_MONTHLY = 'monthly';
    public const FREQ_YEARLY = 'yearly';
    public const FREQ_NEVER = 'never';

    // Relationships

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    // Status methods

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function markAsIndexed(): void
    {
        $this->update([
            'is_indexed' => true,
            'indexed_at' => now(),
        ]);
    }

    // Type checks

    public function isUrlSitemap(): bool
    {
        return $this->sitemap_type === self::TYPE_URL;
    }

    public function isImageSitemap(): bool
    {
        return $this->sitemap_type === self::TYPE_IMAGE;
    }

    public function isVideoSitemap(): bool
    {
        return $this->sitemap_type === self::TYPE_VIDEO;
    }

    public function isNewsSitemap(): bool
    {
        return $this->sitemap_type === self::TYPE_NEWS;
    }

    // XML Generation

    public function toXml(): string
    {
        $xml = '<url>';
        $xml .= '<loc>' . htmlspecialchars($this->loc) . '</loc>';

        if ($this->lastmod) {
            $xml .= '<lastmod>' . $this->lastmod->toW3cString() . '</lastmod>';
        }

        if ($this->changefreq) {
            $xml .= '<changefreq>' . $this->changefreq . '</changefreq>';
        }

        if ($this->priority) {
            $xml .= '<priority>' . number_format($this->priority, 1) . '</priority>';
        }

        // Add alternate language links (hreflang)
        if ($this->alternate_languages) {
            foreach ($this->alternate_languages as $lang => $url) {
                $xml .= '<xhtml:link rel="alternate" hreflang="' . htmlspecialchars($lang) . '" href="' . htmlspecialchars($url) . '" />';
            }
        }

        // Add images
        if ($this->images) {
            foreach ($this->images as $image) {
                $xml .= '<image:image>';
                $xml .= '<image:loc>' . htmlspecialchars($image['loc']) . '</image:loc>';
                if (! empty($image['caption'])) {
                    $xml .= '<image:caption>' . htmlspecialchars($image['caption']) . '</image:caption>';
                }
                if (! empty($image['title'])) {
                    $xml .= '<image:title>' . htmlspecialchars($image['title']) . '</image:title>';
                }
                $xml .= '</image:image>';
            }
        }

        // Add videos
        if ($this->videos) {
            foreach ($this->videos as $video) {
                $xml .= '<video:video>';
                $xml .= '<video:thumbnail_loc>' . htmlspecialchars($video['thumbnail_loc']) . '</video:thumbnail_loc>';
                $xml .= '<video:title>' . htmlspecialchars($video['title']) . '</video:title>';
                $xml .= '<video:description>' . htmlspecialchars($video['description']) . '</video:description>';
                $xml .= '<video:content_loc>' . htmlspecialchars($video['content_loc']) . '</video:content_loc>';
                $xml .= '</video:video>';
            }
        }

        $xml .= '</url>';

        return $xml;
    }

    // Update methods

    public function updateFromEntity($entity): void
    {
        $updates = [
            'lastmod' => $entity->updated_at ?? now(),
        ];

        // Auto-detect change frequency based on entity type
        if (! $this->changefreq || $this->changefreq === '') {
            $updates['changefreq'] = $this->determineChangeFrequency($entity);
        }

        // Auto-detect priority based on entity type
        if (! $this->priority || $this->priority == 0.5) {
            $updates['priority'] = $this->determinePriority($entity);
        }

        $this->update($updates);
    }

    protected function determineChangeFrequency($entity): string
    {
        return match ($this->entity_type) {
            'Product' => self::FREQ_DAILY,
            'Category' => self::FREQ_WEEKLY,
            'Brand' => self::FREQ_MONTHLY,
            'Page' => self::FREQ_MONTHLY,
            default => self::FREQ_WEEKLY,
        };
    }

    protected function determinePriority($entity): float
    {
        return match ($this->entity_type) {
            'Product' => 0.8,
            'Category' => 0.9,
            'Brand' => 0.7,
            'Page' => 0.6,
            default => 0.5,
        };
    }

    // Query scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('sitemap_type', $type);
    }

    public function scopeForLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    public function scopeRecentlyModified($query, int $days = 7)
    {
        return $query->where('lastmod', '>=', now()->subDays($days));
    }

    public function scopeHighPriority($query, float $minPriority = 0.7)
    {
        return $query->where('priority', '>=', $minPriority);
    }

    public function scopeNotIndexed($query)
    {
        return $query->where('is_indexed', false);
    }

    // Static helpers

    public static function getOrCreateForEntity(int $storeId, string $entityType, int $entityId, string $url): self
    {
        return static::firstOrCreate(
            [
                'store_id' => $storeId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            [
                'loc' => $url,
                'lastmod' => now(),
                'changefreq' => self::FREQ_WEEKLY,
                'priority' => 0.5,
                'sitemap_type' => self::TYPE_URL,
                'is_active' => true,
            ]
        );
    }

    public static function createForUrl(int $storeId, string $url, array $options = []): self
    {
        return static::create(array_merge([
            'store_id' => $storeId,
            'loc' => $url,
            'lastmod' => now(),
            'changefreq' => self::FREQ_WEEKLY,
            'priority' => 0.5,
            'sitemap_type' => self::TYPE_URL,
            'is_active' => true,
        ], $options));
    }

    // Statistics

    public static function getStatsByType(int $storeId): array
    {
        return static::where('store_id', $storeId)
            ->where('is_active', true)
            ->selectRaw('sitemap_type, COUNT(*) as count')
            ->groupBy('sitemap_type')
            ->pluck('count', 'sitemap_type')
            ->toArray();
    }

    public static function getTotalUrls(int $storeId): int
    {
        return static::where('store_id', $storeId)
            ->where('is_active', true)
            ->count();
    }
}
