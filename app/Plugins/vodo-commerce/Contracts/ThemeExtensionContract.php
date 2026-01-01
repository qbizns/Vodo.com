<?php

declare(strict_types=1);

namespace VodoCommerce\Contracts;

/**
 * ThemeExtensionContract - Contract for commerce theme extensions.
 *
 * Defines the interface for extending commerce storefront themes with:
 * - Theme slots for content injection
 * - Asset registration (CSS, JS)
 * - Settings schema for customization
 * - Template overrides
 *
 * Implementing plugins can extend the storefront appearance and behavior
 * without modifying core theme files.
 */
interface ThemeExtensionContract
{
    /**
     * Get the unique identifier for this extension.
     */
    public function getIdentifier(): string;

    /**
     * Get the display name.
     */
    public function getName(): string;

    /**
     * Get extension description.
     */
    public function getDescription(): ?string;

    /**
     * Get the plugin that provides this extension.
     */
    public function getPluginSlug(): string;

    /**
     * Get supported theme slot identifiers.
     *
     * Returns array of slot names where this extension can inject content.
     *
     * @return array<string>
     */
    public function getSupportedSlots(): array;

    /**
     * Render content for a specific slot.
     *
     * @param string $slot Slot identifier
     * @param array $context View context data
     * @return string|null Rendered HTML or null if not handling this slot
     */
    public function renderSlot(string $slot, array $context = []): ?string;

    /**
     * Get CSS assets to include.
     *
     * @return array<string, array{url: string, priority?: int, media?: string}>
     */
    public function getStyles(): array;

    /**
     * Get JavaScript assets to include.
     *
     * @return array<string, array{url: string, priority?: int, defer?: bool, async?: bool}>
     */
    public function getScripts(): array;

    /**
     * Get settings schema for this extension.
     *
     * Returns a JSON Schema compatible definition for extension settings.
     *
     * @return array|null
     */
    public function getSettingsSchema(): ?array;

    /**
     * Get default settings values.
     *
     * @return array<string, mixed>
     */
    public function getDefaultSettings(): array;

    /**
     * Get current settings for a store.
     *
     * @param int $storeId
     * @return array<string, mixed>
     */
    public function getSettings(int $storeId): array;

    /**
     * Save settings for a store.
     *
     * @param int $storeId
     * @param array<string, mixed> $settings
     */
    public function saveSettings(int $storeId, array $settings): void;

    /**
     * Check if extension is enabled for a store.
     *
     * @param int $storeId
     */
    public function isEnabled(int $storeId): bool;

    /**
     * Enable the extension for a store.
     *
     * @param int $storeId
     */
    public function enable(int $storeId): void;

    /**
     * Disable the extension for a store.
     *
     * @param int $storeId
     */
    public function disable(int $storeId): void;
}
