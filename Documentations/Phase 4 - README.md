# Phase 4: REST API Extension System

A dynamic API endpoint registration system for Laravel that enables plugins to define custom REST endpoints with routing, authentication, rate limiting, and documentation.

## Overview

This system provides:

- **Dynamic Endpoint Registration** - Plugins can register API endpoints at runtime
- **Multiple Auth Methods** - Sanctum, API Key, Basic Auth, or none
- **API Key Management** - Create, revoke, and manage API keys with scopes
- **Rate Limiting** - Per-endpoint or per-key rate limits
- **Request Logging** - Full request/response logging with analytics
- **OpenAPI Generation** - Auto-generate OpenAPI/Swagger documentation
- **Plugin Ownership** - Track which plugin owns each endpoint

## Installation

### 1. Extract Files

```bash
unzip phase-4.zip
# Files go to: app/, config/, database/migrations/, routes/, helpers/
```

### 2. Register Service Provider

Add to `config/app.php` or `bootstrap/providers.php`:

```php
App\Providers\ApiEndpointServiceProvider::class,
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=api-endpoints-config
```

## Quick Start

### Register an Endpoint

```php
use App\Traits\HasApiEndpoints;

class MyPlugin extends BasePlugin
{
    use HasApiEndpoints;

    public function activate(): void
    {
        // Method 1: Full configuration
        $this->registerEndpoint([
            'name' => 'list_products',
            'method' => 'GET',
            'path' => '/products',
            'handler_class' => ProductController::class,
            'handler_method' => 'index',
            'auth' => 'sanctum',
            'rate_limit' => 60,
            'summary' => 'List all products',
            'tags' => ['products'],
        ]);

        // Method 2: Shorthand methods
        $this->get('/products/{id}', [ProductController::class, 'show'], 'get_product');
        $this->post('/products', [ProductController::class, 'store'], 'create_product');
        $this->put('/products/{id}', [ProductController::class, 'update'], 'update_product');
        $this->delete('/products/{id}', [ProductController::class, 'destroy'], 'delete_product');

        // Method 3: RESTful resource (registers all CRUD endpoints)
        $this->apiResource('orders', OrderController::class);
    }

    public function deactivate(): void
    {
        $this->cleanupApiEndpoints();
    }
}
```

### Using Global Helpers

```php
// Register endpoints
api_get('/users', [UserController::class, 'index'], 'list_users', ['auth' => 'api_key']);
api_post('/users', [UserController::class, 'store'], 'create_user');

// Create an API key
$result = create_api_key([
    'name' => 'Mobile App',
    'user_id' => $user->id,
    'scopes' => ['read', 'write'],
    'rate_limit' => 100,
]);
// Returns: ['api_key' => ApiKey, 'key' => 'pk_xxx', 'secret' => 'sk_xxx']

// Standardized responses
return api_response($data, 'Success');
return api_error('Not found', 404);
return api_paginated($paginator);
```

## Authentication Methods

### No Authentication
```php
$this->registerEndpoint([
    'name' => 'public_endpoint',
    'auth' => 'none',
    // ...
]);
```

### Laravel Sanctum
```php
$this->registerEndpoint([
    'name' => 'protected_endpoint',
    'auth' => 'sanctum',
    // ...
]);
```

### API Key
```php
$this->registerEndpoint([
    'name' => 'api_endpoint',
    'auth' => 'api_key',
    'permissions' => ['read', 'write'], // Required scopes
    // ...
]);
```

API keys can be passed via:
- Header: `X-API-Key: pk_xxx` or `Authorization: Bearer pk_xxx`
- Query: `?api_key=pk_xxx`

### Signed Requests (API Key + Secret)

For enhanced security, enable signed requests:

```php
// config/api-endpoints.php
'require_signed_requests' => true,
```

Clients must include:
- `X-API-Key`: The API key
- `X-API-Timestamp`: Unix timestamp
- `X-API-Signature`: HMAC-SHA256 of `{method}\n{path}\n{timestamp}\n{body}`

## Rate Limiting

### Per-Endpoint
```php
$this->registerEndpoint([
    'name' => 'limited_endpoint',
    'rate_limit' => 30, // 30 requests per minute
    'rate_limit_by' => 'ip', // or 'user', 'api_key'
    // ...
]);
```

### Per-API Key
```php
$result = create_api_key([
    'name' => 'Premium Client',
    'rate_limit' => 1000, // Override endpoint limits
]);
```

## Request Validation

```php
$this->registerEndpoint([
    'name' => 'create_product',
    'method' => 'POST',
    'path' => '/products',
    'handler_class' => ProductController::class,
    'handler_method' => 'store',
    'rules' => [
        'name' => ['required', 'string', 'max:255'],
        'price' => ['required', 'numeric', 'min:0'],
        'category_id' => ['required', 'exists:categories,id'],
    ],
    'messages' => [
        'name.required' => 'Product name is required',
    ],
]);
```

## OpenAPI Documentation

### Auto-Generated Spec

```php
// Get OpenAPI spec
$spec = generate_openapi_spec('v1');

// Or via API
// GET /api/v1/docs/openapi
```

### Document Your Endpoints

```php
$this->registerEndpoint([
    'name' => 'list_products',
    'method' => 'GET',
    'path' => '/products',
    'handler_class' => ProductController::class,
    'handler_method' => 'index',
    
    // Documentation
    'summary' => 'List all products',
    'description' => 'Returns a paginated list of products with optional filtering.',
    'tags' => ['Products'],
    'is_public' => true, // Include in public docs
    
    'parameters' => [
        [
            'name' => 'category',
            'in' => 'query',
            'description' => 'Filter by category ID',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'search',
            'in' => 'query',
            'description' => 'Search term',
            'schema' => ['type' => 'string'],
        ],
    ],
    
    'responses' => [
        '200' => [
            'description' => 'Successful response',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'array'],
                            'meta' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
```

## API Key Management

### Create Key
```php
$result = create_api_key([
    'name' => 'Mobile App',
    'user_id' => $user->id,
    'scopes' => ['products.read', 'orders.write'],
    'allowed_ips' => ['192.168.1.0/24'],
    'expires_at' => now()->addYear(),
]);

// Save these - secret is only shown once!
$key = $result['key'];       // pk_abc123...
$secret = $result['secret']; // sk_xyz789...
```

### Revoke Key
```php
revoke_api_key('pk_abc123...');
```

### Check Key in Controller
```php
public function index(Request $request)
{
    $apiKey = api_key_from_request();
    
    if ($apiKey) {
        // API key authenticated request
        $scopes = $apiKey->scopes;
    }
}
```

## Request Logging & Analytics

### View Logs
```
GET /api/v1/analytics/logs
GET /api/v1/analytics/logs?endpoint_id=5
GET /api/v1/analytics/logs?status=error
GET /api/v1/analytics/logs?slow_only=1&threshold=500
```

### View Statistics
```
GET /api/v1/analytics/stats
GET /api/v1/analytics/stats?days=30&endpoint_id=5
```

Response:
```json
{
    "success": true,
    "data": {
        "summary": {
            "total_requests": 15420,
            "successful": 15200,
            "errors": 220,
            "error_rate": 1.43,
            "avg_response_time_ms": 45.2
        },
        "hourly": [
            {"hour": "2024-01-15 10:00:00", "count": 523, "avg_time": 42.1, "errors": 3}
        ]
    }
}
```

### Configuration

```php
// config/api-endpoints.php
'logging' => [
    'enabled' => true,
    'async' => true,  // Queue logging for better performance
    'sample_rate' => 100, // Log 100% of requests (reduce for high traffic)
    'retention_days' => 30,
],
```

## Management API

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/endpoints | List all endpoints |
| POST | /api/v1/endpoints | Create endpoint |
| GET | /api/v1/endpoints/{id} | Get endpoint |
| PUT | /api/v1/endpoints/{id} | Update endpoint |
| DELETE | /api/v1/endpoints/{id} | Delete endpoint |
| GET | /api/v1/api-keys | List API keys |
| POST | /api/v1/api-keys | Create API key |
| DELETE | /api/v1/api-keys/{id} | Revoke API key |
| GET | /api/v1/api-keys/{id}/stats | Key statistics |
| GET | /api/v1/analytics/logs | Request logs |
| GET | /api/v1/analytics/stats | Statistics |
| GET | /api/v1/docs/openapi | OpenAPI spec |

## RESTful Resource Registration

```php
// Registers: index, store, show, update, destroy
$this->apiResource('products', ProductController::class);

// Customize which methods
$this->apiResource('products', ProductController::class, [
    'only' => ['index', 'show'],
]);

// Or exclude some
$this->apiResource('products', ProductController::class, [
    'except' => ['destroy'],
]);

// Read-only resource (index + show only)
$this->apiReadOnlyResource('categories', CategoryController::class);
```

## Response Helpers

```php
// Success response
return api_response(['id' => 1, 'name' => 'Product'], 'Created successfully', 201);
// {"success": true, "data": {"id": 1, "name": "Product"}, "message": "Created successfully"}

// Error response
return api_error('Validation failed', 422, ['name' => ['Name is required']]);
// {"success": false, "error": "Validation failed", "errors": {"name": ["Name is required"]}}

// Paginated response
return api_paginated(Product::paginate(20));
// {"success": true, "data": [...], "meta": {"total": 100, ...}, "links": {...}}
```

## File Structure

```
phase4/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   └── ApiEndpointsController.php
│   │   └── Middleware/
│   │       ├── ApiKeyAuth.php
│   │       └── ApiRequestLogger.php
│   ├── Models/
│   │   ├── ApiEndpoint.php
│   │   ├── ApiKey.php
│   │   └── ApiRequestLog.php
│   ├── Providers/
│   │   └── ApiEndpointServiceProvider.php
│   ├── Services/Api/
│   │   └── ApiRegistry.php
│   └── Traits/
│       └── HasApiEndpoints.php
├── config/
│   └── api-endpoints.php
├── database/migrations/
│   └── 2025_01_01_000030_create_api_endpoints_tables.php
├── helpers/
│   └── api-helpers.php
├── routes/
│   └── api-endpoints.php
└── README.md
```

## Events/Hooks

The system fires these hooks:

- `api_endpoint_registered` - After endpoint registered
- `api_endpoint_updated` - After endpoint updated
- `api_endpoint_unregistered` - After endpoint removed
- `api_endpoints_ready` - After system initialization
- `api_key_created` - After API key created
- `api_key_revoked` - After API key revoked
- `api_request_logged` - After request logged

## Security Best Practices

1. **Always use HTTPS** in production
2. **Use API keys** for third-party integrations
3. **Set rate limits** to prevent abuse
4. **Scope permissions** narrowly
5. **Rotate keys** periodically
6. **Monitor logs** for anomalies
7. **Whitelist IPs** when possible

## Next Phases

- **Phase 5:** Shortcode System - Content embedding
- **Phase 6:** Enhanced Menu System - Hierarchical admin menus
- **Phase 7:** Permissions System - Granular capabilities
- **Phase 8:** Event/Scheduler - Cron-like scheduling
