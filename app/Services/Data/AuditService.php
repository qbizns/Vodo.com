<?php

declare(strict_types=1);

namespace App\Services\Data;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Audit Service
 *
 * Provides audit trail functionality for tracking changes to records.
 * Automatically logs create, update, and delete operations.
 *
 * @example Log a change manually
 * ```php
 * $auditService->log($model, 'updated', [
 *     'name' => ['old' => 'John', 'new' => 'Jane'],
 * ]);
 * ```
 *
 * @example Get audit history
 * ```php
 * $history = $auditService->getHistory($model);
 * ```
 */
class AuditService
{
    /**
     * Events to track.
     */
    protected array $events = ['created', 'updated', 'deleted', 'restored'];

    /**
     * Fields to exclude from audit.
     */
    protected array $globalExcludedFields = [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
    ];

    /**
     * Log an audit entry.
     *
     * @param Model $model The affected model
     * @param string $event Event type (created, updated, deleted, restored)
     * @param array $changes Changed values
     * @param string|null $comment Optional comment
     * @return AuditLog
     */
    public function log(Model $model, string $event, array $changes = [], ?string $comment = null): AuditLog
    {
        return AuditLog::create([
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $changes['old'] ?? [],
            'new_values' => $changes['new'] ?? [],
            'user_id' => Auth::id(),
            'user_type' => Auth::user() ? get_class(Auth::user()) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'comment' => $comment,
            'tags' => $this->generateTags($model, $event),
        ]);
    }

    /**
     * Generate tags for the audit entry.
     */
    protected function generateTags(Model $model, string $event): array
    {
        $tags = [$event];

        // Add model-specific tags
        if (method_exists($model, 'getAuditTags')) {
            $tags = array_merge($tags, $model->getAuditTags());
        }

        // Add entity name if available
        if (method_exists($model, 'getEntityName')) {
            $tags[] = 'entity:' . $model->getEntityName();
        }

        return $tags;
    }

    /**
     * Get audit history for a model.
     *
     * @param Model $model The model instance
     * @param int|null $limit Limit results
     * @return Collection
     */
    public function getHistory(Model $model, ?int $limit = null): Collection
    {
        $query = AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey())
            ->orderBy('created_at', 'desc')
            ->with('user');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get audit history by type.
     *
     * @param string $modelType Model class name
     * @param int|null $limit Limit results
     * @return Collection
     */
    public function getHistoryByType(string $modelType, ?int $limit = null): Collection
    {
        $query = AuditLog::where('auditable_type', $modelType)
            ->orderBy('created_at', 'desc')
            ->with('user');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get recent activity across all models.
     *
     * @param int $limit Number of entries
     * @return Collection
     */
    public function getRecentActivity(int $limit = 50): Collection
    {
        return AuditLog::orderBy('created_at', 'desc')
            ->with('user')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity for a specific user.
     *
     * @param int $userId User ID
     * @param int|null $limit Limit results
     * @return Collection
     */
    public function getUserActivity(int $userId, ?int $limit = null): Collection
    {
        $query = AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Calculate changes between old and new values.
     *
     * @param Model $model Model instance
     * @return array
     */
    public function calculateChanges(Model $model): array
    {
        $dirty = $model->getDirty();
        $original = $model->getOriginal();

        $excludedFields = $this->getExcludedFields($model);

        $old = [];
        $new = [];

        foreach ($dirty as $key => $value) {
            if (in_array($key, $excludedFields)) {
                continue;
            }

            $old[$key] = $original[$key] ?? null;
            $new[$key] = $value;
        }

        return ['old' => $old, 'new' => $new];
    }

    /**
     * Get excluded fields for a model.
     */
    protected function getExcludedFields(Model $model): array
    {
        $excluded = $this->globalExcludedFields;

        if (method_exists($model, 'getExcludedAuditFields')) {
            $excluded = array_merge($excluded, $model->getExcludedAuditFields());
        }

        return $excluded;
    }

    /**
     * Prune old audit records.
     *
     * @param int $days Keep records for this many days
     * @return int Number of deleted records
     */
    public function prune(int $days = 90): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Restore a model to a specific audit state.
     *
     * @param Model $model Model instance
     * @param int $auditId Audit log ID to restore to
     * @return bool
     */
    public function restore(Model $model, int $auditId): bool
    {
        $audit = AuditLog::findOrFail($auditId);

        if ($audit->auditable_type !== get_class($model) ||
            $audit->auditable_id !== $model->getKey()) {
            throw new \InvalidArgumentException('Audit log does not match model');
        }

        $values = $audit->event === 'deleted'
            ? $audit->old_values
            : $audit->new_values;

        $model->fill($values);
        $model->save();

        $this->log($model, 'restored', [], "Restored to state from audit #{$auditId}");

        return true;
    }

    /**
     * Compare two audit records.
     *
     * @param int $auditId1 First audit ID
     * @param int $auditId2 Second audit ID
     * @return array Diff between the two states
     */
    public function compare(int $auditId1, int $auditId2): array
    {
        $audit1 = AuditLog::findOrFail($auditId1);
        $audit2 = AuditLog::findOrFail($auditId2);

        $state1 = array_merge($audit1->old_values ?? [], $audit1->new_values ?? []);
        $state2 = array_merge($audit2->old_values ?? [], $audit2->new_values ?? []);

        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($state1), array_keys($state2)));

        foreach ($allKeys as $key) {
            $val1 = $state1[$key] ?? null;
            $val2 = $state2[$key] ?? null;

            if ($val1 !== $val2) {
                $diff[$key] = [
                    'from' => $val1,
                    'to' => $val2,
                ];
            }
        }

        return $diff;
    }
}
