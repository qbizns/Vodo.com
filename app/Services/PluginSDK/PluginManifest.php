<?php

declare(strict_types=1);

namespace App\Services\PluginSDK;

use App\Enums\PluginScope;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;

/**
 * PluginManifest - Represents a plugin's manifest file (plugin.json).
 *
 * The manifest defines plugin metadata, dependencies, permissions,
 * and configuration - similar to Salla's partner app manifest.
 */
class PluginManifest implements Arrayable
{
    public const SCHEMA_VERSION = '2.0';

    protected array $data;
    protected array $errors = [];
    protected array $warnings = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    // =========================================================================
    // Factory Methods
    // =========================================================================

    /**
     * Load manifest from file.
     */
    public static function fromFile(string $path): static
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Manifest file not found: {$path}");
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in manifest: " . json_last_error_msg());
        }

        return new static($data);
    }

    /**
     * Load manifest from plugin directory.
     */
    public static function fromPluginPath(string $pluginPath): static
    {
        return static::fromFile($pluginPath . '/plugin.json');
    }

    /**
     * Create a new manifest with defaults.
     */
    public static function create(string $name, string $slug, array $options = []): static
    {
        return new static([
            'schema_version' => self::SCHEMA_VERSION,
            'identifier' => $slug,
            'name' => $name,
            'version' => $options['version'] ?? '1.0.0',
            'description' => $options['description'] ?? "The {$name} plugin",
            'author' => [
                'name' => $options['author'] ?? 'Developer',
                'email' => $options['email'] ?? null,
                'url' => $options['url'] ?? null,
            ],
            'license' => $options['license'] ?? 'MIT',
            'homepage' => $options['homepage'] ?? null,
            'repository' => $options['repository'] ?? null,
            'keywords' => $options['keywords'] ?? [],
            'category' => $options['category'] ?? 'utilities',
            'requires' => [
                'platform' => $options['platform_version'] ?? '>=1.0.0',
                'php' => $options['php_version'] ?? '>=8.2',
            ],
            'dependencies' => [],
            'permissions' => [
                'scopes' => $options['scopes'] ?? ['entities:read'],
                'dangerous_scopes' => [],
            ],
            'capabilities' => [
                'entities' => [],
                'hooks' => [],
                'api_endpoints' => [],
                'settings' => [],
                'webhooks' => [],
            ],
            'settings_schema' => [],
            'lifecycle' => [
                'install' => null,
                'uninstall' => null,
                'activate' => null,
                'deactivate' => null,
                'upgrade' => null,
            ],
            'assets' => [
                'icon' => 'icon.png',
                'screenshots' => [],
                'banner' => null,
            ],
            'marketplace' => [
                'listed' => false,
                'pricing' => 'free',
                'trial_days' => 0,
            ],
        ]);
    }

    // =========================================================================
    // Getters
    // =========================================================================

    public function getIdentifier(): string
    {
        return $this->data['identifier'] ?? '';
    }

    public function getName(): string
    {
        return $this->data['name'] ?? '';
    }

    public function getVersion(): string
    {
        return $this->data['version'] ?? '1.0.0';
    }

    public function getDescription(): string
    {
        return $this->data['description'] ?? '';
    }

    public function getAuthor(): array
    {
        return $this->data['author'] ?? [];
    }

    public function getAuthorName(): string
    {
        $author = $this->getAuthor();
        return is_array($author) ? ($author['name'] ?? '') : (string) $author;
    }

    public function getLicense(): string
    {
        return $this->data['license'] ?? 'proprietary';
    }

    public function getCategory(): string
    {
        return $this->data['category'] ?? 'utilities';
    }

    public function getDependencies(): array
    {
        return $this->data['dependencies'] ?? [];
    }

    public function getScopes(): array
    {
        return $this->data['permissions']['scopes'] ?? [];
    }

    public function getDangerousScopes(): array
    {
        return $this->data['permissions']['dangerous_scopes'] ?? [];
    }

    public function getAllScopes(): array
    {
        return array_merge($this->getScopes(), $this->getDangerousScopes());
    }

    public function getCapabilities(): array
    {
        return $this->data['capabilities'] ?? [];
    }

    public function getEntities(): array
    {
        return $this->data['capabilities']['entities'] ?? [];
    }

    public function getHooks(): array
    {
        return $this->data['capabilities']['hooks'] ?? [];
    }

    public function getApiEndpoints(): array
    {
        return $this->data['capabilities']['api_endpoints'] ?? [];
    }

    public function getSettingsSchema(): array
    {
        return $this->data['settings_schema'] ?? [];
    }

    public function getWebhooks(): array
    {
        return $this->data['capabilities']['webhooks'] ?? [];
    }

    public function getAssets(): array
    {
        return $this->data['assets'] ?? [];
    }

    public function getMarketplaceConfig(): array
    {
        return $this->data['marketplace'] ?? [];
    }

    public function isMarketplaceListed(): bool
    {
        return $this->data['marketplace']['listed'] ?? false;
    }

    public function getPricing(): string
    {
        return $this->data['marketplace']['pricing'] ?? 'free';
    }

    public function getRequirements(): array
    {
        return $this->data['requires'] ?? [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    // =========================================================================
    // Setters
    // =========================================================================

    public function set(string $key, mixed $value): static
    {
        Arr::set($this->data, $key, $value);
        return $this;
    }

    public function addScope(string $scope): static
    {
        $scopes = $this->getScopes();
        if (!in_array($scope, $scopes)) {
            $scopes[] = $scope;
            $this->set('permissions.scopes', $scopes);
        }
        return $this;
    }

    public function addEntity(array $entity): static
    {
        $entities = $this->getEntities();
        $entities[] = $entity;
        $this->set('capabilities.entities', $entities);
        return $this;
    }

    public function addHook(array $hook): static
    {
        $hooks = $this->getHooks();
        $hooks[] = $hook;
        $this->set('capabilities.hooks', $hooks);
        return $this;
    }

    public function addWebhook(array $webhook): static
    {
        $webhooks = $this->getWebhooks();
        $webhooks[] = $webhook;
        $this->set('capabilities.webhooks', $webhooks);
        return $this;
    }

    public function addApiEndpoint(array $endpoint): static
    {
        $endpoints = $this->getApiEndpoints();
        $endpoints[] = $endpoint;
        $this->set('capabilities.api_endpoints', $endpoints);
        return $this;
    }

    public function addDependency(string $plugin, string $version = '*'): static
    {
        $deps = $this->getDependencies();
        $deps[$plugin] = $version;
        $this->set('dependencies', $deps);
        return $this;
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Validate the manifest.
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->warnings = [];

        $this->validateRequired();
        $this->validateScopes();
        $this->validateVersion();
        $this->validateCapabilities();
        $this->validateMarketplace();

        return empty($this->errors);
    }

    protected function validateRequired(): void
    {
        $required = ['identifier', 'name', 'version'];

        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                $this->errors[] = "Missing required field: {$field}";
            }
        }

        // Validate identifier format
        $identifier = $this->getIdentifier();
        if ($identifier && !preg_match('/^[a-z][a-z0-9-]*$/', $identifier)) {
            $this->errors[] = "Invalid identifier format. Must be lowercase, start with a letter, and contain only letters, numbers, and hyphens.";
        }

        // Validate version format (semver)
        $version = $this->getVersion();
        if ($version && !preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?(\+[a-zA-Z0-9.]+)?$/', $version)) {
            $this->warnings[] = "Version '{$version}' does not follow semantic versioning (x.y.z).";
        }
    }

    protected function validateScopes(): void
    {
        $validScopes = array_map(fn($s) => $s->value, PluginScope::cases());
        $allScopes = $this->getAllScopes();

        foreach ($allScopes as $scope) {
            if (!in_array($scope, $validScopes)) {
                $this->warnings[] = "Unknown scope: {$scope}";
            }
        }

        // Check for dangerous scopes in wrong place
        $scopes = $this->getScopes();
        foreach ($scopes as $scope) {
            $enumScope = PluginScope::tryFrom($scope);
            if ($enumScope && $enumScope->isDangerous()) {
                $this->errors[] = "Dangerous scope '{$scope}' must be in 'dangerous_scopes', not 'scopes'.";
            }
        }

        // Warn about dangerous scopes
        $dangerous = $this->getDangerousScopes();
        foreach ($dangerous as $scope) {
            $enumScope = PluginScope::tryFrom($scope);
            if ($enumScope) {
                $this->warnings[] = "Plugin requests dangerous scope: {$scope} - {$enumScope->description()}. Requires manual approval.";
            }
        }

        // Check scope dependencies
        foreach ($allScopes as $scope) {
            $enumScope = PluginScope::tryFrom($scope);
            if ($enumScope) {
                foreach ($enumScope->implies() as $impliedScope) {
                    if (!in_array($impliedScope->value, $allScopes)) {
                        $this->warnings[] = "Scope '{$scope}' implies '{$impliedScope->value}' which is not declared.";
                    }
                }
            }
        }
    }

    protected function validateVersion(): void
    {
        $requires = $this->getRequirements();

        if (isset($requires['php'])) {
            $phpVersion = $requires['php'];
            // Check if current PHP satisfies requirement
            $constraint = ltrim($phpVersion, '><=');
            if (version_compare(PHP_VERSION, $constraint, '<')) {
                $this->warnings[] = "Plugin requires PHP {$phpVersion}, current version is " . PHP_VERSION;
            }
        }
    }

    protected function validateCapabilities(): void
    {
        // Validate entities have required fields
        foreach ($this->getEntities() as $i => $entity) {
            if (empty($entity['name'])) {
                $this->errors[] = "Entity at index {$i} is missing 'name'.";
            }
        }

        // Validate hooks have required fields
        foreach ($this->getHooks() as $i => $hook) {
            if (empty($hook['name'])) {
                $this->errors[] = "Hook at index {$i} is missing 'name'.";
            }
            if (empty($hook['type']) || !in_array($hook['type'], ['action', 'filter'])) {
                $this->warnings[] = "Hook at index {$i} should specify type (action or filter).";
            }
        }

        // Validate API endpoints
        foreach ($this->getApiEndpoints() as $i => $endpoint) {
            if (empty($endpoint['path'])) {
                $this->errors[] = "API endpoint at index {$i} is missing 'path'.";
            }
            if (empty($endpoint['method'])) {
                $this->warnings[] = "API endpoint at index {$i} should specify 'method'.";
            }
        }
    }

    protected function validateMarketplace(): void
    {
        if ($this->isMarketplaceListed()) {
            // Marketplace plugins need more metadata
            if (empty($this->getDescription())) {
                $this->errors[] = "Marketplace plugins must have a description.";
            }
            if (strlen($this->getDescription()) < 50) {
                $this->warnings[] = "Marketplace description should be at least 50 characters.";
            }
            if (empty($this->getAssets()['icon'])) {
                $this->warnings[] = "Marketplace plugins should have an icon.";
            }
            if (empty($this->data['keywords']) || count($this->data['keywords']) < 3) {
                $this->warnings[] = "Marketplace plugins should have at least 3 keywords.";
            }
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function isValid(): bool
    {
        if (empty($this->errors) && empty($this->warnings)) {
            $this->validate();
        }
        return empty($this->errors);
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->data, $options);
    }

    /**
     * Save manifest to file.
     */
    public function save(string $path): bool
    {
        $json = $this->toJson();
        return file_put_contents($path, $json) !== false;
    }
}
