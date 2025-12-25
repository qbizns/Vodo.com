<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

/**
 * Contract for Integration Actions.
 *
 * Actions are operations performed on external services.
 * Examples: "Send message", "Create record", "Update status"
 *
 * @example Telegram Send Message Action
 * ```php
 * class SendMessageAction implements ActionContract
 * {
 *     public function getName(): string { return 'send_message'; }
 *     public function execute(array $credentials, array $input): array {
 *         // Send message via Telegram API
 *         return ['message_id' => '123', 'sent_at' => '...'];
 *     }
 * }
 * ```
 */
interface ActionContract
{
    // =========================================================================
    // IDENTITY
    // =========================================================================

    /**
     * Get action identifier (unique within connector).
     *
     * @return string e.g., 'send_message', 'create_contact', 'charge_card'
     */
    public function getName(): string;

    /**
     * Get human-readable display name.
     *
     * @return string e.g., 'Send Message', 'Create Contact', 'Charge Card'
     */
    public function getDisplayName(): string;

    /**
     * Get action description.
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

    /**
     * Get action group/category within connector.
     *
     * @return string|null e.g., 'messages', 'contacts', 'payments'
     */
    public function getGroup(): ?string;

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Get input fields for this action.
     * Defines what data the action needs.
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
     * Get output fields this action produces.
     *
     * @return array Field definitions
     */
    public function getOutputFields(): array;

    /**
     * Validate input before execution.
     *
     * @param array $input Input data
     * @return array Validation errors (empty if valid)
     */
    public function validateInput(array $input): array;

    // =========================================================================
    // EXECUTION
    // =========================================================================

    /**
     * Execute the action.
     *
     * @param array $credentials Decrypted connection credentials
     * @param array $input Action input data
     * @return array Action output data
     * @throws \App\Exceptions\Integration\ActionExecutionException
     */
    public function execute(array $credentials, array $input): array;

    /**
     * Can this action be safely retried on failure?
     *
     * @return bool
     */
    public function isIdempotent(): bool;

    /**
     * Get maximum retry attempts.
     *
     * @return int
     */
    public function getMaxRetries(): int;

    /**
     * Get retry delay strategy.
     *
     * @return array ['type' => 'fixed'|'exponential', 'delay' => int]
     */
    public function getRetryStrategy(): array;

    // =========================================================================
    // RATE LIMITING
    // =========================================================================

    /**
     * Get action-specific rate limit (overrides connector default).
     *
     * @return array|null ['requests' => int, 'per_seconds' => int]
     */
    public function getRateLimit(): ?array;

    /**
     * Get estimated API calls this action makes.
     *
     * @return int
     */
    public function getApiCallCount(): int;

    // =========================================================================
    // BULK OPERATIONS
    // =========================================================================

    /**
     * Does this action support bulk/batch execution?
     *
     * @return bool
     */
    public function supportsBulk(): bool;

    /**
     * Get maximum batch size.
     *
     * @return int
     */
    public function getMaxBatchSize(): int;

    /**
     * Execute action in bulk.
     *
     * @param array $credentials Connection credentials
     * @param array $items Array of input items
     * @return array Results for each item
     */
    public function executeBulk(array $credentials, array $items): array;

    // =========================================================================
    // TESTING
    // =========================================================================

    /**
     * Get sample input data for testing.
     *
     * @return array
     */
    public function getSampleInput(): array;

    /**
     * Get sample output data for testing.
     *
     * @return array
     */
    public function getSampleOutput(): array;
}
