<?php

namespace App\Traits;

use App\Models\ApiEndpoint;
use App\Services\Api\ApiRegistry;
use Illuminate\Support\Collection;

/**
 * Trait for plugins to easily register and manage API endpoints
 * 
 * Usage:
 * 
 * class MyPlugin extends BasePlugin
 * {
 *     use HasApiEndpoints;
 * 
 *     public function activate(): void
 *     {
 *         // Simple endpoint
 *         $this->registerEndpoint([
 *             'name' => 'list_products',
 *             'method' => 'GET',
 *             'path' => '/products',
 *             'handler_class' => ProductController::class,
 *             'handler_method' => 'index',
 *         ]);
 * 
 *         // With inline handler
 *         $this->get('/products/{id}', function ($id) {
 *             return Product::find($id);
 *         }, 'get_product');
 * 
 *         // Resource endpoints
 *         $this->apiResource('orders', OrderController::class);
 *     }
 * }
 */
trait HasApiEndpoints
{
    /**
     * Default API configuration
     */
    protected array $apiConfig = [
        'version' => 'v1',
        'prefix' => null,
        'auth' => 'sanctum',
        'rate_limit' => 60,
    ];

    /**
     * Get the API registry
     */
    protected function apiRegistry(): ApiRegistry
    {
        return app(ApiRegistry::class);
    }

    /**
     * Get the plugin slug for ownership tracking
     */
    protected function getApiPluginSlug(): string
    {
        return $this->slug ?? $this->pluginSlug ?? strtolower(class_basename($this));
    }

    /**
     * Set default API configuration
     */
    public function setApiDefaults(array $config): self
    {
        $this->apiConfig = array_merge($this->apiConfig, $config);
        return $this;
    }

    // =========================================================================
    // Endpoint Registration
    // =========================================================================

    /**
     * Register an API endpoint
     */
    public function registerEndpoint(array $config): ApiEndpoint
    {
        $config = array_merge($this->apiConfig, $config);
        
        return $this->apiRegistry()->register($config, $this->getApiPluginSlug());
    }

    /**
     * Register multiple endpoints
     */
    public function registerEndpoints(array $endpoints): array
    {
        $registered = [];
        
        foreach ($endpoints as $config) {
            $registered[] = $this->registerEndpoint($config);
        }

        return $registered;
    }

    /**
     * Unregister an endpoint
     */
    public function unregisterEndpoint(string $name): bool
    {
        return $this->apiRegistry()->unregister($name, $this->getApiPluginSlug());
    }

    // =========================================================================
    // HTTP Method Shortcuts
    // =========================================================================

    /**
     * Register a GET endpoint
     */
    public function get(string $path, $handler, string $name, array $config = []): ApiEndpoint
    {
        return $this->registerEndpoint(array_merge($config, [
            'name' => $name,
            'method' => 'GET',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
            'handler_type' => is_callable($handler) && !is_array($handler) && !is_string($handler) 
                ? 'closure' 
                : 'controller',
        ]));
    }

    /**
     * Register a POST endpoint
     */
    public function post(string $path, $handler, string $name, array $config = []): ApiEndpoint
    {
        return $this->registerEndpoint(array_merge($config, [
            'name' => $name,
            'method' => 'POST',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
            'handler_type' => is_callable($handler) && !is_array($handler) && !is_string($handler) 
                ? 'closure' 
                : 'controller',
        ]));
    }

    /**
     * Register a PUT endpoint
     */
    public function put(string $path, $handler, string $name, array $config = []): ApiEndpoint
    {
        return $this->registerEndpoint(array_merge($config, [
            'name' => $name,
            'method' => 'PUT',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
            'handler_type' => is_callable($handler) && !is_array($handler) && !is_string($handler) 
                ? 'closure' 
                : 'controller',
        ]));
    }

    /**
     * Register a PATCH endpoint
     */
    public function patch(string $path, $handler, string $name, array $config = []): ApiEndpoint
    {
        return $this->registerEndpoint(array_merge($config, [
            'name' => $name,
            'method' => 'PATCH',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
            'handler_type' => is_callable($handler) && !is_array($handler) && !is_string($handler) 
                ? 'closure' 
                : 'controller',
        ]));
    }

    /**
     * Register a DELETE endpoint
     */
    public function delete(string $path, $handler, string $name, array $config = []): ApiEndpoint
    {
        return $this->registerEndpoint(array_merge($config, [
            'name' => $name,
            'method' => 'DELETE',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
            'handler_type' => is_callable($handler) && !is_array($handler) && !is_string($handler) 
                ? 'closure' 
                : 'controller',
        ]));
    }

    // =========================================================================
    // Resource Registration
    // =========================================================================

    /**
     * Register a full RESTful resource
     */
    public function apiResource(string $name, string $controller, array $config = []): array
    {
        $prefix = $config['prefix'] ?? $name;
        $singular = \Illuminate\Support\Str::singular($name);
        
        $methods = $config['only'] ?? ['index', 'show', 'store', 'update', 'destroy'];
        $except = $config['except'] ?? [];
        
        $methods = array_diff($methods, $except);

        $endpoints = [];

        if (in_array('index', $methods)) {
            $endpoints[] = $this->registerEndpoint(array_merge($this->apiConfig, $config, [
                'name' => "{$name}.index",
                'method' => 'GET',
                'path' => "/{$prefix}",
                'handler_class' => $controller,
                'handler_method' => 'index',
                'summary' => "List all {$name}",
            ]));
        }

        if (in_array('store', $methods)) {
            $endpoints[] = $this->registerEndpoint(array_merge($this->apiConfig, $config, [
                'name' => "{$name}.store",
                'method' => 'POST',
                'path' => "/{$prefix}",
                'handler_class' => $controller,
                'handler_method' => 'store',
                'summary' => "Create a new {$singular}",
            ]));
        }

        if (in_array('show', $methods)) {
            $endpoints[] = $this->registerEndpoint(array_merge($this->apiConfig, $config, [
                'name' => "{$name}.show",
                'method' => 'GET',
                'path' => "/{$prefix}/{id}",
                'handler_class' => $controller,
                'handler_method' => 'show',
                'where' => ['id' => '[0-9]+'],
                'summary' => "Get a specific {$singular}",
            ]));
        }

        if (in_array('update', $methods)) {
            $endpoints[] = $this->registerEndpoint(array_merge($this->apiConfig, $config, [
                'name' => "{$name}.update",
                'method' => 'PUT',
                'path' => "/{$prefix}/{id}",
                'handler_class' => $controller,
                'handler_method' => 'update',
                'where' => ['id' => '[0-9]+'],
                'summary' => "Update a {$singular}",
            ]));
        }

        if (in_array('destroy', $methods)) {
            $endpoints[] = $this->registerEndpoint(array_merge($this->apiConfig, $config, [
                'name' => "{$name}.destroy",
                'method' => 'DELETE',
                'path' => "/{$prefix}/{id}",
                'handler_class' => $controller,
                'handler_method' => 'destroy',
                'where' => ['id' => '[0-9]+'],
                'summary' => "Delete a {$singular}",
            ]));
        }

        return $endpoints;
    }

    /**
     * Register API-only resource (no store/update/destroy)
     */
    public function apiReadOnlyResource(string $name, string $controller, array $config = []): array
    {
        return $this->apiResource($name, $controller, array_merge($config, [
            'only' => ['index', 'show'],
        ]));
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    /**
     * Get all endpoints registered by this plugin
     */
    public function getPluginEndpoints(): Collection
    {
        return $this->apiRegistry()->getByPlugin($this->getApiPluginSlug());
    }

    /**
     * Get a specific endpoint
     */
    public function getEndpoint(string $name): ?ApiEndpoint
    {
        return $this->apiRegistry()->get($name);
    }

    // =========================================================================
    // API Keys
    // =========================================================================

    /**
     * Create an API key for this plugin
     */
    public function createApiKey(string $name, array $config = []): array
    {
        return $this->apiRegistry()->createApiKey(array_merge($config, [
            'name' => $name,
            'plugin_slug' => $this->getApiPluginSlug(),
        ]));
    }

    // =========================================================================
    // Cleanup
    // =========================================================================

    /**
     * Remove all endpoints registered by this plugin
     */
    public function cleanupApiEndpoints(): int
    {
        return $this->apiRegistry()->unregisterPlugin($this->getApiPluginSlug());
    }
}
