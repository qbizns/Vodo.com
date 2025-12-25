<?php

declare(strict_types=1);

namespace App\Services\Integration\Connector;

use App\Contracts\Integration\ActionContract;
use App\Contracts\Integration\ConnectorContract;

/**
 * Abstract Action Base Class.
 *
 * Plugins extend this to create actions.
 *
 * @example Telegram Send Message Action
 * ```php
 * class SendMessageAction extends AbstractAction
 * {
 *     public function getName(): string { return 'send_message'; }
 *     public function getDisplayName(): string { return 'Send Message'; }
 *
 *     public function getInputFields(): array {
 *         return [
 *             'chat_id' => [
 *                 'type' => 'text',
 *                 'label' => 'Chat ID',
 *                 'required' => true,
 *             ],
 *             'text' => [
 *                 'type' => 'textarea',
 *                 'label' => 'Message',
 *                 'required' => true,
 *             ],
 *             'parse_mode' => [
 *                 'type' => 'select',
 *                 'label' => 'Parse Mode',
 *                 'options' => ['HTML', 'Markdown', 'MarkdownV2'],
 *             ],
 *         ];
 *     }
 *
 *     public function execute(array $credentials, array $input): array {
 *         $response = $this->makeRequest('POST', '/sendMessage', $credentials, [
 *             'chat_id' => $input['chat_id'],
 *             'text' => $input['text'],
 *             'parse_mode' => $input['parse_mode'] ?? 'HTML',
 *         ]);
 *
 *         return [
 *             'message_id' => $response['result']['message_id'],
 *             'sent_at' => now()->toIso8601String(),
 *         ];
 *     }
 * }
 * ```
 */
abstract class AbstractAction implements ActionContract
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
    // ABSTRACT METHODS (Must be implemented)
    // =========================================================================

    /**
     * Execute the action.
     *
     * @param array $credentials Decrypted connection credentials
     * @param array $input Action input data
     * @return array Action output data
     */
    abstract public function execute(array $credentials, array $input): array;

    // =========================================================================
    // IDENTITY (with defaults)
    // =========================================================================

    public function getDescription(): string
    {
        return $this->getDisplayName();
    }

    public function getConnectorName(): string
    {
        return $this->connector->getName();
    }

    public function getGroup(): ?string
    {
        return null;
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

    public function validateInput(array $input): array
    {
        $errors = [];

        foreach ($this->getInputFields() as $name => $config) {
            $value = $input[$name] ?? null;

            // Check required
            if (($config['required'] ?? false) && ($value === null || $value === '')) {
                $errors[$name] = "{$config['label']} is required";
                continue;
            }

            // Skip validation if not provided and not required
            if ($value === null || $value === '') {
                continue;
            }

            // Type validation
            $type = $config['type'] ?? 'text';
            switch ($type) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$name] = "Invalid email format";
                    }
                    break;
                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[$name] = "Invalid URL format";
                    }
                    break;
                case 'number':
                case 'integer':
                    if (!is_numeric($value)) {
                        $errors[$name] = "Must be a number";
                    }
                    break;
            }

            // Custom validation
            if ($validator = $config['validator'] ?? null) {
                if (is_callable($validator)) {
                    $result = $validator($value, $input);
                    if ($result !== true) {
                        $errors[$name] = $result;
                    }
                }
            }
        }

        return $errors;
    }

    // =========================================================================
    // EXECUTION (with defaults)
    // =========================================================================

    public function isIdempotent(): bool
    {
        return false;
    }

    public function getMaxRetries(): int
    {
        return 3;
    }

    public function getRetryStrategy(): array
    {
        return [
            'type' => 'exponential',
            'delay' => 60, // seconds
        ];
    }

    // =========================================================================
    // RATE LIMITING (with defaults)
    // =========================================================================

    public function getRateLimit(): ?array
    {
        return null; // Use connector default
    }

    public function getApiCallCount(): int
    {
        return 1;
    }

    // =========================================================================
    // BULK OPERATIONS (with defaults)
    // =========================================================================

    public function supportsBulk(): bool
    {
        return false;
    }

    public function getMaxBatchSize(): int
    {
        return 100;
    }

    public function executeBulk(array $credentials, array $items): array
    {
        if (!$this->supportsBulk()) {
            // Fallback to sequential execution
            $results = [];
            foreach ($items as $index => $item) {
                try {
                    $results[$index] = [
                        'success' => true,
                        'data' => $this->execute($credentials, $item),
                    ];
                } catch (\Exception $e) {
                    $results[$index] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            return $results;
        }

        throw new \RuntimeException('Bulk execution not implemented');
    }

    // =========================================================================
    // TESTING (with defaults)
    // =========================================================================

    public function getSampleInput(): array
    {
        $sample = [];

        foreach ($this->getInputFields() as $name => $config) {
            $sample[$name] = $config['sample'] ?? $config['default'] ?? null;
        }

        return $sample;
    }

    public function getSampleOutput(): array
    {
        return [];
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
        array $data = [],
        array $headers = []
    ): array {
        return $this->connector->makeRequest($method, $endpoint, $credentials, $data, $headers);
    }

    /**
     * Export action definition.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'connector' => $this->getConnectorName(),
            'group' => $this->getGroup(),
            'input_fields' => $this->getInputFields(),
            'output_fields' => $this->getOutputFields(),
            'is_idempotent' => $this->isIdempotent(),
            'supports_bulk' => $this->supportsBulk(),
            'max_batch_size' => $this->getMaxBatchSize(),
            'sample_input' => $this->getSampleInput(),
            'sample_output' => $this->getSampleOutput(),
        ];
    }
}
