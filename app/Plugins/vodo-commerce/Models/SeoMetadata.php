<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class SeoMetadata extends Model
{
    use BelongsToStore;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'commerce_seo_metadata';

    protected $fillable = [
        'store_id',
        'entity_type',
        'entity_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'robots_index',
        'robots_follow',
        'robots_advanced',
        'og_title',
        'og_description',
        'og_image',
        'og_image_width',
        'og_image_height',
        'og_type',
        'og_locale',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'twitter_creator',
        'twitter_site',
        'focus_keyword',
        'focus_keyword_density',
        'seo_score',
        'seo_analysis',
        'schema_markup',
        'schema_auto_generate',
        'custom_meta',
        'is_indexed',
        'last_indexed_at',
        'last_crawled_at',
    ];

    protected function casts(): array
    {
        return [
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'focus_keyword_density' => 'integer',
            'seo_score' => 'integer',
            'seo_analysis' => 'array',
            'schema_markup' => 'array',
            'schema_auto_generate' => 'boolean',
            'custom_meta' => 'array',
            'is_indexed' => 'boolean',
            'last_indexed_at' => 'datetime',
            'last_crawled_at' => 'datetime',
        ];
    }

    // Relationships

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    // Robots Meta Helpers

    public function getRobotsMetaContent(): string
    {
        $parts = [];

        $parts[] = $this->robots_index ? 'index' : 'noindex';
        $parts[] = $this->robots_follow ? 'follow' : 'nofollow';

        if ($this->robots_advanced) {
            $parts[] = $this->robots_advanced;
        }

        return implode(', ', $parts);
    }

    public function setNoIndex(): void
    {
        $this->update(['robots_index' => false]);
    }

    public function setNoFollow(): void
    {
        $this->update(['robots_follow' => false]);
    }

    // SEO Score Methods

    public function calculateSeoScore(): int
    {
        $score = 0;
        $checks = 0;

        // Meta title check (10 points)
        if ($this->meta_title) {
            $titleLength = mb_strlen($this->meta_title);
            if ($titleLength >= 30 && $titleLength <= 60) {
                $score += 10;
            } elseif ($titleLength > 0) {
                $score += 5;
            }
        }
        $checks += 10;

        // Meta description check (10 points)
        if ($this->meta_description) {
            $descLength = mb_strlen($this->meta_description);
            if ($descLength >= 120 && $descLength <= 160) {
                $score += 10;
            } elseif ($descLength > 0) {
                $score += 5;
            }
        }
        $checks += 10;

        // Focus keyword in title (15 points)
        if ($this->focus_keyword && $this->meta_title) {
            if (stripos($this->meta_title, $this->focus_keyword) !== false) {
                $score += 15;
            }
        }
        $checks += 15;

        // Focus keyword in description (10 points)
        if ($this->focus_keyword && $this->meta_description) {
            if (stripos($this->meta_description, $this->focus_keyword) !== false) {
                $score += 10;
            }
        }
        $checks += 10;

        // Canonical URL (5 points)
        if ($this->canonical_url) {
            $score += 5;
        }
        $checks += 5;

        // Open Graph tags (15 points)
        $ogScore = 0;
        if ($this->og_title) {
            $ogScore += 5;
        }
        if ($this->og_description) {
            $ogScore += 5;
        }
        if ($this->og_image) {
            $ogScore += 5;
        }
        $score += $ogScore;
        $checks += 15;

        // Twitter Card (10 points)
        $twitterScore = 0;
        if ($this->twitter_title) {
            $twitterScore += 3;
        }
        if ($this->twitter_description) {
            $twitterScore += 3;
        }
        if ($this->twitter_image) {
            $twitterScore += 4;
        }
        $score += $twitterScore;
        $checks += 10;

        // Schema markup (15 points)
        if ($this->schema_markup && count($this->schema_markup) > 0) {
            $score += 15;
        }
        $checks += 15;

        // Robots indexing (10 points)
        if ($this->robots_index && $this->robots_follow) {
            $score += 10;
        }
        $checks += 10;

        // Calculate final score as percentage
        return (int) round(($score / $checks) * 100);
    }

    public function updateSeoScore(): void
    {
        $score = $this->calculateSeoScore();
        $this->update(['seo_score' => $score]);
    }

    // Focus Keyword Analysis

    public function calculateKeywordDensity(string $content): int
    {
        if (! $this->focus_keyword || empty($content)) {
            return 0;
        }

        $content = strtolower(strip_tags($content));
        $keyword = strtolower($this->focus_keyword);

        $totalWords = str_word_count($content);
        if ($totalWords === 0) {
            return 0;
        }

        $keywordCount = substr_count($content, $keyword);

        // Return density as percentage * 100 (e.g., 250 = 2.5%)
        return (int) round(($keywordCount / $totalWords) * 10000);
    }

    public function analyzeKeywordPlacement(string $content, ?string $title = null, ?string $firstParagraph = null): array
    {
        if (! $this->focus_keyword) {
            return [];
        }

        $keyword = strtolower($this->focus_keyword);

        return [
            'in_meta_title' => $this->meta_title ? stripos(strtolower($this->meta_title), $keyword) !== false : false,
            'in_meta_description' => $this->meta_description ? stripos(strtolower($this->meta_description), $keyword) !== false : false,
            'in_og_title' => $this->og_title ? stripos(strtolower($this->og_title), $keyword) !== false : false,
            'in_content_title' => $title ? stripos(strtolower($title), $keyword) !== false : false,
            'in_first_paragraph' => $firstParagraph ? stripos(strtolower($firstParagraph), $keyword) !== false : false,
            'in_url' => $this->canonical_url ? stripos(strtolower($this->canonical_url), str_replace(' ', '-', $keyword)) !== false : false,
        ];
    }

    // Open Graph Helpers

    public function generateOpenGraphTags(): array
    {
        return array_filter([
            'og:title' => $this->og_title ?? $this->meta_title,
            'og:description' => $this->og_description ?? $this->meta_description,
            'og:image' => $this->og_image,
            'og:image:width' => $this->og_image_width,
            'og:image:height' => $this->og_image_height,
            'og:type' => $this->og_type,
            'og:locale' => $this->og_locale,
            'og:url' => $this->canonical_url,
        ]);
    }

    // Twitter Card Helpers

    public function generateTwitterCardTags(): array
    {
        return array_filter([
            'twitter:card' => $this->twitter_card,
            'twitter:title' => $this->twitter_title ?? $this->meta_title,
            'twitter:description' => $this->twitter_description ?? $this->meta_description,
            'twitter:image' => $this->twitter_image ?? $this->og_image,
            'twitter:creator' => $this->twitter_creator,
            'twitter:site' => $this->twitter_site,
        ]);
    }

    // Indexing Helpers

    public function markAsIndexed(): void
    {
        $this->update([
            'is_indexed' => true,
            'last_indexed_at' => now(),
        ]);
    }

    public function markAsCrawled(): void
    {
        $this->update([
            'last_crawled_at' => now(),
        ]);
    }

    // Query Scopes

    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    public function scopeIndexable($query)
    {
        return $query->where('robots_index', true);
    }

    public function scopeNotIndexed($query)
    {
        return $query->where('is_indexed', false);
    }

    public function scopeWithGoodScore($query, int $minScore = 70)
    {
        return $query->where('seo_score', '>=', $minScore);
    }

    public function scopeNeedsImprovement($query, int $maxScore = 50)
    {
        return $query->where('seo_score', '<=', $maxScore);
    }

    // Static Helpers

    public static function getOrCreateForEntity(string $entityType, int $entityId, int $storeId): self
    {
        return static::firstOrCreate(
            [
                'store_id' => $storeId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            [
                'robots_index' => true,
                'robots_follow' => true,
                'schema_auto_generate' => true,
            ]
        );
    }
}
