<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class SeoKeyword extends Model
{
    use BelongsToStore;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'commerce_seo_keywords';

    protected $fillable = [
        'store_id',
        'entity_type',
        'entity_id',
        'keyword',
        'keyword_type',
        'search_volume',
        'difficulty',
        'cpc',
        'search_intent',
        'current_rank',
        'target_rank',
        'best_rank',
        'worst_rank',
        'rank_change',
        'last_rank_check',
        'rank_history',
        'competitor_ranks',
        'target_url',
        'url_rank',
        'keyword_density',
        'in_title',
        'in_meta_description',
        'in_h1',
        'in_url',
        'in_first_paragraph',
        'optimization_score',
        'is_tracking',
        'country_code',
        'language',
        'search_engine',
        'notes',
        'strategy',
        'target_date',
    ];

    protected function casts(): array
    {
        return [
            'search_volume' => 'integer',
            'difficulty' => 'integer',
            'cpc' => 'decimal:2',
            'current_rank' => 'integer',
            'target_rank' => 'integer',
            'best_rank' => 'integer',
            'worst_rank' => 'integer',
            'rank_change' => 'integer',
            'last_rank_check' => 'datetime',
            'rank_history' => 'array',
            'competitor_ranks' => 'array',
            'url_rank' => 'integer',
            'keyword_density' => 'integer',
            'in_title' => 'boolean',
            'in_meta_description' => 'boolean',
            'in_h1' => 'boolean',
            'in_url' => 'boolean',
            'in_first_paragraph' => 'boolean',
            'optimization_score' => 'integer',
            'is_tracking' => 'boolean',
            'target_date' => 'date',
        ];
    }

    // Constants
    public const TYPE_PRIMARY = 'primary';
    public const TYPE_SECONDARY = 'secondary';
    public const TYPE_LONG_TAIL = 'long-tail';

    public const INTENT_INFORMATIONAL = 'informational';
    public const INTENT_COMMERCIAL = 'commercial';
    public const INTENT_TRANSACTIONAL = 'transactional';
    public const INTENT_NAVIGATIONAL = 'navigational';

    public const DIFFICULTY_EASY = 30;
    public const DIFFICULTY_MEDIUM = 60;
    public const DIFFICULTY_HARD = 80;

    // Relationships

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    // Ranking methods

    public function updateRank(int $newRank): void
    {
        $oldRank = $this->current_rank;
        $change = $oldRank ? ($oldRank - $newRank) : 0;

        $this->update([
            'current_rank' => $newRank,
            'rank_change' => $change,
            'best_rank' => $this->best_rank ? min($this->best_rank, $newRank) : $newRank,
            'worst_rank' => $this->worst_rank ? max($this->worst_rank, $newRank) : $newRank,
            'last_rank_check' => now(),
        ]);

        $this->addToRankHistory($newRank);
    }

    protected function addToRankHistory(int $rank): void
    {
        $history = $this->rank_history ?? [];

        $history[] = [
            'date' => now()->toDateString(),
            'rank' => $rank,
            'url' => $this->target_url,
        ];

        // Keep only last 365 days
        if (count($history) > 365) {
            $history = array_slice($history, -365);
        }

        $this->update(['rank_history' => $history]);
    }

    public function isImproving(): bool
    {
        return $this->rank_change > 0;
    }

    public function isDeclining(): bool
    {
        return $this->rank_change < 0;
    }

    public function isOnFirstPage(): bool
    {
        return $this->current_rank && $this->current_rank <= 10;
    }

    public function hasReachedTarget(): bool
    {
        return $this->current_rank && $this->current_rank <= $this->target_rank;
    }

    // Difficulty assessment

    public function isEasy(): bool
    {
        return $this->difficulty <= self::DIFFICULTY_EASY;
    }

    public function isMedium(): bool
    {
        return $this->difficulty > self::DIFFICULTY_EASY && $this->difficulty <= self::DIFFICULTY_MEDIUM;
    }

    public function isHard(): bool
    {
        return $this->difficulty > self::DIFFICULTY_MEDIUM && $this->difficulty <= self::DIFFICULTY_HARD;
    }

    public function isVeryHard(): bool
    {
        return $this->difficulty > self::DIFFICULTY_HARD;
    }

    // Optimization methods

    public function calculateOptimizationScore(): int
    {
        $score = 0;

        // On-page factors (100 points total)
        if ($this->in_title) {
            $score += 25;
        }
        if ($this->in_meta_description) {
            $score += 15;
        }
        if ($this->in_h1) {
            $score += 20;
        }
        if ($this->in_url) {
            $score += 15;
        }
        if ($this->in_first_paragraph) {
            $score += 15;
        }

        // Keyword density (10 points)
        if ($this->keyword_density > 0 && $this->keyword_density <= 300) { // 0-3%
            $score += 10;
        } elseif ($this->keyword_density > 300) {
            $score += 5; // Penalize over-optimization
        }

        return min($score, 100);
    }

    public function updateOptimizationScore(): void
    {
        $score = $this->calculateOptimizationScore();
        $this->update(['optimization_score' => $score]);
    }

    public function isWellOptimized(): bool
    {
        return $this->optimization_score >= 70;
    }

    // Tracking methods

    public function startTracking(): void
    {
        $this->update(['is_tracking' => true]);
    }

    public function stopTracking(): void
    {
        $this->update(['is_tracking' => false]);
    }

    // Query scopes

    public function scopeTracking($query)
    {
        return $query->where('is_tracking', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('keyword_type', self::TYPE_PRIMARY);
    }

    public function scopeLongTail($query)
    {
        return $query->where('keyword_type', self::TYPE_LONG_TAIL);
    }

    public function scopeHighVolume($query, int $minVolume = 1000)
    {
        return $query->where('search_volume', '>=', $minVolume);
    }

    public function scopeEasy($query)
    {
        return $query->where('difficulty', '<=', self::DIFFICULTY_EASY);
    }

    public function scopeMedium($query)
    {
        return $query->whereBetween('difficulty', [self::DIFFICULTY_EASY + 1, self::DIFFICULTY_MEDIUM]);
    }

    public function scopeHard($query)
    {
        return $query->where('difficulty', '>', self::DIFFICULTY_MEDIUM);
    }

    public function scopeRanking($query)
    {
        return $query->whereNotNull('current_rank');
    }

    public function scopeFirstPage($query)
    {
        return $query->where('current_rank', '<=', 10);
    }

    public function scopeImproving($query)
    {
        return $query->where('rank_change', '>', 0);
    }

    public function scopeDeclining($query)
    {
        return $query->where('rank_change', '<', 0);
    }

    public function scopeWellOptimized($query, int $minScore = 70)
    {
        return $query->where('optimization_score', '>=', $minScore);
    }

    public function scopeNeedsOptimization($query, int $maxScore = 50)
    {
        return $query->where('optimization_score', '<=', $maxScore);
    }

    // Static helpers

    public static function getTopPerformers(int $storeId, int $limit = 10): \Illuminate\Support\Collection
    {
        return static::where('store_id', $storeId)
            ->whereNotNull('current_rank')
            ->where('is_tracking', true)
            ->orderBy('current_rank', 'asc')
            ->limit($limit)
            ->get();
    }

    public static function getMostImproved(int $storeId, int $limit = 10): \Illuminate\Support\Collection
    {
        return static::where('store_id', $storeId)
            ->where('rank_change', '>', 0)
            ->orderBy('rank_change', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getMostDeclining(int $storeId, int $limit = 10): \Illuminate\Support\Collection
    {
        return static::where('store_id', $storeId)
            ->where('rank_change', '<', 0)
            ->orderBy('rank_change', 'asc')
            ->limit($limit)
            ->get();
    }

    public static function getLowHangingFruit(int $storeId, int $limit = 10): \Illuminate\Support\Collection
    {
        // Keywords on page 2-3 that are easy to medium difficulty
        return static::where('store_id', $storeId)
            ->whereBetween('current_rank', [11, 30])
            ->where('difficulty', '<=', self::DIFFICULTY_MEDIUM)
            ->where('is_tracking', true)
            ->orderBy('current_rank', 'asc')
            ->orderBy('difficulty', 'asc')
            ->limit($limit)
            ->get();
    }
}
