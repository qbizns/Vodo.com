<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Permission Middleware
 * 
 * Usage in routes:
 * Route::get('/admin', ...)->middleware('permission:admin.access');
 * Route::get('/posts', ...)->middleware('permission:posts.view,posts.list');
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }
            return redirect()->route('login');
        }

        // Check if user has any of the required permissions
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden - insufficient permissions',
                'required' => $permissions,
            ], 403);
        }

        abort(403, 'You do not have permission to access this resource.');
    }
}

/**
 * Check All Permissions Middleware
 * 
 * Usage: Route::get('/admin', ...)->middleware('permissions:posts.view,posts.edit');
 * User must have ALL listed permissions.
 */
class CheckAllPermissions
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        foreach ($permissions as $permission) {
            if (!$user->hasPermission($permission)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Forbidden - missing permission: ' . $permission,
                    ], 403);
                }
                abort(403, "Missing required permission: {$permission}");
            }
        }

        return $next($request);
    }
}

/**
 * Check Role Middleware
 * 
 * Usage: Route::get('/admin', ...)->middleware('role:admin');
 * Route::get('/manage', ...)->middleware('role:admin,manager');
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden - requires role',
                'required' => $roles,
            ], 403);
        }

        abort(403, 'You do not have the required role to access this resource.');
    }
}

/**
 * Check Role Level Middleware
 * 
 * Usage: Route::get('/admin', ...)->middleware('role_level:50');
 * User must have a role with level >= specified value.
 */
class CheckRoleLevel
{
    public function handle(Request $request, Closure $next, int $level): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        $highestRole = $user->getHighestRole();

        if (!$highestRole || $highestRole->level < $level) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Forbidden - insufficient role level',
                    'required_level' => $level,
                ], 403);
            }
            abort(403, 'Your role level is insufficient to access this resource.');
        }

        return $next($request);
    }
}
