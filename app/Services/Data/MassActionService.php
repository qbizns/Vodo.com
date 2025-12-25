<?php

declare(strict_types=1);

namespace App\Services\Data;

use App\Contracts\MassActionContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mass Action Service
 *
 * Handles bulk operations on multiple records.
 * Supports built-in and custom mass actions.
 *
 * @example Register a mass action
 * ```php
 * $service->register(new ArchiveAction());
 * ```
 *
 * @example Apply a mass action
 * ```php
 * $result = $service->apply('archive', $records, ['reason' => 'cleanup']);
 * ```
 */
class MassActionService
{
    /**
     * Registered mass actions.
     *
     * @var array<string, MassActionContract>
     */
    protected array $actions = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    public function __construct()
    {
        $this->registerBuiltInActions();
    }

    /**
     * Register built-in mass actions.
     */
    protected function registerBuiltInActions(): void
    {
        $this->register(new Actions\DeleteMassAction());
        $this->register(new Actions\ArchiveMassAction());
        $this->register(new Actions\RestoreMassAction());
        $this->register(new Actions\ExportMassAction());
        $this->register(new Actions\UpdateFieldMassAction());
        $this->register(new Actions\DuplicateMassAction());
    }

    /**
     * Register a mass action.
     *
     * @param MassActionContract $action Action instance
     * @param string|null $pluginSlug Plugin slug
     * @return self
     */
    public function register(MassActionContract $action, ?string $pluginSlug = null): self
    {
        $name = $action->getName();
        $this->actions[$name] = $action;

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        return $this;
    }

    /**
     * Unregister a mass action.
     *
     * @param string $name Action name
     * @return bool
     */
    public function unregister(string $name): bool
    {
        if (!isset($this->actions[$name])) {
            return false;
        }

        unset($this->actions[$name]);
        unset($this->pluginOwnership[$name]);

        return true;
    }

    /**
     * Get a mass action by name.
     *
     * @param string $name Action name
     * @return MassActionContract|null
     */
    public function get(string $name): ?MassActionContract
    {
        return $this->actions[$name] ?? null;
    }

    /**
     * Get all registered actions.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return collect($this->actions);
    }

    /**
     * Get actions available for an entity.
     *
     * @param string $entityName Entity name
     * @return Collection
     */
    public function getForEntity(string $entityName): Collection
    {
        return $this->all()->filter(function (MassActionContract $action) use ($entityName) {
            $targetEntity = $action->getEntity();

            return $targetEntity === null || $targetEntity === $entityName;
        });
    }

    /**
     * Apply a mass action to records.
     *
     * @param string $name Action name
     * @param Collection $records Records to process
     * @param array $params Optional parameters
     * @return array Result with counts and errors
     */
    public function apply(string $name, Collection $records, array $params = []): array
    {
        $action = $this->get($name);

        if (!$action) {
            throw new \InvalidArgumentException("Mass action not found: {$name}");
        }

        if (!$action->canApply($records)) {
            throw new \RuntimeException("Cannot apply action '{$name}' to selected records");
        }

        // Fire before hook
        do_action('mass_action_before', $name, $records, $params);

        $result = DB::transaction(function () use ($action, $records, $params) {
            return $action->apply($records, $params);
        });

        // Fire after hook
        do_action('mass_action_after', $name, $records, $params, $result);

        return $result;
    }

    /**
     * Get actions as options for UI.
     *
     * @param string|null $entityName Filter by entity
     * @return array
     */
    public function asOptions(?string $entityName = null): array
    {
        $actions = $entityName ? $this->getForEntity($entityName) : $this->all();

        return $actions->map(fn(MassActionContract $action) => [
            'value' => $action->getName(),
            'label' => $action->getLabel(),
            'confirmation' => $action->getConfirmation(),
            'requires_params' => $action->requiresParams(),
            'param_schema' => $action->getParamSchema(),
        ])->values()->toArray();
    }
}
