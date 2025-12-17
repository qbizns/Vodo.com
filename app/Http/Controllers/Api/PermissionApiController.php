<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Permission\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionApiController extends Controller
{
    protected PermissionRegistry $registry;

    public function __construct(PermissionRegistry $registry)
    {
        $this->registry = $registry;
    }

    // =========================================================================
    // Permissions CRUD
    // =========================================================================

    public function indexPermissions(Request $request): JsonResponse
    {
        $query = Permission::query();

        if ($request->has('group')) {
            $query->inGroup($request->group);
        }
        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }
        if ($request->boolean('active_only', true)) {
            $query->active();
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('slug', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }

        $permissions = $request->boolean('grouped') 
            ? $query->ordered()->get()->groupBy('group')
            : $query->ordered()->get();

        return response()->json(['success' => true, 'data' => $permissions]);
    }

    public function showPermission(string $slug): JsonResponse
    {
        $permission = Permission::findBySlug($slug);
        if (!$permission) {
            return response()->json(['success' => false, 'error' => 'Permission not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $permission->toDocumentation()]);
    }

    public function storePermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9._-]*$/'],
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'group' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:50'],
            'requires' => ['nullable', 'array'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $permission = $this->registry->registerPermission($validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $permission], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function updatePermission(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'group' => ['nullable', 'string', 'max:50'],
            'active' => ['nullable', 'boolean'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $permission = $this->registry->updatePermission($slug, $validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $permission]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function destroyPermission(Request $request, string $slug): JsonResponse
    {
        $pluginSlug = $request->input('plugin_slug');
        if (!$pluginSlug) {
            return response()->json(['success' => false, 'error' => 'plugin_slug required'], 400);
        }

        try {
            $this->registry->unregisterPermission($slug, $pluginSlug);
            return response()->json(['success' => true, 'message' => 'Permission deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    // =========================================================================
    // Roles CRUD
    // =========================================================================

    public function indexRoles(Request $request): JsonResponse
    {
        $query = Role::query();

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }
        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $roles = $query->ordered()->with('grantedPermissions')->get();

        return response()->json(['success' => true, 'data' => $roles]);
    }

    public function showRole(string $slug): JsonResponse
    {
        $role = Role::findBySlug($slug);
        if (!$role) {
            return response()->json(['success' => false, 'error' => 'Role not found'], 404);
        }

        $data = $role->toArray();
        $data['permissions'] = $role->getAllPermissionSlugs();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function storeRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'parent' => ['nullable', 'string', 'max:50'],
            'permissions' => ['nullable', 'array'],
            'default' => ['nullable', 'boolean'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $role = $this->registry->registerRole($validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $role], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function updateRole(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'integer', 'min:0'],
            'permissions' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $role = $this->registry->updateRole($slug, $validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $role]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function destroyRole(Request $request, string $slug): JsonResponse
    {
        $pluginSlug = $request->input('plugin_slug');
        if (!$pluginSlug) {
            return response()->json(['success' => false, 'error' => 'plugin_slug required'], 400);
        }

        try {
            $this->registry->unregisterRole($slug, $pluginSlug);
            return response()->json(['success' => true, 'message' => 'Role deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    // =========================================================================
    // Role Permissions
    // =========================================================================

    public function grantPermissions(Request $request, string $roleSlug): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string'],
        ]);

        $role = Role::findBySlug($roleSlug);
        if (!$role) {
            return response()->json(['success' => false, 'error' => 'Role not found'], 404);
        }

        $role->grantPermission($validated['permissions']);

        return response()->json(['success' => true, 'message' => 'Permissions granted']);
    }

    public function revokePermissions(Request $request, string $roleSlug): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
        ]);

        $role = Role::findBySlug($roleSlug);
        if (!$role) {
            return response()->json(['success' => false, 'error' => 'Role not found'], 404);
        }

        $role->revokePermission($validated['permissions']);

        return response()->json(['success' => true, 'message' => 'Permissions revoked']);
    }

    // =========================================================================
    // User Management
    // =========================================================================

    public function userRoles(Request $request, int $userId): JsonResponse
    {
        $userModel = config('auth.providers.users.model', 'App\Models\User');
        $user = $userModel::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'User not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $user->roles,
                'permissions' => $user->getAllPermissionSlugs(),
            ],
        ]);
    }

    public function assignRole(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $userModel = config('auth.providers.users.model', 'App\Models\User');
        $user = $userModel::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'User not found'], 404);
        }

        $user->assignRole($validated['role']);

        return response()->json(['success' => true, 'message' => 'Role assigned']);
    }

    public function removeRole(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $userModel = config('auth.providers.users.model', 'App\Models\User');
        $user = $userModel::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'User not found'], 404);
        }

        $user->removeRole($validated['role']);

        return response()->json(['success' => true, 'message' => 'Role removed']);
    }

    public function checkPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $user = $validated['user_id'] 
            ? config('auth.providers.users.model')::find($validated['user_id'])
            : $request->user();

        if (!$user) {
            return response()->json(['success' => true, 'data' => ['has_permission' => false]]);
        }

        $hasPermission = $user->hasPermission($validated['permission']);

        return response()->json([
            'success' => true,
            'data' => ['has_permission' => $hasPermission],
        ]);
    }

    // =========================================================================
    // Meta & Documentation
    // =========================================================================

    public function groups(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Permission::getGroups(),
        ]);
    }

    public function documentation(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->registry->getDocumentation(),
        ]);
    }

    public function clearCache(): JsonResponse
    {
        $this->registry->clearCache();
        return response()->json(['success' => true, 'message' => 'Cache cleared']);
    }
}
