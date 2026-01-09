<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use VodoCommerce\Traits\BelongsToStore;

class SeoAudit extends Model
{
    use BelongsToStore;
    use HasFactory;

    protected $table = 'commerce_seo_audits';

    protected $fillable = [
        'store_id',
        'entity_type',
        'entity_id',
        'audit_type',
        'overall_score',
        'content_score',
        'technical_score',
        'meta_score',
        'performance_score',
        'mobile_score',
        'accessibility_score',
        'critical_issues',
        'warnings',
        'recommendations',
        'passed_checks',
        'word_count',
        'heading_count',
        'image_count',
        'link_count',
        'internal_link_count',
        'external_link_count',
        'readability_score',
        'keyword_analysis',
        'has_meta_title',
        'has_meta_description',
        'has_canonical',
        'has_schema_markup',
        'has_og_tags',
        'has_twitter_card',
        'has_robots_txt',
        'has_sitemap',
        'is_mobile_friendly',
        'is_https',
        'page_load_time',
        'time_to_first_byte',
        'largest_contentful_paint',
        'first_input_delay',
        'cumulative_layout_shift',
        'audited_by',
        'audited_at',
        'audit_duration',
    ];

    protected function casts(): array
    {
        return [
            'overall_score' => 'integer',
            'content_score' => 'integer',
            'technical_score' => 'integer',
            'meta_score' => 'integer',
            'performance_score' => 'integer',
            'mobile_score' => 'integer',
            'accessibility_score' => 'integer',
            'critical_issues' => 'array',
            'warnings' => 'array',
            'recommendations' => 'array',
            'passed_checks' => 'array',
            'word_count' => 'integer',
            'heading_count' => 'integer',
            'image_count' => 'integer',
            'link_count' => 'integer',
            'internal_link_count' => 'integer',
            'external_link_count' => 'integer',
            'readability_score' => 'decimal:2',
            'keyword_analysis' => 'array',
            'has_meta_title' => 'boolean',
            'has_meta_description' => 'boolean',
            'has_canonical' => 'boolean',
            'has_schema_markup' => 'boolean',
            'has_og_tags' => 'boolean',
            'has_twitter_card' => 'boolean',
            'has_robots_txt' => 'boolean',
            'has_sitemap' => 'boolean',
            'is_mobile_friendly' => 'boolean',
            'is_https' => 'boolean',
            'page_load_time' => 'integer',
            'time_to_first_byte' => 'integer',
            'largest_contentful_paint' => 'decimal:2',
            'first_input_delay' => 'decimal:2',
            'cumulative_layout_shift' => 'decimal:3',
            'audited_at' => 'datetime',
            'audit_duration' => 'integer',
        ];
    }

    // Constants
    public const TYPE_FULL = 'full';
    public const TYPE_CONTENT = 'content';
    public const TYPE_TECHNICAL = 'technical';
    public const TYPE_META = 'meta';
    public const TYPE_SCHEMA = 'schema';
    public const TYPE_PERFORMANCE = 'performance';
    public const TYPE_MOBILE = 'mobile';
    public const TYPE_ACCESSIBILITY = 'accessibility';
    public const TYPE_SECURITY = 'security';

    public const SCORE_EXCELLENT = 90;
    public const SCORE_GOOD = 70;
    public const SCORE_NEEDS_IMPROVEMENT = 50;
    public const SCORE_POOR = 30;

    // Relationships

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    // Score evaluation methods

    public function getScoreGrade(): string
    {
        return match (true) {
            $this->overall_score >= self::SCORE_EXCELLENT => 'A',
            $this->overall_score >= self::SCORE_GOOD => 'B',
            $this->overall_score >= self::SCORE_NEEDS_IMPROVEMENT => 'C',
            $this->overall_score >= self::SCORE_POOR => 'D',
            default => 'F',
        };
    }

    public function getScoreLabel(): string
    {
        return match (true) {
            $this->overall_score >= self::SCORE_EXCELLENT => 'Excellent',
            $this->overall_score >= self::SCORE_GOOD => 'Good',
            $this->overall_score >= self::SCORE_NEEDS_IMPROVEMENT => 'Needs Improvement',
            $this->overall_score >= self::SCORE_POOR => 'Poor',
            default => 'Critical',
        };
    }

    public function hasCriticalIssues(): bool
    {
        return ! empty($this->critical_issues);
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    public function getCriticalIssuesCount(): int
    {
        return count($this->critical_issues ?? []);
    }

    public function getWarningsCount(): int
    {
        return count($this->warnings ?? []);
    }

    public function getRecommendationsCount(): int
    {
        return count($this->recommendations ?? []);
    }

    // Core Web Vitals evaluation

    public function hasGoodCoreWebVitals(): bool
    {
        return $this->hasGoodLCP() && $this->hasGoodFID() && $this->hasGoodCLS();
    }

    public function hasGoodLCP(): bool
    {
        return $this->largest_contentful_paint && $this->largest_contentful_paint <= 2.5;
    }

    public function hasGoodFID(): bool
    {
        return $this->first_input_delay && $this->first_input_delay <= 100;
    }

    public function hasGoodCLS(): bool
    {
        return $this->cumulative_layout_shift !== null && $this->cumulative_layout_shift <= 0.1;
    }

    // Content analysis

    public function hasGoodContentLength(): bool
    {
        return $this->word_count && $this->word_count >= 300;
    }

    public function hasExcellentContentLength(): bool
    {
        return $this->word_count && $this->word_count >= 1000;
    }

    public function hasGoodReadability(): bool
    {
        return $this->readability_score && $this->readability_score >= 60;
    }

    // Technical checks summary

    public function getTechnicalChecksPassed(): int
    {
        $checks = [
            $this->has_meta_title,
            $this->has_meta_description,
            $this->has_canonical,
            $this->has_schema_markup,
            $this->has_og_tags,
            $this->has_twitter_card,
            $this->has_robots_txt,
            $this->has_sitemap,
            $this->is_mobile_friendly,
            $this->is_https,
        ];

        return count(array_filter($checks));
    }

    public function getTechnicalChecksFailed(): int
    {
        return 10 - $this->getTechnicalChecksPassed();
    }

    // Summary methods

    public function getSummary(): array
    {
        return [
            'overall_score' => $this->overall_score,
            'grade' => $this->getScoreGrade(),
            'label' => $this->getScoreLabel(),
            'critical_issues' => $this->getCriticalIssuesCount(),
            'warnings' => $this->getWarningsCount(),
            'recommendations' => $this->getRecommendationsCount(),
            'technical_checks_passed' => $this->getTechnicalChecksPassed(),
            'core_web_vitals' => $this->hasGoodCoreWebVitals() ? 'Pass' : 'Fail',
            'audited_at' => $this->audited_at?->toDateTimeString(),
        ];
    }

    // Query scopes

    public function scopeOfType($query, string $type)
    {
        return $query->where('audit_type', $type);
    }

    public function scopeExcellent($query)
    {
        return $query->where('overall_score', '>=', self::SCORE_EXCELLENT);
    }

    public function scopeGood($query)
    {
        return $query->where('overall_score', '>=', self::SCORE_GOOD);
    }

    public function scopeNeedsImprovement($query)
    {
        return $query->where('overall_score', '<', self::SCORE_GOOD);
    }

    public function scopePoor($query)
    {
        return $query->where('overall_score', '<', self::SCORE_NEEDS_IMPROVEMENT);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('audited_at', '>=', now()->subDays($days));
    }

    public function scopeWithCriticalIssues($query)
    {
        return $query->whereNotNull('critical_issues')
            ->whereRaw('JSON_LENGTH(critical_issues) > 0');
    }

    // Static helpers

    public static function getAverageScore(int $storeId): float
    {
        return (float) static::where('store_id', $storeId)->avg('overall_score');
    }

    public static function getLatestForEntity(int $storeId, ?string $entityType = null, ?int $entityId = null): ?self
    {
        $query = static::where('store_id', $storeId);

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query->orderBy('audited_at', 'desc')->first();
    }
}
