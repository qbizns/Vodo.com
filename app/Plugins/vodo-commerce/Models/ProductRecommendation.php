<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

class ProductRecommendation extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_product_recommendations';

    protected $fillable = [
        'store_id',
        'source_product_id',
        'recommended_product_id',
        'type',
        'source',
        'relevance_score',
        'sort_order',
        'is_active',
        'impression_count',
        'click_count',
        'conversion_count',
        'conversion_rate',
        'display_context',
        'custom_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'relevance_score' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'impression_count' => 'integer',
            'click_count' => 'integer',
            'conversion_count' => 'integer',
            'conversion_rate' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    /**
     * Get the source product (the product being viewed).
     */
    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    /**
     * Get the recommended product.
     */
    public function recommendedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'recommended_product_id');
    }

    /**
     * Record an impression (recommendation was shown).
     */
    public function recordImpression(): void
    {
        $this->increment('impression_count');
        $this->updateConversionRate();
    }

    /**
     * Record a click (user clicked on recommendation).
     */
    public function recordClick(): void
    {
        $this->increment('click_count');
    }

    /**
     * Record a conversion (user purchased recommended product).
     */
    public function recordConversion(): void
    {
        $this->increment('conversion_count');
        $this->updateConversionRate();
    }

    /**
     * Update the conversion rate.
     */
    protected function updateConversionRate(): void
    {
        if ($this->impression_count > 0) {
            $rate = ($this->conversion_count / $this->impression_count) * 100;
            $this->update(['conversion_rate' => round($rate, 2)]);
        }
    }

    /**
     * Get click-through rate (CTR).
     */
    public function getClickThroughRate(): float
    {
        if ($this->impression_count == 0) {
            return 0;
        }

        return round(($this->click_count / $this->impression_count) * 100, 2);
    }

    /**
     * Get the relevance score as a percentage.
     */
    public function getRelevancePercentage(): float
    {
        return (float) $this->relevance_score;
    }

    /**
     * Check if this recommendation is performing well.
     */
    public function isPerforming(float $threshold = 2.0): bool
    {
        return $this->conversion_rate >= $threshold;
    }

    /**
     * Scope: Active recommendations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By recommendation type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Upsell recommendations.
     */
    public function scopeUpsell($query)
    {
        return $query->where('type', 'upsell');
    }

    /**
     * Scope: Cross-sell recommendations.
     */
    public function scopeCrossSell($query)
    {
        return $query->where('type', 'cross_sell');
    }

    /**
     * Scope: Frequently bought together.
     */
    public function scopeFrequentlyBought($query)
    {
        return $query->where('type', 'frequently_bought');
    }

    /**
     * Scope: By source (manual, ai, behavioral, etc.).
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: AI-powered recommendations.
     */
    public function scopeAiPowered($query)
    {
        return $query->where('source', 'ai');
    }

    /**
     * Scope: High relevance (score >= threshold).
     */
    public function scopeHighRelevance($query, float $threshold = 70.0)
    {
        return $query->where('relevance_score', '>=', $threshold);
    }

    /**
     * Scope: High performing (conversion rate >= threshold).
     */
    public function scopeHighPerforming($query, float $threshold = 2.0)
    {
        return $query->where('conversion_rate', '>=', $threshold);
    }

    /**
     * Scope: Ordered by relevance and performance.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('relevance_score', 'desc')
            ->orderBy('conversion_rate', 'desc')
            ->orderBy('sort_order', 'asc');
    }
}
