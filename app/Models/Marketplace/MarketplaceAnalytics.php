<?php

declare(strict_types=1);

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace Analytics Model
 *
 * Tracks marketplace events (views, installs, etc.)
 */
class MarketplaceAnalytics extends Model
{
    public $timestamps = false;

    protected $table = 'marketplace_analytics';

    protected $fillable = [
        'listing_id',
        'tenant_id',
        'event',
        'metadata',
        'ip_address',
        'user_agent',
        'referrer',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function scopeByEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeByListing(Builder $query, int $listingId): Builder
    {
        return $query->where('listing_id', $listingId);
    }

    public function scopeInPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->startOfWeek());
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    public static function record(
        int $listingId,
        string $event,
        ?int $tenantId = null,
        array $metadata = []
    ): self {
        return static::create([
            'listing_id' => $listingId,
            'tenant_id' => $tenantId,
            'event' => $event,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'created_at' => now(),
        ]);
    }

    public static function getEventCounts(int $listingId, string $period = 'month'): array
    {
        $query = static::byListing($listingId);

        match ($period) {
            'day' => $query->today(),
            'week' => $query->thisWeek(),
            'month' => $query->thisMonth(),
            default => null,
        };

        return $query
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();
    }
}
