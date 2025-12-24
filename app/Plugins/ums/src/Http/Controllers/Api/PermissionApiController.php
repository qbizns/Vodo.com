<?php

namespace Ums\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;

/**
 * Permission API Controller
 */
class PermissionApiController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        $query = Permission::active()->with('permissionGroup');

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('group')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * Get permission groups with permissions.
     */
    public function groups()
    {
        $groups = PermissionGroup::getGroupedPermissions();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission)
    {
        $permission->load('permissionGroup', 'roles');

        return response()->json([
            'success' => true,
            'data' => $permission,
        ]);
    }
}

