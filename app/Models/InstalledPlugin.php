<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstalledPlugin extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'version', 'author', 'author_url',
        'homepage', 'marketplace_id', 'marketplace_url', 'price', 'currency',
        'install_path', 'entry_class', 'dependencies', 'requirements',
        'status', 'is_premium', 'is_verified', 'installed_at', 'activated_at',
        'last_update_check', 'meta',
    ];

    protected $casts = [
        'dependencies' => 'array',
        'requirements' => 'array',
        'is_premium' => 'boolean',
        'is_verified' => 'boolean',
        'price' => 'decimal:2',
        'installed_at' => 'datetime',
        'activated_at' => 'datetime',
        'last_update_check' => 'datetime',
        'meta' => 'array',
    ];

    // Status constants
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ERROR = 'error';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function license(): HasOne
    {
        return $this->hasOne(PluginLicense::class, 'plugin_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(PluginUpdate::class, 'plugin_id');
    }

    public function pendingUpdate(): HasOne
    {
        return $this->hasOne(PluginUpdate::class, 'plugin_id')
            ->whereNull('installed_at')
            ->latest('released_at');
    }

    public function updateHistory(): HasMany
    {
        return $this->hasMany(PluginUpdateHistory::class, 'plugin_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_premium', false);
    }

    public function scopeHasUpdate(Builder $query): Builder
    {
        return $query->whereHas('pendingUpdate');
    }

    public function scopeFromMarketplace(Builder $query): Builder
    {
        return $query->whereNotNull('marketplace_id');
    }

    // =========================================================================
    // Status Methods
    // =========================================================================

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    public function activate(): bool
    {
        if ($this->is_premium && !$this->hasValidLicense()) {
            return false;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->activated_at = now();
        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->status = self::STATUS_INACTIVE;
        return $this->save();
    }

    public function markError(string $message = null): bool
    {
        $this->status = self::STATUS_ERROR;
        if ($message) {
            $meta = $this->meta ?? [];
            $meta['last_error'] = $message;
            $meta['error_at'] = now()->toIso8601String();
            $this->meta = $meta;
        }
        return $this->save();
    }

    // =========================================================================
    // License Methods
    // =========================================================================

    public function hasLicense(): bool
    {
        return $this->license !== null;
    }

    public function hasValidLicense(): bool
    {
        return $this->license && $this->license->isValid();
    }

    public function requiresLicense(): bool
    {
        return $this->is_premium;
    }

    public function getLicenseStatus(): string
    {
        if (!$this->is_premium) {
            return 'not_required';
        }
        if (!$this->license) {
            return 'missing';
        }
        return $this->license->status;
    }

    // =========================================================================
    // Update Methods
    // =========================================================================

    public function hasUpdate(): bool
    {
        return $this->pendingUpdate()->exists();
    }

    public function getLatestUpdate(): ?PluginUpdate
    {
        return $this->pendingUpdate;
    }

    public function markUpdateChecked(): void
    {
        $this->last_update_check = now();
        $this->save();
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function findByMarketplaceId(string $marketplaceId): ?self
    {
        return static::where('marketplace_id', $marketplaceId)->first();
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ERROR => 'Error',
        ];
    }

    // =========================================================================
    // Instance Loading
    // =========================================================================

    public function getInstance(): ?object
    {
        if (!class_exists($this->entry_class)) {
            return null;
        }
        return app($this->entry_class);
    }

    public function getPath(): string
    {
        return $this->install_path;
    }

    // =========================================================================
    // Export
    // =========================================================================

    public function toManifest(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'author' => $this->author,
            'author_url' => $this->author_url,
            'homepage' => $this->homepage,
            'entry_class' => $this->entry_class,
            'dependencies' => $this->dependencies,
            'requirements' => $this->requirements,
            'is_premium' => $this->is_premium,
        ];
    }
}
