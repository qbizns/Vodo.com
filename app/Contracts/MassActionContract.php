<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for Mass Action operations.
 */
interface MassActionContract
{
    /**
     * Get the action name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the action label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get the target entity.
     *
     * @return string|null
     */
    public function getEntity(): ?string;

    /**
     * Check if action can be applied to the given records.
     *
     * @param Collection $records Records to check
     * @return bool
     */
    public function canApply(Collection $records): bool;

    /**
     * Apply the action to the given records.
     *
     * @param Collection $records Records to process
     * @param array $params Optional parameters
     * @return array Result with counts and errors
     */
    public function apply(Collection $records, array $params = []): array;

    /**
     * Get required confirmation message (if any).
     *
     * @return string|null
     */
    public function getConfirmation(): ?string;

    /**
     * Check if action requires additional parameters.
     *
     * @return bool
     */
    public function requiresParams(): bool;

    /**
     * Get parameter schema for this action.
     *
     * @return array
     */
    public function getParamSchema(): array;
}
