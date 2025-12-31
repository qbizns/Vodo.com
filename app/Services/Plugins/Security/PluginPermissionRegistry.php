<?php

declare(strict_types=1);

namespace App\Services\Plugins\Security;

use App\Enums\PluginScope;
use App\Models\Plugin;
use App\Models\PluginAuditLog;
use App\Models\PluginPermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Plugin Permission Registry - Manages plugin permission grants and checks.
 *
 * This service is responsible for:
 * - Granting and revoking plugin permissions
 * - Checking if a plugin has a specific permission
 * - Parsing permission requirements from plugin manifests
 * - Caching permission lookups for performance
 */
class PluginPermissionRegistry
{
    /**
     * Cache TTL in seconds.
     */
    protected const CACHE_TTL = 300;

    /**
     * Cache key prefix.
     */
    protected const CACHE_PREFIX = 'plugin_permissions:';

    /**
     * In-memory permission cache for the current request.
     *
     * @var array<string, array<string, bool>>
     */
    protected array $runtimeCache = [];

    // =========================================================================
    // Permission Checking
    // =========================================================================

    /**
     * Check if a plugin has a specific permission.
     */
    public function hasPermission(
        string $pluginSlug,
        string|PluginScope $scope,
        ?string $resource = null,
        string $accessLevel = PluginPermission::ACCESS_READ
    ): bool {
        $scopeValue = $scope instanceof PluginScope ? $scope->value : $scope;

        // Check runtime cache first
        $cacheKey = "{$pluginSlug}:{$scopeValue}:{$resource}:{$accessLevel}";
        if (isset($this->runtimeCache[$cacheKey])) {
            return $this->runtimeCache[$cacheKey];
        }

        // Check persistent cache
        $persistentKey = self::CACHE_PREFIX . $pluginSlug;
        $permissions = Cache::remember($persistentKey, self::CACHE_TTL, function () use ($pluginSlug) {
            return $this->loadPluginPermissions($pluginSlug);
        });

        $hasPermission = $this->checkPermissionInSet($permissions, $scopeValue, $resource, $accessLevel);

        // Store in runtime cache
        $this->runtimeCache[$cacheKey] = $hasPermission;

        return $hasPermission;
    }

    /**
     * Check if plugin has any of the given permissions.
     *
     * @param array<string|PluginScope> $scopes
     */
    public function hasAnyPermission(string $pluginSlug, array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($this->hasPermission($pluginSlug, $scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if plugin has all of the given permissions.
     *
     * @param array<string|PluginScope> $scopes
     */
    public function hasAllPermissions(string $pluginSlug, array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (!$this->hasPermission($pluginSlug, $scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Authorize a permission check, logging the result.
     */
    public function authorize(
        string $pluginSlug,
        string|PluginScope $scope,
        ?string $resource = null,
        string $accessLevel = PluginPermission::ACCESS_READ
    ): bool {
        $hasPermission = $this->hasPermission($pluginSlug, $scope, $resource, $accessLevel);

        // Log the authorization check
        $scopeValue = $scope instanceof PluginScope ? $scope->value : $scope;

        PluginAuditLog::access(
            $pluginSlug,
            $hasPermission ? PluginAuditLog::EVENT_SCOPE_CHECK : PluginAuditLog::EVENT_PERMISSION_DENIED,
            $hasPermission ? "Permission check passed: {$scopeValue}" : "Permission denied: {$scopeValue}",
            [
                'scope' => $scopeValue,
                'resource' => $resource,
                'access_level' => $accessLevel,
                'granted' => $hasPermission,
            ],
            $hasPermission ? PluginAuditLog::SEVERITY_DEBUG : PluginAuditLog::SEVERITY_WARNING
        );

        return $hasPermission;
    }

    // =========================================================================
    // Permission Management
    // =========================================================================

    /**
     * Grant a permission to a plugin.
     */
    public function grant(
        string $pluginSlug,
        string|PluginScope $scope,
        ?string $resource = null,
        string $accessLevel = PluginPermission::ACCESS_READ,
        ?array $constraints = null,
        ?int $grantedBy = null
    ): PluginPermission {
        $scopeValue = $scope instanceof PluginScope ? $scope->value : $scope;

        $permission = PluginPermission::updateOrCreate(
            [
                'plugin_slug' => $pluginSlug,
                'scope' => $scopeValue,
                'resource' => $resource,
            ],
            [
                'access_level' => $accessLevel,
                'constraints' => $constraints,
                'is_granted' => true,
                'granted_at' => now(),
                'granted_by' => $grantedBy ?? auth()->id(),
                'revoked_at' => null,
            ]
        );

        // Clear cache
        $this->clearCache($pluginSlug);

        // Log the grant
        PluginAuditLog::security(
            $pluginSlug,
            PluginAuditLog::EVENT_PERMISSION_GRANTED,
            "Permission granted: {$scopeValue}",
            [
                'scope' => $scopeValue,
                'resource' => $resource,
                'access_level' => $accessLevel,
                'granted_by' => $grantedBy ?? auth()->id(),
            ]
        );

        Log::info("Permission granted to plugin", [
            'plugin' => $pluginSlug,
            'scope' => $scopeValue,
            'resource' => $resource,
        ]);

        return $permission;
    }

    /**
     * Grant multiple permissions at once.
     *
     * @param array<array{scope: string|PluginScope, resource?: string, access_level?: string}> $permissions
     * @return Collection<PluginPermission>
     */
    public function grantMany(string $pluginSlug, array $permissions, ?int $grantedBy = null): Collection
    {
        return DB::transaction(function () use ($pluginSlug, $permissions, $grantedBy) {
            $granted = collect();

            foreach ($permissions as $perm) {
                $scope = $perm['scope'];
                $resource = $perm['resource'] ?? null;
                $accessLevel = $perm['access_level'] ?? PluginPermission::ACCESS_READ;

                $granted->push($this->grant($pluginSlug, $scope, $resource, $accessLevel, null, $grantedBy));
            }

            return $granted;
        });
    }

    /**
     * Revoke a permission from a plugin.
     */
    public function revoke(
        string $pluginSlug,
        string|PluginScope $scope,
        ?string $resource = null
    ): bool {
        $scopeValue = $scope instanceof PluginScope ? $scope->value : $scope;

        $permission = PluginPermission::where('plugin_slug', $pluginSlug)
            ->where('scope', $scopeValue)
            ->where('resource', $resource)
            ->first();

        if (!$permission) {
            return false;
        }

        $permission->revoke();

        // Clear cache
        $this->clearCache($pluginSlug);

        // Log the revocation
        PluginAuditLog::security(
            $pluginSlug,
            PluginAuditLog::EVENT_PERMISSION_REVOKED,
            "Permission revoked: {$scopeValue}",
            [
                'scope' => $scopeValue,
                'resource' => $resource,
            ]
        );

        Log::info("Permission revoked from plugin", [
            'plugin' => $pluginSlug,
            'scope' => $scopeValue,
            'resource' => $resource,
        ]);

        return true;
    }

    /**
     * Revoke all permissions for a plugin.
     */
    public function revokeAll(string $pluginSlug): int
    {
        $count = PluginPermission::where('plugin_slug', $pluginSlug)
            ->granted()
            ->update([
                'is_granted' => false,
                'revoked_at' => now(),
            ]);

        $this->clearCache($pluginSlug);

        if ($count > 0) {
            PluginAuditLog::security(
                $pluginSlug,
                PluginAuditLog::EVENT_PERMISSION_REVOKED,
                "All permissions revoked ({$count} total)",
                ['count' => $count]
            );
        }

        return $count;
    }

    // =========================================================================
    // Manifest Processing
    // =========================================================================

    /**
     * Process permissions from a plugin manifest.
     *
     * @param array $manifest The plugin.json content
     * @return array{required: array, optional: array, dangerous: array}
     */
    public function parseManifestPermissions(array $manifest): array
    {
        $permissions = $manifest['permissions'] ?? [];
        $result = [
            'required' => [],
            'optional' => [],
            'dangerous' => [],
        ];

        // Parse entity permissions
        if (isset($permissions['entities'])) {
            foreach ((array) $permissions['entities'] as $entityPerm) {
                $parsed = $this->parsePermissionString($entityPerm, 'entities');
                $this->categorizePermission($parsed, $result);
            }
        }

        // Parse hook permissions
        if (isset($permissions['hooks'])) {
            foreach ((array) $permissions['hooks'] as $hookPerm) {
                $parsed = $this->parsePermissionString($hookPerm, 'hooks');
                $this->categorizePermission($parsed, $result);
            }
        }

        // Parse API permissions
        if (isset($permissions['api'])) {
            $apiPerms = $permissions['api'];
            if (isset($apiPerms['endpoints'])) {
                $result['required'][] = [
                    'scope' => PluginScope::API_ACCESS,
                    'resource' => null,
                ];
            }
        }

        // Parse network permissions
        if (isset($permissions['network'])) {
            if (isset($permissions['network']['outbound'])) {
                $result['required'][] = [
                    'scope' => PluginScope::NETWORK_OUTBOUND,
                    'resource' => null,
                    'constraints' => ['domains' => $permissions['network']['outbound']],
                ];
            }
        }

        // Parse storage permissions
        if (isset($permissions['storage'])) {
            $result['required'][] = [
                'scope' => PluginScope::STORAGE_WRITE,
                'resource' => null,
                'constraints' => $permissions['storage'],
            ];
        }

        return $result;
    }

    /**
     * Grant permissions from a manifest during plugin activation.
     */
    public function grantFromManifest(string $pluginSlug, array $manifest, ?int $grantedBy = null): array
    {
        $parsed = $this->parseManifestPermissions($manifest);
        $granted = [];
        $requiresApproval = [];

        foreach ($parsed['required'] as $perm) {
            $scope = $perm['scope'];

            // Check if scope requires approval
            if ($scope instanceof PluginScope && $scope->requiresApproval()) {
                $requiresApproval[] = $perm;
                continue;
            }

            $this->grant(
                $pluginSlug,
                $scope,
                $perm['resource'] ?? null,
                $perm['access_level'] ?? PluginPermission::ACCESS_READ,
                $perm['constraints'] ?? null,
                $grantedBy
            );

            $granted[] = $perm;
        }

        return [
            'granted' => $granted,
            'requires_approval' => $requiresApproval,
            'optional' => $parsed['optional'],
            'dangerous' => $parsed['dangerous'],
        ];
    }

    // =========================================================================
    // Query Methods
    // =========================================================================

    /**
     * Get all permissions for a plugin.
     */
    public function getPluginPermissions(string $pluginSlug): Collection
    {
        return PluginPermission::forPlugin($pluginSlug)->granted()->get();
    }

    /**
     * Get all plugins with a specific permission.
     */
    public function getPluginsWithPermission(string|PluginScope $scope, ?string $resource = null): Collection
    {
        $scopeValue = $scope instanceof PluginScope ? $scope->value : $scope;

        return PluginPermission::forScope($scopeValue)
            ->forResource($resource)
            ->granted()
            ->get()
            ->pluck('plugin_slug')
            ->unique();
    }

    /**
     * Get missing permissions for a plugin based on manifest requirements.
     */
    public function getMissingPermissions(string $pluginSlug, array $manifest): array
    {
        $parsed = $this->parseManifestPermissions($manifest);
        $missing = [];

        foreach ($parsed['required'] as $perm) {
            $scope = $perm['scope'];
            $resource = $perm['resource'] ?? null;

            if (!$this->hasPermission($pluginSlug, $scope, $resource)) {
                $missing[] = $perm;
            }
        }

        return $missing;
    }

    // =========================================================================
    // Cache Management
    // =========================================================================

    /**
     * Clear the permission cache for a plugin.
     */
    public function clearCache(string $pluginSlug): void
    {
        Cache::forget(self::CACHE_PREFIX . $pluginSlug);
        unset($this->runtimeCache[$pluginSlug]);

        // Clear all runtime cache entries for this plugin
        foreach (array_keys($this->runtimeCache) as $key) {
            if (str_starts_with($key, "{$pluginSlug}:")) {
                unset($this->runtimeCache[$key]);
            }
        }
    }

    /**
     * Clear all permission caches.
     */
    public function clearAllCaches(): void
    {
        $this->runtimeCache = [];
        // Would need to iterate through plugins to clear all caches
        // For now, this clears runtime cache only
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Load all permissions for a plugin from the database.
     */
    protected function loadPluginPermissions(string $pluginSlug): array
    {
        return PluginPermission::forPlugin($pluginSlug)
            ->granted()
            ->get()
            ->map(fn($p) => [
                'scope' => $p->scope,
                'resource' => $p->resource,
                'access_level' => $p->access_level,
                'constraints' => $p->constraints,
            ])
            ->toArray();
    }

    /**
     * Check if a permission exists in a permission set.
     */
    protected function checkPermissionInSet(
        array $permissions,
        string $scope,
        ?string $resource,
        string $accessLevel
    ): bool {
        $accessHierarchy = [
            PluginPermission::ACCESS_READ => 1,
            PluginPermission::ACCESS_WRITE => 2,
            PluginPermission::ACCESS_DELETE => 3,
            PluginPermission::ACCESS_ADMIN => 4,
        ];

        $requiredLevel = $accessHierarchy[$accessLevel] ?? 1;

        foreach ($permissions as $perm) {
            // Check scope match
            if ($perm['scope'] !== $scope) {
                continue;
            }

            // Check resource match (null means all resources)
            if ($perm['resource'] !== null && $perm['resource'] !== $resource && $resource !== null) {
                continue;
            }

            // Check access level
            $grantedLevel = $accessHierarchy[$perm['access_level']] ?? 1;
            if ($grantedLevel >= $requiredLevel) {
                return true;
            }
        }

        // Check implied permissions from scope enums
        $scopeEnum = PluginScope::tryFrom($scope);
        if ($scopeEnum) {
            foreach ($scopeEnum->implies() as $impliedScope) {
                if ($this->checkPermissionInSet($permissions, $impliedScope->value, $resource, $accessLevel)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Parse a permission string like "read:product" or "write:*".
     */
    protected function parsePermissionString(string $permission, string $category): array
    {
        $parts = explode(':', $permission);
        $action = $parts[0];
        $resource = $parts[1] ?? null;

        if ($resource === '*') {
            $resource = null;
        }

        $scopeValue = "{$category}:{$action}";
        $scope = PluginScope::tryFrom($scopeValue);

        return [
            'scope' => $scope ?? $scopeValue,
            'resource' => $resource,
            'access_level' => $this->actionToAccessLevel($action),
        ];
    }

    /**
     * Convert an action to an access level.
     */
    protected function actionToAccessLevel(string $action): string
    {
        return match ($action) {
            'read' => PluginPermission::ACCESS_READ,
            'write', 'create', 'update' => PluginPermission::ACCESS_WRITE,
            'delete' => PluginPermission::ACCESS_DELETE,
            'admin', 'manage' => PluginPermission::ACCESS_ADMIN,
            default => PluginPermission::ACCESS_READ,
        };
    }

    /**
     * Categorize a permission into required, optional, or dangerous.
     */
    protected function categorizePermission(array $permission, array &$result): void
    {
        $scope = $permission['scope'];

        if ($scope instanceof PluginScope) {
            if ($scope->requiresApproval()) {
                $result['dangerous'][] = $permission;
            } elseif ($scope->isDangerous()) {
                $result['dangerous'][] = $permission;
            } else {
                $result['required'][] = $permission;
            }
        } else {
            $result['required'][] = $permission;
        }
    }
}
