<?php

declare(strict_types=1);

namespace App\Services\Action;

use App\Contracts\ActionContract;
use Illuminate\Support\Facades\Gate;

/**
 * Abstract base class for actions.
 *
 * Provides common functionality for server actions, client actions,
 * URL actions, and report actions.
 */
abstract class AbstractAction implements ActionContract
{
    /**
     * Action name.
     */
    protected string $name;

    /**
     * Human-readable label.
     */
    protected string $label;

    /**
     * Action type: server, client, url, report, object.
     */
    protected string $type = 'server';

    /**
     * Target entity (null for global actions).
     */
    protected ?string $entity = null;

    /**
     * Required permissions.
     *
     * @var array<string>
     */
    protected array $permissions = [];

    /**
     * Action configuration.
     */
    protected array $config = [];

    /**
     * Action icon.
     */
    protected string $icon = 'play';

    /**
     * Action priority (for ordering).
     */
    protected int $priority = 10;

    /**
     * Whether this action is destructive (requires confirmation).
     */
    protected bool $destructive = false;

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function getRequiredPermissions(): array
    {
        return $this->permissions;
    }

    public function getConfig(): array
    {
        return array_merge([
            'icon' => $this->icon,
            'priority' => $this->priority,
            'destructive' => $this->destructive,
        ], $this->config);
    }

    public function canExecute(array $context = []): bool
    {
        // Check permissions
        foreach ($this->permissions as $permission) {
            if (!Gate::allows($permission)) {
                return false;
            }
        }

        // Run custom guard
        return $this->guard($context);
    }

    /**
     * Custom guard logic. Override in subclasses.
     *
     * @param array $context Execution context
     */
    protected function guard(array $context = []): bool
    {
        return true;
    }

    /**
     * Execute the action.
     *
     * @param array $context Execution context
     * @return mixed
     */
    abstract public function execute(array $context = []): mixed;

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'entity' => $this->getEntity(),
            'permissions' => $this->getRequiredPermissions(),
            'config' => $this->getConfig(),
        ];
    }
}
