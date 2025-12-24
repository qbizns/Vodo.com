<?php

namespace Ums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;

/**
 * Permission Controller
 */
class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        $permissionGroups = PermissionGroup::active()
            ->with(['permissions' => fn($q) => $q->active()->orderBy('name')])
            ->withCount('permissions')
            ->ordered()
            ->get();

        $totalPermissions = Permission::active()->count();

        return view('ums::permissions.index', compact('permissionGroups', 'totalPermissions'));
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
}

