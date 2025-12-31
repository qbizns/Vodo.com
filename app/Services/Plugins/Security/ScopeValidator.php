<?php

declare(strict_types=1);

namespace App\Services\Plugins\Security;

use App\Enums\PluginScope;
use App\Exceptions\Plugins\PluginScopeException;
use App\Models\PluginAuditLog;
use Illuminate\Support\Facades\Log;

/**
 * Scope Validator - Validates plugin scope requests and access.
 *
 * This service is responsible for:
 * - Validating scope strings
 * - Checking scope compatibility
 * - Enforcing scope restrictions at runtime
 * - Providing scope information for UI
 */
class ScopeValidator
{
    protected PluginPermissionRegistry $permissionRegistry;

    /**
     * Current plugin context for validation.
     */
    protected ?string $currentPluginSlug = null;

    public function __construct(PluginPermissionRegistry $permissionRegistry)
    {
        $this->permissionRegistry = $permissionRegistry;
    }

    // =========================================================================
    // Context Management
    // =========================================================================

    /**
     * Set the current plugin context for validation.
     */
    public function setPluginContext(?string $pluginSlug): void
    {
        $this->currentPluginSlug = $pluginSlug;
    }

    /**
     * Get the current plugin context.
     */
    public function getPluginContext(): ?string
    {
        return $this->currentPluginSlug;
    }

    /**
     * Execute a callback within a specific plugin context.
     */
    public function withinContext(string $pluginSlug, callable $callback): mixed
    {
        $previousContext = $this->currentPluginSlug;
        $this->currentPluginSlug = $pluginSlug;

        try {
            return $callback();
        } finally {
            $this->currentPluginSlug = $previousContext;
        }
    }

    // =========================================================================
    // Scope Validation
    // =========================================================================

    /**
     * Validate a scope string.
     */
    public function isValidScope(string $scope): bool
    {
        // Check if it's a known enum value
        if (PluginScope::tryFrom($scope) !== null) {
            return true;
        }

        // Check if it's a valid scope with resource specifier
        $parsed = PluginScope::parse($scope);
        return $parsed['scope'] !== null;
    }

    /**
     * Validate an array of scope strings.
     *
     * @return array{valid: array, invalid: array}
     */
    public function validateScopes(array $scopes): array
    {
        $result = ['valid' => [], 'invalid' => []];

        foreach ($scopes as $scope) {
            if ($this->isValidScope($scope)) {
                $result['valid'][] = $scope;
            } else {
                $result['invalid'][] = $scope;
            }
        }

        return $result;
    }

    /**
     * Check if a scope requires admin approval.
     */
    public function requiresApproval(string|PluginScope $scope): bool
    {
        $scopeEnum = $scope instanceof PluginScope ? $scope : PluginScope::tryFrom($scope);

        if ($scopeEnum === null) {
            return true; // Unknown scopes require approval by default
        }

        return $scopeEnum->requiresApproval();
    }

    /**
     * Get the risk level for a scope (1-5).
     */
    public function getRiskLevel(string|PluginScope $scope): int
    {
        $scopeEnum = $scope instanceof PluginScope ? $scope : PluginScope::tryFrom($scope);

        if ($scopeEnum === null) {
            return 5; // Unknown scopes are highest risk
        }

        return $scopeEnum->riskLevel();
    }

    // =========================================================================
    // Access Enforcement
    // =========================================================================

    /**
     * Check if the current plugin can access a scope.
     *
     * @throws PluginScopeException if no plugin context is set
     */
    public function canAccess(string|PluginScope $scope, ?string $resource = null): bool
    {
        if ($this->currentPluginSlug === null) {
            throw new PluginScopeException('No plugin context set for scope validation');
        }

        return $this->permissionRegistry->hasPermission(
            $this->currentPluginSlug,
            $scope,
            $resource
        );
    }

    /**
     * Assert that the current plugin can access a scope, throwing if not.
     *
     * @throws PluginScopeException
     */
    public function assertCanAccess(string|PluginScope $scope, ?string $resource = null): void
    {
        if (!$this->canAccess($scope, $resource)) {
            $scopeValue = $scope instanceof PluginScope ? $scope->value : $scope;

            PluginAuditLog::security(
                $this->currentPluginSlug ?? 'unknown',
                PluginAuditLog::EVENT_PERMISSION_DENIED,
                "Scope access denied: {$scopeValue}",
                [
                    'scope' => $scopeValue,
                    'resource' => $resource,
                ],
                PluginAuditLog::SEVERITY_WARNING
            );

            throw PluginScopeException::accessDenied($scopeValue, $this->currentPluginSlug);
        }
    }

    /**
     * Check if a plugin can access an entity.
     */
    public function canAccessEntity(string $entityName, string $action = 'read'): bool
    {
        $scope = match ($action) {
            'read' => PluginScope::ENTITIES_READ,
            'write', 'create', 'update' => PluginScope::ENTITIES_WRITE,
            'delete' => PluginScope::ENTITIES_DELETE,
            'schema' => PluginScope::ENTITIES_SCHEMA,
            default => PluginScope::ENTITIES_READ,
        };

        // Check for specific entity access
        if ($this->canAccess($scope, $entityName)) {
            return true;
        }

        // Check for wildcard access
        return $this->canAccess($scope, null);
    }

    /**
     * Check if a plugin can subscribe to a hook.
     */
    public function canSubscribeToHook(string $hookName): bool
    {
        return $this->canAccess(PluginScope::HOOKS_SUBSCRIBE, $hookName)
            || $this->canAccess(PluginScope::HOOKS_SUBSCRIBE, null);
    }

    /**
     * Check if a plugin can make outbound network requests.
     */
    public function canAccessNetwork(string $domain): bool
    {
        // Check specific domain permission
        if ($this->canAccess(PluginScope::NETWORK_OUTBOUND, $domain)) {
            return true;
        }

        // Check wildcard permission with domain constraints
        return $this->canAccess(PluginScope::NETWORK_OUTBOUND, null);
    }

    // =========================================================================
    // Scope Information
    // =========================================================================

    /**
     * Get all available scopes.
     *
     * @return array<array{value: string, name: string, description: string, category: string, risk_level: int}>
     */
    public function getAllScopes(): array
    {
        return array_map(
            fn(PluginScope $scope) => $scope->toArray(),
            PluginScope::cases()
        );
    }

    /**
     * Get scopes grouped by category.
     *
     * @return array<string, array<array{value: string, name: string, description: string}>>
     */
    public function getScopesGrouped(): array
    {
        $grouped = [];

        foreach (PluginScope::cases() as $scope) {
            $category = $scope->category();
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $scope->toArray();
        }

        return $grouped;
    }

    /**
     * Get only dangerous scopes.
     *
     * @return array<array{value: string, name: string, description: string}>
     */
    public function getDangerousScopes(): array
    {
        return array_map(
            fn(PluginScope $scope) => $scope->toArray(),
            PluginScope::dangerous()
        );
    }

    /**
     * Get scope information for display in consent UI.
     *
     * @param array<string> $requestedScopes
     * @return array{safe: array, caution: array, dangerous: array}
     */
    public function categorizeScopesForConsent(array $requestedScopes): array
    {
        $result = [
            'safe' => [],
            'caution' => [],
            'dangerous' => [],
        ];

        foreach ($requestedScopes as $scopeString) {
            $scope = PluginScope::tryFrom($scopeString);

            if ($scope === null) {
                $result['dangerous'][] = [
                    'value' => $scopeString,
                    'name' => $scopeString,
                    'description' => 'Unknown permission',
                    'risk_level' => 5,
                ];
                continue;
            }

            $info = $scope->toArray();
            $riskLevel = $scope->riskLevel();

            if ($riskLevel <= 2) {
                $result['safe'][] = $info;
            } elseif ($riskLevel <= 4) {
                $result['caution'][] = $info;
            } else {
                $result['dangerous'][] = $info;
            }
        }

        return $result;
    }

    /**
     * Get expanded scopes including implied scopes.
     *
     * @param array<string> $scopes
     * @return array<string>
     */
    public function expandScopes(array $scopes): array
    {
        $expanded = [];

        foreach ($scopes as $scopeString) {
            $expanded[] = $scopeString;

            $scope = PluginScope::tryFrom($scopeString);
            if ($scope !== null) {
                foreach ($scope->implies() as $implied) {
                    $expanded[] = $implied->value;
                }
            }
        }

        return array_unique($expanded);
    }

    /**
     * Get the minimum scopes required to satisfy a set of requested scopes.
     *
     * @param array<string> $requestedScopes
     * @return array<string>
     */
    public function minimizeScopes(array $requestedScopes): array
    {
        $minimized = [];
        $covered = [];

        // Sort by risk level descending (higher risk = more permissions = covers more)
        usort($requestedScopes, function ($a, $b) {
            return $this->getRiskLevel($b) - $this->getRiskLevel($a);
        });

        foreach ($requestedScopes as $scopeString) {
            if (in_array($scopeString, $covered, true)) {
                continue;
            }

            $minimized[] = $scopeString;

            // Mark all implied scopes as covered
            $scope = PluginScope::tryFrom($scopeString);
            if ($scope !== null) {
                foreach ($scope->implies() as $implied) {
                    $covered[] = $implied->value;
                }
            }
        }

        return $minimized;
    }
}
