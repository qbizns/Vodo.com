<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Access Rule Model
 *
 * Defines conditional access rules for fine-grained permission control (ABAC).
 * Rules can deny or log access based on conditions like time, IP, role, or attributes.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property array $permissions Array of permission slugs this rule applies to
 * @property array $conditions Array of condition objects
 * @property string $action 'deny' or 'log'
 * @property int $priority Lower = higher priority
 * @property bool $is_active
 * @property int $retention_days
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccessRule extends Model
{
    protected $table = 'access_rules';

    protected $fillable = [
        'name',
        'description',
        'permissions',
        'conditions',
        'action',
        'priority',
        'is_active',
        'retention_days',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'retention_days' => 'integer',
    ];

    protected $attributes = [
        'action' => 'deny',
        'priority' => 100,
        'is_active' => true,
        'retention_days' => 90,
    ];

    // =========================================================================
    // Action Constants
    // =========================================================================

    public const ACTION_DENY = 'deny';
    public const ACTION_LOG = 'log';

    // =========================================================================
    // Condition Type Constants
    // =========================================================================

    public const CONDITION_TIME = 'time';
    public const CONDITION_DAY = 'day';
    public const CONDITION_IP = 'ip';
    public const CONDITION_ROLE = 'role';
    public const CONDITION_ATTRIBUTE = 'attribute';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority');
    }

    public function scopeForPermission(Builder $query, string $permission): Builder
    {
        return $query->whereJsonContains('permissions', $permission);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // =========================================================================
    // Permission Matching
    // =========================================================================

    /**
     * Check if this rule applies to a permission
     */
    public function matchesPermission(string $permission): bool
    {
        foreach ($this->permissions as $rulePermission) {
            if ($rulePermission === $permission) {
                return true;
            }

            // Wildcard matching
            if (str_ends_with($rulePermission, '.*')) {
                $prefix = rtrim($rulePermission, '.*');
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    // =========================================================================
    // Condition Evaluation
    // =========================================================================

    /**
     * Evaluate all conditions against context
     *
     * @param array $context ['user' => User, 'ip' => string, 'arguments' => mixed]
     * @return bool True if all conditions pass (access allowed), false if any fail
     */
    public function evaluateConditions(array $context): bool
    {
        foreach ($this->conditions as $condition) {
            $result = $this->evaluateCondition($condition, $context);

            // All conditions must pass (AND logic)
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(array $condition, array $context): bool
    {
        $type = $condition['type'] ?? null;

        return match ($type) {
            self::CONDITION_TIME => $this->evaluateTimeCondition($condition['operator'] ?? 'between', $condition['value'] ?? null),
            self::CONDITION_DAY => $this->evaluateDayCondition($condition['operator'] ?? 'is_one_of', $condition['value'] ?? []),
            self::CONDITION_IP => $this->evaluateIpCondition($condition['operator'] ?? 'is', $condition['value'] ?? null, $context['ip'] ?? null),
            self::CONDITION_ROLE => $this->evaluateRoleCondition($condition['operator'] ?? 'is', $condition['value'] ?? null, $context['user'] ?? null),
            self::CONDITION_ATTRIBUTE => $this->evaluateAttributeCondition($condition, $context),
            default => true, // Unknown condition types pass by default
        };
    }

    /**
     * Evaluate time-based condition
     */
    protected function evaluateTimeCondition(string $operator, $value): bool
    {
        $now = now();
        $currentTime = $now->format('H:i');

        return match ($operator) {
            'between' => is_array($value) && $currentTime >= ($value['start'] ?? '00:00') && $currentTime <= ($value['end'] ?? '23:59'),
            'not_between' => is_array($value) && ($currentTime < ($value['start'] ?? '00:00') || $currentTime > ($value['end'] ?? '23:59')),
            'before' => $currentTime < $value,
            'after' => $currentTime > $value,
            default => true,
        };
    }

    /**
     * Evaluate day-of-week condition
     */
    protected function evaluateDayCondition(string $operator, $value): bool
    {
        $today = strtolower(now()->format('l'));
        $days = array_map('strtolower', (array) $value);

        return match ($operator) {
            'is', 'is_one_of' => in_array($today, $days),
            'is_not' => !in_array($today, $days),
            default => true,
        };
    }

    /**
     * Evaluate IP address condition
     */
    protected function evaluateIpCondition(string $operator, $value, ?string $ip): bool
    {
        if (!$ip) {
            return true;
        }

        return match ($operator) {
            'is' => $ip === $value,
            'is_not' => $ip !== $value,
            'starts_with' => str_starts_with($ip, $value),
            'in_range' => $this->ipInRange($ip, $value),
            'in_list' => in_array($ip, (array) $value),
            default => true,
        };
    }

    /**
     * Evaluate role-based condition
     */
    protected function evaluateRoleCondition(string $operator, $value, $user): bool
    {
        if (!$user) {
            return true;
        }

        $roles = (array) $value;

        return match ($operator) {
            'is' => method_exists($user, 'hasRole') && $user->hasRole($value),
            'is_not' => method_exists($user, 'hasRole') && !$user->hasRole($value),
            'is_one_of' => method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles),
            default => true,
        };
    }

    /**
     * Evaluate attribute-based condition
     */
    protected function evaluateAttributeCondition(array $condition, array $context): bool
    {
        $attribute = $condition['attribute'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        $actualValue = data_get($context, $attribute);

        return match ($operator) {
            'equals' => $actualValue == $value,
            'not_equals' => $actualValue != $value,
            'greater_than' => $actualValue > $value,
            'less_than' => $actualValue < $value,
            'greater_or_equal' => $actualValue >= $value,
            'less_or_equal' => $actualValue <= $value,
            'contains' => is_string($actualValue) && str_contains($actualValue, $value),
            'not_contains' => is_string($actualValue) && !str_contains($actualValue, $value),
            'in' => in_array($actualValue, (array) $value),
            'not_in' => !in_array($actualValue, (array) $value),
            'is_null' => $actualValue === null,
            'is_not_null' => $actualValue !== null,
            default => true,
        };
    }

    /**
     * Check if IP is in range (CIDR or start-end)
     */
    protected function ipInRange(string $ip, $range): bool
    {
        if (is_array($range) && isset($range['start'], $range['end'])) {
            return ip2long($ip) >= ip2long($range['start'])
                && ip2long($ip) <= ip2long($range['end']);
        }

        // CIDR notation (e.g., 192.168.1.0/24)
        if (is_string($range) && str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range);
            $subnet = ip2long($subnet);
            $ipLong = ip2long($ip);
            $mask = -1 << (32 - (int) $bits);

            return ($ipLong & $mask) === ($subnet & $mask);
        }

        return $ip === $range;
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Get all active rules that apply to a permission
     */
    public static function getActiveRulesForPermission(string $permission): Collection
    {
        $cacheKey = "access_rules.permission.{$permission}";

        return Cache::remember($cacheKey, 300, function () use ($permission) {
            return static::active()
                ->ordered()
                ->get()
                ->filter(fn($rule) => $rule->matchesPermission($permission));
        });
    }

    /**
     * Clear rules cache
     */
    public static function clearRulesCache(): void
    {
        // Clear all cached rule queries
        $keys = Cache::get('access_rules_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('access_rules_cache_keys');
    }

    /**
     * Get condition type options for UI
     */
    public static function getConditionTypes(): array
    {
        return [
            self::CONDITION_TIME => [
                'label' => 'Time of Day',
                'icon' => 'clock',
                'operators' => ['between', 'not_between', 'before', 'after'],
            ],
            self::CONDITION_DAY => [
                'label' => 'Day of Week',
                'icon' => 'calendar',
                'operators' => ['is_one_of', 'is_not'],
            ],
            self::CONDITION_IP => [
                'label' => 'IP Address',
                'icon' => 'globe',
                'operators' => ['is', 'is_not', 'starts_with', 'in_range', 'in_list'],
            ],
            self::CONDITION_ROLE => [
                'label' => 'User Role',
                'icon' => 'shield',
                'operators' => ['is', 'is_not', 'is_one_of'],
            ],
            self::CONDITION_ATTRIBUTE => [
                'label' => 'Custom Attribute',
                'icon' => 'tag',
                'operators' => ['equals', 'not_equals', 'greater_than', 'less_than', 'contains', 'in'],
            ],
        ];
    }

    // =========================================================================
    // Instance Methods
    // =========================================================================

    /**
     * Check if this rule would deny access
     */
    public function isDenyRule(): bool
    {
        return $this->action === self::ACTION_DENY;
    }

    /**
     * Check if this rule only logs (doesn't block)
     */
    public function isLogOnlyRule(): bool
    {
        return $this->action === self::ACTION_LOG;
    }

    /**
     * Get a summary of conditions for display
     */
    public function getConditionsSummary(): string
    {
        if (empty($this->conditions)) {
            return 'No conditions';
        }

        $parts = [];
        foreach ($this->conditions as $condition) {
            $type = $condition['type'] ?? 'unknown';
            $parts[] = ucfirst($type);
        }

        return implode(', ', $parts);
    }

    /**
     * Get permissions summary
     */
    public function getPermissionsSummary(): string
    {
        if (empty($this->permissions)) {
            return 'No permissions';
        }

        $count = count($this->permissions);
        if ($count <= 3) {
            return implode(', ', $this->permissions);
        }

        return implode(', ', array_slice($this->permissions, 0, 3)) . " (+{" . ($count - 3) . "} more)";
    }

    /**
     * Export rule to array
     */
    public function toExportArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->permissions,
            'conditions' => $this->conditions,
            'action' => $this->action,
            'priority' => $this->priority,
        ];
    }

    /**
     * Boot method to clear cache on changes
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearRulesCache();
        });

        static::deleted(function () {
            static::clearRulesCache();
        });
    }
}
