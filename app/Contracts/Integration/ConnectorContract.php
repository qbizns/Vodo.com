<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

use Illuminate\Support\Collection;

/**
 * Contract for Integration Connectors.
 *
 * Every integration plugin (Telegram, Slack, Gmail, etc.) MUST implement this.
 * This is the main entry point for any external service integration.
 *
 * @example Plugin Implementation
 * ```php
 * class TelegramConnector implements ConnectorContract
 * {
 *     public function getName(): string { return 'telegram'; }
 *     public function getDisplayName(): string { return 'Telegram'; }
 *     public function getAuthType(): string { return 'api_key'; }
 *     // ...
 * }
 * ```
 */
interface ConnectorContract
{
    // =========================================================================
    // IDENTITY
    // =========================================================================

    /**
     * Get unique connector identifier (slug).
     * Must be unique across all connectors.
     *
     * @return string e.g., 'telegram', 'slack', 'gmail'
     */
    public function getName(): string;

    /**
     * Get human-readable display name.
     *
     * @return string e.g., 'Telegram', 'Slack', 'Gmail'
     */
    public function getDisplayName(): string;

    /**
     * Get connector description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get connector icon (path or URL).
     *
     * @return string
     */
    public function getIcon(): string;

    /**
     * Get connector brand color.
     *
     * @return string Hex color e.g., '#0088cc'
     */
    public function getColor(): string;

    /**
     * Get connector category.
     *
     * @return string e.g., 'communication', 'crm', 'payment', 'social'
     */
    public function getCategory(): string;

    /**
     * Get connector documentation URL.
     *
     * @return string|null
     */
    public function getDocumentationUrl(): ?string;

    // =========================================================================
    // AUTHENTICATION
    // =========================================================================

    /**
     * Get authentication type required.
     *
     * @return string One of: 'none', 'api_key', 'oauth1', 'oauth2', 'basic', 'custom'
     */
    public function getAuthType(): string;

    /**
     * Get authentication configuration.
     * Defines what fields are needed for auth.
     *
     * @return array
     */
    public function getAuthConfig(): array;

    /**
     * Get OAuth configuration (if OAuth type).
     *
     * @return array|null
     */
    public function getOAuthConfig(): ?array;

    /**
     * Test if credentials are valid.
     *
     * @param array $credentials Decrypted credentials
     * @return array ['success' => bool, 'message' => string, 'user' => ?array]
     */
    public function testConnection(array $credentials): array;

    // =========================================================================
    // CAPABILITIES
    // =========================================================================

    /**
     * Get all triggers this connector provides.
     *
     * @return Collection<TriggerContract>
     */
    public function getTriggers(): Collection;

    /**
     * Get all actions this connector provides.
     *
     * @return Collection<ActionContract>
     */
    public function getActions(): Collection;

    /**
     * Get a specific trigger by name.
     *
     * @param string $name Trigger name
     * @return TriggerContract|null
     */
    public function getTrigger(string $name): ?TriggerContract;

    /**
     * Get a specific action by name.
     *
     * @param string $name Action name
     * @return ActionContract|null
     */
    public function getAction(string $name): ?ActionContract;

    /**
     * Check if connector supports webhooks.
     *
     * @return bool
     */
    public function supportsWebhooks(): bool;

    /**
     * Check if connector supports real-time events.
     *
     * @return bool
     */
    public function supportsRealtime(): bool;

    // =========================================================================
    // HTTP CLIENT
    // =========================================================================

    /**
     * Get base URL for API requests.
     *
     * @return string
     */
    public function getBaseUrl(): string;

    /**
     * Get default headers for API requests.
     *
     * @param array $credentials Decrypted credentials
     * @return array
     */
    public function getDefaultHeaders(array $credentials): array;

    /**
     * Get rate limit configuration.
     *
     * @return array ['requests' => int, 'per_seconds' => int]
     */
    public function getRateLimits(): array;

    // =========================================================================
    // LIFECYCLE
    // =========================================================================

    /**
     * Called when connection is created.
     *
     * @param array $credentials
     * @return void
     */
    public function onConnect(array $credentials): void;

    /**
     * Called when connection is disconnected.
     *
     * @param array $credentials
     * @return void
     */
    public function onDisconnect(array $credentials): void;

    /**
     * Get connector version.
     *
     * @return string
     */
    public function getVersion(): string;
}
