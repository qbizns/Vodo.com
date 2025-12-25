<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

use Illuminate\Support\Collection;

/**
 * Contract for Integration Triggers.
 *
 * Triggers are events that START an automation flow.
 * Examples: "New message received", "Payment completed", "Form submitted"
 *
 * Types of triggers:
 * - webhook: Receives external webhooks
 * - polling: Periodically checks for changes
 * - instant: Real-time via websocket/SSE
 * - app: Internal application events
 *
 * @example Telegram New Message Trigger
 * ```php
 * class NewMessageTrigger implements TriggerContract
 * {
 *     public function getName(): string { return 'new_message'; }
 *     public function getType(): string { return 'webhook'; }
 *     // ...
 * }
 * ```
 */
interface TriggerContract
{
    // =========================================================================
    // IDENTITY
    // =========================================================================

    /**
     * Get trigger identifier (unique within connector).
     *
     * @return string e.g., 'new_message', 'new_order', 'payment_received'
     */
    public function getName(): string;

    /**
     * Get human-readable display name.
     *
     * @return string e.g., 'New Message', 'New Order', 'Payment Received'
     */
    public function getDisplayName(): string;

    /**
     * Get trigger description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get parent connector name.
     *
     * @return string
     */
    public function getConnectorName(): string;

    // =========================================================================
    // TYPE & BEHAVIOR
    // =========================================================================

    /**
     * Get trigger type.
     *
     * @return string One of: 'webhook', 'polling', 'instant', 'app'
     */
    public function getType(): string;

    /**
     * Get polling interval (for polling triggers).
     *
     * @return int|null Seconds between polls, null for non-polling
     */
    public function getPollingInterval(): ?int;

    /**
     * Can this trigger be manually tested?
     *
     * @return bool
     */
    public function canTest(): bool;

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Get input fields for trigger configuration.
     * User configures these when setting up the trigger.
     *
     * @return array Field definitions
     */
    public function getInputFields(): array;

    /**
     * Get dynamic input fields based on previous selections.
     *
     * @param array $values Current field values
     * @param array $credentials Connection credentials
     * @return array Additional field definitions
     */
    public function getDynamicInputFields(array $values, array $credentials): array;

    /**
     * Get output fields this trigger produces.
     * Defines the data structure passed to actions.
     *
     * @return array Field definitions
     */
    public function getOutputFields(): array;

    /**
     * Get sample output data for testing.
     *
     * @return array
     */
    public function getSampleOutput(): array;

    // =========================================================================
    // WEBHOOK HANDLING (for webhook triggers)
    // =========================================================================

    /**
     * Register webhook with external service.
     *
     * @param array $credentials Connection credentials
     * @param string $webhookUrl Our webhook URL to register
     * @param array $config Trigger configuration
     * @return array Registration result ['webhook_id' => string, ...]
     */
    public function registerWebhook(array $credentials, string $webhookUrl, array $config): array;

    /**
     * Unregister webhook from external service.
     *
     * @param array $credentials Connection credentials
     * @param string $webhookId Webhook ID from registration
     * @return bool
     */
    public function unregisterWebhook(array $credentials, string $webhookId): bool;

    /**
     * Process incoming webhook payload.
     *
     * @param array $payload Raw webhook payload
     * @param array $headers Webhook headers
     * @param array $config Trigger configuration
     * @return array|null Parsed data or null if should be ignored
     */
    public function processWebhook(array $payload, array $headers, array $config): ?array;

    /**
     * Verify webhook signature/authenticity.
     *
     * @param string $payload Raw payload
     * @param array $headers Request headers
     * @param array $credentials Connection credentials
     * @return bool
     */
    public function verifyWebhook(string $payload, array $headers, array $credentials): bool;

    // =========================================================================
    // POLLING (for polling triggers)
    // =========================================================================

    /**
     * Poll for new data.
     *
     * @param array $credentials Connection credentials
     * @param array $config Trigger configuration
     * @param array $state Previous poll state (cursor, last_id, etc.)
     * @return array ['items' => array, 'state' => array]
     */
    public function poll(array $credentials, array $config, array $state): array;

    /**
     * Get deduplication key from item.
     * Used to prevent processing same item twice.
     *
     * @param array $item Polled item
     * @return string Unique identifier
     */
    public function getDeduplicationKey(array $item): string;

    // =========================================================================
    // FILTERING
    // =========================================================================

    /**
     * Get available filter fields.
     *
     * @return array
     */
    public function getFilterFields(): array;

    /**
     * Apply filters to determine if trigger should fire.
     *
     * @param array $data Trigger data
     * @param array $filters Configured filters
     * @return bool Should trigger fire?
     */
    public function applyFilters(array $data, array $filters): bool;
}
