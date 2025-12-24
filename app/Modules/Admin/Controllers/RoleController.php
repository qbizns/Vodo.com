<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\PermissionAudit;
use App\Models\User;
use App\Services\Permission\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

/**
 * Role Management Controller
 *
 * Handles all role-related admin operations including CRUD,
 * permission assignment, comparison, import/export, and bulk operations.
 */
class RoleController extends Controller
{
    public function __construct(
        protected PermissionRegistry $permissionRegistry
    ) {}

    /**
     * Display roles list
     */
    public function index(Request $request): View
    {
        $query = Role::with(['parent', 'grantedPermissions'])
            ->withCount('users');

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filter by status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by plugin
        if ($plugin = $request->input('plugin')) {
            if ($plugin === 'core') {
                $query->whereNull('plugin_slug');
            } else {
                $query->where('plugin_slug', $plugin);
            }
        }

        // Sort
        $sortField = $request->input('sort', 'level');
        $sortDir = $request->input('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $roles = $query->paginate($request->integer('per_page', 15));

        return view('backend.permissions.roles.index', [
            'roles' => $roles,
            'currentPage' => 'permissions-roles',
            'currentPageLabel' => 'Roles',
            'currentPageIcon' => 'shield',
            'filters' => [
                'search' => $search,
                'plugin' => $plugin,
                'active' => $request->input('active'),
            ],
        ]);
    }

    /**
     * Show create role form
     */
    public function create(): View
    {
        $parentRoles = Role::active()
            ->withCount('permissions')
            ->ordered()
            ->get();
        $permissions = $this->permissionRegistry->getGroupedForUI();

        // Get locked permissions (permissions the current user doesn't have)
        $lockedPermissions = $this->getLockedPermissions($permissions);

        return view('backend.permissions.roles.create', [
            'parentRoles' => $parentRoles,
            'permissions' => $permissions,
            'selectedPermissions' => [],
            'inheritedPermissions' => [],
            'lockedPermissions' => $lockedPermissions,
            'currentPage' => 'permissions-roles',
            'currentPageLabel' => 'Create Role',
            'currentPageIcon' => 'shield',
        ]);
    }

    /**
     * Get permissions that the current user cannot grant (locked permissions)
     */
    protected function getLockedPermissions(array $groupedPermissions): array
    {
        $lockedPermissions = [];
        $currentUser = auth()->user();

        if (!$currentUser) {
            return [];
        }

        // Admin users (backend admins) can grant anything
        if ($currentUser instanceof \App\Modules\Admin\Models\Admin) {
            return [];
        }

        // Super admin can grant anything
        if ($this->permissionRegistry->userHasRole($currentUser, \App\Models\Role::ROLE_SUPER_ADMIN)) {
            return [];
        }

        foreach ($groupedPermissions as $group) {
            foreach ($group['permissions'] ?? [] as $permission) {
                $slug = $permission['slug'] ?? null;
                if ($slug && !$this->permissionRegistry->checkPermission($currentUser, $slug)) {
                    $lockedPermissions[] = $permission['id'];
                }
            }
        }

        return $lockedPermissions;
    }

    /**
     * Store new role
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:roles,slug', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'level' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'parent_id' => ['nullable', 'exists:roles,id'],
            'is_default' => ['boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // Check for circular inheritance
        if (!empty($validated['parent_id'])) {
            $parent = Role::find($validated['parent_id']);
            if ($parent && $parent->level >= ($validated['level'] ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent role must have a lower level than this role.',
                ], 422);
            }
        }

        // Privilege escalation check
        $permissions = $validated['permissions'] ?? [];
        $grantablePermissions = $this->filterGrantablePermissions($permissions);

        if (count($grantablePermissions) < count($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot grant permissions you do not have.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            // If making this default, unset other defaults
            if (!empty($validated['is_default'])) {
                Role::where('is_default', true)->update(['is_default' => false]);
            }

            $role = Role::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? '#6B7280',
                'icon' => $validated['icon'] ?? 'shield',
                'level' => $validated['level'] ?? 0,
                'parent_id' => $validated['parent_id'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
            ]);

            // Assign permissions
            if (!empty($grantablePermissions)) {
                $permissionData = [];
                foreach ($grantablePermissions as $permId) {
                    $permissionData[$permId] = [
                        'granted' => true,
                        'granted_at' => now(),
                        'granted_by' => auth()->id(),
                    ];
                }
                $role->permissions()->sync($permissionData);
            }

            PermissionAudit::logRoleChange($role, PermissionAudit::ACTION_ROLE_CREATED);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully.',
                'data' => $role,
                'redirect' => route('admin.roles.edit', $role),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show role details
     */
    public function show(Role $role): View
    {
        $role->loadCount('users');
        $role->load(['parent', 'children', 'grantedPermissions', 'users' => fn($q) => $q->limit(10)]);

        $inheritedPermissions = $role->getInheritedPermissions();
        $allPermissions = $role->getAllPermissions();
        $directPermissionIds = $role->grantedPermissions->pluck('id')->toArray();
        
        // Calculate permission stats
        $directCount = $role->grantedPermissions->count();
        $inheritedCount = $inheritedPermissions->count();
        $permissionStats = [
            'total' => $directCount + $inheritedCount,
            'direct' => $directCount,
            'inherited' => $inheritedCount,
        ];
        
        // Build grouped permissions for the view
        $groupedPermissions = [];
        foreach ($allPermissions as $permission) {
            $groupSlug = $permission->group?->slug ?? 'general';
            $groupName = $permission->group?->name ?? 'General';
            
            if (!isset($groupedPermissions[$groupSlug])) {
                $groupedPermissions[$groupSlug] = [
                    'name' => $groupName,
                    'permissions' => [],
                ];
            }
            
            $groupedPermissions[$groupSlug]['permissions'][] = [
                'id' => $permission->id,
                'slug' => $permission->slug,
                'name' => $permission->name,
                'label' => $permission->label ?? $permission->name,
                'description' => $permission->description,
                'is_dangerous' => $permission->is_dangerous ?? false,
                'inherited' => !in_array($permission->id, $directPermissionIds),
            ];
        }
        
        // Sort groups alphabetically
        ksort($groupedPermissions);
        
        // Get users for display (already loaded with limit)
        $users = $role->users;

        return view('backend.permissions.roles.show', [
            'role' => $role,
            'inheritedPermissions' => $inheritedPermissions,
            'allPermissions' => $allPermissions,
            'permissionStats' => $permissionStats,
            'groupedPermissions' => $groupedPermissions,
            'users' => $users,
            'currentPage' => 'permissions-roles',
            'currentPageLabel' => $role->name,
            'currentPageIcon' => 'shield',
        ]);
    }

    /**
     * Show edit role form
     */
    public function edit(Role $role): View
    {
        // Check if user can edit this role
        if (!$this->permissionRegistry->canUserEditRole(auth()->user(), $role)) {
            abort(403, 'You do not have permission to edit this role.');
        }

        $parentRoles = Role::active()
            ->where('id', '!=', $role->id)
            ->withCount('permissions')
            ->ordered()
            ->get();

        $permissions = $this->permissionRegistry->getGroupedForUI();
        $selectedPermissions = $role->grantedPermissions->pluck('id')->toArray();
        $inheritedPermissions = $role->getInheritedPermissions()->pluck('id')->toArray();

        // Get locked permissions (permissions the current user doesn't have)
        $lockedPermissions = $this->getLockedPermissions($permissions);

        return view('backend.permissions.roles.edit', [
            'role' => $role,
            'parentRoles' => $parentRoles,
            'permissions' => $permissions,
            'selectedPermissions' => $selectedPermissions,
            'inheritedPermissions' => $inheritedPermissions,
            'lockedPermissions' => $lockedPermissions,
            'currentPage' => 'permissions-roles',
            'currentPageLabel' => 'Edit ' . $role->name,
            'currentPageIcon' => 'shield',
        ]);
    }

    /**
     * Update role
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        // Check if user can edit this role
        if (!$this->permissionRegistry->canUserEditRole(auth()->user(), $role)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this role.',
            ], 403);
        }

        // System roles have limited editability
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];

        if (!$role->is_system) {
            $rules['slug'] = ['required', 'string', 'max:50', 'unique:roles,slug,' . $role->id, 'regex:/^[a-z][a-z0-9_-]*$/'];
            $rules['level'] = ['nullable', 'integer', 'min:0', 'max:1000'];
            $rules['parent_id'] = ['nullable', 'exists:roles,id'];
            $rules['is_default'] = ['boolean'];
        }

        $validated = $request->validate($rules);

        // Check for circular inheritance
        if (!empty($validated['parent_id'])) {
            if ($role->wouldCreateCircularInheritance($validated['parent_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting this parent would create circular inheritance.',
                ], 422);
            }
        }

        // Privilege escalation check for permissions
        $newPermissions = $validated['permissions'] ?? [];
        $currentPermissionIds = $role->grantedPermissions->pluck('id')->toArray();
        $addedPermissions = array_diff($newPermissions, $currentPermissionIds);

        if (!empty($addedPermissions)) {
            $grantable = $this->filterGrantablePermissions($addedPermissions);
            if (count($grantable) < count($addedPermissions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot grant permissions you do not have.',
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            $changes = [];

            // Track changes for audit
            foreach (['name', 'description', 'color', 'icon', 'level', 'parent_id'] as $field) {
                if (isset($validated[$field]) && $role->$field != $validated[$field]) {
                    $changes[$field] = ['from' => $role->$field, 'to' => $validated[$field]];
                }
            }

            // Update basic fields
            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? $role->color,
                'icon' => $validated['icon'] ?? $role->icon,
            ];

            if (!$role->is_system) {
                $updateData['slug'] = $validated['slug'] ?? $role->slug;
                $updateData['level'] = $validated['level'] ?? $role->level;
                $updateData['parent_id'] = $validated['parent_id'] ?? null;

                if (!empty($validated['is_default']) && !$role->is_default) {
                    Role::where('is_default', true)->update(['is_default' => false]);
                    $updateData['is_default'] = true;
                } elseif (isset($validated['is_default'])) {
                    $updateData['is_default'] = $validated['is_default'];
                }
            }

            $role->update($updateData);

            // Update permissions
            $removedPermissions = array_diff($currentPermissionIds, $newPermissions);
            $addedPermissionSlugs = Permission::whereIn('id', $addedPermissions)->pluck('slug')->toArray();
            $removedPermissionSlugs = Permission::whereIn('id', $removedPermissions)->pluck('slug')->toArray();

            if (!empty($addedPermissions) || !empty($removedPermissions)) {
                $permissionData = [];
                foreach ($newPermissions as $permId) {
                    $permissionData[$permId] = [
                        'granted' => true,
                        'granted_at' => now(),
                        'granted_by' => auth()->id(),
                    ];
                }
                $role->permissions()->sync($permissionData);

                PermissionAudit::logPermissionSync($role, $addedPermissionSlugs, $removedPermissionSlugs);
            }

            if (!empty($changes)) {
                PermissionAudit::logRoleChange($role, PermissionAudit::ACTION_ROLE_UPDATED, $changes);
            }

            // Clear permission cache
            $role->clearPermissionCache();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'data' => $role->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete role
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system roles.',
            ], 403);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with assigned users. Reassign users first.',
            ], 422);
        }

        if ($role->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with child roles. Delete or reassign children first.',
            ], 422);
        }

        PermissionAudit::logRoleChange($role, PermissionAudit::ACTION_ROLE_DELETED);
        $role->delete();
        $this->permissionRegistry->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * Duplicate a role
     */
    public function duplicate(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:50', 'unique:roles,slug', 'regex:/^[a-z][a-z0-9_-]*$/'],
        ]);

        $newRole = $role->duplicate($validated['name'], $validated['slug'] ?? null);

        PermissionAudit::logRoleChange($newRole, PermissionAudit::ACTION_ROLE_DUPLICATED, [
            'source_role_id' => $role->id,
            'source_role_name' => $role->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role duplicated successfully.',
            'data' => $newRole,
            'redirect' => route('admin.roles.edit', $newRole),
        ]);
    }

    /**
     * Compare two or more roles
     */
    public function compare(Request $request): View
    {
        $roleIds = $request->input('roles', []);
        $roles = Role::whereIn('id', $roleIds)
            ->withCount('permissions')
            ->with('grantedPermissions')
            ->get();

        $comparison = null;

        if ($roles->count() >= 2) {
            // Get all permissions for each role (including inherited)
            $rolePermissions = [];
            foreach ($roles as $role) {
                $rolePermissions[$role->id] = $role->getAllPermissions()->pluck('slug')->toArray();
            }

            // Find common permissions (in all roles)
            $common = array_values(array_intersect(...array_values($rolePermissions)));

            // Find unique permissions per role
            $unique = [];
            foreach ($roles as $role) {
                $otherPermissions = [];
                foreach ($rolePermissions as $otherId => $perms) {
                    if ($otherId !== $role->id) {
                        $otherPermissions = array_merge($otherPermissions, $perms);
                    }
                }
                $otherPermissions = array_unique($otherPermissions);
                $unique[$role->id] = array_values(array_diff($rolePermissions[$role->id], $otherPermissions));
            }

            // Build comparison result
            $comparison = [
                'roles' => $roles->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                    'color' => $r->color ?? '#6B7280',
                    'permissions_count' => $r->permissions_count,
                ])->toArray(),
                'common' => $common,
                'unique' => $unique,
                'differences_count' => [
                    'common' => count($common),
                ],
            ];

            // Add unique counts
            foreach ($unique as $roleId => $perms) {
                $comparison['differences_count']['only_in_' . $roleId] = count($perms);
            }
        }

        return view('backend.permissions.roles.compare', [
            'roles' => $roles,
            'comparison' => $comparison,
            'allRoles' => Role::active()->withCount('permissions')->ordered()->get(),
            'currentPage' => 'permissions-roles',
            'currentPageLabel' => 'Compare Roles',
            'currentPageIcon' => 'gitCompare',
        ]);
    }

    /**
     * Export role to JSON
     */
    public function export(Role $role): JsonResponse
    {
        return response()->json($role->toExportArray())
            ->header('Content-Disposition', 'attachment; filename="role-' . $role->slug . '.json"');
    }

    /**
     * Import role from JSON
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:json', 'max:1024'],
        ]);

        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($content, true);

            if (!is_array($data) || empty($data['name']) || empty($data['slug'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role file format.',
                ], 422);
            }

            // Check if slug exists
            if (Role::where('slug', $data['slug'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A role with this slug already exists.',
                ], 422);
            }

            $role = Role::fromImportArray($data);

            PermissionAudit::logRoleChange($role, PermissionAudit::ACTION_ROLE_CREATED, [
                'imported' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role imported successfully.',
                'data' => $role,
                'redirect' => route('admin.roles.edit', $role),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk assign role to users form
     */
    public function bulkAssignForm(Role $role, Request $request): View
    {
        $role->loadCount('users');
        $role->load('grantedPermissions');

        // Get users who already have this role
        $existingUserIds = DB::table('user_roles')
            ->where('role_id', $role->id)
            ->pluck('user_id')
            ->toArray();

        // Query all users with optional search
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('backend.permissions.roles.bulk-assign', [
            'role' => $role,
            'users' => $users,
            'existingUserIds' => $existingUserIds,
            'currentPage' => 'permissions-roles',
            'currentPageLabel' => 'Bulk Assign: ' . $role->name,
            'currentPageIcon' => 'users',
        ]);
    }

    /**
     * Bulk assign role to users
     */
    public function bulkAssign(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'users' => ['required', 'array', 'min:1'],
            'users.*' => ['exists:users,id'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'notify_users' => ['nullable', 'boolean'],
        ]);

        $userIds = $validated['users'];
        $expiresAt = $validated['expires_at'] ?? null;
        $notifyUsers = $validated['notify_users'] ?? false;

        $assignedCount = 0;

        foreach ($userIds as $userId) {
            $exists = DB::table('user_roles')
                ->where('user_id', $userId)
                ->where('role_id', $role->id)
                ->exists();

            if (!$exists) {
                DB::table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => $role->id,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $assignedCount++;

                // TODO: Send notification email if $notifyUsers is true
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Role assigned to {$assignedCount} user(s).",
        ]);
    }

    /**
     * Filter permissions the current user can grant
     */
    protected function filterGrantablePermissions(array $permissionIds): array
    {
        $user = auth()->user();

        // Super admin can grant anything
        if ($this->permissionRegistry->userHasRole($user, Role::ROLE_SUPER_ADMIN)) {
            return $permissionIds;
        }

        $permissions = Permission::whereIn('id', $permissionIds)->pluck('slug', 'id');

        return $permissions->filter(fn($slug) =>
            $this->permissionRegistry->checkPermission($user, $slug)
        )->keys()->toArray();
    }
}
