<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\Security\SecurityException;

/**
 * CSRF protection middleware for plugin operations.
 * Provides nonce-based protection similar to WordPress.
 */
class PluginCsrfMiddleware
{
    /**
     * Token lifetime in seconds (default: 12 hours).
     */
    protected const TOKEN_LIFETIME = 43200;

    /**
     * Actions that require CSRF protection.
     */
    protected array $protectedActions = [
        'plugin.install',
        'plugin.activate',
        'plugin.deactivate',
        'plugin.uninstall',
        'plugin.update',
        'entity.create',
        'entity.update',
        'entity.delete',
        'settings.update',
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, ?string $action = null): Response
    {
        // Skip for GET, HEAD, OPTIONS requests
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        // Get the action from route or parameter
        $action = $action ?? $request->route()?->getName() ?? $request->input('_action');

        // Check if this action needs protection
        if (!$this->actionRequiresProtection($action)) {
            return $next($request);
        }

        // Verify the nonce
        $nonce = $request->header('X-Plugin-Nonce') ?? $request->input('_plugin_nonce');

        if (!$nonce || !$this->verifyNonce($nonce, $action)) {
            throw SecurityException::invalidCsrfToken();
        }

        return $next($request);
    }

    /**
     * Check if an action requires CSRF protection.
     */
    protected function actionRequiresProtection(?string $action): bool
    {
        if (!$action) {
            return false;
        }

        foreach ($this->protectedActions as $protected) {
            if ($action === $protected || str_starts_with($action, $protected . '.')) {
                return true;
            }
        }

        return config('plugin.csrf.protect_all', false);
    }

    /**
     * Generate a nonce for an action.
     */
    public static function createNonce(string $action): string
    {
        $userId = auth()->id() ?? 0;
        $tick = ceil(time() / (self::TOKEN_LIFETIME / 2));
        $sessionToken = Session::token();

        return substr(
            hash('sha256', "{$tick}|{$action}|{$userId}|{$sessionToken}"),
            0,
            32
        );
    }

    /**
     * Verify a nonce for an action.
     */
    public function verifyNonce(string $nonce, string $action): bool
    {
        $userId = auth()->id() ?? 0;
        $sessionToken = Session::token();

        // Check current tick
        $tick = ceil(time() / (self::TOKEN_LIFETIME / 2));
        $expected = substr(
            hash('sha256', "{$tick}|{$action}|{$userId}|{$sessionToken}"),
            0,
            32
        );

        if (hash_equals($expected, $nonce)) {
            return true;
        }

        // Check previous tick (allows for tokens near expiry)
        $tick--;
        $expected = substr(
            hash('sha256', "{$tick}|{$action}|{$userId}|{$sessionToken}"),
            0,
            32
        );

        return hash_equals($expected, $nonce);
    }

    /**
     * Get a list of protected actions.
     */
    public function getProtectedActions(): array
    {
        return $this->protectedActions;
    }

    /**
     * Add an action to the protected list.
     */
    public function addProtectedAction(string $action): void
    {
        if (!in_array($action, $this->protectedActions, true)) {
            $this->protectedActions[] = $action;
        }
    }
}
