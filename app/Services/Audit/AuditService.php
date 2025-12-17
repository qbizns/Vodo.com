<?php

declare(strict_types=1);

namespace App\Services\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Collection;

/**
 * Audit Service - Comprehensive audit logging for all record changes.
 * 
 * Features:
 * - Automatic change tracking
 * - Before/after snapshots
 * - User and request context
 * - Configurable retention
 * - Query API for audit history
 * - Diff generation
 * - Restore capability
 * 
 * Example usage:
 * 
 * // Log a change manually
 * $auditService->log($invoice, 'update', [
 *     'old' => ['status' => 'draft'],
 *     'new' => ['status' => 'sent'],
 * ]);
 * 
 * // Get audit history
 * $history = $auditService->history($invoice);
 * 
 * // Get diff between versions
 * $diff = $auditService->diff($invoice, $auditId1, $auditId2);
 * 
 * // Restore to previous version
 * $auditService->restore($invoice, $auditId);
 */
class AuditService
{
    /**
     * Audit event types.
     */
    public const EVENT_CREATE = 'create';
    public const EVENT_UPDATE = 'update';
    public const EVENT_DELETE = 'delete';
    public const EVENT_RESTORE = 'restore';
    public const EVENT_LOGIN = 'login';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_ACCESS = 'access';
    public const EVENT_EXPORT = 'export';
    public const EVENT_IMPORT = 'import';
    public const EVENT_CUSTOM = 'custom';

    /**
     * Fields to exclude from auditing by default.
     */
    protected array $excludedFields = [
        'password',
        'remember_token',
        'api_token',
        'secret',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Entity-specific excluded fields.
     */
    protected array $entityExcludedFields = [];

    /**
     * Whether auditing is enabled.
     */
    protected bool $enabled = true;

    /**
     * Log an audit entry.
     */
    public function log(
        Model|string $subject,
        string $event,
        array $data = [],
        ?int $userId = null,
        ?string $description = null
    ): int {
        if (!$this->enabled) {
            return 0;
        }

        $subjectType = is_string($subject) ? $subject : get_class($subject);
        $subjectId = is_string($subject) ? null : $subject->getKey();

        $userId = $userId ?? Auth::id();
        
        return DB::table('audit_logs')->insertGetId([
            'auditable_type' => $subjectType,
            'auditable_id' => $subjectId,
            'event' => $event,
            'old_values' => isset($data['old']) ? json_encode($this->filterSensitive($data['old'], $subjectType)) : null,
            'new_values' => isset($data['new']) ? json_encode($this->filterSensitive($data['new'], $subjectType)) : null,
            'user_id' => $userId,
            'user_type' => $userId ? $this->getUserType() : null,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'batch_id' => $data['batch_id'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Log model creation.
     */
    public function logCreate(Model $model, ?array $attributes = null): int
    {
        $attributes = $attributes ?? $model->getAttributes();

        return $this->log($model, self::EVENT_CREATE, [
            'new' => $attributes,
        ], description: "Created {$this->getModelName($model)}");
    }

    /**
     * Log model update.
     */
    public function logUpdate(Model $model, array $oldValues, array $newValues): int
    {
        // Filter to only changed values
        $changes = [];
        foreach ($newValues as $key => $value) {
            if (array_key_exists($key, $oldValues) && $oldValues[$key] !== $value) {
                $changes[$key] = $value;
            }
        }

        if (empty($changes)) {
            return 0;
        }

        return $this->log($model, self::EVENT_UPDATE, [
            'old' => array_intersect_key($oldValues, $changes),
            'new' => $changes,
        ], description: "Updated {$this->getModelName($model)}");
    }

    /**
     * Log model deletion.
     */
    public function logDelete(Model $model): int
    {
        return $this->log($model, self::EVENT_DELETE, [
            'old' => $model->getAttributes(),
        ], description: "Deleted {$this->getModelName($model)}");
    }

    /**
     * Log model restore (soft delete).
     */
    public function logRestore(Model $model): int
    {
        return $this->log($model, self::EVENT_RESTORE, [
            'new' => $model->getAttributes(),
        ], description: "Restored {$this->getModelName($model)}");
    }

    /**
     * Log a custom event.
     */
    public function logCustom(
        Model|string $subject,
        string $action,
        ?string $description = null,
        array $metadata = []
    ): int {
        return $this->log($subject, self::EVENT_CUSTOM, [
            'metadata' => array_merge(['action' => $action], $metadata),
        ], description: $description);
    }

    /**
     * Log user login.
     */
    public function logLogin(?int $userId = null): int
    {
        return $this->log('App\\Models\\User', self::EVENT_LOGIN, [], $userId, 'User logged in');
    }

    /**
     * Log user logout.
     */
    public function logLogout(?int $userId = null): int
    {
        return $this->log('App\\Models\\User', self::EVENT_LOGOUT, [], $userId, 'User logged out');
    }

    /**
     * Log data export.
     */
    public function logExport(string $entityType, int $count, string $format = 'csv'): int
    {
        return $this->log($entityType, self::EVENT_EXPORT, [
            'metadata' => [
                'count' => $count,
                'format' => $format,
            ],
        ], description: "Exported {$count} {$entityType} records as {$format}");
    }

    /**
     * Log data import.
     */
    public function logImport(string $entityType, int $count, int $success, int $failed): int
    {
        return $this->log($entityType, self::EVENT_IMPORT, [
            'metadata' => [
                'total' => $count,
                'success' => $success,
                'failed' => $failed,
            ],
        ], description: "Imported {$success}/{$count} {$entityType} records");
    }

    /**
     * Get audit history for a model.
     */
    public function history(Model $model, int $limit = 50): Collection
    {
        return DB::table('audit_logs')
            ->where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($log) => $this->hydrateLog($log));
    }

    /**
     * Get audit history for an entity type.
     */
    public function historyForType(string $type, int $limit = 100): Collection
    {
        return DB::table('audit_logs')
            ->where('auditable_type', $type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($log) => $this->hydrateLog($log));
    }

    /**
     * Get audit history for a user.
     */
    public function historyForUser(int $userId, int $limit = 100): Collection
    {
        return DB::table('audit_logs')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($log) => $this->hydrateLog($log));
    }

    /**
     * Get a specific audit log entry.
     */
    public function find(int $id): ?object
    {
        $log = DB::table('audit_logs')->where('id', $id)->first();
        return $log ? $this->hydrateLog($log) : null;
    }

    /**
     * Get diff between two audit versions.
     */
    public function diff(Model $model, int $fromId, int $toId): array
    {
        $from = $this->find($fromId);
        $to = $this->find($toId);

        if (!$from || !$to) {
            return [];
        }

        $fromValues = $from->new_values ?? $from->old_values ?? [];
        $toValues = $to->new_values ?? [];

        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($fromValues), array_keys($toValues)));

        foreach ($allKeys as $key) {
            $oldValue = $fromValues[$key] ?? null;
            $newValue = $toValues[$key] ?? null;

            if ($oldValue !== $newValue) {
                $diff[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $diff;
    }

    /**
     * Restore model to a previous audit state.
     */
    public function restore(Model $model, int $auditId): bool
    {
        $audit = $this->find($auditId);

        if (!$audit) {
            return false;
        }

        $values = $audit->event === self::EVENT_DELETE 
            ? $audit->old_values 
            : ($audit->new_values ?? $audit->old_values);

        if (empty($values)) {
            return false;
        }

        // Remove non-fillable fields
        $fillable = $model->getFillable();
        if (!empty($fillable)) {
            $values = array_intersect_key($values, array_flip($fillable));
        }

        // Remove guarded fields
        unset($values['id'], $values['created_at'], $values['updated_at']);

        return $model->update($values);
    }

    /**
     * Get snapshot of model at a specific audit point.
     */
    public function snapshot(Model $model, int $auditId): array
    {
        $audit = $this->find($auditId);

        if (!$audit) {
            return [];
        }

        return $audit->new_values ?? $audit->old_values ?? [];
    }

    /**
     * Search audit logs.
     */
    public function search(array $criteria, int $limit = 100): Collection
    {
        $query = DB::table('audit_logs');

        if (isset($criteria['type'])) {
            $query->where('auditable_type', $criteria['type']);
        }

        if (isset($criteria['event'])) {
            $query->where('event', $criteria['event']);
        }

        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (isset($criteria['from'])) {
            $query->where('created_at', '>=', $criteria['from']);
        }

        if (isset($criteria['to'])) {
            $query->where('created_at', '<=', $criteria['to']);
        }

        if (isset($criteria['ip'])) {
            $query->where('ip_address', $criteria['ip']);
        }

        if (isset($criteria['search'])) {
            $search = '%' . $criteria['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', $search)
                  ->orWhere('old_values', 'like', $search)
                  ->orWhere('new_values', 'like', $search);
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($log) => $this->hydrateLog($log));
    }

    /**
     * Batch multiple audit logs together.
     */
    public function batch(callable $callback): string
    {
        $batchId = uniqid('audit_batch_', true);

        // Store batch ID in context for the callback
        $this->currentBatchId = $batchId;

        try {
            $callback($this);
        } finally {
            $this->currentBatchId = null;
        }

        return $batchId;
    }

    /**
     * Current batch ID.
     */
    protected ?string $currentBatchId = null;

    /**
     * Clean old audit logs.
     */
    public function cleanup(int $daysToKeep = 90): int
    {
        $cutoff = now()->subDays($daysToKeep);

        return DB::table('audit_logs')
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    /**
     * Get audit statistics.
     */
    public function statistics(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'total' => DB::table('audit_logs')
                ->where('created_at', '>=', $since)
                ->count(),
            'by_event' => DB::table('audit_logs')
                ->where('created_at', '>=', $since)
                ->selectRaw('event, COUNT(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
            'by_type' => DB::table('audit_logs')
                ->where('created_at', '>=', $since)
                ->selectRaw('auditable_type, COUNT(*) as count')
                ->groupBy('auditable_type')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'auditable_type')
                ->toArray(),
            'by_user' => DB::table('audit_logs')
                ->where('created_at', '>=', $since)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'user_id')
                ->toArray(),
            'by_day' => DB::table('audit_logs')
                ->where('created_at', '>=', $since)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }

    /**
     * Enable auditing.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable auditing.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Execute callback without auditing.
     */
    public function withoutAuditing(callable $callback): mixed
    {
        $this->disable();

        try {
            return $callback();
        } finally {
            $this->enable();
        }
    }

    /**
     * Set excluded fields for an entity.
     */
    public function setExcludedFields(string $entityType, array $fields): void
    {
        $this->entityExcludedFields[$entityType] = $fields;
    }

    /**
     * Filter sensitive fields from data.
     */
    protected function filterSensitive(array $data, string $entityType): array
    {
        $excluded = array_merge(
            $this->excludedFields,
            $this->entityExcludedFields[$entityType] ?? []
        );

        return array_diff_key($data, array_flip($excluded));
    }

    /**
     * Hydrate a log entry from database.
     */
    protected function hydrateLog(object $log): object
    {
        $log->old_values = $log->old_values ? json_decode($log->old_values, true) : [];
        $log->new_values = $log->new_values ? json_decode($log->new_values, true) : [];
        $log->tags = $log->tags ? json_decode($log->tags, true) : [];
        $log->metadata = $log->metadata ? json_decode($log->metadata, true) : [];

        return $log;
    }

    /**
     * Get user type from guard.
     */
    protected function getUserType(): string
    {
        foreach (['web', 'admin', 'owner', 'api'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }
        return 'unknown';
    }

    /**
     * Get human-readable model name.
     */
    protected function getModelName(Model $model): string
    {
        $className = class_basename($model);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $className));
    }
}
