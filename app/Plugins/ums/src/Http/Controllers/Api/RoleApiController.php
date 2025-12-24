<?php

namespace Ums\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

/**
 * Role API Controller
 */
class RoleApiController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $roles = Role::withCount('permissions', 'users')
            ->orderBy('level', 'desc')
            ->orderBy('name')
            ->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
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
        ]);

        $role = Role::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role,
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->loadCount('permissions', 'users');

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be modified.',
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'slug' => 'sometimes|string|max:50|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string|max:500',
            'level' => 'sometimes|integer|min:0|max:1000',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        $role->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $role,
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted.',
            ], 403);
        }

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
     * Get role permissions.
     */
    public function permissions(Role $role)
    {
        return response()->json([
            'success' => true,
            'data' => $role->permissions,
        ]);
    }

    /**
     * Sync role permissions.
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

    /**
     * Get users with this role.
     */
    public function users(Role $role)
    {
        $users = $role->users()->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}

