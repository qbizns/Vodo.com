<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceVersion;
use App\Models\Marketplace\MarketplaceInstallation;
use App\Models\Marketplace\MarketplaceSubscription;
use App\Models\Marketplace\MarketplaceAnalytics;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Installation Manager
 *
 * Manages plugin installations, activations, and updates.
 */
class InstallationManager
{
    public function __construct(
        protected VersionManager $versionManager
    ) {}

    /**
     * Install a plugin for a tenant.
     */
    public function install(
        MarketplaceListing $listing,
        int $tenantId,
        ?string $channel = 'stable'
    ): MarketplaceInstallation {
        return DB::transaction(function () use ($listing, $tenantId, $channel) {
            // Check if already installed
            $existing = MarketplaceInstallation::where('listing_id', $listing->id)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($existing && $existing->status !== 'uninstalled') {
                throw new \InvalidArgumentException('Plugin is already installed');
            }

            // Get latest version
            $version = $this->versionManager->getLatestVersion($listing, $channel);

            if (!$version) {
                throw new \InvalidArgumentException('No version available for this channel');
            }

            // Check compatibility
            $phpVersion = PHP_VERSION;
            $platformVersion = config('app.version', '1.0.0');
            $compatibility = $this->versionManager->isCompatible($version, $phpVersion, $platformVersion);

            if (!$compatibility['compatible']) {
                throw new \InvalidArgumentException(
                    'Plugin is not compatible: ' . implode(', ', $compatibility['issues'])
                );
            }

            // Create or reactivate installation
            if ($existing) {
                $installation = $existing;
                $installation->update([
                    'version_id' => $version->id,
                    'installed_version' => $version->version,
                    'status' => 'active',
                    'installed_at' => now(),
                    'activated_at' => now(),
                    'uninstalled_at' => null,
                    'update_channel' => $channel,
                ]);
            } else {
                $installation = MarketplaceInstallation::create([
                    'listing_id' => $listing->id,
                    'tenant_id' => $tenantId,
                    'version_id' => $version->id,
                    'installed_version' => $version->version,
                    'status' => 'active',
                    'installed_at' => now(),
                    'activated_at' => now(),
                    'auto_update' => true,
                    'update_channel' => $channel,
                ]);
            }

            // Handle trial for paid plugins
            if ($listing->pricing_model !== 'free' && $listing->trial_days > 0) {
                $installation->startTrial($listing->trial_days);
            }

            // Update listing stats
            $listing->incrementInstallCount();

            // Increment version download count
            $version->incrementDownloads();

            // Record analytics
            MarketplaceAnalytics::record($listing->id, 'install', $tenantId, [
                'version' => $version->version,
                'channel' => $channel,
            ]);

            Log::info("Plugin installed", [
                'listing_id' => $listing->id,
                'tenant_id' => $tenantId,
                'version' => $version->version,
            ]);

            return $installation;
        });
    }

    /**
     * Uninstall a plugin.
     */
    public function uninstall(MarketplaceInstallation $installation): void
    {
        DB::transaction(function () use ($installation) {
            $installation->uninstall();

            // Cancel any active subscription
            $subscription = $installation->subscription;
            if ($subscription && $subscription->isActive()) {
                $subscription->cancel();
            }

            // Record analytics
            MarketplaceAnalytics::record(
                $installation->listing_id,
                'uninstall',
                $installation->tenant_id
            );

            Log::info("Plugin uninstalled", [
                'installation_id' => $installation->id,
                'tenant_id' => $installation->tenant_id,
            ]);
        });
    }

    /**
     * Activate an inactive installation.
     */
    public function activate(MarketplaceInstallation $installation): void
    {
        if ($installation->status === 'active') {
            return;
        }

        // Check license validity for paid plugins
        if (!$installation->listing->is_free && !$installation->has_valid_license) {
            throw new \InvalidArgumentException('Valid license required to activate');
        }

        $installation->activate();

        MarketplaceAnalytics::record(
            $installation->listing_id,
            'activate',
            $installation->tenant_id
        );

        Log::info("Plugin activated", [
            'installation_id' => $installation->id,
        ]);
    }

    /**
     * Deactivate an installation.
     */
    public function deactivate(MarketplaceInstallation $installation): void
    {
        if ($installation->status !== 'active') {
            return;
        }

        $installation->deactivate();

        MarketplaceAnalytics::record(
            $installation->listing_id,
            'deactivate',
            $installation->tenant_id
        );

        Log::info("Plugin deactivated", [
            'installation_id' => $installation->id,
        ]);
    }

    /**
     * Update a plugin to the latest version.
     */
    public function update(MarketplaceInstallation $installation): void
    {
        $newVersion = $this->versionManager->getAvailableUpdate($installation);

        if (!$newVersion) {
            return; // Already up to date
        }

        $oldVersion = $installation->installed_version;

        DB::transaction(function () use ($installation, $newVersion, $oldVersion) {
            $installation->updateToVersion($newVersion);

            MarketplaceAnalytics::record(
                $installation->listing_id,
                'update',
                $installation->tenant_id,
                [
                    'from_version' => $oldVersion,
                    'to_version' => $newVersion->version,
                ]
            );

            Log::info("Plugin updated", [
                'installation_id' => $installation->id,
                'from' => $oldVersion,
                'to' => $newVersion->version,
            ]);
        });
    }

    /**
     * Rollback to a previous version.
     */
    public function rollback(MarketplaceInstallation $installation, string $targetVersion): void
    {
        $version = $this->versionManager->getVersion($installation->listing, $targetVersion);

        if (!$version) {
            throw new \InvalidArgumentException("Version {$targetVersion} not found");
        }

        $this->versionManager->rollback($installation, $version);

        MarketplaceAnalytics::record(
            $installation->listing_id,
            'rollback',
            $installation->tenant_id,
            [
                'from_version' => $installation->installed_version,
                'to_version' => $targetVersion,
            ]
        );
    }

    /**
     * Get installations needing updates.
     */
    public function getInstallationsNeedingUpdate(int $tenantId): Collection
    {
        return MarketplaceInstallation::byTenant($tenantId)
            ->active()
            ->where('auto_update', true)
            ->with(['listing', 'version'])
            ->get()
            ->filter(fn($i) => $this->versionManager->hasUpdate($i));
    }

    /**
     * Update all plugins for a tenant.
     */
    public function updateAll(int $tenantId): array
    {
        $installations = $this->getInstallationsNeedingUpdate($tenantId);
        $results = [];

        foreach ($installations as $installation) {
            try {
                $this->update($installation);
                $results[$installation->listing->plugin_slug] = [
                    'success' => true,
                    'version' => $installation->fresh()->installed_version,
                ];
            } catch (\Throwable $e) {
                $results[$installation->listing->plugin_slug] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get installation statistics for a tenant.
     */
    public function getStats(int $tenantId): array
    {
        $installations = MarketplaceInstallation::byTenant($tenantId);

        return [
            'total' => $installations->count(),
            'active' => $installations->clone()->active()->count(),
            'inactive' => $installations->clone()->where('status', 'inactive')->count(),
            'needs_update' => $this->getInstallationsNeedingUpdate($tenantId)->count(),
            'trials_expiring' => MarketplaceInstallation::byTenant($tenantId)
                ->trialExpiring(7)
                ->count(),
        ];
    }

    /**
     * Check if a plugin is installed for a tenant.
     */
    public function isInstalled(string $pluginSlug, int $tenantId): bool
    {
        return MarketplaceInstallation::whereHas('listing', fn($q) => $q->where('plugin_slug', $pluginSlug))
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'uninstalled')
            ->exists();
    }

    /**
     * Get installation for a plugin.
     */
    public function getInstallation(string $pluginSlug, int $tenantId): ?MarketplaceInstallation
    {
        return MarketplaceInstallation::whereHas('listing', fn($q) => $q->where('plugin_slug', $pluginSlug))
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'uninstalled')
            ->first();
    }

    /**
     * Suspend installations for a listing (admin action).
     */
    public function suspendAllInstallations(MarketplaceListing $listing, string $reason): int
    {
        $count = 0;

        MarketplaceInstallation::where('listing_id', $listing->id)
            ->active()
            ->chunk(100, function ($installations) use (&$count, $reason) {
                foreach ($installations as $installation) {
                    $installation->suspend();
                    $count++;

                    MarketplaceAnalytics::record(
                        $installation->listing_id,
                        'suspended_by_admin',
                        $installation->tenant_id,
                        ['reason' => $reason]
                    );
                }
            });

        Log::warning("Suspended all installations for listing", [
            'listing_id' => $listing->id,
            'count' => $count,
            'reason' => $reason,
        ]);

        return $count;
    }

    /**
     * Process expired trials.
     */
    public function processExpiredTrials(): int
    {
        $count = 0;

        MarketplaceInstallation::where('is_trial', true)
            ->where('trial_expires_at', '<=', now())
            ->chunk(100, function ($installations) use (&$count) {
                foreach ($installations as $installation) {
                    $installation->deactivate();
                    $installation->endTrial();
                    $count++;

                    Log::info("Trial expired", [
                        'installation_id' => $installation->id,
                        'tenant_id' => $installation->tenant_id,
                    ]);
                }
            });

        return $count;
    }
}
