<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

class ProductBadge extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_product_badges';

    protected $fillable = [
        'store_id',
        'product_id',
        'label',
        'slug',
        'type',
        'color',
        'background_color',
        'icon',
        'position',
        'is_active',
        'priority',
        'start_date',
        'end_date',
        'auto_apply',
        'conditions',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'auto_apply' => 'boolean',
            'conditions' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * Get the product this badge belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if badge should be displayed.
     */
    public function shouldDisplay(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        if ($this->start_date && now()->isBefore($this->start_date)) {
            return false;
        }

        if ($this->end_date && now()->isAfter($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if badge is currently active based on date range.
     */
    public function isCurrentlyActive(): bool
    {
        return $this->shouldDisplay();
    }

    /**
     * Check if badge has expired.
     */
    public function hasExpired(): bool
    {
        return $this->end_date && now()->isAfter($this->end_date);
    }

    /**
     * Check if badge will start in the future.
     */
    public function isScheduled(): bool
    {
        return $this->start_date && now()->isBefore($this->start_date);
    }

    /**
     * Get CSS styles for badge display.
     */
    public function getCssStyles(): string
    {
        return "color: {$this->color}; background-color: {$this->background_color};";
    }

    /**
     * Evaluate auto-apply conditions against product.
     */
    public function evaluateConditions(Product $product): bool
    {
        if (!$this->auto_apply || empty($this->conditions)) {
            return false;
        }

        foreach ($this->conditions as $field => $condition) {
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            $productValue = $product->$field ?? null;

            $matches = match ($operator) {
                '=' => $productValue == $value,
                '!=' => $productValue != $value,
                '>' => $productValue > $value,
                '>=' => $productValue >= $value,
                '<' => $productValue < $value,
                '<=' => $productValue <= $value,
                'contains' => str_contains((string) $productValue, (string) $value),
                default => false,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Scope: Active badges.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Currently displayable badges.
     */
    public function scopeDisplayable($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            });
    }

    /**
     * Scope: By badge type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: By product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Expired badges.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }

    /**
     * Scope: Scheduled badges.
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('start_date')
            ->where('start_date', '>', now());
    }

    /**
     * Scope: Auto-apply badges.
     */
    public function scopeAutoApply($query)
    {
        return $query->where('auto_apply', true);
    }

    /**
     * Scope: Ordered by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
