<?php

declare(strict_types=1);

namespace App\Models\Marketplace;

use App\Enums\MarketplaceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace Listing Model
 *
 * Represents a plugin listing in the marketplace.
 */
class MarketplaceListing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plugin_slug',
        'name',
        'tagline',
        'description',
        'features',
        'category',
        'tags',
        'icon_url',
        'banner_url',
        'screenshots',
        'video_url',
        'publisher_id',
        'publisher_name',
        'publisher_url',
        'support_email',
        'support_url',
        'documentation_url',
        'current_version',
        'current_version_id',
        'min_platform_version',
        'max_platform_version',
        'pricing_model',
        'price',
        'price_currency',
        'trial_days',
        'status',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'tags' => 'array',
            'screenshots' => 'array',
            'meta_keywords' => 'array',
            'status' => MarketplaceStatus::class,
            'price' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'published_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function versions(): HasMany
    {
        return $this->hasMany(MarketplaceVersion::class, 'listing_id');
    }

    public function currentVersionRelation(): BelongsTo
    {
        return $this->belongsTo(MarketplaceVersion::class, 'current_version_id');
    }

    public function installations(): HasMany
    {
        return $this->hasMany(MarketplaceInstallation::class, 'listing_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'listing_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(MarketplaceSubmission::class, 'listing_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MarketplaceSubscription::class, 'listing_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category', 'slug');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', MarketplaceStatus::Published);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('pricing_model', 'free');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereIn('pricing_model', ['one_time', 'subscription']);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('install_count');
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->orderByDesc('average_rating')
            ->where('rating_count', '>=', 5);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('tagline', 'like', "%{$term}%")
                ->orWhereJsonContains('tags', $term);
        });
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getFormattedPriceAttribute(): string
    {
        if ($this->pricing_model === 'free') {
            return 'Free';
        }

        return number_format($this->price, 2) . ' ' . $this->price_currency;
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === MarketplaceStatus::Published;
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->pricing_model === 'free';
    }

    public function getHasTrialAttribute(): bool
    {
        return $this->trial_days > 0;
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function publish(): void
    {
        $this->update([
            'status' => MarketplaceStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function suspend(string $reason): void
    {
        $this->update([
            'status' => MarketplaceStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    public function deprecate(): void
    {
        $this->update([
            'status' => MarketplaceStatus::Deprecated,
        ]);
    }

    public function incrementInstallCount(): void
    {
        $this->increment('install_count');
        $this->increment('active_install_count');
    }

    public function decrementActiveInstallCount(): void
    {
        $this->decrement('active_install_count');
    }

    public function recalculateRating(): void
    {
        $stats = $this->reviews()
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as average, COUNT(*) as count')
            ->first();

        $this->update([
            'average_rating' => $stats->average ?? 0,
            'rating_count' => $stats->count ?? 0,
        ]);
    }

    public function getLatestVersion(?string $channel = 'stable'): ?MarketplaceVersion
    {
        return $this->versions()
            ->where('status', 'published')
            ->where('channel', $channel)
            ->orderByDesc('published_at')
            ->first();
    }

    public function isCompatibleWith(string $platformVersion): bool
    {
        if ($this->min_platform_version && version_compare($platformVersion, $this->min_platform_version, '<')) {
            return false;
        }

        if ($this->max_platform_version && version_compare($platformVersion, $this->max_platform_version, '>')) {
            return false;
        }

        return true;
    }
}
