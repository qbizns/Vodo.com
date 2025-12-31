<?php

declare(strict_types=1);

namespace App\Models\Enterprise;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $table = 'feature_flags';

    protected $fillable = [
        'key',
        'name',
        'description',
        'enabled',
        'rollout_percentage',
        'tenant_ids',
        'user_ids',
        'conditions',
        'starts_at',
        'ends_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'rollout_percentage' => 'integer',
            'tenant_ids' => 'array',
            'user_ids' => 'array',
            'conditions' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Check if the feature is enabled for a given context.
     */
    public function isEnabledFor(?int $tenantId = null, ?int $userId = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Check time constraints
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        // Check explicit tenant/user lists first
        if ($tenantId && $this->tenant_ids && in_array($tenantId, $this->tenant_ids)) {
            return true;
        }

        if ($userId && $this->user_ids && in_array($userId, $this->user_ids)) {
            return true;
        }

        // If explicit lists exist but didn't match, and no rollout, disable
        if (($this->tenant_ids || $this->user_ids) && $this->rollout_percentage === 0) {
            return false;
        }

        // Check rollout percentage
        if ($this->rollout_percentage === 100) {
            return true;
        }

        if ($this->rollout_percentage === 0) {
            return false;
        }

        // Consistent rollout based on tenant/user ID
        $identifier = $tenantId ?? $userId ?? 0;
        $hash = crc32($this->key . ':' . $identifier);
        $percentage = abs($hash) % 100;

        return $percentage < $this->rollout_percentage;
    }

    /**
     * Check conditions.
     */
    public function checkConditions(array $context): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field || !array_key_exists($field, $context)) {
                continue;
            }

            $contextValue = $context[$field];

            $result = match ($operator) {
                '=' => $contextValue == $value,
                '!=' => $contextValue != $value,
                '>' => $contextValue > $value,
                '>=' => $contextValue >= $value,
                '<' => $contextValue < $value,
                '<=' => $contextValue <= $value,
                'in' => in_array($contextValue, (array) $value),
                'not_in' => !in_array($contextValue, (array) $value),
                'contains' => str_contains($contextValue, $value),
                default => true,
            };

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    public function enable(): void
    {
        $this->update(['enabled' => true]);
    }

    public function disable(): void
    {
        $this->update(['enabled' => false]);
    }

    public function setRolloutPercentage(int $percentage): void
    {
        $this->update(['rollout_percentage' => min(100, max(0, $percentage))]);
    }

    public function addTenant(int $tenantId): void
    {
        $tenants = $this->tenant_ids ?? [];
        if (!in_array($tenantId, $tenants)) {
            $tenants[] = $tenantId;
            $this->update(['tenant_ids' => $tenants]);
        }
    }

    public function removeTenant(int $tenantId): void
    {
        $tenants = $this->tenant_ids ?? [];
        $tenants = array_values(array_filter($tenants, fn($id) => $id !== $tenantId));
        $this->update(['tenant_ids' => $tenants ?: null]);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeActive($query)
    {
        return $query->where('enabled', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}
