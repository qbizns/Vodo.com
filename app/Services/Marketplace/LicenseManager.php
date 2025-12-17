<?php

namespace App\Services\Marketplace;

use App\Models\InstalledPlugin;
use App\Models\PluginLicense;
use Illuminate\Support\Facades\Log;

class LicenseManager
{
    protected MarketplaceClient $client;

    public function __construct(MarketplaceClient $client)
    {
        $this->client = $client;
    }

    // =========================================================================
    // License Activation
    // =========================================================================

    /**
     * Activate a license for a plugin
     */
    public function activate(InstalledPlugin $plugin, string $licenseKey, string $email): array
    {
        // Check if license already exists
        $existing = PluginLicense::findByKey($licenseKey);
        if ($existing && $existing->plugin_id !== $plugin->id) {
            return [
                'success' => false,
                'error' => 'License key is already in use by another plugin',
            ];
        }

        try {
            // Verify with marketplace
            $response = $this->client->activateLicense($licenseKey, $plugin->slug, $email);

            if (!($response['success'] ?? false)) {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? 'License activation failed',
                ];
            }

            // Create or update license record
            $license = PluginLicense::updateOrCreate(
                ['plugin_id' => $plugin->id],
                [
                    'license_key' => $licenseKey,
                    'license_type' => $response['license_type'] ?? PluginLicense::TYPE_STANDARD,
                    'status' => PluginLicense::STATUS_ACTIVE,
                    'activation_id' => $response['activation_id'] ?? null,
                    'activation_email' => $email,
                    'instance_id' => $response['instance_id'] ?? null,
                    'activations_used' => $response['activations_used'] ?? 1,
                    'activations_limit' => $response['activations_limit'] ?? null,
                    'purchased_at' => $response['purchased_at'] ?? null,
                    'expires_at' => $response['expires_at'] ?? null,
                    'support_active' => $response['support_active'] ?? true,
                    'support_expires_at' => $response['support_expires_at'] ?? null,
                    'updates_active' => $response['updates_active'] ?? true,
                    'updates_expire_at' => $response['updates_expire_at'] ?? null,
                    'features' => $response['features'] ?? [],
                    'last_verified_at' => now(),
                ]
            );

            // Activate the plugin if not already
            if (!$plugin->isActive()) {
                $plugin->activate();
            }

            if (function_exists('do_action')) {
                do_action('license_activated', $plugin, $license);
            }

            return [
                'success' => true,
                'license' => $license,
                'message' => 'License activated successfully',
            ];

        } catch (\Exception $e) {
            Log::error("License activation failed for {$plugin->slug}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to connect to licensing server',
            ];
        }
    }

    /**
     * Deactivate a license
     */
    public function deactivate(InstalledPlugin $plugin): array
    {
        $license = $plugin->license;

        if (!$license) {
            return [
                'success' => false,
                'error' => 'No license found for this plugin',
            ];
        }

        try {
            // Deactivate on marketplace
            if ($license->activation_id) {
                $this->client->deactivateLicense($license->license_key, $license->instance_id);
            }

            // Update local record
            $license->deactivate();

            // Deactivate plugin if premium
            if ($plugin->is_premium) {
                $plugin->deactivate();
            }

            if (function_exists('do_action')) {
                do_action('license_deactivated', $plugin, $license);
            }

            return [
                'success' => true,
                'message' => 'License deactivated successfully',
            ];

        } catch (\Exception $e) {
            Log::error("License deactivation failed for {$plugin->slug}: " . $e->getMessage());

            // Still deactivate locally even if API fails
            $license->deactivate();

            return [
                'success' => true,
                'message' => 'License deactivated locally (could not reach server)',
            ];
        }
    }

    // =========================================================================
    // License Verification
    // =========================================================================

    /**
     * Verify a license is still valid
     */
    public function verify(InstalledPlugin $plugin): array
    {
        $license = $plugin->license;

        if (!$license) {
            return [
                'valid' => false,
                'error' => 'No license found',
            ];
        }

        // Check local expiration first
        if ($license->isExpired()) {
            $license->markExpired();
            return [
                'valid' => false,
                'error' => 'License has expired',
            ];
        }

        try {
            // Verify with marketplace
            $response = $this->client->verifyLicense(
                $license->license_key,
                $plugin->slug,
                config('app.url')
            );

            if ($response['valid'] ?? false) {
                // Update license info
                $this->updateLicenseFromResponse($license, $response);
                $license->markVerified();

                return [
                    'valid' => true,
                    'license' => $license,
                ];
            }

            // License invalid on server
            $license->status = $response['status'] ?? PluginLicense::STATUS_INVALID;
            $license->save();

            return [
                'valid' => false,
                'error' => $response['message'] ?? 'License validation failed',
            ];

        } catch (\Exception $e) {
            Log::warning("License verification failed for {$plugin->slug}: " . $e->getMessage());

            // If we can't reach server, use local check
            return [
                'valid' => $license->isValid(),
                'error' => 'Could not reach licensing server',
                'offline_check' => true,
            ];
        }
    }

    /**
     * Verify all plugin licenses
     */
    public function verifyAll(): array
    {
        $results = [];
        $plugins = InstalledPlugin::premium()->with('license')->get();

        foreach ($plugins as $plugin) {
            $results[$plugin->slug] = $this->verify($plugin);
        }

        return $results;
    }

    // =========================================================================
    // License Queries
    // =========================================================================

    /**
     * Get expiring licenses
     */
    public function getExpiring(int $days = 30): \Illuminate\Support\Collection
    {
        return PluginLicense::expiringSoon($days)->with('plugin')->get();
    }

    /**
     * Get expired licenses
     */
    public function getExpired(): \Illuminate\Support\Collection
    {
        return PluginLicense::expired()->with('plugin')->get();
    }

    /**
     * Check if plugin can receive updates
     */
    public function canUpdate(InstalledPlugin $plugin): bool
    {
        if (!$plugin->is_premium) {
            return true;
        }

        $license = $plugin->license;
        if (!$license) {
            return false;
        }

        return $license->hasUpdates();
    }

    /**
     * Check if plugin has support
     */
    public function hasSupport(InstalledPlugin $plugin): bool
    {
        if (!$plugin->is_premium) {
            return false;
        }

        $license = $plugin->license;
        if (!$license) {
            return false;
        }

        return $license->hasSupport();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function updateLicenseFromResponse(PluginLicense $license, array $response): void
    {
        $fields = [
            'license_type', 'activations_used', 'activations_limit',
            'expires_at', 'support_active', 'support_expires_at',
            'updates_active', 'updates_expire_at', 'features',
        ];

        foreach ($fields as $field) {
            if (isset($response[$field])) {
                $license->{$field} = $response[$field];
            }
        }

        $license->save();
    }

    /**
     * Generate license status summary
     */
    public function getStatusSummary(): array
    {
        return [
            'total' => PluginLicense::count(),
            'active' => PluginLicense::active()->count(),
            'expired' => PluginLicense::expired()->count(),
            'expiring_soon' => PluginLicense::expiringSoon(30)->count(),
            'with_support' => PluginLicense::where('support_active', true)
                ->where(fn($q) => $q->whereNull('support_expires_at')->orWhere('support_expires_at', '>', now()))
                ->count(),
            'with_updates' => PluginLicense::where('updates_active', true)
                ->where(fn($q) => $q->whereNull('updates_expire_at')->orWhere('updates_expire_at', '>', now()))
                ->count(),
        ];
    }
}
