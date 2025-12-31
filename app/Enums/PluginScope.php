<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Plugin Scope - Defines all available permission scopes for plugins.
 *
 * Inspired by Salla's OAuth scopes, this enum defines what resources
 * and actions a plugin can access.
 *
 * Scope Format: {resource}:{action}[:{specific}]
 *
 * Examples:
 * - entities:read           - Read any entity
 * - entities:read:product   - Read only product entities
 * - hooks:subscribe         - Subscribe to hooks
 * - api:access              - Access platform API
 */
enum PluginScope: string
{
    // =========================================================================
    // Entity Scopes
    // =========================================================================
    case ENTITIES_READ = 'entities:read';
    case ENTITIES_WRITE = 'entities:write';
    case ENTITIES_DELETE = 'entities:delete';
    case ENTITIES_SCHEMA = 'entities:schema';

    // =========================================================================
    // Hook Scopes
    // =========================================================================
    case HOOKS_SUBSCRIBE = 'hooks:subscribe';
    case HOOKS_TRIGGER = 'hooks:trigger';

    // =========================================================================
    // API Scopes
    // =========================================================================
    case API_ACCESS = 'api:access';
    case API_ADMIN = 'api:admin';

    // =========================================================================
    // User Scopes
    // =========================================================================
    case USERS_READ = 'users:read';
    case USERS_WRITE = 'users:write';
    case USERS_DELETE = 'users:delete';

    // =========================================================================
    // Settings Scopes
    // =========================================================================
    case SETTINGS_READ = 'settings:read';
    case SETTINGS_WRITE = 'settings:write';

    // =========================================================================
    // Storage Scopes
    // =========================================================================
    case STORAGE_READ = 'storage:read';
    case STORAGE_WRITE = 'storage:write';

    // =========================================================================
    // Network Scopes
    // =========================================================================
    case NETWORK_OUTBOUND = 'network:outbound';
    case NETWORK_WEBHOOK = 'network:webhook';

    // =========================================================================
    // Tenant Scopes
    // =========================================================================
    case TENANT_READ = 'tenant:read';
    case TENANT_WRITE = 'tenant:write';
    case TENANT_CROSS = 'tenant:cross';

    // =========================================================================
    // System Scopes (Dangerous - require approval)
    // =========================================================================
    case SYSTEM_ADMIN = 'system:admin';
    case SYSTEM_PLUGINS = 'system:plugins';
    case SYSTEM_MIGRATIONS = 'system:migrations';

    /**
     * Get the display name for this scope.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::ENTITIES_READ => 'Read Entities',
            self::ENTITIES_WRITE => 'Write Entities',
            self::ENTITIES_DELETE => 'Delete Entities',
            self::ENTITIES_SCHEMA => 'Manage Entity Schema',
            self::HOOKS_SUBSCRIBE => 'Subscribe to Hooks',
            self::HOOKS_TRIGGER => 'Trigger Hooks',
            self::API_ACCESS => 'API Access',
            self::API_ADMIN => 'Admin API Access',
            self::USERS_READ => 'Read Users',
            self::USERS_WRITE => 'Modify Users',
            self::USERS_DELETE => 'Delete Users',
            self::SETTINGS_READ => 'Read Settings',
            self::SETTINGS_WRITE => 'Modify Settings',
            self::STORAGE_READ => 'Read Storage',
            self::STORAGE_WRITE => 'Write Storage',
            self::NETWORK_OUTBOUND => 'Outbound Network Access',
            self::NETWORK_WEBHOOK => 'Webhook Access',
            self::TENANT_READ => 'Read Tenant Data',
            self::TENANT_WRITE => 'Modify Tenant Data',
            self::TENANT_CROSS => 'Cross-Tenant Access',
            self::SYSTEM_ADMIN => 'System Administration',
            self::SYSTEM_PLUGINS => 'Manage Plugins',
            self::SYSTEM_MIGRATIONS => 'Run Migrations',
        };
    }

    /**
     * Get the description for this scope.
     */
    public function description(): string
    {
        return match ($this) {
            self::ENTITIES_READ => 'Allows the plugin to read entity records',
            self::ENTITIES_WRITE => 'Allows the plugin to create and update entity records',
            self::ENTITIES_DELETE => 'Allows the plugin to delete entity records',
            self::ENTITIES_SCHEMA => 'Allows the plugin to register and modify entity schemas',
            self::HOOKS_SUBSCRIBE => 'Allows the plugin to subscribe to system hooks and events',
            self::HOOKS_TRIGGER => 'Allows the plugin to trigger custom hooks',
            self::API_ACCESS => 'Allows the plugin to make API requests',
            self::API_ADMIN => 'Allows the plugin to access admin-only API endpoints',
            self::USERS_READ => 'Allows the plugin to read user information',
            self::USERS_WRITE => 'Allows the plugin to modify user accounts',
            self::USERS_DELETE => 'Allows the plugin to delete user accounts',
            self::SETTINGS_READ => 'Allows the plugin to read system and plugin settings',
            self::SETTINGS_WRITE => 'Allows the plugin to modify settings',
            self::STORAGE_READ => 'Allows the plugin to read from its storage area',
            self::STORAGE_WRITE => 'Allows the plugin to write to its storage area',
            self::NETWORK_OUTBOUND => 'Allows the plugin to make outbound HTTP requests',
            self::NETWORK_WEBHOOK => 'Allows the plugin to receive webhook callbacks',
            self::TENANT_READ => 'Allows the plugin to read current tenant data',
            self::TENANT_WRITE => 'Allows the plugin to modify tenant settings',
            self::TENANT_CROSS => 'Allows the plugin to access data across tenants',
            self::SYSTEM_ADMIN => 'Full system administration access',
            self::SYSTEM_PLUGINS => 'Allows the plugin to manage other plugins',
            self::SYSTEM_MIGRATIONS => 'Allows the plugin to run database migrations',
        };
    }

    /**
     * Get the category for this scope.
     */
    public function category(): string
    {
        return match ($this) {
            self::ENTITIES_READ, self::ENTITIES_WRITE, self::ENTITIES_DELETE, self::ENTITIES_SCHEMA => 'entities',
            self::HOOKS_SUBSCRIBE, self::HOOKS_TRIGGER => 'hooks',
            self::API_ACCESS, self::API_ADMIN => 'api',
            self::USERS_READ, self::USERS_WRITE, self::USERS_DELETE => 'users',
            self::SETTINGS_READ, self::SETTINGS_WRITE => 'settings',
            self::STORAGE_READ, self::STORAGE_WRITE => 'storage',
            self::NETWORK_OUTBOUND, self::NETWORK_WEBHOOK => 'network',
            self::TENANT_READ, self::TENANT_WRITE, self::TENANT_CROSS => 'tenant',
            self::SYSTEM_ADMIN, self::SYSTEM_PLUGINS, self::SYSTEM_MIGRATIONS => 'system',
        };
    }

    /**
     * Get the risk level (1-5, where 5 is most dangerous).
     */
    public function riskLevel(): int
    {
        return match ($this) {
            self::ENTITIES_READ, self::SETTINGS_READ, self::STORAGE_READ, self::TENANT_READ => 1,
            self::HOOKS_SUBSCRIBE, self::API_ACCESS, self::USERS_READ => 2,
            self::ENTITIES_WRITE, self::SETTINGS_WRITE, self::STORAGE_WRITE, self::NETWORK_OUTBOUND, self::NETWORK_WEBHOOK => 3,
            self::ENTITIES_DELETE, self::USERS_WRITE, self::HOOKS_TRIGGER, self::TENANT_WRITE, self::ENTITIES_SCHEMA => 4,
            self::USERS_DELETE, self::API_ADMIN, self::TENANT_CROSS, self::SYSTEM_ADMIN, self::SYSTEM_PLUGINS, self::SYSTEM_MIGRATIONS => 5,
        };
    }

    /**
     * Check if this scope is dangerous and requires manual approval.
     */
    public function isDangerous(): bool
    {
        return $this->riskLevel() >= 4;
    }

    /**
     * Check if this scope requires explicit admin approval.
     */
    public function requiresApproval(): bool
    {
        return $this->riskLevel() >= 5;
    }

    /**
     * Get scopes implied by this scope.
     *
     * @return array<PluginScope>
     */
    public function implies(): array
    {
        return match ($this) {
            self::ENTITIES_WRITE => [self::ENTITIES_READ],
            self::ENTITIES_DELETE => [self::ENTITIES_READ, self::ENTITIES_WRITE],
            self::ENTITIES_SCHEMA => [self::ENTITIES_READ],
            self::USERS_WRITE => [self::USERS_READ],
            self::USERS_DELETE => [self::USERS_READ, self::USERS_WRITE],
            self::SETTINGS_WRITE => [self::SETTINGS_READ],
            self::STORAGE_WRITE => [self::STORAGE_READ],
            self::TENANT_WRITE => [self::TENANT_READ],
            self::TENANT_CROSS => [self::TENANT_READ, self::TENANT_WRITE],
            self::API_ADMIN => [self::API_ACCESS],
            self::SYSTEM_ADMIN => [
                self::ENTITIES_READ, self::ENTITIES_WRITE, self::ENTITIES_DELETE, self::ENTITIES_SCHEMA,
                self::USERS_READ, self::USERS_WRITE, self::USERS_DELETE,
                self::SETTINGS_READ, self::SETTINGS_WRITE,
                self::API_ACCESS, self::API_ADMIN,
                self::SYSTEM_PLUGINS, self::SYSTEM_MIGRATIONS,
            ],
            default => [],
        };
    }

    /**
     * Parse a scope string which may include a resource specifier.
     *
     * Example: "entities:read:product" -> ['scope' => ENTITIES_READ, 'resource' => 'product']
     *
     * @return array{scope: ?PluginScope, resource: ?string}
     */
    public static function parse(string $scopeString): array
    {
        $parts = explode(':', $scopeString);

        if (count($parts) < 2) {
            return ['scope' => null, 'resource' => null];
        }

        $baseScope = $parts[0] . ':' . $parts[1];
        $resource = $parts[2] ?? null;

        $scope = self::tryFrom($baseScope);

        return ['scope' => $scope, 'resource' => $resource];
    }

    /**
     * Check if a given scope string matches this scope.
     *
     * Supports wildcard matching:
     * - "entities:read" matches "entities:read:product"
     * - "entities:*" matches "entities:read", "entities:write", etc.
     */
    public function matches(string $scopeString): bool
    {
        $parsed = self::parse($scopeString);

        if ($parsed['scope'] === null) {
            return false;
        }

        return $parsed['scope'] === $this;
    }

    /**
     * Get all scopes in a category.
     *
     * @return array<PluginScope>
     */
    public static function inCategory(string $category): array
    {
        return array_filter(
            self::cases(),
            fn(PluginScope $scope) => $scope->category() === $category
        );
    }

    /**
     * Get all dangerous scopes.
     *
     * @return array<PluginScope>
     */
    public static function dangerous(): array
    {
        return array_filter(
            self::cases(),
            fn(PluginScope $scope) => $scope->isDangerous()
        );
    }

    /**
     * Get all scopes grouped by category.
     *
     * @return array<string, array<PluginScope>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::cases() as $scope) {
            $category = $scope->category();
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $scope;
        }

        return $grouped;
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'name' => $this->displayName(),
            'description' => $this->description(),
            'category' => $this->category(),
            'risk_level' => $this->riskLevel(),
            'is_dangerous' => $this->isDangerous(),
            'requires_approval' => $this->requiresApproval(),
        ];
    }
}
