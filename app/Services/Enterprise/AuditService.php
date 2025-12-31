<?php

declare(strict_types=1);

namespace App\Services\Enterprise;

use App\Models\Enterprise\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Audit Service
 *
 * Provides comprehensive audit logging for compliance and security.
 */
class AuditService
{
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'secret',
        'token',
        'api_key',
        'private_key',
        'credit_card',
        'cvv',
        'ssn',
    ];

    protected array $piiFields = [
        'email',
        'phone',
        'address',
        'date_of_birth',
        'national_id',
    ];

    /**
     * Log a model event.
     */
    public function log(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $tags = [],
        array $metadata = []
    ): AuditLog {
        $oldValues = $this->sanitizeValues($oldValues);
        $newValues = $this->sanitizeValues($newValues);

        // Auto-detect tags
        $tags = array_merge($tags, $this->detectTags($model, $event, $oldValues, $newValues));

        return AuditLog::create([
            'uuid' => Str::uuid(),
            'tenant_id' => $this->getTenantId($model),
            'user_id' => Auth::id(),
            'user_type' => $this->getUserType(),
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'tags' => array_unique($tags),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a created event.
     */
    public function logCreated(Model $model, array $tags = [], array $metadata = []): AuditLog
    {
        return $this->log(
            'created',
            $model,
            null,
            $model->getAttributes(),
            $tags,
            $metadata
        );
    }

    /**
     * Log an updated event.
     */
    public function logUpdated(Model $model, array $tags = [], array $metadata = []): AuditLog
    {
        $dirty = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);

        return $this->log(
            'updated',
            $model,
            $original,
            $dirty,
            $tags,
            $metadata
        );
    }

    /**
     * Log a deleted event.
     */
    public function logDeleted(Model $model, array $tags = [], array $metadata = []): AuditLog
    {
        return $this->log(
            'deleted',
            $model,
            $model->getAttributes(),
            null,
            $tags,
            $metadata
        );
    }

    /**
     * Log an access event (for sensitive data viewing).
     */
    public function logAccessed(Model $model, array $fields = [], array $tags = [], array $metadata = []): AuditLog
    {
        return $this->log(
            'accessed',
            $model,
            null,
            null,
            array_merge($tags, ['access']),
            array_merge($metadata, ['accessed_fields' => $fields])
        );
    }

    /**
     * Log an export event.
     */
    public function logExport(
        string $exportType,
        int $recordCount,
        array $filters = [],
        array $tags = [],
        array $metadata = []
    ): AuditLog {
        // Create a dummy model for the audit log structure
        $model = new class extends Model {
            protected $table = 'exports';
        };

        return $this->log(
            'exported',
            $model,
            null,
            null,
            array_merge($tags, ['export']),
            array_merge($metadata, [
                'export_type' => $exportType,
                'record_count' => $recordCount,
                'filters' => $filters,
            ])
        );
    }

    /**
     * Log a security event.
     */
    public function logSecurity(
        string $event,
        string $description,
        array $metadata = []
    ): AuditLog {
        $model = new class extends Model {
            protected $table = 'security_events';
        };

        return $this->log(
            $event,
            $model,
            null,
            null,
            ['security'],
            array_merge($metadata, ['description' => $description])
        );
    }

    /**
     * Log a login event.
     */
    public function logLogin(int $userId, bool $success, ?string $reason = null): AuditLog
    {
        $model = new class extends Model {
            protected $table = 'auth_events';
        };

        return $this->log(
            $success ? 'login_success' : 'login_failed',
            $model,
            null,
            ['user_id' => $userId],
            ['security', 'authentication'],
            ['reason' => $reason]
        );
    }

    /**
     * Get audit trail for a model.
     */
    public function getAuditTrail(Model $model, int $limit = 100): \Illuminate\Support\Collection
    {
        return AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Search audit logs.
     */
    public function search(array $filters, int $perPage = 50)
    {
        $query = AuditLog::query();

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (isset($filters['model'])) {
            $query->where('auditable_type', $filters['model']);
        }

        if (isset($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        if (isset($filters['ip'])) {
            $query->where('ip_address', $filters['ip']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Clean up old audit logs based on retention policy.
     */
    public function cleanup(int $retentionDays = 90): int
    {
        $cutoff = now()->subDays($retentionDays);

        // Keep security and financial events longer
        return AuditLog::where('created_at', '<', $cutoff)
            ->whereNotIn('tags', [['security'], ['financial']])
            ->delete();
    }

    /**
     * Export audit logs for compliance.
     */
    public function export(array $filters): \Illuminate\Support\Collection
    {
        return $this->search($filters, PHP_INT_MAX)->getCollection();
    }

    /**
     * Sanitize sensitive values.
     */
    protected function sanitizeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $values[$key] = '[REDACTED]';
            } elseif ($this->isPiiField($key)) {
                $values[$key] = $this->maskPii($value);
            }
        }

        return $values;
    }

    /**
     * Check if a field is sensitive.
     */
    protected function isSensitiveField(string $field): bool
    {
        $field = strtolower($field);

        foreach ($this->sensitiveFields as $sensitive) {
            if (str_contains($field, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a field contains PII.
     */
    protected function isPiiField(string $field): bool
    {
        $field = strtolower($field);

        foreach ($this->piiFields as $pii) {
            if (str_contains($field, $pii)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask PII data.
     */
    protected function maskPii(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
    }

    /**
     * Detect tags based on model and changes.
     */
    protected function detectTags(Model $model, string $event, ?array $old, ?array $new): array
    {
        $tags = [];
        $modelClass = get_class($model);

        // Financial models
        if (str_contains($modelClass, 'Invoice') ||
            str_contains($modelClass, 'Payment') ||
            str_contains($modelClass, 'Transaction') ||
            str_contains($modelClass, 'Payout')) {
            $tags[] = 'financial';
        }

        // User/auth models
        if (str_contains($modelClass, 'User') ||
            str_contains($modelClass, 'Auth')) {
            $tags[] = 'security';
        }

        // Check for PII changes
        $allValues = array_merge($old ?? [], $new ?? []);
        foreach (array_keys($allValues) as $key) {
            if ($this->isPiiField($key)) {
                $tags[] = 'pii';
                break;
            }
        }

        // Deletion is always notable
        if ($event === 'deleted') {
            $tags[] = 'deletion';
        }

        return $tags;
    }

    /**
     * Get tenant ID from model.
     */
    protected function getTenantId(Model $model): ?int
    {
        if (method_exists($model, 'getTenantId')) {
            return $model->getTenantId();
        }

        if (isset($model->tenant_id)) {
            return $model->tenant_id;
        }

        return null;
    }

    /**
     * Get user type.
     */
    protected function getUserType(): string
    {
        if (!Auth::check()) {
            return 'system';
        }

        if (Request::is('api/*')) {
            return 'api';
        }

        return 'user';
    }
}
