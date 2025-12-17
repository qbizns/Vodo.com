<?php

declare(strict_types=1);

namespace App\Services\PluginBus;

use App\Contracts\PluginBusContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Plugin Bus - Central communication hub for inter-plugin messaging.
 * 
 * Provides:
 * - Service registration and discovery
 * - Cross-plugin service calls
 * - Event publishing and subscription
 * - Dependency declaration and validation
 * 
 * Example usage:
 * 
 * // In Accounting Plugin - provide a service
 * $bus->provide('accounting.journal.create', function($data) {
 *     return JournalEntry::create($data);
 * }, [
 *     'description' => 'Create a journal entry',
 *     'parameters' => ['date', 'lines', 'reference'],
 *     'returns' => 'JournalEntry'
 * ]);
 * 
 * // In Sales Plugin - consume the service
 * $bus->declareDependency('sales', 'accounting.journal.create');
 * $entry = $bus->call('accounting.journal.create', [
 *     'date' => now(),
 *     'lines' => [...],
 *     'reference' => 'INV-001'
 * ]);
 * 
 * // Event-based communication
 * $bus->subscribe('sales.order.confirmed', function($order) {
 *     // Create accounting entries
 * });
 * $bus->publish('sales.order.confirmed', ['order_id' => 123]);
 */
class PluginBus implements PluginBusContract
{
    /**
     * Registered services.
     * @var array<string, array{handler: callable, metadata: array, plugin: ?string}>
     */
    protected array $services = [];

    /**
     * Event subscribers.
     * @var array<string, array<int, array<array{handler: callable, plugin: ?string}>>>
     */
    protected array $subscribers = [];

    /**
     * Declared dependencies.
     * @var array<string, array<string, bool>>
     */
    protected array $dependencies = [];

    /**
     * Current plugin context.
     */
    protected ?string $currentPlugin = null;

    /**
     * Set the current plugin context.
     */
    public function setPluginContext(?string $pluginSlug): void
    {
        $this->currentPlugin = $pluginSlug;
    }

    /**
     * Register a service that this plugin provides.
     */
    public function provide(string $serviceId, callable $handler, array $metadata = []): void
    {
        $this->validateServiceId($serviceId);

        $this->services[$serviceId] = [
            'handler' => $handler,
            'metadata' => array_merge([
                'description' => '',
                'parameters' => [],
                'returns' => 'mixed',
                'version' => '1.0',
            ], $metadata),
            'plugin' => $this->currentPlugin,
        ];

        Log::debug("PluginBus: Service registered", [
            'service' => $serviceId,
            'plugin' => $this->currentPlugin,
        ]);
    }

    /**
     * Default service call timeout in seconds.
     */
    protected int $defaultTimeout = 30;

    /**
     * Call a service provided by another plugin.
     */
    public function call(string $serviceId, array $parameters = [], ?int $timeout = null): mixed
    {
        if (!$this->hasService($serviceId)) {
            throw new ServiceNotFoundException("Service not found: {$serviceId}");
        }

        $service = $this->services[$serviceId];
        $timeout = $timeout ?? $this->defaultTimeout;

        try {
            Log::debug("PluginBus: Calling service", [
                'service' => $serviceId,
                'caller' => $this->currentPlugin,
                'provider' => $service['plugin'],
                'timeout' => $timeout,
            ]);

            // Execute with timeout protection
            return $this->executeWithTimeout(
                fn() => call_user_func($service['handler'], $parameters),
                $timeout,
                $serviceId
            );

        } catch (ServiceTimeoutException $e) {
            Log::error("PluginBus: Service call timed out", [
                'service' => $serviceId,
                'timeout' => $timeout,
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error("PluginBus: Service call failed", [
                'service' => $serviceId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Execute a callback with timeout protection.
     */
    protected function executeWithTimeout(callable $callback, int $timeout, string $serviceId): mixed
    {
        // For PHP environments without pcntl, we use a simpler approach
        // In production, consider using a queue-based approach for long-running services
        $startTime = microtime(true);
        
        // Set a maximum execution time for this call
        $previousTimeout = ini_get('max_execution_time');
        if ($previousTimeout > 0 && $timeout < $previousTimeout) {
            set_time_limit($timeout + 5); // Add buffer
        }

        try {
            $result = $callback();
            
            $elapsed = microtime(true) - $startTime;
            if ($elapsed > $timeout) {
                Log::warning("PluginBus: Service call exceeded soft timeout", [
                    'service' => $serviceId,
                    'elapsed' => round($elapsed, 2),
                    'timeout' => $timeout,
                ]);
            }
            
            return $result;
        } finally {
            // Restore previous timeout
            if ($previousTimeout > 0) {
                set_time_limit((int) $previousTimeout);
            }
        }
    }

    /**
     * Set default timeout for service calls.
     */
    public function setDefaultTimeout(int $seconds): void
    {
        $this->defaultTimeout = max(1, $seconds);
    }

    /**
     * Call a service asynchronously (queued).
     */
    public function callAsync(string $serviceId, array $parameters = []): string
    {
        $jobId = uniqid('bus_job_', true);

        dispatch(function () use ($serviceId, $parameters, $jobId) {
            try {
                $result = $this->call($serviceId, $parameters);
                Cache::put("plugin_bus:job:{$jobId}", [
                    'status' => 'completed',
                    'result' => $result,
                ], 3600);
            } catch (\Throwable $e) {
                Cache::put("plugin_bus:job:{$jobId}", [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ], 3600);
            }
        });

        Cache::put("plugin_bus:job:{$jobId}", ['status' => 'pending'], 3600);

        return $jobId;
    }

    /**
     * Get async job result.
     */
    public function getAsyncResult(string $jobId): ?array
    {
        return Cache::get("plugin_bus:job:{$jobId}");
    }

    /**
     * Check if a service is available.
     */
    public function hasService(string $serviceId): bool
    {
        return isset($this->services[$serviceId]);
    }

    /**
     * Get all registered services.
     */
    public function getServices(): array
    {
        return array_map(fn($s) => [
            'metadata' => $s['metadata'],
            'plugin' => $s['plugin'],
        ], $this->services);
    }

    /**
     * Get services by category/namespace.
     */
    public function getServicesByNamespace(string $namespace): array
    {
        return array_filter(
            $this->getServices(),
            fn($key) => str_starts_with($key, $namespace . '.'),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Subscribe to an event from any plugin.
     */
    public function subscribe(string $eventId, callable $handler, int $priority = 10): void
    {
        if (!isset($this->subscribers[$eventId])) {
            $this->subscribers[$eventId] = [];
        }

        if (!isset($this->subscribers[$eventId][$priority])) {
            $this->subscribers[$eventId][$priority] = [];
        }

        $this->subscribers[$eventId][$priority][] = [
            'handler' => $handler,
            'plugin' => $this->currentPlugin,
        ];

        Log::debug("PluginBus: Event subscription added", [
            'event' => $eventId,
            'plugin' => $this->currentPlugin,
            'priority' => $priority,
        ]);
    }

    /**
     * Publish an event for other plugins to consume.
     */
    public function publish(string $eventId, array $payload = []): void
    {
        if (!isset($this->subscribers[$eventId])) {
            return;
        }

        $subscribers = $this->subscribers[$eventId];
        ksort($subscribers);

        $eventData = [
            'event_id' => $eventId,
            'payload' => $payload,
            'publisher' => $this->currentPlugin,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::debug("PluginBus: Publishing event", [
            'event' => $eventId,
            'publisher' => $this->currentPlugin,
            'subscriber_count' => array_sum(array_map('count', $subscribers)),
        ]);

        foreach ($subscribers as $priorityHandlers) {
            foreach ($priorityHandlers as $subscriber) {
                try {
                    call_user_func($subscriber['handler'], $eventData);
                } catch (\Throwable $e) {
                    Log::error("PluginBus: Event handler failed", [
                        'event' => $eventId,
                        'subscriber_plugin' => $subscriber['plugin'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Publish event asynchronously.
     */
    public function publishAsync(string $eventId, array $payload = []): void
    {
        dispatch(fn() => $this->publish($eventId, $payload));
    }

    /**
     * Declare a dependency on another plugin's service.
     */
    public function declareDependency(string $pluginSlug, string $serviceId, bool $required = true): void
    {
        if (!isset($this->dependencies[$pluginSlug])) {
            $this->dependencies[$pluginSlug] = [];
        }

        $this->dependencies[$pluginSlug][$serviceId] = $required;
    }

    /**
     * Check if all declared dependencies are satisfied.
     */
    public function checkDependencies(string $pluginSlug): array
    {
        $result = [
            'satisfied' => true,
            'missing' => [],
            'optional_missing' => [],
        ];

        if (!isset($this->dependencies[$pluginSlug])) {
            return $result;
        }

        foreach ($this->dependencies[$pluginSlug] as $serviceId => $required) {
            if (!$this->hasService($serviceId)) {
                if ($required) {
                    $result['satisfied'] = false;
                    $result['missing'][] = $serviceId;
                } else {
                    $result['optional_missing'][] = $serviceId;
                }
            }
        }

        return $result;
    }

    /**
     * Get dependency graph for visualization.
     */
    public function getDependencyGraph(): array
    {
        $graph = [
            'nodes' => [],
            'edges' => [],
        ];

        // Add plugin nodes
        $plugins = array_unique(array_filter(array_column($this->services, 'plugin')));
        foreach ($plugins as $plugin) {
            $graph['nodes'][] = [
                'id' => $plugin,
                'type' => 'plugin',
                'services' => count(array_filter($this->services, fn($s) => $s['plugin'] === $plugin)),
            ];
        }

        // Add dependency edges
        foreach ($this->dependencies as $pluginSlug => $deps) {
            foreach ($deps as $serviceId => $required) {
                if ($this->hasService($serviceId)) {
                    $provider = $this->services[$serviceId]['plugin'];
                    $graph['edges'][] = [
                        'from' => $pluginSlug,
                        'to' => $provider,
                        'service' => $serviceId,
                        'required' => $required,
                    ];
                }
            }
        }

        return $graph;
    }

    /**
     * Remove all services and subscriptions from a plugin.
     */
    public function removePlugin(string $pluginSlug): int
    {
        $removed = 0;

        // Remove services
        foreach ($this->services as $serviceId => $service) {
            if ($service['plugin'] === $pluginSlug) {
                unset($this->services[$serviceId]);
                $removed++;
            }
        }

        // Remove subscriptions
        foreach ($this->subscribers as $eventId => $priorities) {
            foreach ($priorities as $priority => $handlers) {
                foreach ($handlers as $key => $handler) {
                    if ($handler['plugin'] === $pluginSlug) {
                        unset($this->subscribers[$eventId][$priority][$key]);
                        $removed++;
                    }
                }
            }
        }

        // Remove dependencies
        unset($this->dependencies[$pluginSlug]);

        return $removed;
    }

    /**
     * Validate service ID format.
     */
    protected function validateServiceId(string $serviceId): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $serviceId)) {
            throw new \InvalidArgumentException(
                "Invalid service ID format: {$serviceId}. Must be dot-separated lowercase identifiers (e.g., 'accounting.journal.create')"
            );
        }
    }
}

/**
 * Exception for missing services.
 */
class ServiceNotFoundException extends \Exception {}

/**
 * Exception for service call timeouts.
 */
class ServiceTimeoutException extends \Exception
{
    public function __construct(string $serviceId, int $timeout)
    {
        parent::__construct("Service call to '{$serviceId}' timed out after {$timeout} seconds");
    }
}
