<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiEndpoint extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'method',
        'path',
        'handler_type',
        'handler_class',
        'handler_method',
        'handler_closure',
        'prefix',
        'version',
        'middleware',
        'where_constraints',
        'request_rules',
        'request_messages',
        'response_schema',
        'response_type',
        'rate_limit',
        'rate_limit_by',
        'auth_type',
        'auth_config',
        'permissions',
        'summary',
        'description',
        'tags',
        'parameters',
        'request_body',
        'responses',
        'plugin_slug',
        'is_system',
        'is_active',
        'is_public',
        'priority',
        'meta',
    ];

    protected $casts = [
        'middleware' => 'array',
        'where_constraints' => 'array',
        'request_rules' => 'array',
        'request_messages' => 'array',
        'response_schema' => 'array',
        'auth_config' => 'array',
        'permissions' => 'array',
        'tags' => 'array',
        'parameters' => 'array',
        'request_body' => 'array',
        'responses' => 'array',
        'meta' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * HTTP Methods
     */
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_OPTIONS = 'OPTIONS';

    /**
     * Handler Types
     */
    public const HANDLER_CONTROLLER = 'controller';
    public const HANDLER_CLOSURE = 'closure';
    public const HANDLER_ACTION = 'action';

    /**
     * Auth Types
     */
    public const AUTH_NONE = 'none';
    public const AUTH_SANCTUM = 'sanctum';
    public const AUTH_API_KEY = 'api_key';
    public const AUTH_BASIC = 'basic';

    /**
     * Response Types
     */
    public const RESPONSE_JSON = 'json';
    public const RESPONSE_XML = 'xml';
    public const RESPONSE_HTML = 'html';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function requestLogs(): HasMany
    {
        return $this->hasMany(ApiRequestLog::class, 'endpoint_id');
    }

    // =========================================================================
    // Route Building
    // =========================================================================

    /**
     * Get the full route path including prefix and version
     */
    public function getFullPath(): string
    {
        $parts = ['api'];
        
        if ($this->version) {
            $parts[] = $this->version;
        }
        
        if ($this->prefix) {
            $parts[] = trim($this->prefix, '/');
        }
        
        $parts[] = ltrim($this->path, '/');
        
        return '/' . implode('/', $parts);
    }

    /**
     * Get the route name
     */
    public function getRouteName(): string
    {
        $parts = ['api'];
        
        if ($this->version) {
            $parts[] = $this->version;
        }
        
        if ($this->plugin_slug) {
            $parts[] = $this->plugin_slug;
        }
        
        $parts[] = $this->slug;
        
        return implode('.', $parts);
    }

    /**
     * Get middleware array for route
     */
    public function getMiddleware(): array
    {
        $middleware = ['api'];
        
        // Add authentication middleware
        if ($this->auth_type !== self::AUTH_NONE) {
            switch ($this->auth_type) {
                case self::AUTH_SANCTUM:
                    $middleware[] = 'auth:sanctum';
                    break;
                case self::AUTH_API_KEY:
                    $middleware[] = 'api.key';
                    break;
                case self::AUTH_BASIC:
                    $middleware[] = 'auth.basic';
                    break;
            }
        }
        
        // Add rate limiting
        if ($this->rate_limit) {
            $middleware[] = "throttle:{$this->rate_limit},1";
        }
        
        // Add custom middleware
        if ($this->middleware) {
            $middleware = array_merge($middleware, $this->middleware);
        }
        
        return $middleware;
    }

    /**
     * Get the handler callback for routing
     */
    public function getHandler(): array|callable|string
    {
        switch ($this->handler_type) {
            case self::HANDLER_CONTROLLER:
                return [$this->handler_class, $this->handler_method];
                
            case self::HANDLER_ACTION:
                return $this->handler_class;
                
            case self::HANDLER_CLOSURE:
                // Return a wrapper that executes the stored logic
                return function (...$args) {
                    return $this->executeClosure($args);
                };
                
            default:
                throw new \RuntimeException("Unknown handler type: {$this->handler_type}");
        }
    }

    /**
     * Execute stored closure handler
     */
    protected function executeClosure(array $args)
    {
        // The closure is stored as serialized PHP or as a reference
        // For security, we use a registry-based approach
        $registry = app(\App\Services\Api\ApiRegistry::class);
        $handler = $registry->getClosureHandler($this->name);
        
        if ($handler && is_callable($handler)) {
            return $handler(...$args);
        }
        
        return response()->json(['error' => 'Handler not found'], 500);
    }

    /**
     * Check if handler is valid
     */
    public function hasValidHandler(): bool
    {
        switch ($this->handler_type) {
            case self::HANDLER_CONTROLLER:
                return $this->handler_class 
                    && class_exists($this->handler_class)
                    && method_exists($this->handler_class, $this->handler_method);
                    
            case self::HANDLER_ACTION:
                return $this->handler_class 
                    && class_exists($this->handler_class)
                    && method_exists($this->handler_class, '__invoke');
                    
            case self::HANDLER_CLOSURE:
                return true; // Validated at runtime
                
            default:
                return false;
        }
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Get validation rules for request
     */
    public function getValidationRules(): array
    {
        return $this->request_rules ?? [];
    }

    /**
     * Get custom validation messages
     */
    public function getValidationMessages(): array
    {
        return $this->request_messages ?? [];
    }

    // =========================================================================
    // Documentation
    // =========================================================================

    /**
     * Get OpenAPI operation object
     */
    public function toOpenApiOperation(): array
    {
        $operation = [
            'operationId' => $this->slug,
            'summary' => $this->summary ?? $this->name,
            'description' => $this->description ?? '',
            'tags' => $this->tags ?? [],
        ];

        // Parameters
        if ($this->parameters) {
            $operation['parameters'] = $this->parameters;
        }

        // Request body
        if ($this->request_body && in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $operation['requestBody'] = $this->request_body;
        }

        // Responses
        $operation['responses'] = $this->responses ?? [
            '200' => ['description' => 'Successful response'],
            '400' => ['description' => 'Bad request'],
            '401' => ['description' => 'Unauthorized'],
            '500' => ['description' => 'Server error'],
        ];

        // Security
        if ($this->auth_type !== self::AUTH_NONE) {
            $operation['security'] = [[$this->auth_type => []]];
        }

        return $operation;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeForVersion(Builder $query, string $version): Builder
    {
        return $query->where('version', $version);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeMethod(Builder $query, string $method): Builder
    {
        return $query->where('method', strtoupper($method));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('path');
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Get all HTTP methods
     */
    public static function getMethods(): array
    {
        return [
            self::METHOD_GET,
            self::METHOD_POST,
            self::METHOD_PUT,
            self::METHOD_PATCH,
            self::METHOD_DELETE,
            self::METHOD_OPTIONS,
        ];
    }

    /**
     * Get all auth types
     */
    public static function getAuthTypes(): array
    {
        return [
            self::AUTH_NONE => 'No Authentication',
            self::AUTH_SANCTUM => 'Laravel Sanctum',
            self::AUTH_API_KEY => 'API Key',
            self::AUTH_BASIC => 'HTTP Basic',
        ];
    }

    /**
     * Find by method and path
     */
    public static function findByRoute(string $method, string $path, string $version = 'v1'): ?self
    {
        return static::where('method', strtoupper($method))
            ->where('path', $path)
            ->where('version', $version)
            ->first();
    }
}
