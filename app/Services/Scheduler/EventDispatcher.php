<?php

namespace App\Services\Scheduler;

use App\Models\EventSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class EventDispatcher
{
    protected array $runtimeListeners = [];

    // =========================================================================
    // Subscription Management
    // =========================================================================

    /**
     * Subscribe to an event (persisted)
     */
    public function subscribe(string $event, string $listener, array $options = [], ?string $pluginSlug = null): EventSubscription
    {
        return EventSubscription::updateOrCreate(
            [
                'event' => $event,
                'listener' => $listener,
                'plugin_slug' => $pluginSlug,
            ],
            [
                'priority' => $options['priority'] ?? 100,
                'is_active' => $options['active'] ?? true,
                'run_async' => $options['async'] ?? false,
                'queue' => $options['queue'] ?? null,
                'conditions' => $options['conditions'] ?? null,
                'meta' => $options['meta'] ?? null,
            ]
        );
    }

    /**
     * Subscribe to an event (runtime only, not persisted)
     */
    public function listen(string $event, callable|string $listener, int $priority = 100): void
    {
        $this->runtimeListeners[$event][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->runtimeListeners[$event], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Unsubscribe from an event
     */
    public function unsubscribe(string $event, string $listener, ?string $pluginSlug = null): bool
    {
        return EventSubscription::where('event', $event)
            ->where('listener', $listener)
            ->where('plugin_slug', $pluginSlug)
            ->delete() > 0;
    }

    /**
     * Unsubscribe all listeners for a plugin
     */
    public function unsubscribePlugin(string $pluginSlug): int
    {
        return EventSubscription::where('plugin_slug', $pluginSlug)->delete();
    }

    // =========================================================================
    // Event Dispatching
    // =========================================================================

    /**
     * Dispatch an event to all listeners
     */
    public function dispatch(string $event, array $payload = []): array
    {
        $results = [];

        // Get persisted subscriptions
        $subscriptions = EventSubscription::getForEvent($event);

        // Get runtime listeners
        $runtimeListeners = $this->runtimeListeners[$event] ?? [];

        // Dispatch to persisted subscriptions
        foreach ($subscriptions as $subscription) {
            if (!$subscription->shouldRun($payload)) {
                continue;
            }

            try {
                if ($subscription->run_async) {
                    $results[] = $this->dispatchAsync($subscription, $payload);
                } else {
                    $results[] = $this->dispatchSync($subscription, $payload);
                }
            } catch (Throwable $e) {
                Log::error("Event listener failed for {$event}: " . $e->getMessage());
                $results[] = ['status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        // Dispatch to runtime listeners
        foreach ($runtimeListeners as $listenerConfig) {
            try {
                $listener = $listenerConfig['listener'];
                
                if (is_callable($listener)) {
                    $result = $listener($payload);
                } else {
                    $result = $this->invokeListener($listener, $payload);
                }

                $results[] = ['status' => 'completed', 'result' => $result];
            } catch (Throwable $e) {
                Log::error("Runtime event listener failed for {$event}: " . $e->getMessage());
                $results[] = ['status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        if (function_exists('do_action')) {
            do_action('event_dispatched', $event, $payload, $results);
        }

        return $results;
    }

    /**
     * Dispatch event synchronously
     */
    protected function dispatchSync(EventSubscription $subscription, array $payload): array
    {
        $result = $this->invokeListener($subscription->listener, $payload);

        return [
            'status' => 'completed',
            'listener' => $subscription->listener,
            'result' => $result,
        ];
    }

    /**
     * Dispatch event asynchronously via queue
     */
    protected function dispatchAsync(EventSubscription $subscription, array $payload): array
    {
        $job = new \App\Jobs\ProcessEventListener(
            $subscription->listener,
            $payload
        );

        if ($subscription->queue) {
            $job->onQueue($subscription->queue);
        }

        dispatch($job);

        return [
            'status' => 'queued',
            'listener' => $subscription->listener,
            'queue' => $subscription->queue ?? 'default',
        ];
    }

    /**
     * Invoke a listener
     */
    protected function invokeListener(string $listener, array $payload)
    {
        if (str_contains($listener, '@')) {
            [$class, $method] = explode('@', $listener);
        } else {
            $class = $listener;
            $method = 'handle';
        }

        $instance = app($class);
        return $instance->{$method}($payload);
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Check if event has listeners
     */
    public function hasListeners(string $event): bool
    {
        $hasPersistedListeners = EventSubscription::active()
            ->forEvent($event)
            ->exists();

        $hasRuntimeListeners = isset($this->runtimeListeners[$event]) 
            && count($this->runtimeListeners[$event]) > 0;

        return $hasPersistedListeners || $hasRuntimeListeners;
    }

    /**
     * Get all listeners for an event
     */
    public function getListeners(string $event): Collection
    {
        return EventSubscription::getForEvent($event);
    }

    /**
     * Get all subscribed events
     */
    public function getSubscribedEvents(): Collection
    {
        return EventSubscription::active()
            ->select('event')
            ->distinct()
            ->pluck('event');
    }

    /**
     * Clear runtime listeners
     */
    public function clearRuntimeListeners(?string $event = null): void
    {
        if ($event) {
            unset($this->runtimeListeners[$event]);
        } else {
            $this->runtimeListeners = [];
        }
    }
}
