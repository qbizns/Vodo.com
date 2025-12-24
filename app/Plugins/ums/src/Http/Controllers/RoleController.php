<?php

namespace Ums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;

/**
 * Role Controller
 */
class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $roles = Role::withCount('permissions', 'users')
            ->orderBy('level', 'desc')
            ->orderBy('name')
            ->paginate(25);

        return view('ums::roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissionGroups = PermissionGroup::active()
            ->with(['permissions' => fn($q) => $q->active()])
            ->ordered()
            ->get();

        return view('ums::roles.create', compact('permissionGroups'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:50|unique:roles,slug',
            'description' => 'nullable|string|max:500',
            'level' => 'required|integer|min:0|max:1000',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'level' => $request->level,
            'color' => $request->color ?? '#6B7280',
            'icon' => $request->icon ?? 'shield',
            'is_active' => true,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role,
            'redirect' => route('plugins.ums.roles.index'),
        ]);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load('permissions.permissionGroup');
        $users = $role->users()->paginate(10);

        return view('ums::roles.show', compact('role', 'users'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissionGroups = PermissionGroup::active()
            ->with(['permissions' => fn($q) => $q->active()])
            ->ordered()
            ->get();

        $assignedPermissionIds = $role->permissions->pluck('id')->toArray();

        return view('ums::roles.edit', compact('role', 'permissionGroups', 'assignedPermissionIds'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        // Prevent updating system roles
        if ($role->is_system && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be modified.',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:50|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string|max:500',
            'level' => 'required|integer|min:0|max:1000',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'level' => $request->level,
            'color' => $request->color ?? $role->color,
            'icon' => $request->icon ?? $role->icon,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $role,
            'redirect' => route('plugins.ums.roles.index'),
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted.',
            ], 403);
        }

        // Check if role has users
        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with assigned users.',
            ], 400);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($request->permissions);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
            'data' => $role->load('permissions'),
        ]);
    }
}

