<?php

declare(strict_types=1);

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace Version Model
 *
 * Represents a specific version of a plugin.
 */
class MarketplaceVersion extends Model
{
    protected $fillable = [
        'listing_id',
        'version',
        'changelog',
        'release_notes',
        'package_url',
        'package_hash',
        'package_size',
        'package_signature',
        'min_php_version',
        'min_platform_version',
        'dependencies',
        'required_scopes',
        'optional_scopes',
        'status',
        'channel',
        'is_current',
        'published_at',
        'yanked_at',
        'yank_reason',
    ];

    protected function casts(): array
    {
        return [
            'dependencies' => 'array',
            'required_scopes' => 'array',
            'optional_scopes' => 'array',
            'is_current' => 'boolean',
            'published_at' => 'datetime',
            'yanked_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeStable(Builder $query): Builder
    {
        return $query->where('channel', 'stable');
    }

    public function scopeBeta(Builder $query): Builder
    {
        return $query->where('channel', 'beta');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeNotYanked(Builder $query): Builder
    {
        return $query->where('status', '!=', 'yanked');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->package_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    public function getIsYankedAttribute(): bool
    {
        return $this->status === 'yanked';
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function publish(): void
    {
        // Unset current flag from other versions
        $this->listing->versions()
            ->where('id', '!=', $this->id)
            ->where('channel', $this->channel)
            ->update(['is_current' => false]);

        $this->update([
            'status' => 'published',
            'is_current' => true,
            'published_at' => now(),
        ]);

        // Update listing's current version
        if ($this->channel === 'stable') {
            $this->listing->update([
                'current_version' => $this->version,
                'current_version_id' => $this->id,
            ]);
        }
    }

    public function yank(string $reason): void
    {
        $this->update([
            'status' => 'yanked',
            'is_current' => false,
            'yanked_at' => now(),
            'yank_reason' => $reason,
        ]);

        // If this was the current version, set previous as current
        if ($this->listing->current_version_id === $this->id) {
            $previousVersion = $this->listing->versions()
                ->where('id', '!=', $this->id)
                ->where('status', 'published')
                ->where('channel', 'stable')
                ->orderByDesc('published_at')
                ->first();

            if ($previousVersion) {
                $previousVersion->update(['is_current' => true]);
                $this->listing->update([
                    'current_version' => $previousVersion->version,
                    'current_version_id' => $previousVersion->id,
                ]);
            }
        }
    }

    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }

    public function isNewerThan(string $version): bool
    {
        return version_compare($this->version, $version, '>');
    }

    public function isCompatibleWithPhp(string $phpVersion): bool
    {
        return version_compare($phpVersion, $this->min_php_version, '>=');
    }

    public function isCompatibleWithPlatform(string $platformVersion): bool
    {
        if (!$this->min_platform_version) {
            return true;
        }

        return version_compare($platformVersion, $this->min_platform_version, '>=');
    }

    public function verifyHash(string $fileContent): bool
    {
        return hash('sha256', $fileContent) === $this->package_hash;
    }

    public function getAllScopes(): array
    {
        return array_merge(
            $this->required_scopes ?? [],
            $this->optional_scopes ?? []
        );
    }
}
