<?php

declare(strict_types=1);

namespace App\Services\Integration\Connector;

use App\Contracts\Integration\ConnectorContract;
use App\Contracts\Integration\TriggerContract;
use App\Contracts\Integration\ActionContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Abstract Connector Base Class.
 *
 * Plugins extend this to create their connectors.
 * Provides common functionality and HTTP client utilities.
 *
 * @example Telegram Connector
 * ```php
 * class TelegramConnector extends AbstractConnector
 * {
 *     public function getName(): string { return 'telegram'; }
 *     public function getDisplayName(): string { return 'Telegram'; }
 *     public function getAuthType(): string { return 'api_key'; }
 *
 *     protected function registerTriggers(): void {
 *         $this->addTrigger(new NewMessageTrigger($this));
 *         $this->addTrigger(new NewMemberTrigger($this));
 *     }
 *
 *     protected function registerActions(): void {
 *         $this->addAction(new SendMessageAction($this));
 *         $this->addAction(new SendPhotoAction($this));
 *     }
 * }
 * ```
 */
abstract class AbstractConnector implements ConnectorContract
{
    /**
     * Registered triggers.
     *
     * @var array<string, TriggerContract>
     */
    protected array $triggers = [];

    /**
     * Registered actions.
     *
     * @var array<string, ActionContract>
     */
    protected array $actions = [];

    /**
     * HTTP client instance.
     */
    protected ?\Illuminate\Http\Client\PendingRequest $httpClient = null;

    public function __construct()
    {
        $this->registerTriggers();
        $this->registerActions();
    }

    // =========================================================================
    // ABSTRACT METHODS (Must be implemented by plugins)
    // =========================================================================

    /**
     * Register triggers for this connector.
     * Called in constructor.
     */
    abstract protected function registerTriggers(): void;

    /**
     * Register actions for this connector.
     * Called in constructor.
     */
    abstract protected function registerActions(): void;

    // =========================================================================
    // IDENTITY (with defaults)
    // =========================================================================

    public function getDescription(): string
    {
        return "Connect to {$this->getDisplayName()}";
    }

    public function getIcon(): string
    {
        return "connectors/{$this->getName()}.svg";
    }

    public function getColor(): string
    {
        return '#666666';
    }

    public function getCategory(): string
    {
        return 'other';
    }

    public function getDocumentationUrl(): ?string
    {
        return null;
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    // =========================================================================
    // AUTHENTICATION (with defaults)
    // =========================================================================

    public function getAuthConfig(): array
    {
        return match ($this->getAuthType()) {
            'api_key' => [
                'fields' => [
                    'api_key' => [
                        'type' => 'password',
                        'label' => 'API Key',
                        'required' => true,
                        'help' => 'Your API key',
                    ],
                ],
            ],
            'oauth2' => [
                'fields' => [],
                'oauth' => $this->getOAuthConfig(),
            ],
            'basic' => [
                'fields' => [
                    'username' => [
                        'type' => 'text',
                        'label' => 'Username',
                        'required' => true,
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => 'Password',
                        'required' => true,
                    ],
                ],
            ],
            default => ['fields' => []],
        };
    }

    public function getOAuthConfig(): ?array
    {
        return null;
    }

    public function testConnection(array $credentials): array
    {
        try {
            $response = $this->makeRequest('GET', $this->getTestEndpoint(), $credentials);

            return [
                'success' => true,
                'message' => 'Connection successful',
                'user' => $this->parseUserFromTestResponse($response),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'user' => null,
            ];
        }
    }

    /**
     * Get endpoint for testing connection.
     * Override in subclass.
     */
    protected function getTestEndpoint(): string
    {
        return '/me';
    }

    /**
     * Parse user info from test response.
     * Override in subclass.
     */
    protected function parseUserFromTestResponse(array $response): ?array
    {
        return $response;
    }

    // =========================================================================
    // CAPABILITIES
    // =========================================================================

    public function getTriggers(): Collection
    {
        return collect($this->triggers);
    }

    public function getActions(): Collection
    {
        return collect($this->actions);
    }

    public function getTrigger(string $name): ?TriggerContract
    {
        return $this->triggers[$name] ?? null;
    }

    public function getAction(string $name): ?ActionContract
    {
        return $this->actions[$name] ?? null;
    }

    public function supportsWebhooks(): bool
    {
        foreach ($this->triggers as $trigger) {
            if ($trigger->getType() === 'webhook') {
                return true;
            }
        }
        return false;
    }

    public function supportsRealtime(): bool
    {
        foreach ($this->triggers as $trigger) {
            if ($trigger->getType() === 'instant') {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // HTTP CLIENT
    // =========================================================================

    public function getBaseUrl(): string
    {
        return '';
    }

    public function getDefaultHeaders(array $credentials): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function getRateLimits(): array
    {
        return [
            'requests' => 100,
            'per_seconds' => 60,
        ];
    }

    /**
     * Make an HTTP request to the service.
     */
    protected function makeRequest(
        string $method,
        string $endpoint,
        array $credentials,
        array $data = [],
        array $headers = []
    ): array {
        $client = Http::baseUrl($this->getBaseUrl())
            ->withHeaders(array_merge(
                $this->getDefaultHeaders($credentials),
                $this->getAuthHeaders($credentials),
                $headers
            ))
            ->timeout(30);

        $url = ltrim($endpoint, '/');

        $response = match (strtoupper($method)) {
            'GET' => $client->get($url, $data),
            'POST' => $client->post($url, $data),
            'PUT' => $client->put($url, $data),
            'PATCH' => $client->patch($url, $data),
            'DELETE' => $client->delete($url, $data),
            default => throw new \InvalidArgumentException("Invalid method: {$method}"),
        };

        if ($response->failed()) {
            throw new \App\Exceptions\Integration\ApiRequestException(
                $response->json('error.message') ?? $response->body(),
                $response->status()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Get authorization headers for requests.
     * Override for custom auth handling.
     */
    protected function getAuthHeaders(array $credentials): array
    {
        return match ($this->getAuthType()) {
            'api_key' => [
                'Authorization' => 'Bearer ' . ($credentials['api_key'] ?? ''),
            ],
            'basic' => [
                'Authorization' => 'Basic ' . base64_encode(
                    ($credentials['username'] ?? '') . ':' . ($credentials['password'] ?? '')
                ),
            ],
            'oauth2' => [
                'Authorization' => 'Bearer ' . ($credentials['access_token'] ?? ''),
            ],
            default => [],
        };
    }

    // =========================================================================
    // LIFECYCLE
    // =========================================================================

    public function onConnect(array $credentials): void
    {
        // Override in subclass if needed
    }

    public function onDisconnect(array $credentials): void
    {
        // Override in subclass if needed
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Add a trigger.
     */
    protected function addTrigger(TriggerContract $trigger): void
    {
        $this->triggers[$trigger->getName()] = $trigger;
    }

    /**
     * Add an action.
     */
    protected function addAction(ActionContract $action): void
    {
        $this->actions[$action->getName()] = $action;
    }

    /**
     * Get full identifier for trigger/action.
     */
    public function getFullIdentifier(string $name): string
    {
        return $this->getName() . '.' . $name;
    }

    /**
     * Export connector definition (for UI).
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'category' => $this->getCategory(),
            'auth_type' => $this->getAuthType(),
            'auth_config' => $this->getAuthConfig(),
            'supports_webhooks' => $this->supportsWebhooks(),
            'supports_realtime' => $this->supportsRealtime(),
            'triggers' => $this->getTriggers()->map(fn($t) => [
                'name' => $t->getName(),
                'display_name' => $t->getDisplayName(),
                'description' => $t->getDescription(),
                'type' => $t->getType(),
            ])->values()->toArray(),
            'actions' => $this->getActions()->map(fn($a) => [
                'name' => $a->getName(),
                'display_name' => $a->getDisplayName(),
                'description' => $a->getDescription(),
                'group' => $a->getGroup(),
            ])->values()->toArray(),
            'version' => $this->getVersion(),
        ];
    }
}
