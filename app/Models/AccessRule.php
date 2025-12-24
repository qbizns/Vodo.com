<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
 * @property string|null $creator_type
 * @property int|null $creator_id
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
        'creator_type',
        'creator_id',
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

    /**
     * Get the creator (polymorphic - can be User or Admin)
     */
    public function creator(): MorphTo
    {
        return $this->morphTo();
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

        // Wildcard pattern (e.g., 10.0.0.* or 192.168.*.*)
        if (is_string($range) && str_contains($range, '*')) {
            // Escape special regex chars except *, then replace * with \d+
            $pattern = preg_quote($range, '/');
            $pattern = str_replace('\*', '\d+', $pattern);
            return (bool) preg_match('/^' . $pattern . '$/', $ip);
        }

        return $ip === $range;
    }

    // =========================================================================
    // Test Evaluation
    // =========================================================================

    /**
     * Evaluate rule with test data (for rule testing modal)
     *
     * @param array $testData ['time' => 'H:i', 'day' => 'string', 'ip' => 'string', 'role' => 'string', 'custom' => 'string', 'permission' => 'string']
     * @return array ['matches' => bool, 'action' => string, 'permission_matches' => bool, 'condition_results' => array]
     */
    public function evaluateWithTestData(array $testData): array
    {
        $permission = $testData['permission'] ?? '';
        
        // Check permission matching first
        $permissionMatches = $this->matchesPermission($permission);
        
        if (!$permissionMatches) {
            return [
                'matches' => false,
                'action' => $this->action,
                'permission_matches' => false,
                'condition_results' => [],
                'summary' => 'Rule does not apply - permission does not match any target',
            ];
        }
        
        // Evaluate each condition with test data
        $conditionResults = [];
        $allConditionsPass = true;
        
        foreach ($this->conditions ?? [] as $index => $condition) {
            $type = $condition['type'] ?? 'unknown';
            $operator = $condition['operator'] ?? '';
            $value = $condition['value'] ?? null;
            
            $result = $this->evaluateConditionWithTestData($condition, $testData);
            
            $conditionResults[] = [
                'index' => $index,
                'type' => $type,
                'operator' => $operator,
                'value' => $value,
                'test_value' => $this->getTestValueForCondition($type, $testData),
                'passes' => $result['passes'],
                'explanation' => $result['explanation'],
            ];
            
            if (!$result['passes']) {
                $allConditionsPass = false;
            }
        }
        
        // Determine outcome
        // If conditions pass (all match), the rule triggers its action
        // If conditions fail, the rule doesn't apply
        $ruleMatches = $allConditionsPass;
        
        $summary = $this->buildTestSummary($ruleMatches, $permissionMatches);
        
        return [
            'matches' => $ruleMatches,
            'action' => $this->action,
            'permission_matches' => $permissionMatches,
            'condition_results' => $conditionResults,
            'summary' => $summary,
        ];
    }

    /**
     * Evaluate a single condition with test data
     */
    protected function evaluateConditionWithTestData(array $condition, array $testData): array
    {
        $type = $condition['type'] ?? null;
        $operator = $condition['operator'] ?? '';
        $value = $condition['value'] ?? null;
        
        switch ($type) {
            case self::CONDITION_TIME:
                return $this->evaluateTimeConditionWithTest($operator, $value, $testData['time'] ?? null);
                
            case self::CONDITION_DAY:
                return $this->evaluateDayConditionWithTest($operator, $value, $testData['day'] ?? null);
                
            case self::CONDITION_IP:
                return $this->evaluateIpConditionWithTest($operator, $value, $testData['ip'] ?? null);
                
            case self::CONDITION_ROLE:
                return $this->evaluateRoleConditionWithTest($operator, $value, $testData['role'] ?? null);
                
            case self::CONDITION_ATTRIBUTE:
                return $this->evaluateAttributeConditionWithTest($condition, $testData['custom'] ?? null);
                
            default:
                return ['passes' => true, 'explanation' => 'Unknown condition type - passes by default'];
        }
    }

    /**
     * Evaluate time condition with test time
     */
    protected function evaluateTimeConditionWithTest(string $operator, $value, ?string $testTime): array
    {
        if (!$testTime) {
            $testTime = now()->format('H:i');
        }
        
        // Handle array format [from, to]
        if (is_array($value) && isset($value[0], $value[1])) {
            $start = $value[0];
            $end = $value[1];
        } elseif (is_array($value)) {
            $start = $value['start'] ?? '00:00';
            $end = $value['end'] ?? '23:59';
        } else {
            $start = '00:00';
            $end = '23:59';
        }
        
        $passes = match ($operator) {
            'between' => $testTime >= $start && $testTime <= $end,
            'not_between' => $testTime < $start || $testTime > $end,
            'before' => $testTime < $value,
            'after' => $testTime > $value,
            default => true,
        };
        
        $explanation = match ($operator) {
            'between' => "Time ({$testTime}) " . ($passes ? 'is' : 'is not') . " between {$start} and {$end}",
            'not_between' => "Time ({$testTime}) " . ($passes ? 'is not' : 'is') . " between {$start} and {$end}",
            'before' => "Time ({$testTime}) " . ($passes ? 'is' : 'is not') . " before {$value}",
            'after' => "Time ({$testTime}) " . ($passes ? 'is' : 'is not') . " after {$value}",
            default => "Time condition evaluated",
        };
        
        return ['passes' => $passes, 'explanation' => $explanation];
    }

    /**
     * Evaluate day condition with test day
     */
    protected function evaluateDayConditionWithTest(string $operator, $value, ?string $testDay): array
    {
        if (!$testDay) {
            $testDay = now()->format('l');
        }
        
        $testDayLower = strtolower($testDay);
        $days = array_map('strtolower', (array) $value);
        $daysDisplay = implode(', ', (array) $value);
        
        $passes = match ($operator) {
            'is', 'is_one_of' => in_array($testDayLower, $days),
            'is_not' => !in_array($testDayLower, $days),
            default => true,
        };
        
        $explanation = match ($operator) {
            'is', 'is_one_of' => "Day ({$testDay}) " . ($passes ? 'is' : 'is not') . " one of [{$daysDisplay}]",
            'is_not' => "Day ({$testDay}) " . ($passes ? 'is not' : 'is') . " [{$daysDisplay}]",
            default => "Day condition evaluated",
        };
        
        return ['passes' => $passes, 'explanation' => $explanation];
    }

    /**
     * Evaluate IP condition with test IP
     */
    protected function evaluateIpConditionWithTest(string $operator, $value, ?string $testIp): array
    {
        if (!$testIp) {
            return ['passes' => true, 'explanation' => 'No IP provided - condition passes by default'];
        }
        
        $passes = match ($operator) {
            'is' => $testIp === $value,
            'is_not' => $testIp !== $value,
            'starts_with' => str_starts_with($testIp, $value),
            'in_range' => $this->ipInRange($testIp, $value),
            'in_list' => in_array($testIp, (array) $value),
            default => true,
        };
        
        $valueDisplay = is_array($value) ? implode(', ', $value) : $value;
        
        $explanation = match ($operator) {
            'is' => "IP ({$testIp}) " . ($passes ? 'equals' : 'does not equal') . " {$valueDisplay}",
            'is_not' => "IP ({$testIp}) " . ($passes ? 'does not equal' : 'equals') . " {$valueDisplay}",
            'starts_with' => "IP ({$testIp}) " . ($passes ? 'starts with' : 'does not start with') . " {$valueDisplay}",
            'in_range' => "IP ({$testIp}) " . ($passes ? 'is in' : 'is not in') . " range {$valueDisplay}",
            'in_list' => "IP ({$testIp}) " . ($passes ? 'is in' : 'is not in') . " list [{$valueDisplay}]",
            default => "IP condition evaluated",
        };
        
        return ['passes' => $passes, 'explanation' => $explanation];
    }

    /**
     * Evaluate role condition with test role
     */
    protected function evaluateRoleConditionWithTest(string $operator, $value, ?string $testRole): array
    {
        if (!$testRole) {
            return ['passes' => true, 'explanation' => 'No role provided - condition passes by default'];
        }
        
        $testRoleLower = strtolower($testRole);
        $roles = array_map('strtolower', (array) $value);
        $rolesDisplay = implode(', ', (array) $value);
        
        $passes = match ($operator) {
            'is' => $testRoleLower === strtolower($value),
            'is_not' => $testRoleLower !== strtolower($value),
            'is_one_of' => in_array($testRoleLower, $roles),
            default => true,
        };
        
        $explanation = match ($operator) {
            'is' => "Role ({$testRole}) " . ($passes ? 'is' : 'is not') . " {$value}",
            'is_not' => "Role ({$testRole}) " . ($passes ? 'is not' : 'is') . " {$value}",
            'is_one_of' => "Role ({$testRole}) " . ($passes ? 'is in' : 'is not in') . " [{$rolesDisplay}]",
            default => "Role condition evaluated",
        };
        
        return ['passes' => $passes, 'explanation' => $explanation];
    }

    /**
     * Evaluate attribute condition with test custom value
     */
    protected function evaluateAttributeConditionWithTest(array $condition, ?string $testCustom): array
    {
        $attribute = $condition['attribute'] ?? 'custom';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;
        
        // Parse custom attribute format (e.g., "department=finance")
        $testValue = null;
        if ($testCustom && str_contains($testCustom, '=')) {
            [$testAttr, $testValue] = explode('=', $testCustom, 2);
            if ($testAttr !== $attribute) {
                $testValue = null;
            }
        }
        
        if ($testValue === null) {
            return ['passes' => true, 'explanation' => "No value for attribute '{$attribute}' - condition passes by default"];
        }
        
        $passes = match ($operator) {
            'equals' => $testValue == $value,
            'not_equals' => $testValue != $value,
            'contains' => str_contains($testValue, $value),
            'greater_than' => $testValue > $value,
            'less_than' => $testValue < $value,
            default => true,
        };
        
        $explanation = "{$attribute} ({$testValue}) " . ($passes ? 'matches' : 'does not match') . " condition ({$operator} {$value})";
        
        return ['passes' => $passes, 'explanation' => $explanation];
    }

    /**
     * Get the test value used for a condition type
     */
    protected function getTestValueForCondition(string $type, array $testData): ?string
    {
        return match ($type) {
            self::CONDITION_TIME => $testData['time'] ?? now()->format('H:i'),
            self::CONDITION_DAY => $testData['day'] ?? now()->format('l'),
            self::CONDITION_IP => $testData['ip'] ?? null,
            self::CONDITION_ROLE => $testData['role'] ?? null,
            self::CONDITION_ATTRIBUTE => $testData['custom'] ?? null,
            default => null,
        };
    }

    /**
     * Build summary message for test result
     */
    protected function buildTestSummary(bool $ruleMatches, bool $permissionMatches): string
    {
        if (!$permissionMatches) {
            return 'Rule does not apply - permission does not match any target';
        }
        
        if (!$ruleMatches) {
            return 'Rule conditions do NOT match - access would be ALLOWED';
        }
        
        return match ($this->action) {
            self::ACTION_DENY => 'Rule conditions MATCH - access would be DENIED',
            self::ACTION_LOG => 'Rule conditions MATCH - access would be ALLOWED but LOGGED',
            default => 'Rule conditions match',
        };
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
