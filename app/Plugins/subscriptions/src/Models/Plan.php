<?php

namespace Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan Model
 */
class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'interval',
        'interval_count',
        'trial_days',
        'features',
        'limits',
        'is_active',
        'is_featured',
        'is_popular',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'interval_count' => 'integer',
        'features' => 'array',
        'limits' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'interval' => 'monthly',
        'interval_count' => 1,
        'trial_days' => 0,
        'is_active' => true,
        'is_featured' => false,
        'is_popular' => false,
        'sort_order' => 0,
    ];

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        $symbol = config('subscriptions.currency_symbol', '$');
        return $symbol . number_format($this->price, 2);
    }

    public function getIntervalLabelAttribute(): string
    {
        $intervals = config('subscriptions.intervals', []);
        return $intervals[$this->interval]['label'] ?? ucfirst($this->interval);
    }

    // Methods
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getLimit(string $key, $default = null)
    {
        return $this->limits[$key] ?? $default;
    }

    public function getSubscriberCount(): int
    {
        return $this->activeSubscriptions()->count();
    }
}

