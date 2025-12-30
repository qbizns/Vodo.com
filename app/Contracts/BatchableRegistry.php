<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * BatchableRegistry - Contract for registries that support batch operations.
 *
 * Phase 2, Task 2.1: Registry Transaction Wrapper
 *
 * Registries implementing this contract can participate in atomic
 * batch operations that ensure all-or-nothing semantics.
 */
interface BatchableRegistry
{
    /**
     * Get the registry name for identification.
     */
    public function getRegistryName(): string;

    /**
     * Begin a batch operation.
     *
     * @param string $batchId Unique identifier for this batch
     */
    public function beginBatch(string $batchId): void;

    /**
     * Commit the current batch.
     *
     * @param string $batchId The batch ID to commit
     */
    public function commitBatch(string $batchId): void;

    /**
     * Rollback the current batch.
     *
     * @param string $batchId The batch ID to rollback
     */
    public function rollbackBatch(string $batchId): void;

    /**
     * Check if currently in a batch operation.
     */
    public function isInBatch(): bool;

    /**
     * Get operations pending in the current batch.
     *
     * @return array<array{operation: string, args: array}>
     */
    public function getPendingOperations(): array;
}
