<?php

declare(strict_types=1);

namespace VodoCommerce\Api;

use App\Services\Api\ApiRegistry;
use Illuminate\Support\Facades\Route;
use VodoCommerce\Auth\CommerceScopes;
use VodoCommerce\Events\CommerceEventRegistry;

/**
 * Commerce OpenAPI Generator
 *
 * Generates OpenAPI 3.0.3 specification for the commerce API.
 * Integrates with the platform's ApiRegistry for unified documentation.
 */
class CommerceOpenApiGenerator
{
    protected const VERSION = '1.0.0';
    protected const OPENAPI_VERSION = '3.0.3';

    public function __construct(
        protected ApiRegistry $apiRegistry
    ) {
    }

    /**
     * Generate the complete OpenAPI specification.
     *
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $endpoints = CommerceApiDocumentation::getEndpoints();

        return [
            'openapi' => self::OPENAPI_VERSION,
            'info' => $this->getInfo(),
            'servers' => $this->getServers(),
            'tags' => $this->getTags(),
            'paths' => $this->buildPaths($endpoints),
            'components' => $this->getComponents(),
            'security' => [
                ['bearerAuth' => []],
                ['oAuth2' => ['commerce.read']],
            ],
        ];
    }

    /**
     * Get API info.
     *
     * @return array<string, mixed>
     */
    protected function getInfo(): array
    {
        return [
            'title' => 'Vodo Commerce API',
            'version' => self::VERSION,
            'description' => $this->getDescription(),
            'termsOfService' => config('app.url') . '/terms',
            'contact' => [
                'name' => 'API Support',
                'email' => 'api@' . parse_url(config('app.url'), PHP_URL_HOST),
                'url' => config('app.url') . '/docs',
            ],
            'license' => [
                'name' => 'Proprietary',
                'url' => config('app.url') . '/license',
            ],
            'x-logo' => [
                'url' => config('app.url') . '/images/logo.png',
                'altText' => 'Vodo Commerce',
            ],
        ];
    }

    /**
     * Get API description.
     *
     * @return string
     */
    protected function getDescription(): string
    {
        return <<<'MARKDOWN'
# Vodo Commerce API

The Vodo Commerce API provides programmatic access to manage your online store.

## Authentication

All API requests require authentication using one of these methods:

### Bearer Token (Sanctum)
```
Authorization: Bearer {your-token}
```

### OAuth 2.0
For third-party integrations, use OAuth 2.0 with authorization code flow.

### API Key
For server-to-server communication:
```
X-API-Key: {your-api-key}
```

## Rate Limiting

API requests are rate-limited based on your plan:
- Standard: 60 requests/minute
- Pro: 300 requests/minute
- Enterprise: Custom limits

Rate limit headers are included in all responses:
- `X-RateLimit-Limit`: Maximum requests per window
- `X-RateLimit-Remaining`: Remaining requests
- `X-RateLimit-Reset`: Unix timestamp when limit resets

## Pagination

List endpoints support pagination:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)

## Filtering

Many endpoints support filtering via query parameters.
Use dot notation for nested fields: `filter[customer.email]=test@example.com`

## Webhooks

Subscribe to events to receive real-time notifications.
See the Webhooks section for available events.

## Errors

The API uses standard HTTP status codes:
- `400`: Bad Request - Invalid input
- `401`: Unauthorized - Missing or invalid authentication
- `403`: Forbidden - Insufficient permissions
- `404`: Not Found - Resource doesn't exist
- `422`: Unprocessable Entity - Validation failed
- `429`: Too Many Requests - Rate limit exceeded
- `500`: Internal Server Error

Error responses include:
```json
{
  "error": {
    "code": "validation_error",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."]
    }
  }
}
```
MARKDOWN;
    }

    /**
     * Get server definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getServers(): array
    {
        $servers = [
            [
                'url' => config('app.url') . '/api/v1/commerce',
                'description' => 'Production API',
            ],
        ];

        if (config('app.env') !== 'production') {
            $servers[] = [
                'url' => 'http://localhost/api/v1/commerce',
                'description' => 'Local development',
            ];
        }

        return $servers;
    }

    /**
     * Get tag definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getTags(): array
    {
        return [
            [
                'name' => 'Products',
                'description' => 'Manage store products and inventory',
            ],
            [
                'name' => 'Categories',
                'description' => 'Organize products into categories',
            ],
            [
                'name' => 'Orders',
                'description' => 'View and manage customer orders',
            ],
            [
                'name' => 'Cart',
                'description' => 'Shopping cart operations',
            ],
            [
                'name' => 'Checkout',
                'description' => 'Checkout flow and payment processing',
            ],
            [
                'name' => 'Customers',
                'description' => 'Customer management',
            ],
            [
                'name' => 'Discounts',
                'description' => 'Discount codes and promotions',
            ],
            [
                'name' => 'Payments',
                'description' => 'Payment processing and refunds',
            ],
            [
                'name' => 'Shipping',
                'description' => 'Shipping methods and rates',
            ],
            [
                'name' => 'Inventory',
                'description' => 'Stock management',
            ],
            [
                'name' => 'Webhooks',
                'description' => 'Webhook subscriptions and events',
            ],
            [
                'name' => 'Store',
                'description' => 'Store configuration and settings',
            ],
            [
                'name' => 'Analytics',
                'description' => 'Store statistics and reports',
            ],
        ];
    }

    /**
     * Build paths from endpoint definitions.
     *
     * @param array<int, array<string, mixed>> $endpoints
     * @return array<string, array<string, mixed>>
     */
    protected function buildPaths(array $endpoints): array
    {
        $paths = [];

        foreach ($endpoints as $endpoint) {
            $path = '/' . ltrim($endpoint['path'], '/');
            $method = strtolower($endpoint['method']);

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }

            $operation = [
                'operationId' => $endpoint['name'],
                'summary' => $endpoint['summary'] ?? '',
                'description' => $endpoint['description'] ?? '',
                'tags' => $endpoint['tags'] ?? [],
            ];

            // Parameters
            if (!empty($endpoint['parameters'])) {
                $operation['parameters'] = $this->normalizeParameters($endpoint['parameters']);
            }

            // Request body
            if (!empty($endpoint['request_body'])) {
                $operation['requestBody'] = $endpoint['request_body'];
            }

            // Responses
            $operation['responses'] = $endpoint['responses'] ?? [
                '200' => ['description' => 'Success'],
            ];

            // Security
            if (($endpoint['auth'] ?? 'none') !== 'none') {
                $operation['security'] = $this->buildSecurityRequirement($endpoint);
            }

            // Rate limiting
            if (!empty($endpoint['rate_limit'])) {
                $operation['x-ratelimit'] = [
                    'limit' => $endpoint['rate_limit'],
                    'window' => 60,
                ];
            }

            // Required scopes
            if (!empty($endpoint['permissions'])) {
                $operation['x-required-scopes'] = $endpoint['permissions'];
            }

            $paths[$path][$method] = $operation;
        }

        return $paths;
    }

    /**
     * Normalize parameters array.
     *
     * @param array<int|string, mixed> $parameters
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeParameters(array $parameters): array
    {
        $normalized = [];

        foreach ($parameters as $param) {
            if (is_array($param) && isset($param['name'])) {
                $normalized[] = $param;
            }
        }

        return $normalized;
    }

    /**
     * Build security requirement.
     *
     * @param array<string, mixed> $endpoint
     * @return array<int, array<string, mixed>>
     */
    protected function buildSecurityRequirement(array $endpoint): array
    {
        $auth = $endpoint['auth'] ?? 'sanctum';
        $scopes = $endpoint['permissions'] ?? [];

        return match ($auth) {
            'sanctum' => [['bearerAuth' => []]],
            'api_key' => [['apiKeyAuth' => []]],
            'oauth' => [['oAuth2' => $scopes]],
            default => [['bearerAuth' => []]],
        };
    }

    /**
     * Get component definitions.
     *
     * @return array<string, mixed>
     */
    protected function getComponents(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'description' => 'Enter your bearer token',
                ],
                'apiKeyAuth' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-Key',
                    'description' => 'API key for server-to-server authentication',
                ],
                'oAuth2' => [
                    'type' => 'oauth2',
                    'flows' => [
                        'authorizationCode' => [
                            'authorizationUrl' => config('app.url') . '/oauth/authorize',
                            'tokenUrl' => config('app.url') . '/oauth/token',
                            'refreshUrl' => config('app.url') . '/oauth/token',
                            'scopes' => $this->getOAuthScopes(),
                        ],
                    ],
                ],
            ],
            'schemas' => $this->getSchemas(),
            'responses' => $this->getCommonResponses(),
            'parameters' => $this->getCommonParameters(),
        ];
    }

    /**
     * Get OAuth scopes.
     *
     * @return array<string, string>
     */
    protected function getOAuthScopes(): array
    {
        $scopes = [];
        $scopeMetadata = CommerceScopes::all();

        foreach ($scopeMetadata as $scope => $meta) {
            $scopes[$scope] = $meta['description'] ?? $scope;
        }

        return $scopes;
    }

    /**
     * Get schema definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getSchemas(): array
    {
        return [
            'Product' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'format' => 'int64'],
                    'name' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'sku' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'price' => ['type' => 'number', 'format' => 'float'],
                    'compare_at_price' => ['type' => 'number', 'format' => 'float', 'nullable' => true],
                    'quantity' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'draft', 'archived']],
                    'images' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/ProductImage'],
                    ],
                    'categories' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Category'],
                    ],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'ProductImage' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'url' => ['type' => 'string', 'format' => 'uri'],
                    'alt' => ['type' => 'string'],
                    'position' => ['type' => 'integer'],
                ],
            ],
            'Category' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'parent_id' => ['type' => 'integer', 'nullable' => true],
                    'products_count' => ['type' => 'integer'],
                ],
            ],
            'Order' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'order_number' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled']],
                    'payment_status' => ['type' => 'string', 'enum' => ['pending', 'paid', 'failed', 'refunded']],
                    'subtotal' => ['type' => 'number'],
                    'tax_total' => ['type' => 'number'],
                    'shipping_total' => ['type' => 'number'],
                    'discount_total' => ['type' => 'number'],
                    'total' => ['type' => 'number'],
                    'currency' => ['type' => 'string'],
                    'items' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/OrderItem'],
                    ],
                    'customer' => ['$ref' => '#/components/schemas/Customer'],
                    'billing_address' => ['$ref' => '#/components/schemas/Address'],
                    'shipping_address' => ['$ref' => '#/components/schemas/Address'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'OrderItem' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'product_id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'sku' => ['type' => 'string'],
                    'quantity' => ['type' => 'integer'],
                    'unit_price' => ['type' => 'number'],
                    'total' => ['type' => 'number'],
                ],
            ],
            'Cart' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string'],
                    'items' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/CartItem'],
                    ],
                    'subtotal' => ['type' => 'number'],
                    'tax_total' => ['type' => 'number'],
                    'discount_total' => ['type' => 'number'],
                    'total' => ['type' => 'number'],
                    'item_count' => ['type' => 'integer'],
                ],
            ],
            'CartItem' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string'],
                    'product_id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'quantity' => ['type' => 'integer'],
                    'unit_price' => ['type' => 'number'],
                    'total' => ['type' => 'number'],
                ],
            ],
            'Customer' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'first_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'phone' => ['type' => 'string', 'nullable' => true],
                    'orders_count' => ['type' => 'integer'],
                    'total_spent' => ['type' => 'number'],
                ],
            ],
            'Address' => [
                'type' => 'object',
                'properties' => [
                    'first_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'company' => ['type' => 'string', 'nullable' => true],
                    'address1' => ['type' => 'string'],
                    'address2' => ['type' => 'string', 'nullable' => true],
                    'city' => ['type' => 'string'],
                    'state' => ['type' => 'string'],
                    'postal_code' => ['type' => 'string'],
                    'country' => ['type' => 'string'],
                    'phone' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'Discount' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'code' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['percentage', 'fixed', 'free_shipping']],
                    'value' => ['type' => 'number'],
                    'is_active' => ['type' => 'boolean'],
                ],
            ],
            'Webhook' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'url' => ['type' => 'string', 'format' => 'uri'],
                    'events' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'is_active' => ['type' => 'boolean'],
                ],
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'error' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'details' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                ],
            ],
            'PaginationLinks' => [
                'type' => 'object',
                'properties' => [
                    'first' => ['type' => 'string', 'format' => 'uri'],
                    'last' => ['type' => 'string', 'format' => 'uri'],
                    'prev' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                    'next' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                ],
            ],
        ];
    }

    /**
     * Get common response definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getCommonResponses(): array
    {
        return [
            'BadRequest' => [
                'description' => 'Bad Request - Invalid input',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'Unauthorized' => [
                'description' => 'Unauthorized - Missing or invalid authentication',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'Forbidden' => [
                'description' => 'Forbidden - Insufficient permissions',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'NotFound' => [
                'description' => 'Not Found - Resource does not exist',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'ValidationError' => [
                'description' => 'Validation Error - Invalid data provided',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'TooManyRequests' => [
                'description' => 'Too Many Requests - Rate limit exceeded',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
                'headers' => [
                    'X-RateLimit-Limit' => [
                        'schema' => ['type' => 'integer'],
                        'description' => 'Request limit per minute',
                    ],
                    'X-RateLimit-Remaining' => [
                        'schema' => ['type' => 'integer'],
                        'description' => 'Remaining requests',
                    ],
                    'X-RateLimit-Reset' => [
                        'schema' => ['type' => 'integer'],
                        'description' => 'Unix timestamp when limit resets',
                    ],
                ],
            ],
            'InternalError' => [
                'description' => 'Internal Server Error',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get common parameter definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getCommonParameters(): array
    {
        return [
            'pageParam' => [
                'name' => 'page',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
                'description' => 'Page number for pagination',
            ],
            'perPageParam' => [
                'name' => 'per_page',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
                'description' => 'Number of items per page',
            ],
            'sortParam' => [
                'name' => 'sort',
                'in' => 'query',
                'schema' => ['type' => 'string'],
                'description' => 'Field to sort by (prefix with - for descending)',
            ],
            'includeParam' => [
                'name' => 'include',
                'in' => 'query',
                'schema' => ['type' => 'string'],
                'description' => 'Comma-separated list of relations to include',
            ],
        ];
    }

    /**
     * Register endpoints with the platform's ApiRegistry.
     */
    public function registerEndpoints(): void
    {
        $endpoints = CommerceApiDocumentation::getEndpoints();

        $this->apiRegistry->registerMany(
            $endpoints,
            CommerceApiDocumentation::PLUGIN_SLUG
        );
    }

    /**
     * Export OpenAPI spec as JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->generate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export OpenAPI spec as YAML.
     *
     * @return string
     */
    public function toYaml(): string
    {
        return \Symfony\Component\Yaml\Yaml::dump(
            $this->generate(),
            10,
            2,
            \Symfony\Component\Yaml\Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
        );
    }
}
