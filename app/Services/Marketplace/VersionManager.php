<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceVersion;
use App\Models\Marketplace\MarketplaceSubmission;
use App\Models\Marketplace\MarketplaceInstallation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Version Manager
 *
 * Manages plugin versions, packages, and rollbacks.
 */
class VersionManager
{
    protected string $packageStorage = 'marketplace';

    /**
     * Create a new version from a submission.
     */
    public function createVersion(
        MarketplaceListing $listing,
        MarketplaceSubmission $submission
    ): MarketplaceVersion {
        $manifest = $submission->manifest;

        // Store package to permanent storage
        $packageUrl = $this->storePackage($submission);
        $packageHash = hash_file('sha256', $submission->package_path . '/plugin.json');

        // Create version record
        $version = MarketplaceVersion::create([
            'listing_id' => $listing->id,
            'version' => $manifest['version'],
            'changelog' => $manifest['changelog'] ?? null,
            'release_notes' => $manifest['release_notes'] ?? null,
            'package_url' => $packageUrl,
            'package_hash' => $packageHash,
            'package_size' => $submission->package_size,
            'min_php_version' => $manifest['requires']['php'] ?? '8.2',
            'min_platform_version' => $manifest['requires']['platform'] ?? null,
            'dependencies' => $manifest['dependencies'] ?? [],
            'required_scopes' => $manifest['permissions']['scopes'] ?? [],
            'optional_scopes' => $manifest['permissions']['dangerous_scopes'] ?? [],
            'status' => 'published',
            'channel' => $this->determineChannel($manifest['version']),
            'is_current' => true,
            'published_at' => now(),
        ]);

        // Unset current flag from previous versions
        MarketplaceVersion::where('listing_id', $listing->id)
            ->where('id', '!=', $version->id)
            ->where('channel', $version->channel)
            ->update(['is_current' => false]);

        Log::info("Version created", [
            'listing_id' => $listing->id,
            'version' => $version->version,
            'channel' => $version->channel,
        ]);

        return $version;
    }

    /**
     * Get all versions for a listing.
     */
    public function getVersions(MarketplaceListing $listing, ?string $channel = null): Collection
    {
        $query = $listing->versions()
            ->published()
            ->orderByDesc('published_at');

        if ($channel) {
            $query->where('channel', $channel);
        }

        return $query->get();
    }

    /**
     * Get the latest version.
     */
    public function getLatestVersion(
        MarketplaceListing $listing,
        string $channel = 'stable'
    ): ?MarketplaceVersion {
        return $listing->versions()
            ->published()
            ->where('channel', $channel)
            ->orderByDesc('published_at')
            ->first();
    }

    /**
     * Get a specific version.
     */
    public function getVersion(MarketplaceListing $listing, string $version): ?MarketplaceVersion
    {
        return $listing->versions()
            ->where('version', $version)
            ->first();
    }

    /**
     * Check if an update is available.
     */
    public function hasUpdate(MarketplaceInstallation $installation): bool
    {
        $latest = $this->getLatestVersion(
            $installation->listing,
            $installation->update_channel
        );

        if (!$latest) {
            return false;
        }

        return version_compare($latest->version, $installation->installed_version, '>');
    }

    /**
     * Get available update.
     */
    public function getAvailableUpdate(MarketplaceInstallation $installation): ?MarketplaceVersion
    {
        if (!$this->hasUpdate($installation)) {
            return null;
        }

        return $this->getLatestVersion(
            $installation->listing,
            $installation->update_channel
        );
    }

    /**
     * Rollback to a previous version.
     */
    public function rollback(
        MarketplaceInstallation $installation,
        MarketplaceVersion $targetVersion
    ): bool {
        // Verify target version exists and is valid
        if ($targetVersion->listing_id !== $installation->listing_id) {
            throw new \InvalidArgumentException('Version does not belong to this plugin');
        }

        if ($targetVersion->status === 'yanked') {
            throw new \InvalidArgumentException('Cannot rollback to a yanked version');
        }

        // Download and install the target version
        $packagePath = $this->downloadPackage($targetVersion);

        if (!$packagePath) {
            Log::error("Failed to download package for rollback", [
                'installation_id' => $installation->id,
                'version' => $targetVersion->version,
            ]);
            return false;
        }

        // Update installation
        $installation->update([
            'version_id' => $targetVersion->id,
            'installed_version' => $targetVersion->version,
        ]);

        Log::info("Rolled back plugin", [
            'installation_id' => $installation->id,
            'from_version' => $installation->installed_version,
            'to_version' => $targetVersion->version,
        ]);

        return true;
    }

    /**
     * Yank a version (remove from availability).
     */
    public function yankVersion(MarketplaceVersion $version, string $reason): void
    {
        $version->yank($reason);

        Log::warning("Version yanked", [
            'listing_id' => $version->listing_id,
            'version' => $version->version,
            'reason' => $reason,
        ]);
    }

    /**
     * Compare two versions.
     */
    public function compareVersions(string $version1, string $version2): int
    {
        return version_compare($version1, $version2);
    }

    /**
     * Check version compatibility.
     */
    public function isCompatible(
        MarketplaceVersion $version,
        string $phpVersion,
        string $platformVersion
    ): array {
        $issues = [];

        if (!$version->isCompatibleWithPhp($phpVersion)) {
            $issues[] = "Requires PHP {$version->min_php_version} or higher";
        }

        if (!$version->isCompatibleWithPlatform($platformVersion)) {
            $issues[] = "Requires platform version {$version->min_platform_version} or higher";
        }

        // Check dependencies
        foreach ($version->dependencies ?? [] as $dep => $requiredVersion) {
            // In a real implementation, check if dependency is installed
            // For now, just note it
        }

        return [
            'compatible' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Store package to permanent storage.
     */
    protected function storePackage(MarketplaceSubmission $submission): string
    {
        $slug = $submission->plugin_slug;
        $version = $submission->version;
        $filename = "{$slug}-{$version}.zip";
        $path = "packages/{$slug}/{$filename}";

        // In a real implementation, create a ZIP and upload to CDN
        // For now, return a placeholder URL
        return "https://cdn.vodo.com/{$path}";
    }

    /**
     * Download a package.
     */
    protected function downloadPackage(MarketplaceVersion $version): ?string
    {
        // In a real implementation, download from CDN
        // For now, return a placeholder path
        return storage_path("app/packages/{$version->listing->plugin_slug}/{$version->version}");
    }

    /**
     * Determine release channel from version string.
     */
    protected function determineChannel(string $version): string
    {
        if (str_contains($version, '-alpha')) {
            return 'alpha';
        }
        if (str_contains($version, '-beta')) {
            return 'beta';
        }
        if (str_contains($version, '-rc')) {
            return 'rc';
        }
        return 'stable';
    }

    /**
     * Verify package integrity.
     */
    public function verifyPackage(MarketplaceVersion $version, string $packageContent): bool
    {
        return $version->verifyHash($packageContent);
    }

    /**
     * Get version history with changelogs.
     */
    public function getVersionHistory(MarketplaceListing $listing, int $limit = 10): Collection
    {
        return $listing->versions()
            ->published()
            ->notYanked()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['version', 'changelog', 'published_at', 'channel']);
    }
}
