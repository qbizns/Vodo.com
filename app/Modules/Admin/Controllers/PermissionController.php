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

        // Get active plugins for filter
        $plugins = \App\Models\Plugin::active()->get();

        // Build matrix data
        $matrix = [];
        $inheritedMatrix = [];
        $matrixData = [
            'permissions' => [],
            'inherited' => [],
            'grantable' => [],
        ];
        $groupedPermissions = [];
        
        // Build role-permission lookup maps for view
        $rolePermissionMatrix = [];
        $roleInheritedMatrix = [];
        foreach ($roles as $role) {
            $rolePermissionMatrix[$role->id] = [];
            $roleInheritedMatrix[$role->id] = [];
            foreach ($role->grantedPermissions as $perm) {
                $rolePermissionMatrix[$role->id][$perm->id] = true;
            }
            // Get inherited permissions (from parent roles)
            $allPerms = $role->getAllPermissions();
            foreach ($allPerms as $perm) {
                if (!isset($rolePermissionMatrix[$role->id][$perm->id])) {
                    $roleInheritedMatrix[$role->id][$perm->id] = true;
                }
            }
        }

        foreach ($groups as $group) {
            $groupData = [
                'group' => $group,
                'permissions' => [],
            ];
            
            // Build grouped permissions structure for view
            $groupedPermissions[$group->slug] = [
                'name' => $group->name,
                'icon' => $group->icon ?? 'folder',
                'plugin' => $group->plugin_slug,
                'permissions' => [],
            ];

            foreach ($group->permissions as $permission) {
                $permData = [
                    'permission' => $permission,
                    'roles' => [],
                ];
                
                // Build permission data for grouped view
                $dependencies = $permission->dependencies ?? [];
                if ($dependencies instanceof \Illuminate\Support\Collection) {
                    $dependencies = $dependencies->toArray();
                }
                
                $permissionViewData = [
                    'id' => $permission->id,
                    'slug' => $permission->slug,
                    'name' => $permission->name,
                    'label' => $permission->label ?? $permission->name,
                    'description' => $permission->description,
                    'is_dangerous' => $permission->is_dangerous ?? false,
                    'dependencies' => is_array($dependencies) ? $dependencies : [],
                    'roles' => [],
                ];

                foreach ($roles as $role) {
                    $granted = $role->grantedPermissions->contains('id', $permission->id);
                    $inherited = !$granted && $role->getAllPermissions()->contains('id', $permission->id);

                    $permData['roles'][$role->id] = [
                        'granted' => $granted,
                        'inherited' => $inherited,
                    ];
                    
                    $permissionViewData['roles'][$role->id] = [
                        'granted' => $granted,
                        'inherited' => $inherited,
                    ];

                    // Build flat matrixData for JS
                    $key = "{$role->id}-{$permission->id}";
                    $matrixData['permissions'][$key] = $granted;
                    $matrixData['inherited'][$key] = $inherited;
                    $matrixData['grantable'][$key] = true; // TODO: implement grantable logic
                }

                $groupData['permissions'][] = $permData;
                $groupedPermissions[$group->slug]['permissions'][] = $permissionViewData;
            }

            $matrix[] = $groupData;
        }

        return view('backend.permissions.matrix.index', [
            'roles' => $roles,
            'groups' => $groups,
            'plugins' => $plugins,
            'matrix' => $rolePermissionMatrix,
            'inheritedMatrix' => $roleInheritedMatrix,
            'matrixData' => $matrixData,
            'groupedPermissions' => $groupedPermissions,
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

        return view('backend.permissions.rules.form', [
            'permissions' => $permissions,
            'groupedPermissions' => $permissions,
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
        // Transform conditions from form format to normalized format
        $this->normalizeConditions($request);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.type' => ['required_with:conditions', 'string'],
            'conditions.*.operator' => ['required_with:conditions', 'string'],
            'conditions.*.value' => ['nullable'],
            'action' => ['required', 'in:deny,log'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);
        
        // Ensure conditions is an array (default to empty)
        $validated['conditions'] = $validated['conditions'] ?? [];

        // Set creator polymorphic relationship
        $creatorType = null;
        $creatorId = null;
        
        if (auth()->guard('admin')->check()) {
            $creatorType = \App\Modules\Admin\Models\Admin::class;
            $creatorId = auth()->guard('admin')->id();
        } elseif (auth()->guard('web')->check()) {
            $creatorType = \App\Models\User::class;
            $creatorId = auth()->guard('web')->id();
        }
        
        $rule = AccessRule::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'],
            'conditions' => $validated['conditions'],
            'action' => $validated['action'],
            'priority' => $validated['priority'] ?? 100,
            'is_active' => $validated['is_active'] ?? true,
            'retention_days' => $validated['retention_days'] ?? 90,
            'creator_type' => $creatorType,
            'creator_id' => $creatorId,
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

        return view('backend.permissions.rules.form', [
            'rule' => $rule,
            'permissions' => $permissions,
            'groupedPermissions' => $permissions,
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
        // Transform conditions from form format to normalized format
        $this->normalizeConditions($request);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.type' => ['required_with:conditions', 'string'],
            'conditions.*.operator' => ['required_with:conditions', 'string'],
            'conditions.*.value' => ['nullable'],
            'action' => ['required', 'in:deny,log'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);
        
        // Ensure conditions is an array (default to empty)
        $validated['conditions'] = $validated['conditions'] ?? [];

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

    /**
     * Test access rule with custom parameters
     */
    public function testAccessRule(Request $request, AccessRule $rule): JsonResponse
    {
        $validated = $request->validate([
            'time' => ['nullable', 'date_format:H:i'],
            'day' => ['nullable', 'string'],
            'ip' => ['nullable', 'string'],
            'role' => ['nullable', 'string'],
            'custom' => ['nullable', 'string'],
            'permission' => ['required', 'string'],
        ]);
        
        $result = $rule->evaluateWithTestData($validated);
        
        return response()->json([
            'success' => true,
            'data' => $result
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
        
        // Action types for filter dropdown
        $actions = [
            PermissionAudit::ACTION_ROLE_CREATED => 'Role Created',
            PermissionAudit::ACTION_ROLE_UPDATED => 'Role Updated',
            PermissionAudit::ACTION_ROLE_DELETED => 'Role Deleted',
            PermissionAudit::ACTION_ROLE_DUPLICATED => 'Role Duplicated',
            PermissionAudit::ACTION_PERMISSIONS_SYNCED => 'Permissions Updated',
            PermissionAudit::ACTION_USER_ROLE_ASSIGNED => 'Role Assigned',
            PermissionAudit::ACTION_USER_ROLE_REMOVED => 'Role Removed',
            PermissionAudit::ACTION_PERMISSION_GRANTED => 'Permission Granted',
            PermissionAudit::ACTION_PERMISSION_DENIED => 'Permission Denied',
            PermissionAudit::ACTION_ACCESS_RULE_CREATED => 'Access Rule Created',
            PermissionAudit::ACTION_ACCESS_RULE_UPDATED => 'Access Rule Updated',
            PermissionAudit::ACTION_ACCESS_RULE_DELETED => 'Access Rule Deleted',
        ];
        
        // Users for filter dropdown
        $users = User::select('id', 'name')->orderBy('name')->get();

        return view('backend.permissions.audit.index', [
            'logs' => $logs,
            'actionCounts' => $actionCounts,
            'actions' => $actions,
            'users' => $users,
            'filters' => [
                'search' => $search ?? null,
                'action' => $action ?? null,
                'target_type' => $targetType ?? null,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
            'currentPage' => 'permissions-audit',
            'currentPageLabel' => 'Audit Log',
            'currentPageIcon' => 'clipboardList',
        ]);
    }

    /**
     * Get single audit log entry details (JSON response)
     */
    public function auditLogShow(PermissionAudit $audit): JsonResponse
    {
        $log = $audit;
        
        return response()->json([
            'success' => true,
            'log' => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $log->action_label ?? ucwords(str_replace('_', ' ', $log->action)),
                'created_at_formatted' => $log->created_at->format('F j, Y \a\t H:i:s'),
                'created_at_relative' => $log->created_at->diffForHumans(),
                'user_name' => $log->user?->name ?? 'System',
                'user_email' => $log->user?->email,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'target_type' => $log->target_type,
                'target_id' => $log->target_id,
                'target_name' => $log->target_name,
                'changes' => $log->changes,
                'affected_users_count' => $log->affected_users_count ?? null,
            ],
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
    
    /**
     * Normalize conditions from form format to storage format.
     * Handles value_from/value_to (time) and days[] (day) formats.
     */
    protected function normalizeConditions(Request $request): void
    {
        $conditions = $request->input('conditions', []);
        
        if (!is_array($conditions)) {
            return;
        }
        
        $normalized = [];
        foreach ($conditions as $index => $condition) {
            $type = $condition['type'] ?? 'time';
            $operator = $condition['operator'] ?? 'between';
            $value = $condition['value'] ?? null;
            
            // If value is already set, use it
            if ($value !== null) {
                $normalized[$index] = [
                    'type' => $type,
                    'operator' => $operator,
                    'value' => $value,
                ];
                continue;
            }
            
            // Transform based on type
            switch ($type) {
                case 'time':
                    $from = $condition['value_from'] ?? '09:00';
                    $to = $condition['value_to'] ?? '17:00';
                    $value = [$from, $to];
                    break;
                    
                case 'day':
                    $value = $condition['days'] ?? [];
                    break;
                    
                default:
                    $value = '';
                    break;
            }
            
            $normalized[$index] = [
                'type' => $type,
                'operator' => $operator,
                'value' => $value,
            ];
        }
        
        $request->merge(['conditions' => $normalized]);
    }
}
