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
    function get_plugin(string $slug): ?InstalledPlugin
    {
        return InstalledPlugin::findBySlug($slug);
    }
}

if (!function_exists('get_plugins')) {
    function get_plugins(): \Illuminate\Support\Collection
    {
        return InstalledPlugin::all();
    }
}

if (!function_exists('get_active_plugins')) {
    function get_active_plugins(): \Illuminate\Support\Collection
    {
        return InstalledPlugin::active()->get();
    }
}

if (!function_exists('is_plugin_active')) {
    function is_plugin_active(string $slug): bool
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        return $plugin && $plugin->isActive();
    }
}

if (!function_exists('activate_plugin')) {
    function activate_plugin(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return plugins()->activate($plugin);
    }
}

if (!function_exists('deactivate_plugin')) {
    function deactivate_plugin(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return plugins()->deactivate($plugin);
    }
}

if (!function_exists('install_plugin')) {
    function install_plugin(string $source, ?string $licenseKey = null): array
    {
        if (file_exists($source)) {
            return plugins()->installFromPackage($source);
        }
        return plugins()->installFromMarketplace($source, $licenseKey);
    }
}

if (!function_exists('uninstall_plugin')) {
    function uninstall_plugin(string $slug, bool $deleteData = false): array
    {
        $plugin = InstalledPlugin::findBySlug($slug);
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
    function activate_license(string $slug, string $key, string $email): array
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return licenses()->activate($plugin, $key, $email);
    }
}

if (!function_exists('deactivate_license')) {
    function deactivate_license(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return licenses()->deactivate($plugin);
    }
}

if (!function_exists('verify_license')) {
    function verify_license(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return ['valid' => false, 'error' => 'Plugin not found'];
        }
        return licenses()->verify($plugin);
    }
}

if (!function_exists('has_valid_license')) {
    function has_valid_license(string $slug): bool
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        return $plugin && $plugin->hasValidLicense();
    }
}

if (!function_exists('get_expiring_licenses')) {
    function get_expiring_licenses(int $days = 30): \Illuminate\Support\Collection
    {
        return licenses()->getExpiring($days);
    }
}

// =============================================================================
// Updates
// =============================================================================

if (!function_exists('check_plugin_updates')) {
    function check_plugin_updates(): array
    {
        return plugin_updates()->checkAll();
    }
}

if (!function_exists('has_plugin_update')) {
    function has_plugin_update(string $slug): bool
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        return $plugin && $plugin->hasUpdate();
    }
}

if (!function_exists('update_plugin')) {
    function update_plugin(string $slug): array
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        return plugin_updates()->install($plugin);
    }
}

if (!function_exists('update_all_plugins')) {
    function update_all_plugins(): array
    {
        return plugin_updates()->updateAll();
    }
}

if (!function_exists('get_pending_updates')) {
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
    function plugin_stats(): array
    {
        return [
            'total' => InstalledPlugin::count(),
            'active' => InstalledPlugin::active()->count(),
            'inactive' => InstalledPlugin::inactive()->count(),
            'premium' => InstalledPlugin::premium()->count(),
            'with_updates' => InstalledPlugin::hasUpdate()->count(),
        ];
    }
}

if (!function_exists('license_stats')) {
    function license_stats(): array
    {
        return licenses()->getStatusSummary();
    }
}

if (!function_exists('update_stats')) {
    function update_stats(): array
    {
        return plugin_updates()->getUpdateSummary();
    }
}
