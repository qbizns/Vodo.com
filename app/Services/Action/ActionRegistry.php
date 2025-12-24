<?php

declare(strict_types=1);

namespace App\Services\Action;

use App\Contracts\ActionContract;
use App\Contracts\ActionRegistryContract;
use Illuminate\Support\Collection;

/**
 * Action Registry - Manages all registered actions.
 *
 * Actions are reusable business logic blocks that can be triggered
 * from buttons, menus, automation rules, or API calls.
 *
 * @example Register a server action
 * ```php
 * $registry->register(new ApproveOrderAction());
 * ```
 *
 * @example Register from array
 * ```php
 * $registry->registerFromArray('send_email', [
 *     'label' => 'Send Email',
 *     'type' => 'server',
 *     'entity' => 'contact',
 *     'handler' => fn($ctx) => Mail::send(...),
 * ]);
 * ```
 */
class ActionRegistry implements ActionRegistryContract
{
    /**
     * Registered actions.
     *
     * @var array<string, ActionContract>
     */
    protected array $actions = [];

    /**
     * Plugin ownership mapping.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Registration listeners.
     *
     * @var array<callable>
     */
    protected array $listeners = [];

    public function register(ActionContract $action, ?string $pluginSlug = null): self
    {
        $name = $action->getName();
        $this->actions[$name] = $action;

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        foreach ($this->listeners as $listener) {
            $listener($action, $pluginSlug);
        }

        return $this;
    }

    public function registerFromArray(string $name, array $config, ?string $pluginSlug = null): self
    {
        $action = new GenericAction($name, $config);

        return $this->register($action, $pluginSlug);
    }

    public function unregister(string $name): bool
    {
        if (!isset($this->actions[$name])) {
            return false;
        }

        unset($this->actions[$name]);
        unset($this->pluginOwnership[$name]);

        return true;
    }

    public function get(string $name): ?ActionContract
    {
        return $this->actions[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->actions[$name]);
    }

    public function all(): Collection
    {
        return collect($this->actions);
    }

    public function getByType(string $type): Collection
    {
        return $this->all()->filter(
            fn(ActionContract $action) => $action->getType() === $type
        );
    }

    public function getForEntity(string $entityName): Collection
    {
        return $this->all()->filter(
            fn(ActionContract $action) => $action->getEntity() === $entityName || $action->getEntity() === null
        );
    }

    public function execute(string $name, array $context = []): mixed
    {
        $action = $this->get($name);

        if (!$action) {
            throw new \InvalidArgumentException("Action not found: {$name}");
        }

        if (!$action->canExecute($context)) {
            throw new \RuntimeException("Action not allowed: {$name}");
        }

        // Fire before hook
        do_action('action_before_execute', $action, $context);

        $result = $action->execute($context);

        // Fire after hook
        do_action('action_after_execute', $action, $context, $result);

        return $result;
    }

    /**
     * Get actions by plugin.
     *
     * @param string $pluginSlug Plugin slug
     * @return Collection
     */
    public function getByPlugin(string $pluginSlug): Collection
    {
        return $this->all()->filter(
            fn(ActionContract $action, string $name) => ($this->pluginOwnership[$name] ?? null) === $pluginSlug
        );
    }

    /**
     * Add a registration listener.
     *
     * @param callable $callback
     * @return self
     */
    public function onRegister(callable $callback): self
    {
        $this->listeners[] = $callback;

        return $this;
    }

    /**
     * Get actions as options for select inputs.
     *
     * @param string|null $entity Filter by entity
     * @return array
     */
    public function asOptions(?string $entity = null): array
    {
        $actions = $entity ? $this->getForEntity($entity) : $this->all();

        return $actions
            ->map(fn(ActionContract $action) => [
                'value' => $action->getName(),
                'label' => $action->getLabel(),
                'type' => $action->getType(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return $this->all()
            ->map(fn(ActionContract $action) => $action->toArray())
            ->toArray();
    }
}
