<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Services\Api\ApiRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiEndpointsController extends Controller
{
    protected ApiRegistry $registry;

    public function __construct(ApiRegistry $registry)
    {
        $this->registry = $registry;
    }

    // =========================================================================
    // Endpoint Management
    // =========================================================================

    /**
     * List all endpoints
     * GET /api/v1/endpoints
     */
    public function index(Request $request): JsonResponse
    {
        $query = ApiEndpoint::query();

        // Filters
        if ($request->has('version')) {
            $query->forVersion($request->version);
        }

        if ($request->has('method')) {
            $query->method($request->method);
        }

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }

        if ($request->boolean('public_only')) {
            $query->public();
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        // Sorting
        $query->ordered();

        // Pagination
        $perPage = min($request->integer('per_page', 50), 100);
        $endpoints = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $endpoints->items(),
            'meta' => [
                'total' => $endpoints->total(),
                'per_page' => $endpoints->perPage(),
                'current_page' => $endpoints->currentPage(),
                'last_page' => $endpoints->lastPage(),
            ],
        ]);
    }

    /**
     * Get single endpoint
     * GET /api/v1/endpoints/{id}
     */
    public function show(int $id): JsonResponse
    {
        $endpoint = ApiEndpoint::find($id);

        if (!$endpoint) {
            return response()->json([
                'success' => false,
                'error' => 'Endpoint not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $endpoint,
        ]);
    }

    /**
     * Create endpoint
     * POST /api/v1/endpoints
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'method' => ['required', 'string', 'in:GET,POST,PUT,PATCH,DELETE,OPTIONS'],
            'path' => ['required', 'string', 'max:255'],
            'handler_class' => ['required_without:handler', 'string', 'max:255'],
            'handler_method' => ['nullable', 'string', 'max:100'],
            'prefix' => ['nullable', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:10'],
            'middleware' => ['nullable', 'array'],
            'rules' => ['nullable', 'array'],
            'rate_limit' => ['nullable', 'integer', 'min:1'],
            'auth' => ['nullable', 'string', 'in:none,sanctum,api_key,basic'],
            'permissions' => ['nullable', 'array'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'plugin_slug' => ['required', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'public' => ['nullable', 'boolean'],
        ]);

        try {
            $endpoint = $this->registry->register($validated, $validated['plugin_slug']);

            return response()->json([
                'success' => true,
                'data' => $endpoint,
                'message' => 'Endpoint created successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update endpoint
     * PUT /api/v1/endpoints/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $endpoint = ApiEndpoint::find($id);

        if (!$endpoint) {
            return response()->json([
                'success' => false,
                'error' => 'Endpoint not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'handler_class' => ['nullable', 'string', 'max:255'],
            'handler_method' => ['nullable', 'string', 'max:100'],
            'middleware' => ['nullable', 'array'],
            'rules' => ['nullable', 'array'],
            'rate_limit' => ['nullable', 'integer', 'min:1'],
            'auth' => ['nullable', 'string', 'in:none,sanctum,api_key,basic'],
            'permissions' => ['nullable', 'array'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $endpoint = $this->registry->update($id, $validated, $validated['plugin_slug']);

            return response()->json([
                'success' => true,
                'data' => $endpoint,
                'message' => 'Endpoint updated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete endpoint
     * DELETE /api/v1/endpoints/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $endpoint = ApiEndpoint::find($id);

        if (!$endpoint) {
            return response()->json([
                'success' => false,
                'error' => 'Endpoint not found',
            ], 404);
        }

        $pluginSlug = $request->input('plugin_slug');

        if (!$pluginSlug) {
            return response()->json([
                'success' => false,
                'error' => 'plugin_slug is required',
            ], 400);
        }

        try {
            $this->registry->unregister($endpoint->name, $pluginSlug);

            return response()->json([
                'success' => true,
                'message' => 'Endpoint deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // =========================================================================
    // API Keys
    // =========================================================================

    /**
     * List API keys
     * GET /api/v1/api-keys
     */
    public function apiKeys(Request $request): JsonResponse
    {
        $query = ApiKey::query();

        if ($request->has('user_id')) {
            $query->forUser($request->integer('user_id'));
        }

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $keys = $query->orderBy('created_at', 'desc')->paginate(50);

        // Hide sensitive fields
        $data = $keys->through(function ($key) {
            return [
                'id' => $key->id,
                'name' => $key->name,
                'key_preview' => substr($key->key, 0, 10) . '...',
                'user_id' => $key->user_id,
                'plugin_slug' => $key->plugin_slug,
                'scopes' => $key->scopes,
                'is_active' => $key->is_active,
                'expires_at' => $key->expires_at,
                'last_used_at' => $key->last_used_at,
                'request_count' => $key->request_count,
                'created_at' => $key->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'meta' => [
                'total' => $keys->total(),
                'per_page' => $keys->perPage(),
                'current_page' => $keys->currentPage(),
            ],
        ]);
    }

    /**
     * Create API key
     * POST /api/v1/api-keys
     */
    public function createApiKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'plugin_slug' => ['nullable', 'string', 'max:100'],
            'scopes' => ['nullable', 'array'],
            'allowed_endpoints' => ['nullable', 'array'],
            'allowed_ips' => ['nullable', 'array'],
            'rate_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $result = $this->registry->createApiKey($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $result['api_key']->id,
                'name' => $result['api_key']->name,
                'key' => $result['key'],
                'secret' => $result['secret'],
            ],
            'message' => 'API key created. Please save the key and secret - they will not be shown again.',
        ], 201);
    }

    /**
     * Revoke API key
     * DELETE /api/v1/api-keys/{id}
     */
    public function revokeApiKey(int $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key not found',
            ], 404);
        }

        $apiKey->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'API key revoked successfully',
        ]);
    }

    /**
     * Get API key usage stats
     * GET /api/v1/api-keys/{id}/stats
     */
    public function apiKeyStats(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key not found',
            ], 404);
        }

        $days = $request->integer('days', 30);
        $stats = $apiKey->getUsageStats($days);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // =========================================================================
    // Analytics
    // =========================================================================

    /**
     * Get request logs
     * GET /api/v1/analytics/logs
     */
    public function logs(Request $request): JsonResponse
    {
        $query = ApiRequestLog::query()->with(['endpoint', 'apiKey']);

        if ($request->has('endpoint_id')) {
            $query->forEndpoint($request->integer('endpoint_id'));
        }

        if ($request->has('api_key_id')) {
            $query->where('api_key_id', $request->integer('api_key_id'));
        }

        if ($request->has('status')) {
            $status = $request->status;
            match ($status) {
                'success' => $query->successful(),
                'error' => $query->errors(),
                'client_error' => $query->clientErrors(),
                'server_error' => $query->serverErrors(),
                default => null,
            };
        }

        if ($request->has('since')) {
            $query->since($request->since);
        }

        if ($request->boolean('slow_only')) {
            $threshold = $request->integer('threshold', 1000);
            $query->slow($threshold);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(100);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
            ],
        ]);
    }

    /**
     * Get aggregate statistics
     * GET /api/v1/analytics/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $days = $request->integer('days', 7);
        $endpointId = $request->integer('endpoint_id');

        $stats = ApiRequestLog::getStats($days, $endpointId ?: null);
        $hourly = ApiRequestLog::getHourlyStats(24, $endpointId ?: null);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $stats,
                'hourly' => $hourly,
            ],
        ]);
    }

    // =========================================================================
    // Documentation
    // =========================================================================

    /**
     * Get OpenAPI specification
     * GET /api/v1/docs/openapi
     */
    public function openApiSpec(Request $request): JsonResponse
    {
        $version = $request->input('version', 'v1');
        $spec = $this->registry->generateOpenApiSpec($version);

        return response()->json($spec);
    }

    /**
     * Get available methods
     * GET /api/v1/docs/methods
     */
    public function methods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ApiEndpoint::getMethods(),
        ]);
    }

    /**
     * Get available auth types
     * GET /api/v1/docs/auth-types
     */
    public function authTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ApiEndpoint::getAuthTypes(),
        ]);
    }
}
