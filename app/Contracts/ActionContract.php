<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for Action implementations.
 *
 * Actions are reusable business logic blocks that can be triggered
 * from buttons, menus, automation rules, or API calls.
 */
interface ActionContract
{
    /**
     * Get the action's unique identifier.
     */
    public function getName(): string;

    /**
     * Get the human-readable label.
     */
    public function getLabel(): string;

    /**
     * Get the action type.
     *
     * @return string 'server' | 'client' | 'url' | 'report' | 'object'
     */
    public function getType(): string;

    /**
     * Get the target entity (if applicable).
     */
    public function getEntity(): ?string;

    /**
     * Check if this action can be executed in the given context.
     *
     * @param array $context Execution context (record, user, etc.)
     */
    public function canExecute(array $context = []): bool;

    /**
     * Execute the action.
     *
     * @param array $context Execution context
     * @return mixed Action result
     */
    public function execute(array $context = []): mixed;

    /**
     * Get the action configuration.
     */
    public function getConfig(): array;

    /**
     * Get required permissions to execute this action.
     *
     * @return array<string>
     */
    public function getRequiredPermissions(): array;
}
