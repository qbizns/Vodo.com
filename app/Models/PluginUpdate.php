<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginUpdate extends Model
{
    protected $fillable = [
        'plugin_id', 'current_version', 'latest_version', 'changelog',
        'download_url', 'package_size', 'requires_system_version', 
        'requires_php_version', 'is_security_update', 'is_breaking_change',
        'release_date', 'checked_at', 'notified_at',
    ];

    protected $casts = [
        'is_security_update' => 'boolean',
        'is_breaking_change' => 'boolean',
        'release_date' => 'date',
        'checked_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class, 'plugin_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeSecurity(Builder $query): Builder
    {
        return $query->where('is_security_update', true);
    }

    public function scopeBreaking(Builder $query): Builder
    {
        return $query->where('is_breaking_change', true);
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function meetsRequirements(): array
    {
        $issues = [];

        if ($this->requires_php_version && version_compare(PHP_VERSION, $this->requires_php_version, '<')) {
            $issues[] = "Requires PHP {$this->requires_php_version}, current: " . PHP_VERSION;
        }

        if ($this->requires_system_version) {
            $systemVersion = config('app.version', '1.0.0');
            if (version_compare($systemVersion, $this->requires_system_version, '<')) {
                $issues[] = "Requires system version {$this->requires_system_version}, current: {$systemVersion}";
            }
        }

        return $issues;
    }

    public function canUpdate(): bool
    {
        return empty($this->meetsRequirements());
    }

    public function canInstall(): bool
    {
        return $this->canUpdate();
    }

    public function getPackageSizeForHumans(): string
    {
        if (!$this->package_size) {
            return 'Unknown';
        }

        $bytes = $this->package_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}

// =========================================================================
// PluginUpdateHistory Model
// =========================================================================

class PluginUpdateHistory extends Model
{
    protected $table = 'plugin_update_history';

    protected $fillable = [
        'plugin_id', 'from_version', 'to_version', 'status', 'log',
        'error', 'backup_path', 'started_at', 'completed_at',
        'duration_seconds', 'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class, 'plugin_id');
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function wasRolledBack(): bool
    {
        return $this->status === self::STATUS_ROLLED_BACK;
    }

    public static function recordStart(Plugin $plugin, string $fromVersion, string $toVersion): self
    {
        return static::create([
            'plugin_id' => $plugin->id,
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function recordSuccess(string $log = null): bool
    {
        $this->status = self::STATUS_SUCCESS;
        $this->completed_at = now();
        $this->log = $log;
        $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        return $this->save();
    }

    public function recordFailure(string $error, string $log = null): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->completed_at = now();
        $this->error = $error;
        $this->log = $log;
        $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        return $this->save();
    }

    public function recordRollback(string $log = null): bool
    {
        $this->status = self::STATUS_ROLLED_BACK;
        $this->completed_at = now();
        $this->log = $log;
        $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        return $this->save();
    }
}

// =========================================================================
// MarketplacePlugin Model (Cache for browsing)
// =========================================================================

class MarketplacePlugin extends Model
{
    protected $fillable = [
        'marketplace_id', 'slug', 'name', 'short_description', 'description',
        'author', 'author_url', 'is_verified_author', 'latest_version',
        'requires_php', 'requires_laravel', 'price', 'currency', 'is_free',
        'pricing_tiers', 'downloads', 'active_installs', 'rating', 'rating_count',
        'categories', 'tags', 'icon_url', 'screenshots', 'is_featured',
        'is_verified', 'last_updated', 'synced_at', 'meta',
    ];

    protected $casts = [
        'is_verified_author' => 'boolean',
        'is_free' => 'boolean',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'pricing_tiers' => 'array',
        'categories' => 'array',
        'tags' => 'array',
        'screenshots' => 'array',
        'last_updated' => 'datetime',
        'synced_at' => 'datetime',
        'meta' => 'array',
    ];

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_free', true);
    }

    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_free', false);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->whereJsonContains('categories', $category);
    }

    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%")
              ->orWhere('short_description', 'like', "%{$term}%")
              ->orWhere('author', 'like', "%{$term}%");
        });
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('downloads');
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->orderByDesc('rating')->where('rating_count', '>=', 5);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('last_updated');
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function isInstalled(): bool
    {
        return Plugin::where('marketplace_id', $this->marketplace_id)->exists();
    }

    public function getPlugin(): ?Plugin
    {
        return Plugin::where('marketplace_id', $this->marketplace_id)->first();
    }

    public function getPriceFormatted(): string
    {
        if ($this->is_free) {
            return 'Free';
        }

        return '$' . number_format($this->price, 2);
    }

    public function getRatingStars(): string
    {
        $full = floor($this->rating);
        $half = ($this->rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;

        return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
    }

    public static function findByMarketplaceId(string $id): ?self
    {
        return static::where('marketplace_id', $id)->first();
    }
}
