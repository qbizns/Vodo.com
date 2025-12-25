<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Dashboard Builder.
 *
 * Manages dashboard layouts, widgets, and user customization.
 */
interface DashboardBuilderContract
{
    /**
     * Register a dashboard template.
     *
     * @param string $name Dashboard name
     * @param array $config Dashboard configuration
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function register(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Get a dashboard configuration.
     *
     * @param string $name Dashboard name
     * @return array|null
     */
    public function get(string $name): ?array;

    /**
     * Get all dashboards.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Render a dashboard.
     *
     * @param string $name Dashboard name
     * @param array $context Render context
     * @return array Rendered dashboard data
     */
    public function render(string $name, array $context = []): array;

    /**
     * Get user's custom dashboard layout.
     *
     * @param int $userId User ID
     * @param string $name Dashboard name
     * @return array|null
     */
    public function getUserLayout(int $userId, string $name): ?array;

    /**
     * Save user's custom dashboard layout.
     *
     * @param int $userId User ID
     * @param string $name Dashboard name
     * @param array $layout Layout configuration
     * @return void
     */
    public function saveUserLayout(int $userId, string $name, array $layout): void;

    /**
     * Add a widget to a dashboard.
     *
     * @param string $name Dashboard name
     * @param string $widgetName Widget name
     * @param array $config Widget configuration
     * @return self
     */
    public function addWidget(string $name, string $widgetName, array $config = []): self;

    /**
     * Remove a widget from a dashboard.
     *
     * @param string $name Dashboard name
     * @param string $widgetName Widget name
     * @return bool
     */
    public function removeWidget(string $name, string $widgetName): bool;

    /**
     * Get available widgets for a dashboard.
     *
     * @param string $name Dashboard name
     * @return Collection
     */
    public function getAvailableWidgets(string $name): Collection;
}
