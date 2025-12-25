<?php

declare(strict_types=1);

namespace App\Services\Integration\Connector;

use App\Contracts\Integration\TriggerContract;
use App\Contracts\Integration\ConnectorContract;

/**
 * Abstract Trigger Base Class.
 *
 * Plugins extend this to create triggers.
 *
 * @example Telegram New Message Trigger
 * ```php
 * class NewMessageTrigger extends AbstractTrigger
 * {
 *     public function getName(): string { return 'new_message'; }
 *     public function getDisplayName(): string { return 'New Message'; }
 *     public function getType(): string { return 'webhook'; }
 *
 *     public function getInputFields(): array {
 *         return [
 *             'chat_type' => [
 *                 'type' => 'select',
 *                 'label' => 'Chat Type',
 *                 'options' => ['all', 'private', 'group'],
 *             ],
 *         ];
 *     }
 *
 *     public function processWebhook(array $payload, array $headers, array $config): ?array {
 *         if (!isset($payload['message'])) return null;
 *         return [
 *             'message_id' => $payload['message']['message_id'],
 *             'text' => $payload['message']['text'] ?? '',
 *             'from' => $payload['message']['from'],
 *             'chat' => $payload['message']['chat'],
 *         ];
 *     }
 * }
 * ```
 */
abstract class AbstractTrigger implements TriggerContract
{
    /**
     * Parent connector.
     */
    protected ConnectorContract $connector;

    public function __construct(ConnectorContract $connector)
    {
        $this->connector = $connector;
    }

    // =========================================================================
    // IDENTITY (with defaults)
    // =========================================================================

    public function getDescription(): string
    {
        return "Triggers when {$this->getDisplayName()}";
    }

    public function getConnectorName(): string
    {
        return $this->connector->getName();
    }

    // =========================================================================
    // TYPE & BEHAVIOR (with defaults)
    // =========================================================================

    public function getPollingInterval(): ?int
    {
        return $this->getType() === 'polling' ? 300 : null; // 5 minutes default
    }

    public function canTest(): bool
    {
        return true;
    }

    // =========================================================================
    // CONFIGURATION (with defaults)
    // =========================================================================

    public function getInputFields(): array
    {
        return [];
    }

    public function getDynamicInputFields(array $values, array $credentials): array
    {
        return [];
    }

    public function getOutputFields(): array
    {
        return [];
    }

    public function getSampleOutput(): array
    {
        return [];
    }

    // =========================================================================
    // WEBHOOK HANDLING (defaults for non-webhook triggers)
    // =========================================================================

    public function registerWebhook(array $credentials, string $webhookUrl, array $config): array
    {
        throw new \RuntimeException('Webhook registration not supported by this trigger');
    }

    public function unregisterWebhook(array $credentials, string $webhookId): bool
    {
        throw new \RuntimeException('Webhook unregistration not supported by this trigger');
    }

    public function processWebhook(array $payload, array $headers, array $config): ?array
    {
        return $payload;
    }

    public function verifyWebhook(string $payload, array $headers, array $credentials): bool
    {
        return true;
    }

    // =========================================================================
    // POLLING (defaults for non-polling triggers)
    // =========================================================================

    public function poll(array $credentials, array $config, array $state): array
    {
        return ['items' => [], 'state' => $state];
    }

    public function getDeduplicationKey(array $item): string
    {
        return md5(json_encode($item));
    }

    // =========================================================================
    // FILTERING (with defaults)
    // =========================================================================

    public function getFilterFields(): array
    {
        return [];
    }

    public function applyFilters(array $data, array $filters): bool
    {
        if (empty($filters)) {
            return true;
        }

        foreach ($filters as $field => $filter) {
            $value = data_get($data, $field);

            if (!$this->matchesFilter($value, $filter)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if value matches filter condition.
     */
    protected function matchesFilter(mixed $value, array $filter): bool
    {
        $operator = $filter['operator'] ?? 'equals';
        $filterValue = $filter['value'] ?? null;

        return match ($operator) {
            'equals' => $value == $filterValue,
            'not_equals' => $value != $filterValue,
            'contains' => str_contains((string)$value, (string)$filterValue),
            'not_contains' => !str_contains((string)$value, (string)$filterValue),
            'starts_with' => str_starts_with((string)$value, (string)$filterValue),
            'ends_with' => str_ends_with((string)$value, (string)$filterValue),
            'greater_than' => $value > $filterValue,
            'less_than' => $value < $filterValue,
            'is_empty' => empty($value),
            'is_not_empty' => !empty($value),
            'in' => in_array($value, (array)$filterValue),
            'not_in' => !in_array($value, (array)$filterValue),
            'regex' => preg_match($filterValue, (string)$value) === 1,
            default => true,
        };
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Get parent connector.
     */
    protected function getConnector(): ConnectorContract
    {
        return $this->connector;
    }

    /**
     * Make HTTP request via connector.
     */
    protected function makeRequest(
        string $method,
        string $endpoint,
        array $credentials,
        array $data = []
    ): array {
        return $this->connector->makeRequest($method, $endpoint, $credentials, $data);
    }

    /**
     * Export trigger definition.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'connector' => $this->getConnectorName(),
            'type' => $this->getType(),
            'polling_interval' => $this->getPollingInterval(),
            'can_test' => $this->canTest(),
            'input_fields' => $this->getInputFields(),
            'output_fields' => $this->getOutputFields(),
            'filter_fields' => $this->getFilterFields(),
            'sample_output' => $this->getSampleOutput(),
        ];
    }
}
