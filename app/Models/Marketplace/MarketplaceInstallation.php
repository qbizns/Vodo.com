<?php

declare(strict_types=1);

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace Installation Model
 *
 * Tracks plugin installations per tenant.
 */
class MarketplaceInstallation extends Model
{
    protected $fillable = [
        'listing_id',
        'tenant_id',
        'version_id',
        'installed_version',
        'status',
        'installed_at',
        'activated_at',
        'deactivated_at',
        'uninstalled_at',
        'auto_update',
        'update_channel',
        'subscription_id',
        'license_expires_at',
        'is_trial',
        'trial_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'uninstalled_at' => 'datetime',
            'license_expires_at' => 'datetime',
            'trial_expires_at' => 'datetime',
            'auto_update' => 'boolean',
            'is_trial' => 'boolean',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MarketplaceVersion::class, 'version_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSubscription::class, 'subscription_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeNeedsUpdate(Builder $query): Builder
    {
        return $query->where('auto_update', true)
            ->where('status', 'active')
            ->whereHas('listing', function ($q) {
                $q->whereColumn('marketplace_listings.current_version', '!=', 'marketplace_installations.installed_version');
            });
    }

    public function scopeTrialExpiring(Builder $query, int $days = 3): Builder
    {
        return $query->where('is_trial', true)
            ->where('trial_expires_at', '<=', now()->addDays($days))
            ->where('trial_expires_at', '>', now());
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getHasValidLicenseAttribute(): bool
    {
        // Free plugins always have valid license
        if ($this->listing->is_free) {
            return true;
        }

        // Check trial
        if ($this->is_trial) {
            return $this->trial_expires_at && $this->trial_expires_at->isFuture();
        }

        // Check subscription
        if ($this->license_expires_at) {
            return $this->license_expires_at->isFuture();
        }

        return $this->subscription?->isActive() ?? false;
    }

    public function getHasUpdateAvailableAttribute(): bool
    {
        $latestVersion = $this->listing->getLatestVersion($this->update_channel);

        if (!$latestVersion) {
            return false;
        }

        return version_compare($latestVersion->version, $this->installed_version, '>');
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
            'deactivated_at' => null,
        ]);

        $this->listing->incrementInstallCount();
    }

    public function deactivate(): void
    {
        $this->update([
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);

        $this->listing->decrementActiveInstallCount();
    }

    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
            'deactivated_at' => now(),
        ]);

        $this->listing->decrementActiveInstallCount();
    }

    public function uninstall(): void
    {
        $wasActive = $this->status === 'active';

        $this->update([
            'status' => 'uninstalled',
            'uninstalled_at' => now(),
        ]);

        if ($wasActive) {
            $this->listing->decrementActiveInstallCount();
        }
    }

    public function updateToVersion(MarketplaceVersion $version): void
    {
        $this->update([
            'version_id' => $version->id,
            'installed_version' => $version->version,
        ]);

        $version->incrementDownloads();
    }

    public function startTrial(int $days): void
    {
        $this->update([
            'is_trial' => true,
            'trial_expires_at' => now()->addDays($days),
        ]);
    }

    public function endTrial(): void
    {
        $this->update([
            'is_trial' => false,
            'trial_expires_at' => null,
        ]);
    }

    public function getDaysUntilTrialExpires(): ?int
    {
        if (!$this->is_trial || !$this->trial_expires_at) {
            return null;
        }

        return (int) now()->diffInDays($this->trial_expires_at, false);
    }

    public function recordAnalytics(string $event, array $metadata = []): void
    {
        MarketplaceAnalytics::create([
            'listing_id' => $this->listing_id,
            'tenant_id' => $this->tenant_id,
            'event' => $event,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
