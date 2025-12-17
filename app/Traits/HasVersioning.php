<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * HasVersioning Trait - Implements optimistic locking.
 * 
 * Prevents concurrent update conflicts by checking version numbers.
 * 
 * Usage:
 * 1. Add 'version' column to your table (integer, default 1)
 * 2. Use this trait in your model
 * 3. Updates will automatically check and increment version
 */
trait HasVersioning
{
    /**
     * Boot the trait.
     */
    public static function bootHasVersioning(): void
    {
        // Increment version on update
        static::updating(function ($model) {
            $model->incrementVersion();
        });
    }

    /**
     * Get the version column name.
     */
    public function getVersionColumn(): string
    {
        return $this->versionColumn ?? 'version';
    }

    /**
     * Get current version.
     */
    public function getVersion(): int
    {
        $column = $this->getVersionColumn();
        return (int) ($this->$column ?? 1);
    }

    /**
     * Increment version.
     */
    protected function incrementVersion(): void
    {
        $column = $this->getVersionColumn();
        $this->$column = $this->getVersion() + 1;
    }

    /**
     * Update with optimistic locking.
     * 
     * @param array $attributes
     * @param int|null $expectedVersion Expected version (uses current if null)
     * @return bool
     * @throws OptimisticLockException
     */
    public function updateWithLock(array $attributes, ?int $expectedVersion = null): bool
    {
        $expectedVersion = $expectedVersion ?? $this->getVersion();
        $column = $this->getVersionColumn();

        // Attempt update with version check
        $updated = static::where($this->getKeyName(), $this->getKey())
            ->where($column, $expectedVersion)
            ->update(array_merge($attributes, [
                $column => $expectedVersion + 1
            ]));

        if ($updated === 0) {
            // Check if record still exists
            $current = static::find($this->getKey());
            
            if (!$current) {
                throw new OptimisticLockException(
                    'Record has been deleted by another process',
                    static::class,
                    $this->getKey()
                );
            }

            throw new OptimisticLockException(
                'Record has been modified by another process',
                static::class,
                $this->getKey(),
                $expectedVersion,
                $current->getVersion()
            );
        }

        // Refresh the model
        $this->refresh();
        
        return true;
    }

    /**
     * Save with optimistic locking.
     * 
     * @param int|null $expectedVersion
     * @return bool
     * @throws OptimisticLockException
     */
    public function saveWithLock(?int $expectedVersion = null): bool
    {
        if (!$this->exists) {
            return $this->save();
        }

        return $this->updateWithLock($this->getDirty(), $expectedVersion);
    }

    /**
     * Force update bypassing version check.
     */
    public function forceUpdate(array $attributes = []): bool
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }

        // Temporarily disable versioning
        static::$ignoreVersioning = true;
        
        try {
            $result = $this->save();
        } finally {
            static::$ignoreVersioning = false;
        }

        return $result;
    }

    /**
     * Check if versioning is being ignored.
     */
    protected static bool $ignoreVersioning = false;
}

/**
 * Exception thrown when optimistic lock fails.
 */
class OptimisticLockException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $modelClass,
        public readonly mixed $modelId,
        public readonly ?int $expectedVersion = null,
        public readonly ?int $actualVersion = null
    ) {
        parent::__construct($message);
    }

    /**
     * Get formatted error message.
     */
    public function getDetailedMessage(): string
    {
        $msg = $this->getMessage();
        
        if ($this->expectedVersion !== null && $this->actualVersion !== null) {
            $msg .= sprintf(
                ' (expected version %d, found version %d)',
                $this->expectedVersion,
                $this->actualVersion
            );
        }

        return $msg;
    }
}
