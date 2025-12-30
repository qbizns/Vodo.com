<?php

declare(strict_types=1);

namespace App\Services\Registry;

use Exception;
use Throwable;

/**
 * RegistryBatchException - Exception thrown when a batch operation fails.
 *
 * Phase 2, Task 2.1: Registry Transaction Wrapper
 *
 * This exception provides details about which operations succeeded
 * before the failure, useful for debugging and logging.
 */
class RegistryBatchException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message Error message
     * @param string $batchId The batch ID that failed
     * @param array $executedOperations Operations that were executed before failure
     * @param Throwable|null $previous The underlying exception
     */
    public function __construct(
        string $message,
        protected string $batchId,
        protected array $executedOperations = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the batch ID that failed.
     */
    public function getBatchId(): string
    {
        return $this->batchId;
    }

    /**
     * Get operations that were executed before the failure.
     */
    public function getExecutedOperations(): array
    {
        return $this->executedOperations;
    }

    /**
     * Get the count of operations that succeeded before failure.
     */
    public function getSuccessfulOperationCount(): int
    {
        return count($this->executedOperations);
    }

    /**
     * Get the operation that caused the failure (if available).
     */
    public function getFailedOperation(): ?array
    {
        $previous = $this->getPrevious();

        if ($previous instanceof self) {
            return $previous->getFailedOperation();
        }

        return null;
    }

    /**
     * Get a detailed context array for logging.
     */
    public function getContext(): array
    {
        return [
            'batch_id' => $this->batchId,
            'message' => $this->getMessage(),
            'executed_count' => $this->getSuccessfulOperationCount(),
            'executed_operations' => array_map(function ($op) {
                return [
                    'registry' => $op['registry'],
                    'operation' => $op['operation'],
                    // Don't include full args in context (may contain sensitive data)
                    'args_keys' => array_keys($op['args'] ?? []),
                ];
            }, $this->executedOperations),
            'previous_exception' => $this->getPrevious() ? [
                'class' => get_class($this->getPrevious()),
                'message' => $this->getPrevious()->getMessage(),
                'file' => $this->getPrevious()->getFile(),
                'line' => $this->getPrevious()->getLine(),
            ] : null,
        ];
    }
}
