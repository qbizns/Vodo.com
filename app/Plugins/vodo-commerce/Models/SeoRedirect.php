<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class SeoRedirect extends Model
{
    use BelongsToStore;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'commerce_seo_redirects';

    protected $fillable = [
        'store_id',
        'from_url',
        'to_url',
        'is_regex',
        'redirect_type',
        'is_active',
        'hit_count',
        'last_accessed_at',
        'reason',
        'created_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_regex' => 'boolean',
            'is_active' => 'boolean',
            'hit_count' => 'integer',
            'last_accessed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // Constants for redirect types
    public const TYPE_301 = '301'; // Permanent redirect (SEO juice transferred)
    public const TYPE_302 = '302'; // Temporary redirect
    public const TYPE_307 = '307'; // Temporary redirect (maintains HTTP method)
    public const TYPE_308 = '308'; // Permanent redirect (maintains HTTP method)

    // Status check methods

    public function isPermanent(): bool
    {
        return in_array($this->redirect_type, [self::TYPE_301, self::TYPE_308]);
    }

    public function isTemporary(): bool
    {
        return in_array($this->redirect_type, [self::TYPE_302, self::TYPE_307]);
    }

    // Activation methods

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    // Hit tracking

    public function recordHit(): void
    {
        $this->increment('hit_count');
        $this->update(['last_accessed_at' => now()]);
    }

    // URL matching

    public function matches(string $url): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Normalize URLs for comparison
        $normalizedUrl = $this->normalizeUrl($url);
        $normalizedFromUrl = $this->normalizeUrl($this->from_url);

        if ($this->is_regex) {
            return (bool) preg_match($this->from_url, $normalizedUrl);
        }

        return $normalizedUrl === $normalizedFromUrl;
    }

    protected function normalizeUrl(string $url): string
    {
        // Remove protocol
        $url = preg_replace('#^https?://#', '', $url);

        // Remove trailing slash
        $url = rtrim($url, '/');

        // Convert to lowercase for case-insensitive matching
        return strtolower($url);
    }

    public function getRedirectUrl(string $requestUrl): string
    {
        if ($this->is_regex) {
            return (string) preg_replace($this->from_url, $this->to_url, $requestUrl);
        }

        return $this->to_url;
    }

    // Query scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePermanent($query)
    {
        return $query->whereIn('redirect_type', [self::TYPE_301, self::TYPE_308]);
    }

    public function scopeTemporary($query)
    {
        return $query->whereIn('redirect_type', [self::TYPE_302, self::TYPE_307]);
    }

    public function scopePopular($query, int $minHits = 100)
    {
        return $query->where('hit_count', '>=', $minHits)
            ->orderBy('hit_count', 'desc');
    }

    public function scopeUnused($query, int $maxHits = 0)
    {
        return $query->where('hit_count', '<=', $maxHits);
    }

    public function scopeRecentlyAccessed($query)
    {
        return $query->whereNotNull('last_accessed_at')
            ->orderBy('last_accessed_at', 'desc');
    }

    // Static helpers

    public static function findMatchingRedirect(int $storeId, string $url): ?self
    {
        // Try exact matches first (faster)
        $exactMatch = static::where('store_id', $storeId)
            ->where('is_active', true)
            ->where('is_regex', false)
            ->get()
            ->first(fn($redirect) => $redirect->matches($url));

        if ($exactMatch) {
            return $exactMatch;
        }

        // Try regex matches
        return static::where('store_id', $storeId)
            ->where('is_active', true)
            ->where('is_regex', true)
            ->get()
            ->first(fn($redirect) => $redirect->matches($url));
    }

    public static function createFromOldUrl(int $storeId, string $oldUrl, string $newUrl, string $reason = null): self
    {
        return static::create([
            'store_id' => $storeId,
            'from_url' => $oldUrl,
            'to_url' => $newUrl,
            'redirect_type' => self::TYPE_301,
            'is_active' => true,
            'reason' => $reason ?? 'URL changed',
        ]);
    }

    // Analytics

    public function getHitRate(): float
    {
        if (! $this->last_accessed_at) {
            return 0.0;
        }

        $daysSinceCreation = $this->created_at->diffInDays(now());
        if ($daysSinceCreation === 0) {
            return (float) $this->hit_count;
        }

        return round($this->hit_count / $daysSinceCreation, 2);
    }

    public static function getMostPopular(int $storeId, int $limit = 10): \Illuminate\Support\Collection
    {
        return static::where('store_id', $storeId)
            ->where('is_active', true)
            ->orderBy('hit_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getUnusedRedirects(int $storeId, int $daysOld = 90): \Illuminate\Support\Collection
    {
        return static::where('store_id', $storeId)
            ->where('hit_count', 0)
            ->where('created_at', '<', now()->subDays($daysOld))
            ->get();
    }
}
