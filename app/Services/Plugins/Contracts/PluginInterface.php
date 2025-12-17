<?php

namespace App\Services\Plugins\Contracts;

use App\Models\Plugin;

interface PluginInterface
{
    /**
     * Get the plugin model instance.
     *
     * @return Plugin
     */
    public function getPlugin(): Plugin;

    /**
     * Set the plugin model instance.
     *
     * @param Plugin $plugin
     * @return void
     */
    public function setPlugin(Plugin $plugin): void;

    /**
     * Register services, bindings, or configuration.
     * Called when the plugin is being loaded.
     *
     * @return void
     */
    public function register(): void;

    /**
     * Bootstrap the plugin (routes, views, hooks, etc.).
     * Called after all plugins are registered.
     *
     * @return void
     */
    public function boot(): void;

    /**
     * Called when the plugin is being activated.
     * Use for one-time setup tasks like creating default options.
     *
     * @return void
     */
    public function activate(): void;

    /**
     * Called when the plugin is being deactivated.
     * Use for cleanup tasks that should happen on deactivation.
     *
     * @return void
     */
    public function deactivate(): void;

    /**
     * Called when the plugin is being uninstalled.
     * Use for complete cleanup (remove options, clear caches, etc.).
     * Note: Migrations are rolled back separately.
     *
     * @return void
     */
    public function uninstall(): void;

    /**
     * Get the plugin's base path.
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Get the plugin's views path.
     *
     * @return string
     */
    public function getViewsPath(): string;

    /**
     * Get the plugin's migrations path.
     *
     * @return string
     */
    public function getMigrationsPath(): string;

    /**
     * Get the plugin's routes file path.
     *
     * @return string|null
     */
    public function getRoutesPath(): ?string;

    /**
     * Check if the plugin has a settings page.
     *
     * @return bool
     */
    public function hasSettingsPage(): bool;

    /**
     * Get the plugin's settings view name.
     *
     * @return string|null
     */
    public function getSettingsView(): ?string;

    /**
     * Get the plugin's settings fields definition.
     *
     * @return array
     */
    public function getSettingsFields(): array;

    /**
     * Get the plugin's settings icon.
     *
     * @return string
     */
    public function getSettingsIcon(): string;

    /**
     * Check if the plugin has a dashboard.
     *
     * @return bool
     */
    public function hasDashboard(): bool;

    /**
     * Get the plugin's dashboard widgets definition.
     *
     * @return array
     */
    public function getDashboardWidgets(): array;

    /**
     * Get the plugin's dashboard icon.
     *
     * @return string
     */
    public function getDashboardIcon(): string;

    /**
     * Get the plugin's dashboard title.
     *
     * @return string
     */
    public function getDashboardTitle(): string;

    /**
     * Get widget data for a specific widget.
     *
     * @param string $widgetId
     * @return array
     */
    public function getWidgetData(string $widgetId): array;
}
