<?php

/**
 * Marketplace Helper Functions
 */

use App\Models\InstalledPlugin;
use App\Models\MarketplacePlugin;
use App\Models\PluginLicense;
use App\Services\Marketplace\MarketplaceClient;
use App\Services\Marketplace\PluginManager;
use App\Services\Marketplace\LicenseManager;
use App\Services\Marketplace\UpdateManager;
use App\Services\Tenant\TenantManager;

// =============================================================================
// Tenant Context Helper
// =============================================================================

if (!function_exists('get_current_tenant_id')) {
    /**
     * Get the current tenant ID from TenantManager.
     */
    function get_current_tenant_id(): ?int
    {
        try {
            return app(TenantManager::class)->getCurrentTenantId();
        } catch (\Throwable $e) {
            return null;
        }
    }
}

// =============================================================================
// Service Access
// =============================================================================

if (!function_exists('marketplace')) {
    function marketplace(): MarketplaceClient
    {
        return app(MarketplaceClient::class);
    }
}

if (!function_exists('plugins')) {
    function plugins(): PluginManager
    {
        return app(PluginManager::class);
    }
}

if (!function_exists('licenses')) {
    function licenses(): LicenseManager
    {
        return app(LicenseManager::class);
    }
}

if (!function_exists('plugin_updates')) {
    function plugin_updates(): UpdateManager
    {
        return app(UpdateManager::class);
    }
}

// =============================================================================
// Plugin Management
// =============================================================================

if (!function_exists('get_plugin')) {
    /**
     * Get a plugin by slug for the current tenant.
     */
    function get_plugin(string $slug): ?InstalledPlugin
    {
        return InstalledPlugin::findBySlug($slug, get_current_tenant_id());
    }
}

if (!function_exists('get_plugins')) {
    /**
     * Get all plugins for the current tenant.
     */
    function get_plugins(): \Illuminate\Support\Collection
    {
        return InstalledPlugin::forTenant(get_current_tenant_id())->get();
    }
}

if (!function_exists('get_active_plugins')) {
    /**
     * Get all active plugins for the current tenant.
     */
    function get_active_plugins(): \Illuminate\Support\Collection
    {
        return InstalledPlugin::forTenant(get_current_tenant_id())->active()->get();
    }
}

if (!function_exists('is_plugin_active')) {
    /**
     * Check if a plugin is active for the current tenant.
     */
    function is_plugin_active(string $slug): bool
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        return $plugin && $plugin->isActive();
    }
}

if (!function_exists('activate_plugin')) {
    /**
     * Activate a plugin for the current tenant.
     */
    function activate_plugin(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return plugins()->activate($plugin);
    }
}

if (!function_exists('deactivate_plugin')) {
    /**
     * Deactivate a plugin for the current tenant.
     */
    function deactivate_plugin(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return plugins()->deactivate($plugin);
    }
}

if (!function_exists('install_plugin')) {
    /**
     * Install a plugin for the current tenant.
     */
    function install_plugin(string $source, ?string $licenseKey = null): array
    {
        if (file_exists($source)) {
            return plugins()->installFromPackage($source);
        }
        return plugins()->installFromMarketplace($source, $licenseKey);
    }
}

if (!function_exists('uninstall_plugin')) {
    /**
     * Uninstall a plugin for the current tenant.
     */
    function uninstall_plugin(string $slug, bool $deleteData = false): array
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return plugins()->uninstall($plugin, $deleteData);
    }
}

// =============================================================================
// License Management
// =============================================================================

if (!function_exists('activate_license')) {
    /**
     * Activate a license for a plugin for the current tenant.
     */
    function activate_license(string $slug, string $key, string $email): array
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return licenses()->activate($plugin, $key, $email);
    }
}

if (!function_exists('deactivate_license')) {
    /**
     * Deactivate a license for a plugin for the current tenant.
     */
    function deactivate_license(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return licenses()->deactivate($plugin);
    }
}

if (!function_exists('verify_license')) {
    /**
     * Verify a license for a plugin for the current tenant.
     */
    function verify_license(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        if (!$plugin) {
            return ['valid' => false, 'error' => 'Plugin not found'];
        }
        return licenses()->verify($plugin);
    }
}

if (!function_exists('has_valid_license')) {
    /**
     * Check if a plugin has a valid license for the current tenant.
     */
    function has_valid_license(string $slug): bool
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        return $plugin && $plugin->hasValidLicense();
    }
}

if (!function_exists('get_expiring_licenses')) {
    /**
     * Get expiring licenses for the current tenant.
     */
    function get_expiring_licenses(int $days = 30): \Illuminate\Support\Collection
    {
        return licenses()->getExpiring($days);
    }
}

// =============================================================================
// Updates
// =============================================================================

if (!function_exists('check_plugin_updates')) {
    /**
     * Check for plugin updates for the current tenant.
     */
    function check_plugin_updates(): array
    {
        return plugin_updates()->checkAll();
    }
}

if (!function_exists('has_plugin_update')) {
    /**
     * Check if a plugin has an update for the current tenant.
     */
    function has_plugin_update(string $slug): bool
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        return $plugin && $plugin->hasUpdate();
    }
}

if (!function_exists('update_plugin')) {
    /**
     * Update a plugin for the current tenant.
     */
    function update_plugin(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug, get_current_tenant_id());
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return plugin_updates()->install($plugin);
    }
}

if (!function_exists('update_all_plugins')) {
    /**
     * Update all plugins for the current tenant.
     */
    function update_all_plugins(): array
    {
        return plugin_updates()->updateAll();
    }
}

if (!function_exists('get_pending_updates')) {
    /**
     * Get pending updates for the current tenant.
     */
    function get_pending_updates(): \Illuminate\Support\Collection
    {
        return plugin_updates()->getPendingUpdates();
    }
}

// =============================================================================
// Marketplace Browsing
// =============================================================================

if (!function_exists('search_marketplace')) {
    function search_marketplace(string $query, array $filters = []): array
    {
        return marketplace()->search($query, $filters);
    }
}

if (!function_exists('get_featured_plugins')) {
    function get_featured_plugins(int $limit = 10): array
    {
        return marketplace()->getFeatured($limit);
    }
}

if (!function_exists('get_popular_plugins')) {
    function get_popular_plugins(int $limit = 10): array
    {
        return marketplace()->getPopular($limit);
    }
}

if (!function_exists('get_marketplace_plugin')) {
    function get_marketplace_plugin(string $id): ?array
    {
        return marketplace()->getPlugin($id);
    }
}

if (!function_exists('sync_marketplace')) {
    function sync_marketplace(): int
    {
        return marketplace()->syncPlugins();
    }
}

// =============================================================================
// Statistics
// =============================================================================

if (!function_exists('plugin_stats')) {
    /**
     * Get plugin statistics for the current tenant.
     */
    function plugin_stats(): array
    {
        $tenantId = get_current_tenant_id();
        
        return [
            'total' => InstalledPlugin::forTenant($tenantId)->count(),
            'active' => InstalledPlugin::forTenant($tenantId)->active()->count(),
            'inactive' => InstalledPlugin::forTenant($tenantId)->inactive()->count(),
            'premium' => InstalledPlugin::forTenant($tenantId)->premium()->count(),
            'with_updates' => InstalledPlugin::forTenant($tenantId)->hasUpdate()->count(),
        ];
    }
}

if (!function_exists('license_stats')) {
    /**
     * Get license statistics for the current tenant.
     */
    function license_stats(): array
    {
        return licenses()->getStatusSummary();
    }
}

if (!function_exists('update_stats')) {
    /**
     * Get update statistics for the current tenant.
     */
    function update_stats(): array
    {
        return plugin_updates()->getUpdateSummary();
    }
}
