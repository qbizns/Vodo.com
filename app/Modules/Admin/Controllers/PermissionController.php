<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionAudit;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use App\Models\AccessRule;
use App\Services\Permission\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

/**
 * Permission Management Controller
 *
 * Handles permission browsing, matrix view, user permissions, access rules, and audit logs.
 */
class PermissionController extends Controller
{
    public function __construct(
        protected PermissionRegistry $permissionRegistry
    ) {}

    // =========================================================================
    // Permissions Browser
    // =========================================================================

    /**
     * Display permissions browser
     */
    public function index(Request $request): View
    {
        $query = Permission::with('permissionGroup');

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filter by group
        if ($group = $request->input('group')) {
            $query->where('group', $group);
        }

        // Filter by plugin
        if ($plugin = $request->input('plugin')) {
            if ($plugin === 'core') {
                $query->whereNull('plugin_slug');
            } else {
                $query->where('plugin_slug', $plugin);
            }
        }

        // Filter by status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $permissions = $query->ordered()->paginate($request->integer('per_page', 50));

        $groups = PermissionGroup::active()->ordered()->get();
        $plugins = Permission::distinct()->whereNotNull('plugin_slug')->pluck('plugin_slug');

        return view('backend.permissions.permissions.index', [
            'permissions' => $permissions,
            'groups' => $groups,
            'plugins' => $plugins,
            'filters' => [
                'search' => $search,
                'group' => $group,
                'plugin' => $plugin,
                'active' => $request->input('active'),
            ],
            'currentPage' => 'permissions-list',
            'currentPageLabel' => 'Permissions',
            'currentPageIcon' => 'key',
        ]);
    }

    // =========================================================================
    // Permission Matrix
    // =========================================================================

    /**
     * Display permission matrix
     */
    public function matrix(Request $request): View
    {
        $roles = Role::active()->ordered()->get();
        $groups = PermissionGroup::active()
            ->with(['permissions' => fn($q) => $q->active()->ordered()])
            ->ordered()
            ->get();

        // Build matrix data
        $matrix = [];
        foreach ($groups as $group) {
            $groupData = [
                'group' => $group,
                'permissions' => [],
            ];

            foreach ($group->permissions as $permission) {
                $permData = [
                    'permission' => $permission,
                    'roles' => [],
                ];

                foreach ($roles as $role) {
                    $granted = $role->grantedPermissions->contains('id', $permission->id);
                    $inherited = !$granted && $role->getAllPermissions()->contains('id', $permission->id);

                    $permData['roles'][$role->id] = [
                        'granted' => $granted,
                        'inherited' => $inherited,
                    ];
                }

                $groupData['permissions'][] = $permData;
            }

            $matrix[] = $groupData;
        }

        return view('backend.permissions.permissions.matrix', [
            'roles' => $roles,
            'matrix' => $matrix,
            'currentPage' => 'permissions-matrix',
            'currentPageLabel' => 'Permission Matrix',
            'currentPageIcon' => 'layoutGrid',
        ]);
    }

    /**
     * Update permission matrix (bulk update)
     */
    public function updateMatrix(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'changes' => ['required', 'array'],
            'changes.*.role_id' => ['required', 'exists:roles,id'],
            'changes.*.permission_id' => ['required', 'exists:permissions,id'],
            'changes.*.granted' => ['required', 'boolean'],
        ]);

        $user = auth()->user();
        $changes = $validated['changes'];
        $processed = 0;

        DB::beginTransaction();
        try {
            foreach ($changes as $change) {
                $role = Role::find($change['role_id']);
                $permission = Permission::find($change['permission_id']);

                // Privilege escalation check
                if (!$this->permissionRegistry->canUserEditRole($user, $role)) {
                    continue;
                }

                if ($change['granted'] && !$this->permissionRegistry->canUserGrantPermission($user, $permission->slug)) {
                    continue;
                }

                if ($change['granted']) {
                    $role->permissions()->syncWithoutDetaching([
                        $permission->id => [
                            'granted' => true,
                            'granted_at' => now(),
                            'granted_by' => $user->id,
                        ],
                    ]);
                } else {
                    $role->permissions()->detach($permission->id);
                }

                $processed++;
            }

            DB::commit();

            $this->permissionRegistry->clearCache();

            return response()->json([
                'success' => true,
                'message' => "{$processed} permission change(s) applied.",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // User Permissions
    // =========================================================================

    /**
     * Show user permissions page
     */
    public function userPermissions(User $user): View
    {
        $user->load(['roles.grantedPermissions']);

        // Get all permissions from roles
        $rolePermissions = collect();
        foreach ($user->roles as $role) {
            $rolePermissions = $rolePermissions->merge($role->getAllPermissions());
        }
        $rolePermissions = $rolePermissions->unique('id');

        // Get direct user permissions
        $directPermissions = $user->directPermissions()->get();

        // Build effective permissions
        $effectivePermissions = [];
        $allPermissions = Permission::active()->ordered()->get();

        foreach ($allPermissions as $permission) {
            $fromRoles = $rolePermissions->contains('id', $permission->id);
            $directOverride = $directPermissions->firstWhere('id', $permission->id);

            $effectivePermissions[] = [
                'permission' => $permission,
                'from_roles' => $fromRoles,
                'direct_override' => $directOverride ? $directOverride->pivot->granted : null,
                'effective' => $directOverride !== null
                    ? $directOverride->pivot->granted
                    : $fromRoles,
            ];
        }

        return view('backend.permissions.users.permissions', [
            'user' => $user,
            'rolePermissions' => $rolePermissions,
            'directPermissions' => $directPermissions,
            'effectivePermissions' => $effectivePermissions,
            'roles' => Role::active()->ordered()->get(),
            'currentPage' => 'permissions-users',
            'currentPageLabel' => 'User: ' . $user->name,
            'currentPageIcon' => 'userCog',
        ]);
    }

    /**
     * Update user permissions (direct overrides)
     */
    public function updateUserPermissions(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'overrides' => ['nullable', 'array'],
            'overrides.*.permission_id' => ['required', 'exists:permissions,id'],
            'overrides.*.granted' => ['required', 'boolean'],
            'overrides.*.reason' => ['nullable', 'string', 'max:500'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'action' => ['nullable', 'in:assign_role,remove_role'],
        ]);

        $authUser = auth()->user();

        DB::beginTransaction();
        try {
            // Handle role assignment/removal
            if (!empty($validated['action']) && !empty($validated['role_id'])) {
                $role = Role::find($validated['role_id']);

                if ($validated['action'] === 'assign_role') {
                    if (!$user->roles->contains($role->id)) {
                        $user->roles()->attach($role->id, [
                            'assigned_by' => $authUser->id,
                            'assigned_at' => now(),
                        ]);
                        PermissionAudit::logUserRoleChange($user, $role, PermissionAudit::ACTION_USER_ROLE_ASSIGNED);
                    }
                } elseif ($validated['action'] === 'remove_role') {
                    $user->roles()->detach($role->id);
                    PermissionAudit::logUserRoleChange($user, $role, PermissionAudit::ACTION_USER_ROLE_REMOVED);
                }
            }

            // Handle permission overrides
            if (!empty($validated['overrides'])) {
                foreach ($validated['overrides'] as $override) {
                    $permission = Permission::find($override['permission_id']);

                    // Privilege escalation check
                    if ($override['granted'] && !$this->permissionRegistry->canUserGrantPermission($authUser, $permission->slug)) {
                        continue;
                    }

                    // Check if override already exists
                    $existing = $user->directPermissions()
                        ->where('permission_id', $permission->id)
                        ->first();

                    if ($existing) {
                        // Update existing override
                        $user->directPermissions()->updateExistingPivot($permission->id, [
                            'granted' => $override['granted'],
                            'granted_by' => $authUser->id,
                            'reason' => $override['reason'] ?? null,
                        ]);
                    } else {
                        // Create new override
                        $user->directPermissions()->attach($permission->id, [
                            'granted' => $override['granted'],
                            'granted_by' => $authUser->id,
                            'reason' => $override['reason'] ?? null,
                        ]);
                    }

                    $action = $override['granted']
                        ? PermissionAudit::ACTION_PERMISSION_GRANTED
                        : PermissionAudit::ACTION_PERMISSION_DENIED;

                    PermissionAudit::logUserPermissionChange($user, $permission, $action, $override['reason'] ?? null);
                }
            }

            // Clear user's permission cache
            if (method_exists($user, 'clearPermissionCache')) {
                $user->clearPermissionCache();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User permissions updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear user permission override
     */
    public function clearUserOverride(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'permission_id' => ['required', 'exists:permissions,id'],
        ]);

        $permission = Permission::find($validated['permission_id']);
        $user->directPermissions()->detach($permission->id);

        PermissionAudit::logUserPermissionChange(
            $user,
            $permission,
            PermissionAudit::ACTION_PERMISSION_OVERRIDE_CLEARED
        );

        if (method_exists($user, 'clearPermissionCache')) {
            $user->clearPermissionCache();
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission override cleared.',
        ]);
    }

    // =========================================================================
    // Access Rules
    // =========================================================================

    /**
     * Display access rules list
     */
    public function accessRules(Request $request): View
    {
        $query = AccessRule::with('creator');

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $rules = $query->ordered()->paginate(20);

        return view('backend.permissions.rules.index', [
            'rules' => $rules,
            'conditionTypes' => AccessRule::getConditionTypes(),
            'currentPage' => 'permissions-rules',
            'currentPageLabel' => 'Access Rules',
            'currentPageIcon' => 'shieldAlert',
        ]);
    }

    /**
     * Show create access rule form
     */
    public function createAccessRule(): View
    {
        $permissions = $this->permissionRegistry->getGroupedForUI();
        $roles = Role::active()->ordered()->get();

        return view('backend.permissions.rules.create', [
            'permissions' => $permissions,
            'roles' => $roles,
            'conditionTypes' => AccessRule::getConditionTypes(),
            'currentPage' => 'permissions-rules',
            'currentPageLabel' => 'Create Access Rule',
            'currentPageIcon' => 'shieldAlert',
        ]);
    }

    /**
     * Store new access rule
     */
    public function storeAccessRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*.type' => ['required', 'string'],
            'conditions.*.operator' => ['required', 'string'],
            'conditions.*.value' => ['required'],
            'action' => ['required', 'in:deny,log'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $rule = AccessRule::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'],
            'conditions' => $validated['conditions'],
            'action' => $validated['action'],
            'priority' => $validated['priority'] ?? 100,
            'is_active' => $validated['is_active'] ?? true,
            'retention_days' => $validated['retention_days'] ?? 90,
            'created_by' => auth()->id(),
        ]);

        PermissionAudit::logAccessRuleChange($rule, PermissionAudit::ACTION_ACCESS_RULE_CREATED);

        return response()->json([
            'success' => true,
            'message' => 'Access rule created successfully.',
            'data' => $rule,
            'redirect' => route('admin.permissions.rules'),
        ], 201);
    }

    /**
     * Show edit access rule form
     */
    public function editAccessRule(AccessRule $rule): View
    {
        $permissions = $this->permissionRegistry->getGroupedForUI();
        $roles = Role::active()->ordered()->get();

        return view('backend.permissions.rules.edit', [
            'rule' => $rule,
            'permissions' => $permissions,
            'roles' => $roles,
            'conditionTypes' => AccessRule::getConditionTypes(),
            'currentPage' => 'permissions-rules',
            'currentPageLabel' => 'Edit: ' . $rule->name,
            'currentPageIcon' => 'shieldAlert',
        ]);
    }

    /**
     * Update access rule
     */
    public function updateAccessRule(Request $request, AccessRule $rule): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*.type' => ['required', 'string'],
            'conditions.*.operator' => ['required', 'string'],
            'conditions.*.value' => ['required'],
            'action' => ['required', 'in:deny,log'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $changes = [];
        foreach (['name', 'description', 'permissions', 'conditions', 'action', 'priority', 'is_active'] as $field) {
            if (isset($validated[$field]) && $rule->$field != $validated[$field]) {
                $changes[$field] = ['from' => $rule->$field, 'to' => $validated[$field]];
            }
        }

        $rule->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'],
            'conditions' => $validated['conditions'],
            'action' => $validated['action'],
            'priority' => $validated['priority'] ?? 100,
            'is_active' => $validated['is_active'] ?? true,
            'retention_days' => $validated['retention_days'] ?? 90,
        ]);

        if (!empty($changes)) {
            PermissionAudit::logAccessRuleChange($rule, PermissionAudit::ACTION_ACCESS_RULE_UPDATED, $changes);
        }

        return response()->json([
            'success' => true,
            'message' => 'Access rule updated successfully.',
            'data' => $rule->fresh(),
        ]);
    }

    /**
     * Delete access rule
     */
    public function destroyAccessRule(AccessRule $rule): JsonResponse
    {
        PermissionAudit::logAccessRuleChange($rule, PermissionAudit::ACTION_ACCESS_RULE_DELETED);
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Access rule deleted successfully.',
        ]);
    }

    // =========================================================================
    // Audit Log
    // =========================================================================

    /**
     * Display audit log
     */
    public function auditLog(Request $request): View
    {
        $query = PermissionAudit::with('user');

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filter by action
        if ($action = $request->input('action')) {
            $query->forAction($action);
        }

        // Filter by target type
        if ($targetType = $request->input('target_type')) {
            $query->forTarget($targetType);
        }

        // Filter by date range
        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $logs = $query->latest('created_at')->paginate($request->integer('per_page', 50));

        $actionCounts = PermissionAudit::getActionCounts();

        return view('backend.permissions.audit.index', [
            'logs' => $logs,
            'actionCounts' => $actionCounts,
            'filters' => [
                'search' => $search,
                'action' => $action,
                'target_type' => $targetType,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
            'currentPage' => 'permissions-audit',
            'currentPageLabel' => 'Audit Log',
            'currentPageIcon' => 'clipboardList',
        ]);
    }

    // =========================================================================
    // API Endpoints
    // =========================================================================

    /**
     * Check permission API
     */
    public function checkPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $user = $validated['user_id']
            ? User::find($validated['user_id'])
            : auth()->user();

        $hasPermission = $this->permissionRegistry->checkPermission($user, $validated['permission']);

        return response()->json([
            'permission' => $validated['permission'],
            'user_id' => $user->id,
            'granted' => $hasPermission,
        ]);
    }

    /**
     * Get permissions list API
     */
    public function listPermissions(Request $request): JsonResponse
    {
        $query = Permission::active();

        if ($group = $request->input('group')) {
            $query->where('group', $group);
        }

        if ($plugin = $request->input('plugin')) {
            $query->where('plugin_slug', $plugin);
        }

        $permissions = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $permissions->map(fn($p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'label' => $p->label,
                'group' => $p->group,
                'description' => $p->description,
                'is_dangerous' => $p->is_dangerous,
            ]),
        ]);
    }

    /**
     * Get roles list API
     */
    public function listRoles(Request $request): JsonResponse
    {
        $roles = Role::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $roles->map(fn($r) => [
                'id' => $r->id,
                'slug' => $r->slug,
                'name' => $r->name,
                'color' => $r->color,
                'icon' => $r->icon,
                'level' => $r->level,
                'user_count' => $r->getUserCount(),
            ]),
        ]);
    }
}
