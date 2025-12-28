<?php

declare(strict_types=1);

namespace App\Services\Registry;

use App\Contracts\BatchableRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * RegistryBatch - Transaction wrapper for atomic registry operations.
 *
 * Phase 2, Task 2.1: Registry Transaction Wrapper
 *
 * This service allows multiple registry operations to be executed atomically.
 * If any operation fails, all changes are rolled back, preventing partial state.
 *
 * Usage:
 *   $batch = app(RegistryBatch::class);
 *
 *   $batch->entity()->register('product', $config, 'my-plugin');
 *   $batch->view()->register('product_list', $viewDef, 'my-plugin');
 *   $batch->permission()->register('product.create', $permConfig, 'my-plugin');
 *
 *   $batch->commit(); // All or nothing
 *
 * Or use the fluent API:
 *   RegistryBatch::make()
 *       ->addEntity('product', $config, 'my-plugin')
 *       ->addView('product_list', $viewDef, 'my-plugin')
 *       ->commit();
 */
class RegistryBatch
{
    /**
     * Unique batch identifier.
     */
    protected string $batchId;

    /**
     * Plugin context for all operations.
     */
    protected ?string $pluginSlug = null;

    /**
     * Pending operations to execute.
     *
     * @var array<array{registry: string, operation: string, args: array}>
     */
    protected array $pendingOperations = [];

    /**
     * Successfully executed operations (for rollback).
     *
     * @var array<array{registry: string, operation: string, args: array, result: mixed}>
     */
    protected array $executedOperations = [];

    /**
     * Registry instances.
     *
     * @var array<string, object>
     */
    protected array $registries = [];

    /**
     * Whether batch has been committed.
     */
    protected bool $committed = false;

    /**
     * Whether batch has been rolled back.
     */
    protected bool $rolledBack = false;

    /**
     * Create a new batch instance.
     */
    public function __construct()
    {
        $this->batchId = Str::uuid()->toString();
    }

    /**
     * Create a new batch (static factory).
     */
    public static function make(?string $pluginSlug = null): static
    {
        $batch = new static();
        $batch->pluginSlug = $pluginSlug;
        return $batch;
    }

    /**
     * Set the plugin context for all operations.
     */
    public function forPlugin(string $pluginSlug): static
    {
        $this->pluginSlug = $pluginSlug;
        return $this;
    }

    /**
     * Get the batch ID.
     */
    public function getBatchId(): string
    {
        return $this->batchId;
    }

    /**
     * Get the Entity Registry proxy.
     */
    public function entity(): RegistryProxy
    {
        return $this->getProxy('entity', \App\Services\Entity\EntityRegistry::class);
    }

    /**
     * Get the View Registry proxy.
     */
    public function view(): RegistryProxy
    {
        return $this->getProxy('view', \App\Services\View\ViewRegistry::class);
    }

    /**
     * Get the Permission Registry proxy.
     */
    public function permission(): RegistryProxy
    {
        return $this->getProxy('permission', \App\Services\Permission\PermissionRegistry::class);
    }

    /**
     * Get the Menu Registry proxy.
     */
    public function menu(): RegistryProxy
    {
        return $this->getProxy('menu', \App\Services\Menu\MenuRegistry::class);
    }

    /**
     * Get a registry proxy.
     */
    protected function getProxy(string $name, string $class): RegistryProxy
    {
        if (!isset($this->registries[$name])) {
            $this->registries[$name] = app($class);
        }

        return new RegistryProxy($this, $name, $this->registries[$name]);
    }

    /**
     * Add an operation to the batch.
     *
     * @internal Called by RegistryProxy
     */
    public function addOperation(string $registry, string $operation, array $args): void
    {
        if ($this->committed || $this->rolledBack) {
            throw new \RuntimeException('Cannot add operations to a completed batch');
        }

        // Inject plugin slug if not provided
        if ($this->pluginSlug && !isset($args['pluginSlug'])) {
            $args['pluginSlug'] = $this->pluginSlug;
        }

        $this->pendingOperations[] = [
            'registry' => $registry,
            'operation' => $operation,
            'args' => $args,
        ];
    }

    /**
     * Fluent API: Add an entity registration.
     */
    public function addEntity(string $name, array $config, ?string $pluginSlug = null): static
    {
        $this->entity()->register($name, $config, $pluginSlug ?? $this->pluginSlug);
        return $this;
    }

    /**
     * Fluent API: Add a view registration.
     */
    public function addView(string $slug, string $type, array $definition, ?string $pluginSlug = null): static
    {
        $this->view()->registerView($slug, $type, $definition, $pluginSlug ?? $this->pluginSlug);
        return $this;
    }

    /**
     * Fluent API: Add a permission registration.
     */
    public function addPermission(string $slug, array $config, ?string $pluginSlug = null): static
    {
        $this->permission()->register($slug, $config, $pluginSlug ?? $this->pluginSlug);
        return $this;
    }

    /**
     * Commit all pending operations atomically.
     *
     * @throws \Exception If any operation fails
     */
    public function commit(): void
    {
        if ($this->committed) {
            throw new \RuntimeException('Batch already committed');
        }

        if ($this->rolledBack) {
            throw new \RuntimeException('Cannot commit a rolled back batch');
        }

        if (empty($this->pendingOperations)) {
            $this->committed = true;
            return;
        }

        Log::debug("RegistryBatch: Starting commit", [
            'batch_id' => $this->batchId,
            'operations' => count($this->pendingOperations),
            'plugin' => $this->pluginSlug,
        ]);

        DB::beginTransaction();

        try {
            // Notify registries that batch is starting
            $this->notifyBatchStart();

            // Execute all operations
            foreach ($this->pendingOperations as $operation) {
                $this->executeOperation($operation);
            }

            // Notify registries that batch is complete
            $this->notifyBatchCommit();

            DB::commit();
            $this->committed = true;

            Log::info("RegistryBatch: Committed successfully", [
                'batch_id' => $this->batchId,
                'operations' => count($this->executedOperations),
                'plugin' => $this->pluginSlug,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("RegistryBatch: Commit failed, rolling back", [
                'batch_id' => $this->batchId,
                'executed' => count($this->executedOperations),
                'error' => $e->getMessage(),
                'plugin' => $this->pluginSlug,
            ]);

            // Notify registries to rollback
            $this->notifyBatchRollback();

            $this->rolledBack = true;

            throw new RegistryBatchException(
                "Batch commit failed: {$e->getMessage()}",
                $this->batchId,
                $this->executedOperations,
                $e
            );
        }
    }

    /**
     * Rollback the batch (discard pending operations).
     */
    public function rollback(): void
    {
        if ($this->committed) {
            throw new \RuntimeException('Cannot rollback a committed batch');
        }

        $this->pendingOperations = [];
        $this->rolledBack = true;

        Log::debug("RegistryBatch: Rolled back", [
            'batch_id' => $this->batchId,
            'plugin' => $this->pluginSlug,
        ]);
    }

    /**
     * Execute a single operation.
     */
    protected function executeOperation(array $operation): void
    {
        $registry = $this->registries[$operation['registry']] ?? null;

        if (!$registry) {
            throw new \RuntimeException("Registry not found: {$operation['registry']}");
        }

        $method = $operation['operation'];
        $args = $operation['args'];

        if (!method_exists($registry, $method)) {
            throw new \RuntimeException(
                "Method {$method} does not exist on {$operation['registry']} registry"
            );
        }

        // Execute the operation
        $result = $registry->$method(...array_values($args));

        // Track for potential rollback
        $this->executedOperations[] = [
            'registry' => $operation['registry'],
            'operation' => $method,
            'args' => $args,
            'result' => $result,
        ];
    }

    /**
     * Notify registries that a batch is starting.
     */
    protected function notifyBatchStart(): void
    {
        foreach ($this->registries as $name => $registry) {
            if ($registry instanceof BatchableRegistry) {
                $registry->beginBatch($this->batchId);
            }
        }
    }

    /**
     * Notify registries that batch is being committed.
     */
    protected function notifyBatchCommit(): void
    {
        foreach ($this->registries as $name => $registry) {
            if ($registry instanceof BatchableRegistry) {
                $registry->commitBatch($this->batchId);
            }
        }
    }

    /**
     * Notify registries to rollback.
     */
    protected function notifyBatchRollback(): void
    {
        foreach ($this->registries as $name => $registry) {
            if ($registry instanceof BatchableRegistry) {
                $registry->rollbackBatch($this->batchId);
            }
        }
    }

    /**
     * Get pending operations count.
     */
    public function pendingCount(): int
    {
        return count($this->pendingOperations);
    }

    /**
     * Get executed operations count.
     */
    public function executedCount(): int
    {
        return count($this->executedOperations);
    }

    /**
     * Check if batch is committed.
     */
    public function isCommitted(): bool
    {
        return $this->committed;
    }

    /**
     * Check if batch is rolled back.
     */
    public function isRolledBack(): bool
    {
        return $this->rolledBack;
    }

    /**
     * Get batch status.
     */
    public function getStatus(): string
    {
        if ($this->committed) {
            return 'committed';
        }

        if ($this->rolledBack) {
            return 'rolled_back';
        }

        return 'pending';
    }
}
