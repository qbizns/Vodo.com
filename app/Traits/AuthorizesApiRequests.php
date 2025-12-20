<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use App\Models\User;

/**
 * AuthorizesApiRequests - Provides consistent authorization for API controllers.
 *
 * This trait provides:
 * - Permission-based authorization checks
 * - Entity-specific permission generation
 * - Admin bypass capability
 * - Audit logging for access attempts
 *
 * Permission naming convention: {resource}.{action}
 * Examples:
 * - entities.view
 * - entities.create
 * - entities.update
 * - entities.delete
 * - entities.bulk_delete
 * - users.manage
 */
trait AuthorizesApiRequests
{
    /**
     * Get the resource name for permission checks.
     * Override in controller if needed.
     */
    protected function getResourceName(): string
    {
        // Default: derive from controller name
        // EntityApiController -> entities
        // UserApiController -> users
        $className = class_basename(static::class);
        $resource = str_replace(['ApiController', 'Controller'], '', $className);
        return strtolower(\Str::plural(\Str::snake($resource)));
    }

    /**
     * Get the current authenticated user.
     */
    protected function getAuthUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Check if user is authenticated.
     */
    protected function ensureAuthenticated(): void
    {
        if (!Auth::check()) {
            throw new AuthorizationException('Authentication required.');
        }
    }

    /**
     * Authorize a specific action on the resource.
     *
     * @throws AuthorizationException
     */
    protected function authorizeAction(string $action, ?string $resourceType = null): void
    {
        $this->ensureAuthenticated();

        $user = $this->getAuthUser();
        $resource = $resourceType ?? $this->getResourceName();
        $permission = "{$resource}.{$action}";

        if (!$this->userHasPermission($user, $permission)) {
            $this->logUnauthorizedAccess($user, $permission);
            throw new AuthorizationException("You do not have permission to {$action} {$resource}.");
        }
    }

    /**
     * Authorize viewing resources.
     */
    protected function authorizeView(?string $resourceType = null): void
    {
        $this->authorizeAction('view', $resourceType);
    }

    /**
     * Authorize listing resources.
     */
    protected function authorizeIndex(?string $resourceType = null): void
    {
        $this->authorizeAction('view', $resourceType);
    }

    /**
     * Authorize creating resources.
     */
    protected function authorizeCreate(?string $resourceType = null): void
    {
        $this->authorizeAction('create', $resourceType);
    }

    /**
     * Authorize updating resources.
     */
    protected function authorizeUpdate(?string $resourceType = null): void
    {
        $this->authorizeAction('update', $resourceType);
    }

    /**
     * Authorize deleting resources.
     */
    protected function authorizeDelete(?string $resourceType = null): void
    {
        $this->authorizeAction('delete', $resourceType);
    }

    /**
     * Authorize bulk operations.
     */
    protected function authorizeBulk(string $action, ?string $resourceType = null): void
    {
        // Bulk operations require elevated permissions
        $this->authorizeAction("bulk_{$action}", $resourceType);
    }

    /**
     * Authorize access to admin-only resources.
     */
    protected function authorizeAdmin(): void
    {
        $this->ensureAuthenticated();

        $user = $this->getAuthUser();

        if (!$user->isAdmin()) {
            $this->logUnauthorizedAccess($user, 'admin_access');
            throw new AuthorizationException('Admin access required.');
        }
    }

    /**
     * Authorize access to super admin resources.
     */
    protected function authorizeSuperAdmin(): void
    {
        $this->ensureAuthenticated();

        $user = $this->getAuthUser();

        if (!$user->isSuperAdmin()) {
            $this->logUnauthorizedAccess($user, 'super_admin_access');
            throw new AuthorizationException('Super admin access required.');
        }
    }

    /**
     * Check if user owns the resource (for personal data access).
     */
    protected function authorizeOwnership($model, string $ownerField = 'user_id'): void
    {
        $this->ensureAuthenticated();

        $user = $this->getAuthUser();

        // Admins can access all resources
        if ($user->isAdmin()) {
            return;
        }

        $ownerId = $model->$ownerField ?? $model->author_id ?? $model->created_by ?? null;

        if ($ownerId !== $user->id) {
            throw new AuthorizationException('You do not have access to this resource.');
        }
    }

    /**
     * Authorize if user can perform action OR owns the resource.
     */
    protected function authorizeActionOrOwnership(string $action, $model, ?string $ownerField = null): void
    {
        $this->ensureAuthenticated();

        $user = $this->getAuthUser();
        $resource = $this->getResourceName();
        $permission = "{$resource}.{$action}";

        // Check if has permission
        if ($this->userHasPermission($user, $permission)) {
            return;
        }

        // Check ownership
        $ownerField = $ownerField ?? 'user_id';
        $ownerId = $model->$ownerField ?? $model->author_id ?? $model->created_by ?? null;

        if ($ownerId === $user->id) {
            return;
        }

        throw new AuthorizationException("You do not have permission to {$action} this resource.");
    }

    /**
     * Check if user has a specific permission.
     */
    protected function userHasPermission(User $user, string $permission): bool
    {
        // Super admins bypass all checks
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission($permission);
    }

    /**
     * Check if user can access a specific entity type.
     */
    protected function authorizeEntityAccess(string $entityName, string $action): void
    {
        $permission = "entities.{$entityName}.{$action}";
        $this->authorizeAction($action, "entities.{$entityName}");
    }

    /**
     * Log unauthorized access attempt.
     */
    protected function logUnauthorizedAccess(User $user, string $permission): void
    {
        \Log::warning('Unauthorized API access attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'permission' => $permission,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get the list of actions that require specific permissions.
     * Override in controller to customize.
     */
    protected function getPermissionMap(): array
    {
        $resource = $this->getResourceName();

        return [
            'index' => "{$resource}.view",
            'show' => "{$resource}.view",
            'store' => "{$resource}.create",
            'update' => "{$resource}.update",
            'destroy' => "{$resource}.delete",
            'bulk' => "{$resource}.bulk",
            'restore' => "{$resource}.restore",
            'export' => "{$resource}.export",
            'import' => "{$resource}.import",
        ];
    }

    /**
     * Authorize based on controller method name.
     */
    protected function authorizeMethod(string $method): void
    {
        $map = $this->getPermissionMap();

        if (isset($map[$method])) {
            $permission = $map[$method];
            $parts = explode('.', $permission);
            $action = end($parts);
            $this->authorizeAction($action);
        }
    }
}
