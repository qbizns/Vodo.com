<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Permission Audit Model
 *
 * Logs all permission-related changes for audit trail.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $target_type
 * @property int|null $target_id
 * @property string|null $target_name
 * @property array|null $changes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class PermissionAudit extends Model
{
    public $timestamps = false;

    protected $table = 'permission_audit';

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'target_name',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    // =========================================================================
    // Action Constants
    // =========================================================================

    public const ACTION_ROLE_CREATED = 'role_created';
    public const ACTION_ROLE_UPDATED = 'role_updated';
    public const ACTION_ROLE_DELETED = 'role_deleted';
    public const ACTION_ROLE_DUPLICATED = 'role_duplicated';
    public const ACTION_PERMISSIONS_SYNCED = 'permissions_synced';
    public const ACTION_USER_ROLE_ASSIGNED = 'user_role_assigned';
    public const ACTION_USER_ROLE_REMOVED = 'user_role_removed';
    public const ACTION_PERMISSION_GRANTED = 'permission_granted';
    public const ACTION_PERMISSION_DENIED = 'permission_denied';
    public const ACTION_PERMISSION_OVERRIDE_CLEARED = 'permission_override_cleared';
    public const ACTION_ACCESS_RULE_CREATED = 'access_rule_created';
    public const ACTION_ACCESS_RULE_UPDATED = 'access_rule_updated';
    public const ACTION_ACCESS_RULE_DELETED = 'access_rule_deleted';
    public const ACTION_ACCESS_RULE_TRIGGERED = 'access_rule_triggered';
    public const ACTION_PLUGIN_DISABLED = 'plugin_disabled';
    public const ACTION_PLUGIN_ENABLED = 'plugin_enabled';
    public const ACTION_PLUGIN_UNINSTALLED = 'plugin_uninstalled';

    // =========================================================================
    // Target Type Constants
    // =========================================================================

    public const TARGET_ROLE = 'role';
    public const TARGET_PERMISSION = 'permission';
    public const TARGET_USER = 'user';
    public const TARGET_ACCESS_RULE = 'access_rule';
    public const TARGET_PLUGIN = 'plugin';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeForTarget(Builder $query, string $type, ?int $id = null): Builder
    {
        $query->where('target_type', $type);

        if ($id) {
            $query->where('target_id', $id);
        }

        return $query;
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('action', 'like', "%{$term}%")
              ->orWhere('target_name', 'like', "%{$term}%")
              ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$term}%"));
        });
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get human-readable action label
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_ROLE_CREATED => 'Role Created',
            self::ACTION_ROLE_UPDATED => 'Role Updated',
            self::ACTION_ROLE_DELETED => 'Role Deleted',
            self::ACTION_ROLE_DUPLICATED => 'Role Duplicated',
            self::ACTION_PERMISSIONS_SYNCED => 'Permissions Updated',
            self::ACTION_USER_ROLE_ASSIGNED => 'Role Assigned to User',
            self::ACTION_USER_ROLE_REMOVED => 'Role Removed from User',
            self::ACTION_PERMISSION_GRANTED => 'Permission Granted',
            self::ACTION_PERMISSION_DENIED => 'Permission Denied',
            self::ACTION_PERMISSION_OVERRIDE_CLEARED => 'Permission Override Cleared',
            self::ACTION_ACCESS_RULE_CREATED => 'Access Rule Created',
            self::ACTION_ACCESS_RULE_UPDATED => 'Access Rule Updated',
            self::ACTION_ACCESS_RULE_DELETED => 'Access Rule Deleted',
            self::ACTION_ACCESS_RULE_TRIGGERED => 'Access Rule Triggered',
            self::ACTION_PLUGIN_DISABLED => 'Plugin Disabled',
            self::ACTION_PLUGIN_ENABLED => 'Plugin Enabled',
            self::ACTION_PLUGIN_UNINSTALLED => 'Plugin Uninstalled',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get target type label
     */
    public function getTargetTypeLabelAttribute(): string
    {
        return match ($this->target_type) {
            self::TARGET_ROLE => 'Role',
            self::TARGET_PERMISSION => 'Permission',
            self::TARGET_USER => 'User',
            self::TARGET_ACCESS_RULE => 'Access Rule',
            self::TARGET_PLUGIN => 'Plugin',
            default => ucwords(str_replace('_', ' ', $this->target_type)),
        };
    }

    /**
     * Get action icon
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_ROLE_CREATED, self::ACTION_ACCESS_RULE_CREATED => 'plus',
            self::ACTION_ROLE_UPDATED, self::ACTION_ACCESS_RULE_UPDATED, self::ACTION_PERMISSIONS_SYNCED => 'edit',
            self::ACTION_ROLE_DELETED, self::ACTION_ACCESS_RULE_DELETED => 'trash',
            self::ACTION_ROLE_DUPLICATED => 'copy',
            self::ACTION_USER_ROLE_ASSIGNED, self::ACTION_PERMISSION_GRANTED => 'check',
            self::ACTION_USER_ROLE_REMOVED, self::ACTION_PERMISSION_DENIED => 'x',
            self::ACTION_PLUGIN_DISABLED => 'power',
            self::ACTION_PLUGIN_ENABLED => 'plug',
            self::ACTION_PLUGIN_UNINSTALLED => 'trash',
            default => 'activity',
        };
    }

    /**
     * Get severity level for styling
     */
    public function getSeverityAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_ROLE_DELETED,
            self::ACTION_ACCESS_RULE_DELETED,
            self::ACTION_PERMISSION_DENIED,
            self::ACTION_PLUGIN_UNINSTALLED => 'danger',

            self::ACTION_USER_ROLE_REMOVED,
            self::ACTION_PERMISSION_OVERRIDE_CLEARED,
            self::ACTION_ACCESS_RULE_TRIGGERED => 'warning',

            self::ACTION_ROLE_CREATED,
            self::ACTION_ACCESS_RULE_CREATED,
            self::ACTION_USER_ROLE_ASSIGNED,
            self::ACTION_PERMISSION_GRANTED,
            self::ACTION_PLUGIN_ENABLED => 'success',

            default => 'info',
        };
    }

    // =========================================================================
    // Logging Helpers
    // =========================================================================

    /**
     * Log a role change
     */
    public static function logRoleChange(Role $role, string $action, ?array $changes = null): static
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => self::TARGET_ROLE,
            'target_id' => $role->id,
            'target_name' => $role->name,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log permission sync
     */
    public static function logPermissionSync(Role $role, array $added, array $removed): static
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => self::ACTION_PERMISSIONS_SYNCED,
            'target_type' => self::TARGET_ROLE,
            'target_id' => $role->id,
            'target_name' => $role->name,
            'changes' => [
                'permissions_added' => $added,
                'permissions_removed' => $removed,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log user role change
     */
    public static function logUserRoleChange(User $targetUser, Role $role, string $action): static
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => self::TARGET_USER,
            'target_id' => $targetUser->id,
            'target_name' => $targetUser->name,
            'changes' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log user permission override
     */
    public static function logUserPermissionChange(User $targetUser, Permission $permission, string $action, ?string $reason = null): static
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => self::TARGET_USER,
            'target_id' => $targetUser->id,
            'target_name' => $targetUser->name,
            'changes' => [
                'permission_id' => $permission->id,
                'permission_slug' => $permission->slug,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log access rule change
     */
    public static function logAccessRuleChange(AccessRule $rule, string $action, ?array $changes = null): static
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => self::TARGET_ACCESS_RULE,
            'target_id' => $rule->id,
            'target_name' => $rule->name,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log plugin lifecycle event
     */
    public static function logPluginEvent(string $pluginSlug, string $action, ?array $changes = null): static
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => self::TARGET_PLUGIN,
            'target_id' => null,
            'target_name' => $pluginSlug,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // =========================================================================
    // Statistics
    // =========================================================================

    /**
     * Get action counts for stats
     */
    public static function getActionCounts(int $days = 30): Collection
    {
        return static::recent($days)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action');
    }

    /**
     * Get activity timeline
     */
    public static function getTimeline(int $limit = 50): Collection
    {
        return static::with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
