<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\Setting;
use App\Services\Plugins\PluginManager;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * The plugin manager instance.
     */
    protected PluginManager $pluginManager;

    /**
     * Cache key prefix for settings.
     */
    protected const CACHE_PREFIX = 'settings:';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Create a new SettingsService instance.
     */
    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * Get a setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            function () use ($key, $default) {
                $setting = Setting::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            }
        );
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, ?string $group = 'general'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Get all settings for a group.
     */
    public function getGroup(string $group): array
    {
        $settings = Setting::where('group', $group)->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }

        return $result;
    }

    /**
     * Set multiple settings at once.
     */
    public function setMany(array $settings, ?string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    /**
     * Get all general settings with their definitions.
     */
    public function getGeneralSettingsDefinitions(): array
    {
        return [
            'company' => [
                'title' => 'Company Information',
                'description' => 'Basic company and branding settings',
                'fields' => [
                    'app_name' => [
                        'type' => 'text',
                        'label' => 'Application Name',
                        'description' => 'The name displayed in the browser title and branding',
                        'default' => 'VODO',
                    ],
                    'company_name' => [
                        'type' => 'text',
                        'label' => 'Company Name',
                        'description' => 'Your company or organization name',
                        'default' => '',
                    ],
                    'company_email' => [
                        'type' => 'email',
                        'label' => 'Company Email',
                        'description' => 'Primary contact email address',
                        'default' => '',
                    ],
                    'company_phone' => [
                        'type' => 'text',
                        'label' => 'Company Phone',
                        'description' => 'Primary contact phone number',
                        'default' => '',
                    ],
                ],
            ],
            'localization' => [
                'title' => 'Localization',
                'description' => 'Language and regional settings',
                'fields' => [
                    'timezone' => [
                        'type' => 'select',
                        'label' => 'Timezone',
                        'description' => 'Default timezone for the application',
                        'default' => 'UTC',
                        'options' => $this->getTimezoneOptions(),
                    ],
                    'date_format' => [
                        'type' => 'select',
                        'label' => 'Date Format',
                        'description' => 'How dates are displayed throughout the application',
                        'default' => 'Y-m-d',
                        'options' => [
                            'Y-m-d' => '2024-12-09',
                            'd/m/Y' => '09/12/2024',
                            'm/d/Y' => '12/09/2024',
                            'd.m.Y' => '09.12.2024',
                            'F j, Y' => 'December 9, 2024',
                        ],
                    ],
                    'time_format' => [
                        'type' => 'select',
                        'label' => 'Time Format',
                        'description' => 'How times are displayed throughout the application',
                        'default' => 'H:i',
                        'options' => [
                            'H:i' => '14:30 (24-hour)',
                            'h:i A' => '02:30 PM (12-hour)',
                        ],
                    ],
                ],
            ],
            'security' => [
                'title' => 'Security',
                'description' => 'Security and authentication settings',
                'fields' => [
                    'session_lifetime' => [
                        'type' => 'number',
                        'label' => 'Session Lifetime (minutes)',
                        'description' => 'How long before an inactive session expires',
                        'default' => 120,
                        'min' => 5,
                        'max' => 1440,
                    ],
                    'password_min_length' => [
                        'type' => 'number',
                        'label' => 'Minimum Password Length',
                        'description' => 'Minimum number of characters required for passwords',
                        'default' => 8,
                        'min' => 6,
                        'max' => 32,
                    ],
                    'require_2fa' => [
                        'type' => 'toggle',
                        'label' => 'Require Two-Factor Authentication',
                        'description' => 'Require all users to enable 2FA for their accounts',
                        'default' => false,
                    ],
                ],
            ],
            'notifications' => [
                'title' => 'Notifications',
                'description' => 'Email and notification settings',
                'fields' => [
                    'email_notifications' => [
                        'type' => 'toggle',
                        'label' => 'Enable Email Notifications',
                        'description' => 'Send email notifications for important events',
                        'default' => true,
                    ],
                    'notification_email' => [
                        'type' => 'email',
                        'label' => 'Notification Email',
                        'description' => 'Email address for system notifications',
                        'default' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get timezone options.
     */
    protected function getTimezoneOptions(): array
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $options = [];
        
        foreach ($timezones as $timezone) {
            $options[$timezone] = $timezone;
        }

        return $options;
    }

    /**
     * Get all plugins that have settings pages.
     */
    public function getPluginsWithSettings(): array
    {
        $plugins = Plugin::active()->get();
        $pluginsWithSettings = [];

        foreach ($plugins as $plugin) {
            try {
                // Try to get already loaded instance, or load it
                $instance = $this->pluginManager->getLoadedPlugin($plugin->slug);
                
                if (!$instance) {
                    $instance = $this->pluginManager->loadPluginInstance($plugin);
                }
                
                if ($instance && method_exists($instance, 'hasSettingsPage') && $instance->hasSettingsPage()) {
                    $pluginsWithSettings[] = [
                        'slug' => $plugin->slug,
                        'name' => $plugin->name,
                        'icon' => $this->getPluginSettingsIcon($instance),
                        'instance' => $instance,
                    ];
                }
            } catch (\Throwable $e) {
                // Skip plugins that can't be loaded
                continue;
            }
        }

        return $pluginsWithSettings;
    }

    /**
     * Get the settings icon for a plugin.
     */
    protected function getPluginSettingsIcon($instance): string
    {
        if (method_exists($instance, 'getSettingsIcon')) {
            return $instance->getSettingsIcon();
        }

        return 'plug';
    }

    /**
     * Get plugin settings fields.
     */
    public function getPluginSettingsFields(string $slug): array
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();
            
            if (!$plugin) {
                return [];
            }

            // Try to get already loaded instance, or load it
            $instance = $this->pluginManager->getLoadedPlugin($slug);
            
            if (!$instance) {
                $instance = $this->pluginManager->loadPluginInstance($plugin);
            }

            if ($instance && method_exists($instance, 'getSettingsFields')) {
                return $instance->getSettingsFields();
            }
        } catch (\Throwable $e) {
            // Return empty if plugin can't be loaded
        }

        return [];
    }

    /**
     * Get plugin settings values.
     */
    public function getPluginSettings(string $slug): array
    {
        $plugin = Plugin::where('slug', $slug)->first();

        if ($plugin) {
            return $plugin->settings ?? [];
        }

        return [];
    }

    /**
     * Save plugin settings.
     */
    public function savePluginSettings(string $slug, array $settings): bool
    {
        $plugin = Plugin::where('slug', $slug)->first();

        if ($plugin) {
            $plugin->settings = array_merge($plugin->settings ?? [], $settings);
            return $plugin->save();
        }

        return false;
    }

    /**
     * Clear all settings cache.
     */
    public function clearCache(): void
    {
        // Clear individual setting caches
        $settings = Setting::all();
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }
    }
}
