<?php

namespace App\Plugins\subscriptions;

use App\Services\Plugins\BasePlugin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Subscriptions Plugin
 * 
 * SaaS subscription and plan management system.
 * Handles subscription plans, billing cycles, trials, and user subscriptions.
 */
class SubscriptionsPlugin extends BasePlugin
{
    /**
     * Plugin identifier
     */
    public const SLUG = 'subscriptions';

    /**
     * Plugin version
     */
    public const VERSION = '1.0.0';

    /**
     * Register plugin services and bindings.
     */
    public function register(): void
    {
        $this->mergeConfig();
        Log::info('Subscriptions Plugin: Registered');
    }

    /**
     * Bootstrap the plugin.
     */
    public function boot(): void
    {
        parent::boot();
        $this->registerEventListeners();
        $this->registerFiltersAndActions();
        Log::info('Subscriptions Plugin: Booted');
    }

    /**
     * Merge plugin configuration.
     */
    protected function mergeConfig(): void
    {
        $configPath = $this->basePath . '/config/subscriptions.php';
        
        if (file_exists($configPath)) {
            config()->set('subscriptions', require $configPath);
        }
    }

    /**
     * Register event listeners for the plugin.
     */
    protected function registerEventListeners(): void
    {
        // Event listeners are registered through the service provider
    }

    /**
     * Register filters and actions for the plugin.
     */
    protected function registerFiltersAndActions(): void
    {
        // Add a filter for subscription data
        $this->addFilter('subscription_data', function (array $data) {
            return $data;
        });

        // Add actions for subscription lifecycle
        $this->addAction('subscription_created', function ($subscription) {
            Log::info('Subscriptions: Subscription created', ['id' => $subscription->id]);
        });

        $this->addAction('subscription_renewed', function ($subscription) {
            Log::info('Subscriptions: Subscription renewed', ['id' => $subscription->id]);
        });

        $this->addAction('subscription_cancelled', function ($subscription) {
            Log::info('Subscriptions: Subscription cancelled', ['id' => $subscription->id]);
        });

        $this->addAction('subscription_expired', function ($subscription) {
            Log::info('Subscriptions: Subscription expired', ['id' => $subscription->id]);
        });
    }

    /**
     * Called when plugin is being activated
     */
    public function onActivate(): void
    {
        // Create default settings
        $this->setSetting('currency', 'USD');
        $this->setSetting('currency_symbol', '$');
        $this->setSetting('trial_days', 14);
        $this->setSetting('grace_period_days', 3);
        $this->setSetting('allow_plan_changes', true);
        $this->setSetting('prorate_plan_changes', true);
        $this->setSetting('send_renewal_reminders', true);
        $this->setSetting('reminder_days_before', 7);
        $this->setSetting('auto_cancel_expired', false);

        // Clear caches
        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.settings');

        Log::info('Subscriptions Plugin: Activated');
    }

    /**
     * Called when plugin is being deactivated
     */
    public function onDeactivate(): void
    {
        // Clear plugin-specific caches
        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.settings');
        Cache::forget('subscriptions.statistics');

        Log::info('Subscriptions Plugin: Deactivated');
    }

    /**
     * Called before plugin is uninstalled
     */
    public function onUninstall(bool $keepData = false): void
    {
        if (!$keepData) {
            // Drop plugin tables
            $this->dropTables();
        }

        // Clear all caches
        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.settings');
        Cache::forget('subscriptions.statistics');

        Log::info('Subscriptions Plugin: Uninstalled');
    }

    /**
     * Drop plugin database tables.
     */
    protected function dropTables(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('subscription_invoices');
        \Illuminate\Support\Facades\Schema::dropIfExists('subscriptions');
        \Illuminate\Support\Facades\Schema::dropIfExists('plan_features');
        \Illuminate\Support\Facades\Schema::dropIfExists('plans');
    }

    /**
     * Called when plugin is being updated
     */
    public function onUpdate(string $fromVersion, string $toVersion): void
    {
        if (version_compare($fromVersion, '1.0.0', '<')) {
            // Migration tasks for version 1.0.0
        }

        // Clear caches
        Cache::forget('subscriptions.plans');
        Cache::forget('subscriptions.settings');

        Log::info("Subscriptions Plugin: Updated from {$fromVersion} to {$toVersion}");
    }

    /**
     * Handle plugin activation (legacy method).
     */
    public function activate(): void
    {
        $this->onActivate();
    }

    /**
     * Handle plugin deactivation (legacy method).
     */
    public function deactivate(): void
    {
        $this->onDeactivate();
    }

    /**
     * Handle plugin uninstallation (legacy method).
     */
    public function uninstall(): void
    {
        $this->onUninstall(false);
    }

    /**
     * Check if this plugin has a settings page.
     */
    public function hasSettingsPage(): bool
    {
        return true;
    }

    /**
     * Get the icon for the settings page sidebar.
     */
    public function getSettingsIcon(): string
    {
        return 'creditCard';
    }

    /**
     * Get permissions registered by this plugin.
     */
    public function getPermissions(): array
    {
        return [
            // Plans
            'subscriptions.plans.view' => [
                'label' => 'View Plans',
                'description' => 'Can view subscription plans',
                'group' => 'Subscriptions',
            ],
            'subscriptions.plans.create' => [
                'label' => 'Create Plans',
                'description' => 'Can create new subscription plans',
                'group' => 'Subscriptions',
            ],
            'subscriptions.plans.edit' => [
                'label' => 'Edit Plans',
                'description' => 'Can modify existing plans',
                'group' => 'Subscriptions',
            ],
            'subscriptions.plans.delete' => [
                'label' => 'Delete Plans',
                'description' => 'Can delete subscription plans',
                'group' => 'Subscriptions',
            ],
            // Subscriptions
            'subscriptions.subscriptions.view' => [
                'label' => 'View Subscriptions',
                'description' => 'Can view user subscriptions',
                'group' => 'Subscriptions',
            ],
            'subscriptions.subscriptions.create' => [
                'label' => 'Create Subscriptions',
                'description' => 'Can create subscriptions for users',
                'group' => 'Subscriptions',
            ],
            'subscriptions.subscriptions.edit' => [
                'label' => 'Edit Subscriptions',
                'description' => 'Can modify subscriptions',
                'group' => 'Subscriptions',
            ],
            'subscriptions.subscriptions.cancel' => [
                'label' => 'Cancel Subscriptions',
                'description' => 'Can cancel user subscriptions',
                'group' => 'Subscriptions',
                'is_dangerous' => true,
            ],
            // Invoices
            'subscriptions.invoices.view' => [
                'label' => 'View Invoices',
                'description' => 'Can view subscription invoices',
                'group' => 'Subscriptions',
            ],
            'subscriptions.invoices.manage' => [
                'label' => 'Manage Invoices',
                'description' => 'Can manage and process invoices',
                'group' => 'Subscriptions',
            ],
            // Settings
            'subscriptions.settings' => [
                'label' => 'Subscription Settings',
                'description' => 'Can configure subscription settings',
                'group' => 'Subscriptions',
            ],
        ];
    }

    /**
     * Get menu items registered by this plugin.
     */
    public function getMenuItems(): array
    {
        return [
            [
                'id' => 'subscriptions',
                'label' => 'Subscriptions',
                'icon' => 'creditCard',
                'permission' => 'subscriptions.plans.view',
                'position' => 15,
                'children' => [
                    [
                        'id' => 'subscriptions.dashboard',
                        'label' => 'Dashboard',
                        'icon' => 'layoutDashboard',
                        'route' => 'plugins.subscriptions.index',
                        'permission' => 'subscriptions.plans.view',
                    ],
                    [
                        'id' => 'subscriptions.plans',
                        'label' => 'Plans',
                        'icon' => 'package',
                        'route' => 'plugins.subscriptions.plans.index',
                        'permission' => 'subscriptions.plans.view',
                    ],
                    [
                        'id' => 'subscriptions.subscriptions',
                        'label' => 'Subscriptions',
                        'icon' => 'repeat',
                        'route' => 'plugins.subscriptions.subscriptions.index',
                        'permission' => 'subscriptions.subscriptions.view',
                    ],
                    [
                        'id' => 'subscriptions.invoices',
                        'label' => 'Invoices',
                        'icon' => 'fileText',
                        'route' => 'plugins.subscriptions.invoices.index',
                        'permission' => 'subscriptions.invoices.view',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get dashboard widgets registered by this plugin.
     */
    public function getWidgets(): array
    {
        return [
            [
                'id' => 'subscriptions-stats',
                'name' => 'Subscription Statistics',
                'description' => 'Overview of subscription metrics',
                'component' => 'subscriptions::widgets.stats',
                'permissions' => ['subscriptions.subscriptions.view'],
                'default_width' => 6,
                'default_height' => 2,
            ],
            [
                'id' => 'subscriptions-revenue',
                'name' => 'Revenue Overview',
                'description' => 'Monthly recurring revenue chart',
                'component' => 'subscriptions::widgets.revenue',
                'permissions' => ['subscriptions.invoices.view'],
                'default_width' => 6,
                'default_height' => 3,
            ],
            [
                'id' => 'subscriptions-recent',
                'name' => 'Recent Subscriptions',
                'description' => 'Latest subscription activity',
                'component' => 'subscriptions::widgets.recent',
                'permissions' => ['subscriptions.subscriptions.view'],
                'default_width' => 6,
                'default_height' => 3,
            ],
        ];
    }

    /**
     * Get the settings fields definition for this plugin.
     */
    public function getSettingsFields(): array
    {
        return [
            'tabs' => [
                'general' => ['label' => 'General', 'icon' => 'settings'],
                'billing' => ['label' => 'Billing', 'icon' => 'creditCard'],
                'trials' => ['label' => 'Trials & Grace', 'icon' => 'clock'],
                'notifications' => ['label' => 'Notifications', 'icon' => 'bell'],
            ],
            'fields' => [
                [
                    'key' => 'currency',
                    'type' => 'select',
                    'label' => 'Currency',
                    'tab' => 'general',
                    'default' => 'USD',
                    'hint' => 'Default currency for all plans',
                    'options' => [
                        'USD' => 'US Dollar (USD)',
                        'EUR' => 'Euro (EUR)',
                        'GBP' => 'British Pound (GBP)',
                        'SAR' => 'Saudi Riyal (SAR)',
                        'AED' => 'UAE Dirham (AED)',
                        'EGP' => 'Egyptian Pound (EGP)',
                    ],
                ],
                [
                    'key' => 'currency_symbol',
                    'type' => 'text',
                    'label' => 'Currency Symbol',
                    'tab' => 'general',
                    'default' => '$',
                    'hint' => 'Symbol to display with prices',
                ],
                [
                    'key' => 'allow_plan_changes',
                    'type' => 'checkbox',
                    'label' => 'Allow Plan Changes',
                    'tab' => 'billing',
                    'default' => true,
                    'hint' => 'Allow users to upgrade/downgrade their plan',
                ],
                [
                    'key' => 'prorate_plan_changes',
                    'type' => 'checkbox',
                    'label' => 'Prorate Plan Changes',
                    'tab' => 'billing',
                    'default' => true,
                    'hint' => 'Apply prorated charges when changing plans',
                ],
                [
                    'key' => 'auto_cancel_expired',
                    'type' => 'checkbox',
                    'label' => 'Auto-Cancel Expired Subscriptions',
                    'tab' => 'billing',
                    'default' => false,
                    'hint' => 'Automatically cancel subscriptions after grace period',
                ],
                [
                    'key' => 'trial_days',
                    'type' => 'number',
                    'label' => 'Default Trial Days',
                    'tab' => 'trials',
                    'default' => 14,
                    'min' => 0,
                    'max' => 90,
                    'hint' => 'Number of free trial days for new subscriptions',
                ],
                [
                    'key' => 'grace_period_days',
                    'type' => 'number',
                    'label' => 'Grace Period Days',
                    'tab' => 'trials',
                    'default' => 3,
                    'min' => 0,
                    'max' => 30,
                    'hint' => 'Days after expiration before subscription is cancelled',
                ],
                [
                    'key' => 'send_renewal_reminders',
                    'type' => 'checkbox',
                    'label' => 'Send Renewal Reminders',
                    'tab' => 'notifications',
                    'default' => true,
                    'hint' => 'Email users before subscription renewal',
                ],
                [
                    'key' => 'reminder_days_before',
                    'type' => 'number',
                    'label' => 'Reminder Days Before Renewal',
                    'tab' => 'notifications',
                    'default' => 7,
                    'min' => 1,
                    'max' => 30,
                    'hint' => 'Days before renewal to send reminder',
                ],
                [
                    'key' => 'notify_on_expiration',
                    'type' => 'checkbox',
                    'label' => 'Notify on Expiration',
                    'tab' => 'notifications',
                    'default' => true,
                    'hint' => 'Email users when subscription expires',
                ],
                [
                    'key' => 'notify_admins_on_new',
                    'type' => 'checkbox',
                    'label' => 'Notify Admins on New Subscription',
                    'tab' => 'notifications',
                    'default' => false,
                    'hint' => 'Send email to admins when new subscription is created',
                ],
            ],
        ];
    }

    /**
     * Check if this plugin has a dashboard.
     */
    public function hasDashboard(): bool
    {
        return true;
    }

    /**
     * Get the dashboard icon.
     */
    public function getDashboardIcon(): string
    {
        return 'creditCard';
    }

    /**
     * Get the dashboard title.
     */
    public function getDashboardTitle(): string
    {
        return 'Subscriptions Dashboard';
    }

    /**
     * Get API endpoints registered by this plugin.
     */
    public function getApiEndpoints(): array
    {
        return [
            // Plans
            ['method' => 'GET', 'path' => '/plans', 'name' => 'List Plans', 'permission' => 'subscriptions.plans.view'],
            ['method' => 'POST', 'path' => '/plans', 'name' => 'Create Plan', 'permission' => 'subscriptions.plans.create'],
            ['method' => 'GET', 'path' => '/plans/{id}', 'name' => 'Get Plan', 'permission' => 'subscriptions.plans.view'],
            ['method' => 'PUT', 'path' => '/plans/{id}', 'name' => 'Update Plan', 'permission' => 'subscriptions.plans.edit'],
            ['method' => 'DELETE', 'path' => '/plans/{id}', 'name' => 'Delete Plan', 'permission' => 'subscriptions.plans.delete'],
            // Subscriptions
            ['method' => 'GET', 'path' => '/subscriptions', 'name' => 'List Subscriptions', 'permission' => 'subscriptions.subscriptions.view'],
            ['method' => 'POST', 'path' => '/subscriptions', 'name' => 'Create Subscription', 'permission' => 'subscriptions.subscriptions.create'],
            ['method' => 'GET', 'path' => '/subscriptions/{id}', 'name' => 'Get Subscription', 'permission' => 'subscriptions.subscriptions.view'],
            ['method' => 'PUT', 'path' => '/subscriptions/{id}', 'name' => 'Update Subscription', 'permission' => 'subscriptions.subscriptions.edit'],
            ['method' => 'POST', 'path' => '/subscriptions/{id}/cancel', 'name' => 'Cancel Subscription', 'permission' => 'subscriptions.subscriptions.cancel'],
            ['method' => 'POST', 'path' => '/subscriptions/{id}/renew', 'name' => 'Renew Subscription', 'permission' => 'subscriptions.subscriptions.edit'],
            // Invoices
            ['method' => 'GET', 'path' => '/invoices', 'name' => 'List Invoices', 'permission' => 'subscriptions.invoices.view'],
            ['method' => 'GET', 'path' => '/invoices/{id}', 'name' => 'Get Invoice', 'permission' => 'subscriptions.invoices.view'],
        ];
    }

    /**
     * Get workflow triggers registered by this plugin.
     */
    public function getWorkflowTriggers(): array
    {
        return [
            'subscription.created' => [
                'label' => 'Subscription Created',
                'description' => 'Triggered when a new subscription is created',
                'payload' => ['subscription_id', 'user_id', 'plan_id'],
            ],
            'subscription.renewed' => [
                'label' => 'Subscription Renewed',
                'description' => 'Triggered when a subscription is renewed',
                'payload' => ['subscription_id', 'user_id'],
            ],
            'subscription.cancelled' => [
                'label' => 'Subscription Cancelled',
                'description' => 'Triggered when a subscription is cancelled',
                'payload' => ['subscription_id', 'user_id', 'reason'],
            ],
            'subscription.expired' => [
                'label' => 'Subscription Expired',
                'description' => 'Triggered when a subscription expires',
                'payload' => ['subscription_id', 'user_id'],
            ],
            'subscription.trial_ending' => [
                'label' => 'Trial Ending Soon',
                'description' => 'Triggered when trial period is about to end',
                'payload' => ['subscription_id', 'user_id', 'days_remaining'],
            ],
            'invoice.created' => [
                'label' => 'Invoice Created',
                'description' => 'Triggered when an invoice is generated',
                'payload' => ['invoice_id', 'subscription_id', 'amount'],
            ],
            'invoice.paid' => [
                'label' => 'Invoice Paid',
                'description' => 'Triggered when an invoice is marked as paid',
                'payload' => ['invoice_id', 'subscription_id', 'amount'],
            ],
        ];
    }

    /**
     * Get scheduled tasks registered by this plugin.
     */
    public function getScheduledTasks(): array
    {
        return [
            [
                'name' => 'Process Subscription Renewals',
                'description' => 'Automatically renew subscriptions that are due',
                'command' => 'subscriptions:process-renewals',
                'schedule' => 'daily',
                'enabled' => true,
            ],
            [
                'name' => 'Send Renewal Reminders',
                'description' => 'Send email reminders for upcoming renewals',
                'command' => 'subscriptions:send-reminders',
                'schedule' => 'daily',
                'enabled' => true,
            ],
            [
                'name' => 'Expire Overdue Subscriptions',
                'description' => 'Mark subscriptions as expired after grace period',
                'command' => 'subscriptions:expire-overdue',
                'schedule' => 'daily',
                'enabled' => true,
            ],
        ];
    }

    /**
     * Get entities registered by this plugin.
     */
    public function getEntities(): array
    {
        return [
            'plan' => [
                'label' => 'Plan',
                'label_plural' => 'Plans',
                'model' => 'Subscriptions\\Models\\Plan',
                'table' => 'plans',
                'icon' => 'package',
                'searchable' => true,
            ],
            'subscription' => [
                'label' => 'Subscription',
                'label_plural' => 'Subscriptions',
                'model' => 'Subscriptions\\Models\\Subscription',
                'table' => 'subscriptions',
                'icon' => 'repeat',
                'searchable' => true,
            ],
            'invoice' => [
                'label' => 'Invoice',
                'label_plural' => 'Invoices',
                'model' => 'Subscriptions\\Models\\Invoice',
                'table' => 'subscription_invoices',
                'icon' => 'fileText',
                'searchable' => true,
            ],
        ];
    }
}

