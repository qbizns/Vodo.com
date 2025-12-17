<?php

namespace App\Traits;

use App\Models\InstalledPlugin;
use App\Models\PluginLicense;
use App\Services\Marketplace\LicenseManager;
use App\Services\Marketplace\UpdateManager;

/**
 * Trait for plugins to access marketplace functionality
 * 
 * class MyPlugin extends BasePlugin
 * {
 *     use HasMarketplace;
 * 
 *     public function boot(): void
 *     {
 *         if (!$this->hasValidLicense()) {
 *             // Disable premium features
 *         }
 *     }
 * }
 */
trait HasMarketplace
{
    protected ?InstalledPlugin $installedPlugin = null;

    protected function licenseManager(): LicenseManager
    {
        return app(LicenseManager::class);
    }

    protected function updateManager(): UpdateManager
    {
        return app(UpdateManager::class);
    }

    protected function getMarketplaceSlug(): string
    {
        return $this->slug ?? $this->pluginSlug ?? strtolower(class_basename($this));
    }

    // =========================================================================
    // Plugin Registration
    // =========================================================================

    /**
     * Get the installed plugin record
     */
    public function getInstalledPlugin(): ?InstalledPlugin
    {
        if ($this->installedPlugin === null) {
            $this->installedPlugin = InstalledPlugin::findBySlug($this->getMarketplaceSlug());
        }

        return $this->installedPlugin;
    }

    /**
     * Get current version
     */
    public function getVersion(): string
    {
        return $this->getInstalledPlugin()?->version ?? '1.0.0';
    }

    // =========================================================================
    // License Methods
    // =========================================================================

    /**
     * Check if plugin requires a license
     */
    public function requiresLicense(): bool
    {
        $plugin = $this->getInstalledPlugin();
        return $plugin ? $plugin->is_premium : false;
    }

    /**
     * Check if plugin has a valid license
     */
    public function hasValidLicense(): bool
    {
        $plugin = $this->getInstalledPlugin();

        if (!$plugin) {
            return true; // Not registered, assume valid
        }

        if (!$plugin->is_premium) {
            return true; // Free plugin
        }

        return $plugin->hasValidLicense();
    }

    /**
     * Get the license
     */
    public function getLicense(): ?PluginLicense
    {
        return $this->getInstalledPlugin()?->license;
    }

    /**
     * Check if a specific feature is licensed
     */
    public function hasFeature(string $feature): bool
    {
        $license = $this->getLicense();

        if (!$license) {
            return !$this->requiresLicense();
        }

        return $license->hasFeature($feature);
    }

    /**
     * Get license type
     */
    public function getLicenseType(): ?string
    {
        return $this->getLicense()?->license_type;
    }

    /**
     * Check if has support
     */
    public function hasSupport(): bool
    {
        return $this->licenseManager()->hasSupport($this->getInstalledPlugin());
    }

    /**
     * Check if can receive updates
     */
    public function canUpdate(): bool
    {
        return $this->licenseManager()->canUpdate($this->getInstalledPlugin());
    }

    // =========================================================================
    // Update Methods
    // =========================================================================

    /**
     * Check if update is available
     */
    public function hasUpdate(): bool
    {
        $plugin = $this->getInstalledPlugin();
        return $plugin ? $plugin->hasUpdate() : false;
    }

    /**
     * Get available update info
     */
    public function getAvailableUpdate(): ?array
    {
        $plugin = $this->getInstalledPlugin();
        $update = $plugin?->pendingUpdate;

        if (!$update) {
            return null;
        }

        return [
            'current_version' => $plugin->version,
            'new_version' => $update->new_version,
            'changelog' => $update->changelog,
            'is_security' => $update->is_security_update,
            'is_critical' => $update->is_critical,
        ];
    }

    // =========================================================================
    // Lifecycle Hooks (Override in Plugin)
    // =========================================================================

    /**
     * Called when plugin is installed
     */
    public function install(): void
    {
        // Override in plugin
    }

    /**
     * Called when plugin is activated
     */
    public function activate(): void
    {
        // Override in plugin
    }

    /**
     * Called when plugin is deactivated
     */
    public function deactivate(): void
    {
        // Override in plugin
    }

    /**
     * Called when plugin is uninstalled
     */
    public function uninstall(): void
    {
        // Override in plugin
    }

    /**
     * Called when plugin is updated
     */
    public function update(string $fromVersion, string $toVersion): void
    {
        // Override in plugin
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Validate that plugin can run
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->requiresLicense() && !$this->hasValidLicense()) {
            $errors[] = 'Valid license required';
        }

        return $errors;
    }

    /**
     * Check if plugin is properly licensed and configured
     */
    public function isReady(): bool
    {
        return empty($this->validate());
    }
}
