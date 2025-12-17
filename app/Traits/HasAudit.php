<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Audit\AuditService;

/**
 * HasAudit - Trait for models that need audit logging.
 * 
 * Usage:
 * 
 * class Invoice extends Model
 * {
 *     use HasAudit;
 *     
 *     // Optionally exclude fields from auditing
 *     protected array $auditExclude = ['some_field'];
 *     
 *     // Optionally include only specific fields
 *     protected array $auditInclude = ['status', 'total'];
 * }
 */
trait HasAudit
{
    /**
     * Boot the trait.
     */
    public static function bootHasAudit(): void
    {
        static::created(function ($model) {
            if ($model->shouldAudit()) {
                app(AuditService::class)->logCreate($model);
            }
        });

        static::updating(function ($model) {
            if ($model->shouldAudit()) {
                $model->auditOldValues = $model->getOriginal();
            }
        });

        static::updated(function ($model) {
            if ($model->shouldAudit() && isset($model->auditOldValues)) {
                app(AuditService::class)->logUpdate(
                    $model,
                    $model->auditOldValues,
                    $model->getAttributes()
                );
                unset($model->auditOldValues);
            }
        });

        static::deleted(function ($model) {
            if ($model->shouldAudit()) {
                app(AuditService::class)->logDelete($model);
            }
        });

        // Handle soft deletes
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                if ($model->shouldAudit()) {
                    app(AuditService::class)->logRestore($model);
                }
            });
        }
    }

    /**
     * Temporary storage for old values during update.
     */
    protected array $auditOldValues = [];

    /**
     * Check if auditing should be performed.
     */
    protected function shouldAudit(): bool
    {
        return $this->auditEnabled ?? true;
    }

    /**
     * Get audit history for this model.
     */
    public function auditHistory(int $limit = 50)
    {
        return app(AuditService::class)->history($this, $limit);
    }

    /**
     * Get the last audit entry.
     */
    public function lastAudit()
    {
        return $this->auditHistory(1)->first();
    }

    /**
     * Restore to a previous audit state.
     */
    public function restoreToAudit(int $auditId): bool
    {
        return app(AuditService::class)->restore($this, $auditId);
    }

    /**
     * Get snapshot at a specific audit point.
     */
    public function snapshotAt(int $auditId): array
    {
        return app(AuditService::class)->snapshot($this, $auditId);
    }

    /**
     * Perform an action without auditing.
     */
    public function withoutAudit(callable $callback): mixed
    {
        $this->auditEnabled = false;

        try {
            return $callback($this);
        } finally {
            $this->auditEnabled = true;
        }
    }

    /**
     * Log a custom audit event.
     */
    public function logAuditEvent(string $action, ?string $description = null, array $metadata = []): int
    {
        return app(AuditService::class)->logCustom($this, $action, $description, $metadata);
    }
}
