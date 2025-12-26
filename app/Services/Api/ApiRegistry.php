<?php

namespace App\Services\Api;

use App\Models\ApiEndpoint;
use App\Models\ApiKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Router;

/**
 * API Registry Service
 * 
 * Central service for managing plugin-defined API endpoints.
 * Handles registration, routing, and documentation.
 */
class ApiRegistry
{
    /**
     * Runtime closure handlers (keyed by endpoint name)
     */
    protected array $closureHandlers = [];

    /**
     * Runtime endpoints (not persisted)
     */
    protected array $runtimeEndpoints = [];

    /**
     * Flag indicating if routes have been registered
     */
    protected bool $routesRegistered = false;

    // =========================================================================
    // Endpoint Registration
    // =========================================================================

    /**
     * Register a new API endpoint
     */
    public function register(array $config, ?string $pluginSlug = null): ApiEndpoint
    {
        $this->validateConfig($config);

        // Check for existing endpoint
        $existing = ApiEndpoint::findByRoute(
            $config['method'],
            $config['path'],
            $config['version'] ?? 'v1'
        );

        if ($existing) {
            if ($existing->plugin_slug !== $pluginSlug) {
                throw new \RuntimeException(
                    "Endpoint {$config['method']} {$config['path']} is owned by another plugin"
                );
            }
            return $this->update($existing->id, $config, $pluginSlug);
        }

        // Generate slug from name
        $slug = $config['slug'] ?? \Illuminate\Support\Str::slug($config['name'], '_');

        $endpoint = ApiEndpoint::create([
            'name' => $config['name'],
            'slug' => $slug,
            'method' => strtoupper($config['method']),
            'path' => $this->normalizePath($config['path']),
            'handler_type' => $config['handler_type'] ?? ApiEndpoint::HANDLER_CONTROLLER,
            'handler_class' => $config['handler_class'] ?? null,
            'handler_method' => $config['handler_method'] ?? null,
            'prefix' => $config['prefix'] ?? null,
            'version' => $config['version'] ?? 'v1',
            'middleware' => $config['middleware'] ?? [],
            'where_constraints' => $config['where'] ?? null,
            'request_rules' => $config['rules'] ?? null,
            'request_messages' => $config['messages'] ?? null,
            'response_schema' => $config['response_schema'] ?? null,
            'response_type' => $config['response_type'] ?? 'json',
            'rate_limit' => $config['rate_limit'] ?? null,
            'rate_limit_by' => $config['rate_limit_by'] ?? 'ip',
            'auth_type' => $config['auth'] ?? ApiEndpoint::AUTH_NONE,
            'auth_config' => $config['auth_config'] ?? null,
            'permissions' => $config['permissions'] ?? null,
            'summary' => $config['summary'] ?? null,
            'description' => $config['description'] ?? null,
            'tags' => $config['tags'] ?? null,
            'parameters' => $config['parameters'] ?? null,
            'request_body' => $config['request_body'] ?? null,
            'responses' => $config['responses'] ?? null,
            'plugin_slug' => $pluginSlug,
            'is_system' => $config['system'] ?? false,
            'is_active' => $config['active'] ?? true,
            'is_public' => $config['public'] ?? false,
            'priority' => $config['priority'] ?? 100,
            'meta' => $config['meta'] ?? null,
        ]);

        // Store closure handler if provided
        if (isset($config['handler']) && is_callable($config['handler'])) {
            $this->closureHandlers[$endpoint->name] = $config['handler'];
        }

        if (function_exists('do_action')) {
            do_action('api_endpoint_registered', $endpoint);
        }

        return $endpoint;
    }

    /**
     * Register multiple endpoints
     */
    public function registerMany(array $endpoints, ?string $pluginSlug = null): array
    {
        $registered = [];
        
        foreach ($endpoints as $config) {
            $registered[] = $this->register($config, $pluginSlug);
        }

        return $registered;
    }

    /**
     * Update an existing endpoint
     */
    public function update(int $id, array $config, ?string $pluginSlug = null): ApiEndpoint
    {
        $endpoint = ApiEndpoint::findOrFail($id);

        // Check ownership
        if ($endpoint->plugin_slug !== $pluginSlug && !$endpoint->is_system) {
            throw new \RuntimeException("Cannot update endpoint - owned by another plugin");
        }

        $updateData = [];
        
        $allowedFields = [
            'name', 'handler_class', 'handler_method', 'middleware',
            'request_rules', 'request_messages', 'response_schema',
            'rate_limit', 'rate_limit_by', 'auth', 'auth_config', 'permissions',
            'summary', 'description', 'tags', 'parameters', 'request_body',
            'responses', 'is_active', 'is_public', 'priority', 'meta',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $config)) {
                $dbField = $field === 'auth' ? 'auth_type' : $field;
                $dbField = $field === 'rules' ? 'request_rules' : $dbField;
                $dbField = $field === 'messages' ? 'request_messages' : $dbField;
                $dbField = $field === 'where' ? 'where_constraints' : $dbField;
                $dbField = $field === 'system' ? 'is_system' : $dbField;
                $dbField = $field === 'active' ? 'is_active' : $dbField;
                $dbField = $field === 'public' ? 'is_public' : $dbField;
                
                $updateData[$dbField] = $config[$field];
            }
        }

        $endpoint->update($updateData);

        // Update closure handler if provided
        if (isset($config['handler']) && is_callable($config['handler'])) {
            $this->closureHandlers[$endpoint->name] = $config['handler'];
        }

        if (function_exists('do_action')) {
            do_action('api_endpoint_updated', $endpoint);
        }

        return $endpoint->fresh();
    }

    /**
     * Unregister an endpoint
     */
    public function unregister(string $name, ?string $pluginSlug = null): bool
    {
        $endpoint = ApiEndpoint::where('name', $name)->first();
        
        if (!$endpoint) {
            return false;
        }

        // Check ownership
        if ($endpoint->plugin_slug !== $pluginSlug) {
            throw new \RuntimeException("Cannot unregister endpoint - owned by another plugin");
        }

        // Cannot delete system endpoints
        if ($endpoint->is_system) {
            throw new \RuntimeException("Cannot unregister system endpoint");
        }

        unset($this->closureHandlers[$name]);
        $endpoint->delete();

        if (function_exists('do_action')) {
            do_action('api_endpoint_unregistered', $name, $pluginSlug);
        }

        return true;
    }

    /**
     * Unregister all endpoints for a plugin
     */
    public function unregisterPlugin(string $pluginSlug): int
    {
        $endpoints = ApiEndpoint::forPlugin($pluginSlug)->get();
        $count = 0;

        foreach ($endpoints as $endpoint) {
            if (!$endpoint->is_system) {
                unset($this->closureHandlers[$endpoint->name]);
                $endpoint->delete();
                $count++;
            }
        }

        return $count;
    }

    // =========================================================================
    // Runtime Endpoints
    // =========================================================================

    /**
     * Register a runtime endpoint (not persisted)
     */
    public function registerRuntime(array $config, ?string $pluginSlug = null): array
    {
        $this->validateConfig($config);

        $key = $config['method'] . ':' . $config['path'];
        
        $this->runtimeEndpoints[$key] = array_merge($config, [
            'plugin_slug' => $pluginSlug,
        ]);

        if (isset($config['handler']) && is_callable($config['handler'])) {
            $this->closureHandlers[$config['name'] ?? $key] = $config['handler'];
        }

        return $this->runtimeEndpoints[$key];
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    /**
     * Get an endpoint by name
     */
    public function get(string $name): ?ApiEndpoint
    {
        return ApiEndpoint::where('name', $name)->first();
    }

    /**
     * Cache key for active endpoints
     */
    protected const CACHE_KEY = 'api_endpoints:active';
    protected const CACHE_TTL = 3600;

    /**
     * Get all active endpoints
     */
    public function all(): Collection
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                return ApiEndpoint::active()->ordered()->get();
            });
        } catch (\Throwable $e) {
            // Fallback to direct query if cache fails
            return ApiEndpoint::active()->ordered()->get();
        }
    }

    /**
     * Clear the endpoints cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get endpoints by plugin
     */
    public function getByPlugin(string $pluginSlug): Collection
    {
        return ApiEndpoint::forPlugin($pluginSlug)->get();
    }

    /**
     * Get endpoints by version
     */
    public function getByVersion(string $version): Collection
    {
        return ApiEndpoint::active()->forVersion($version)->ordered()->get();
    }

    /**
     * Get closure handler
     */
    public function getClosureHandler(string $name): ?callable
    {
        return $this->closureHandlers[$name] ?? null;
    }

    // =========================================================================
    // Route Registration
    // =========================================================================

    /**
     * Register all routes for active endpoints
     */
    public function registerRoutes(): void
    {
        if ($this->routesRegistered) {
            return;
        }

        $endpoints = $this->all();

        foreach ($endpoints as $endpoint) {
            $this->registerEndpointRoute($endpoint);
        }

        // Register runtime endpoints
        foreach ($this->runtimeEndpoints as $config) {
            $this->registerRuntimeRoute($config);
        }

        $this->routesRegistered = true;
    }

    /**
     * Register route for a single endpoint
     */
    protected function registerEndpointRoute(ApiEndpoint $endpoint): void
    {
        if (!$endpoint->is_active || !$endpoint->hasValidHandler()) {
            return;
        }

        $method = strtolower($endpoint->method);
        $path = $endpoint->getFullPath();
        $handler = $endpoint->getHandler();
        $middleware = $endpoint->getMiddleware();

        $route = Route::$method($path, $handler)
            ->middleware($middleware)
            ->name($endpoint->getRouteName());

        // Add where constraints
        if ($endpoint->where_constraints) {
            foreach ($endpoint->where_constraints as $param => $pattern) {
                $route->where($param, $pattern);
            }
        }
    }

    /**
     * Register route for runtime endpoint
     */
    protected function registerRuntimeRoute(array $config): void
    {
        $method = strtolower($config['method']);
        $path = $this->buildPath($config);
        
        $handler = $config['handler'] ?? $config['handler_class'] 
            ? [$config['handler_class'], $config['handler_method'] ?? '__invoke']
            : null;

        if (!$handler) {
            return;
        }

        $middleware = $this->buildMiddleware($config);

        $route = Route::$method($path, $handler)->middleware($middleware);

        if (isset($config['name'])) {
            $route->name($config['name']);
        }

        if (isset($config['where'])) {
            foreach ($config['where'] as $param => $pattern) {
                $route->where($param, $pattern);
            }
        }
    }

    /**
     * Build full path from config
     */
    protected function buildPath(array $config): string
    {
        $parts = ['api'];
        
        if ($version = $config['version'] ?? 'v1') {
            $parts[] = $version;
        }
        
        if ($prefix = $config['prefix'] ?? null) {
            $parts[] = trim($prefix, '/');
        }
        
        $parts[] = ltrim($config['path'], '/');
        
        return '/' . implode('/', $parts);
    }

    /**
     * Build middleware array from config
     */
    protected function buildMiddleware(array $config): array
    {
        $middleware = ['api'];
        
        $auth = $config['auth'] ?? 'none';
        if ($auth !== 'none') {
            switch ($auth) {
                case 'sanctum':
                    $middleware[] = 'auth:sanctum';
                    break;
                case 'api_key':
                    $middleware[] = 'api.key';
                    break;
                case 'basic':
                    $middleware[] = 'auth.basic';
                    break;
            }
        }
        
        if ($rateLimit = $config['rate_limit'] ?? null) {
            $middleware[] = "throttle:{$rateLimit},1";
        }
        
        if (isset($config['middleware'])) {
            $middleware = array_merge($middleware, $config['middleware']);
        }
        
        return $middleware;
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Validate endpoint configuration
     */
    protected function validateConfig(array $config): void
    {
        $required = ['name', 'method', 'path'];
        
        foreach ($required as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate method
        $method = strtoupper($config['method']);
        if (!in_array($method, ApiEndpoint::getMethods())) {
            throw new \InvalidArgumentException("Invalid HTTP method: {$method}");
        }

        // Validate handler
        if (!isset($config['handler']) && !isset($config['handler_class'])) {
            throw new \InvalidArgumentException("Endpoint must have handler or handler_class");
        }

        // Validate path format
        $path = $config['path'];
        if (!preg_match('#^/?[a-zA-Z0-9\-_/{}\[\]]+$#', $path)) {
            throw new \InvalidArgumentException("Invalid path format: {$path}");
        }
    }

    /**
     * Normalize path
     */
    protected function normalizePath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    // =========================================================================
    // API Keys
    // =========================================================================

    /**
     * Create an API key
     */
    public function createApiKey(array $attributes): array
    {
        return ApiKey::createWithSecret($attributes);
    }

    /**
     * Revoke an API key
     */
    public function revokeApiKey(string $key): bool
    {
        $apiKey = ApiKey::findByKey($key);
        
        if (!$apiKey) {
            return false;
        }

        $apiKey->update(['is_active' => false]);
        return true;
    }

    /**
     * Validate an API key
     */
    public function validateApiKey(string $key, ?string $secret = null): ?ApiKey
    {
        $apiKey = ApiKey::findActiveByKey($key);
        
        if (!$apiKey) {
            return null;
        }

        if ($secret && !$apiKey->verifySecret($secret)) {
            return null;
        }

        return $apiKey;
    }

    // =========================================================================
    // Documentation
    // =========================================================================

    /**
     * Generate OpenAPI specification
     */
    public function generateOpenApiSpec(string $version = 'v1'): array
    {
        $endpoints = $this->getByVersion($version)->where('is_public', true);

        $paths = [];
        $tags = [];

        foreach ($endpoints as $endpoint) {
            $path = $endpoint->getFullPath();
            $method = strtolower($endpoint->method);

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }

            $paths[$path][$method] = $endpoint->toOpenApiOperation();

            // Collect tags
            if ($endpoint->tags) {
                foreach ($endpoint->tags as $tag) {
                    if (!in_array($tag, array_column($tags, 'name'))) {
                        $tags[] = ['name' => $tag];
                    }
                }
            }
        }

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name') . ' API',
                'version' => $version,
                'description' => config('api-endpoints.description', 'API Documentation'),
            ],
            'servers' => [
                ['url' => config('app.url') . '/api/' . $version],
            ],
            'tags' => $tags,
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                    'api_key' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                    ],
                ],
            ],
        ];
    }
}
