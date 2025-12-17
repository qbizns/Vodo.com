<?php

/**
 * API Endpoints Global Helpers
 * 
 * These helper functions provide easy access to API endpoint functionality
 * without needing to inject the registry service.
 */

use App\Models\ApiEndpoint;
use App\Models\ApiKey;
use App\Services\Api\ApiRegistry;

if (!function_exists('api_registry')) {
    /**
     * Get the API registry instance
     */
    function api_registry(): ApiRegistry
    {
        return app(ApiRegistry::class);
    }
}

// =========================================================================
// Endpoint Registration
// =========================================================================

if (!function_exists('register_api_endpoint')) {
    /**
     * Register an API endpoint
     * 
     * @param array $config Endpoint configuration
     * @param string|null $pluginSlug Plugin slug for ownership
     * @return ApiEndpoint
     */
    function register_api_endpoint(array $config, ?string $pluginSlug = null): ApiEndpoint
    {
        return api_registry()->register($config, $pluginSlug);
    }
}

if (!function_exists('register_api_endpoints')) {
    /**
     * Register multiple API endpoints
     * 
     * @param array $endpoints Array of endpoint configurations
     * @param string|null $pluginSlug Plugin slug for ownership
     * @return array Array of registered endpoints
     */
    function register_api_endpoints(array $endpoints, ?string $pluginSlug = null): array
    {
        return api_registry()->registerMany($endpoints, $pluginSlug);
    }
}

if (!function_exists('unregister_api_endpoint')) {
    /**
     * Unregister an API endpoint
     * 
     * @param string $name Endpoint name
     * @param string|null $pluginSlug Plugin slug for ownership verification
     * @return bool
     */
    function unregister_api_endpoint(string $name, ?string $pluginSlug = null): bool
    {
        return api_registry()->unregister($name, $pluginSlug);
    }
}

// =========================================================================
// Quick Registration Helpers
// =========================================================================

if (!function_exists('api_get')) {
    /**
     * Register a GET endpoint
     * 
     * @param string $path Route path
     * @param mixed $handler Controller or closure
     * @param string $name Endpoint name
     * @param array $config Additional configuration
     * @param string|null $pluginSlug Plugin slug
     * @return ApiEndpoint
     */
    function api_get(string $path, $handler, string $name, array $config = [], ?string $pluginSlug = null): ApiEndpoint
    {
        return register_api_endpoint(array_merge($config, [
            'name' => $name,
            'method' => 'GET',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
        ]), $pluginSlug);
    }
}

if (!function_exists('api_post')) {
    /**
     * Register a POST endpoint
     */
    function api_post(string $path, $handler, string $name, array $config = [], ?string $pluginSlug = null): ApiEndpoint
    {
        return register_api_endpoint(array_merge($config, [
            'name' => $name,
            'method' => 'POST',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
        ]), $pluginSlug);
    }
}

if (!function_exists('api_put')) {
    /**
     * Register a PUT endpoint
     */
    function api_put(string $path, $handler, string $name, array $config = [], ?string $pluginSlug = null): ApiEndpoint
    {
        return register_api_endpoint(array_merge($config, [
            'name' => $name,
            'method' => 'PUT',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
        ]), $pluginSlug);
    }
}

if (!function_exists('api_patch')) {
    /**
     * Register a PATCH endpoint
     */
    function api_patch(string $path, $handler, string $name, array $config = [], ?string $pluginSlug = null): ApiEndpoint
    {
        return register_api_endpoint(array_merge($config, [
            'name' => $name,
            'method' => 'PATCH',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
        ]), $pluginSlug);
    }
}

if (!function_exists('api_delete')) {
    /**
     * Register a DELETE endpoint
     */
    function api_delete(string $path, $handler, string $name, array $config = [], ?string $pluginSlug = null): ApiEndpoint
    {
        return register_api_endpoint(array_merge($config, [
            'name' => $name,
            'method' => 'DELETE',
            'path' => $path,
            'handler' => is_callable($handler) ? $handler : null,
            'handler_class' => is_array($handler) ? $handler[0] : (is_string($handler) ? $handler : null),
            'handler_method' => is_array($handler) ? ($handler[1] ?? '__invoke') : null,
        ]), $pluginSlug);
    }
}

// =========================================================================
// Retrieval
// =========================================================================

if (!function_exists('get_api_endpoint')) {
    /**
     * Get an API endpoint by name
     * 
     * @param string $name Endpoint name
     * @return ApiEndpoint|null
     */
    function get_api_endpoint(string $name): ?ApiEndpoint
    {
        return api_registry()->get($name);
    }
}

if (!function_exists('get_all_api_endpoints')) {
    /**
     * Get all active API endpoints
     * 
     * @return \Illuminate\Support\Collection
     */
    function get_all_api_endpoints(): \Illuminate\Support\Collection
    {
        return api_registry()->all();
    }
}

if (!function_exists('get_plugin_api_endpoints')) {
    /**
     * Get all API endpoints for a plugin
     * 
     * @param string $pluginSlug Plugin slug
     * @return \Illuminate\Support\Collection
     */
    function get_plugin_api_endpoints(string $pluginSlug): \Illuminate\Support\Collection
    {
        return api_registry()->getByPlugin($pluginSlug);
    }
}

// =========================================================================
// API Keys
// =========================================================================

if (!function_exists('create_api_key')) {
    /**
     * Create a new API key
     * 
     * @param array $attributes Key attributes
     * @return array ['api_key' => ApiKey, 'key' => string, 'secret' => string]
     */
    function create_api_key(array $attributes): array
    {
        return api_registry()->createApiKey($attributes);
    }
}

if (!function_exists('validate_api_key')) {
    /**
     * Validate an API key
     * 
     * @param string $key The API key
     * @param string|null $secret Optional secret for signed requests
     * @return ApiKey|null
     */
    function validate_api_key(string $key, ?string $secret = null): ?ApiKey
    {
        return api_registry()->validateApiKey($key, $secret);
    }
}

if (!function_exists('revoke_api_key')) {
    /**
     * Revoke an API key
     * 
     * @param string $key The API key
     * @return bool
     */
    function revoke_api_key(string $key): bool
    {
        return api_registry()->revokeApiKey($key);
    }
}

// =========================================================================
// Documentation
// =========================================================================

if (!function_exists('generate_openapi_spec')) {
    /**
     * Generate OpenAPI specification
     * 
     * @param string $version API version
     * @return array OpenAPI spec
     */
    function generate_openapi_spec(string $version = 'v1'): array
    {
        return api_registry()->generateOpenApiSpec($version);
    }
}

// =========================================================================
// Response Helpers
// =========================================================================

if (!function_exists('api_response')) {
    /**
     * Create a standardized API response
     * 
     * @param mixed $data Response data
     * @param string|null $message Optional message
     * @param int $status HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    function api_response($data = null, ?string $message = null, int $status = 200): \Illuminate\Http\JsonResponse
    {
        $response = ['success' => $status >= 200 && $status < 300];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message) {
            $response['message'] = $message;
        }
        
        return response()->json($response, $status);
    }
}

if (!function_exists('api_error')) {
    /**
     * Create a standardized API error response
     * 
     * @param string $error Error message
     * @param int $status HTTP status code
     * @param array $errors Additional error details
     * @return \Illuminate\Http\JsonResponse
     */
    function api_error(string $error, int $status = 400, array $errors = []): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'error' => $error,
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        if (config('api-endpoints.response.include_debug') && config('app.debug')) {
            $response['debug'] = [
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ];
        }
        
        return response()->json($response, $status);
    }
}

if (!function_exists('api_paginated')) {
    /**
     * Create a standardized paginated API response
     * 
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return \Illuminate\Http\JsonResponse
     */
    function api_paginated($paginator): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}

// =========================================================================
// Request Helpers
// =========================================================================

if (!function_exists('api_key_from_request')) {
    /**
     * Get the API key from current request
     * 
     * @return ApiKey|null
     */
    function api_key_from_request(): ?ApiKey
    {
        return request()->attributes->get('api_key');
    }
}

if (!function_exists('api_endpoint_from_request')) {
    /**
     * Get the matched API endpoint from current request
     * 
     * @return ApiEndpoint|null
     */
    function api_endpoint_from_request(): ?ApiEndpoint
    {
        return request()->attributes->get('api_endpoint');
    }
}
