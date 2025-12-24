<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Menu Registry.
 *
 * Manages dynamic menu registration and rendering.
 */
interface MenuRegistryContract
{
    /**
     * Register a menu location.
     *
     * @param string $location Menu location identifier
     * @param array $config Location configuration
     * @return self
     */
    public function registerLocation(string $location, array $config = []): self;

    /**
     * Register a menu item.
     *
     * @param string $location Menu location
     * @param array $item Menu item configuration
     * @param string|null $pluginSlug Owner plugin slug
     * @return self
     */
    public function addItem(string $location, array $item, ?string $pluginSlug = null): self;

    /**
     * Remove a menu item.
     *
     * @param string $location Menu location
     * @param string $itemId Item identifier
     * @return bool
     */
    public function removeItem(string $location, string $itemId): bool;

    /**
     * Get all items for a location.
     *
     * @param string $location Menu location
     * @param array $context Rendering context (user, permissions, etc.)
     * @return Collection
     */
    public function getItems(string $location, array $context = []): Collection;

    /**
     * Get the menu tree for a location.
     *
     * @param string $location Menu location
     * @param array $context Rendering context
     * @return array Nested menu structure
     */
    public function getTree(string $location, array $context = []): array;

    /**
     * Check if a location exists.
     *
     * @param string $location Menu location
     */
    public function hasLocation(string $location): bool;

    /**
     * Get all registered locations.
     *
     * @return array<string>
     */
    public function getLocations(): array;

    /**
     * Update a menu item's badge.
     *
     * @param string $location Menu location
     * @param string $itemId Item identifier
     * @param int|string|null $badge Badge value (null to remove)
     * @return self
     */
    public function setBadge(string $location, string $itemId, int|string|null $badge): self;

    /**
     * Clear the menu cache.
     *
     * @param string|null $location Specific location (null for all)
     */
    public function clearCache(?string $location = null): void;
}
