<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel Access Middleware
 * 
 * Controls access to different panels based on user roles.
 * All users are stored in a single 'users' table.
 * 
 * Usage in routes:
 *   Route::middleware('panel:console')->group(...);
 *   Route::middleware('panel:owner')->group(...);
 *   Route::middleware('panel:admin')->group(...);
 *   Route::middleware('panel:client')->group(...);
 */
class PanelAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $panel): Response
    {
        // Determine guard for the panel
        $modules = config('modules.modules', []);
        $guard = null;
        foreach ($modules as $modName => $modConfig) {
            if (isset($modConfig['subdomain']) && $modConfig['subdomain'] === $panel) {
                $guard = $modConfig['guard'] ?? null;
                break;
            }
        }

        // Use the specific guard if available, otherwise fallback to default
        $user = $guard ? auth()->guard($guard)->user() : $request->user();

        if (!$user) {
            return $this->unauthorized($request, $panel);
        }

        // Check panel access based on user's method
        $hasAccess = match ($panel) {
            'console' => $user->canAccessConsole(),
            'owner' => $user->canAccessOwner(),
            'admin' => $user->canAccessAdmin(),
            'client', 'client-area' => $user->canAccessClient(),
            default => false,
        };

        if (!$hasAccess) {
            return $this->forbidden($request, $panel);
        }

        return $next($request);
    }

    /**
     * Handle unauthorized (not logged in) response
     */
    protected function unauthorized(Request $request, string $panel): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Redirect to panel-specific login
        return redirect()->route("{$panel}.login");
    }

    /**
     * Handle forbidden (no permission) response
     */
    protected function forbidden(Request $request, string $panel): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this panel.',
            ], 403);
        }

        // Show 403 error page
        abort(403, 'You do not have permission to access this panel.');
    }
}

