<?php

declare(strict_types=1);

namespace App\Services\PluginSDK\Templates;

use Illuminate\Support\Str;

/**
 * API Plugin Template
 *
 * Plugin focused on API endpoints, resources, and external integrations.
 * Suitable for building API connectors, webhooks, and data sync plugins.
 */
class ApiTemplate extends PluginTemplate
{
    protected string $resourceName;
    protected string $resourceSlug;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $this->resourceName = $options['resource_name'] ?? Str::singular($this->name);
        $this->resourceSlug = Str::kebab($this->resourceName);

        // Add API endpoints to manifest
        $this->manifest->addApiEndpoint([
            'path' => "/api/{$this->slug}/{$this->resourceSlug}s",
            'method' => 'GET',
            'description' => "List all {$this->resourceSlug}s",
            'auth' => 'api_key',
        ]);
        $this->manifest->addApiEndpoint([
            'path' => "/api/{$this->slug}/{$this->resourceSlug}s/{id}",
            'method' => 'GET',
            'description' => "Get a single {$this->resourceSlug}",
            'auth' => 'api_key',
        ]);
        $this->manifest->addApiEndpoint([
            'path' => "/api/{$this->slug}/{$this->resourceSlug}s",
            'method' => 'POST',
            'description' => "Create a new {$this->resourceSlug}",
            'auth' => 'api_key',
        ]);

        // Add webhook to manifest
        $this->manifest->addWebhook([
            'event' => "{$this->slug}.{$this->resourceSlug}.created",
            'description' => "Triggered when a {$this->resourceSlug} is created",
        ]);
    }

    public function getType(): string
    {
        return 'api';
    }

    public function getDescription(): string
    {
        return 'API-focused plugin with REST endpoints, resources, and webhook support - ideal for integrations.';
    }

    public function getDefaultScopes(): array
    {
        return [
            'api:read',
            'api:write',
            'hooks:subscribe',
            'network:outbound',
        ];
    }

    public function getDirectoryStructure(): array
    {
        return [
            'config',
            'routes',
            'src/Http/Controllers/Api',
            'src/Http/Middleware',
            'src/Http/Resources',
            'src/Http/Requests',
            'src/Services',
            'src/Events',
            'src/Webhooks',
            'tests/Feature/Api',
            'tests/Unit',
        ];
    }

    public function getFiles(): array
    {
        return [
            "src/{$this->name}Plugin.php" => $this->generatePluginClass(),
            "src/{$this->name}ServiceProvider.php" => $this->generateServiceProvider(),
            "src/Http/Controllers/Api/{$this->resourceName}Controller.php" => $this->generateApiController(),
            "src/Http/Resources/{$this->resourceName}Resource.php" => $this->generateResource(),
            "src/Http/Resources/{$this->resourceName}Collection.php" => $this->generateCollection(),
            "src/Http/Requests/Store{$this->resourceName}Request.php" => $this->generateStoreRequest(),
            "src/Http/Middleware/Authenticate{$this->name}.php" => $this->generateMiddleware(),
            "src/Services/{$this->name}ApiClient.php" => $this->generateApiClient(),
            "src/Events/{$this->resourceName}Created.php" => $this->generateEvent(),
            "src/Webhooks/{$this->resourceName}WebhookHandler.php" => $this->generateWebhookHandler(),
            "config/{$this->slug}.php" => $this->generateConfig(),
            'routes/api.php' => $this->generateApiRoutes(),
            "tests/Feature/Api/{$this->resourceName}ApiTest.php" => $this->generateApiTest(),
            'composer.json' => $this->generateComposerJson(),
            'plugin.json' => $this->manifest->toJson(),
            'README.md' => $this->generateReadme(),
            '.gitignore' => $this->generateGitignore(),
        ];
    }

    protected function generatePluginClass(): string
    {
        $description = $this->options['description'] ?? "The {$this->name} API plugin.";
        $version = $this->options['version'] ?? '1.0.0';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name};

use App\Services\Plugins\BasePlugin;
use App\Services\Hooks\HookManager;
use Plugins\\{$this->name}\\Events\\{$this->resourceName}Created;
use Plugins\\{$this->name}\\Webhooks\\{$this->resourceName}WebhookHandler;

/**
 * {$this->name} Plugin
 *
 * {$description}
 */
class {$this->name}Plugin extends BasePlugin
{
    protected string \$identifier = '{$this->slug}';
    protected string \$name = '{$this->name}';
    protected string \$version = '{$version}';
    protected string \$description = '{$description}';

    public function boot(): void
    {
        \$this->registerHooks();
        \$this->registerWebhooks();
        \$this->registerServices();
    }

    public function install(): void
    {
        // Setup API keys if needed
    }

    public function uninstall(): void
    {
        // Cleanup
    }

    protected function registerHooks(): void
    {
        \$hooks = app(HookManager::class);

        // Listen for resource creation
        \$hooks->addAction('{$this->slug}.{$this->resourceSlug}.created', function (\$data) {
            event(new {$this->resourceName}Created(\$data));
        });
    }

    protected function registerWebhooks(): void
    {
        // Register webhook handlers
        \$this->registerWebhookHandler(
            '{$this->slug}.{$this->resourceSlug}.created',
            {$this->resourceName}WebhookHandler::class
        );
    }

    protected function registerServices(): void
    {
        // Register API client as singleton
        app()->singleton(Services\\{$this->name}ApiClient::class, function () {
            return new Services\\{$this->name}ApiClient(
                config('{$this->slug}.api_url'),
                config('{$this->slug}.api_key')
            );
        });
    }
}
PHP;
    }

    protected function generateApiController(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Controllers\\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Plugins\\{$this->name}\\Http\Resources\\{$this->resourceName}Resource;
use Plugins\\{$this->name}\\Http\\Resources\\{$this->resourceName}Collection;
use Plugins\\{$this->name}\\Http\\Requests\\Store{$this->resourceName}Request;
use Plugins\\{$this->name}\\Services\\{$this->name}ApiClient;

/**
 * {$this->resourceName} API Controller
 *
 * @group {$this->name}
 */
class {$this->resourceName}Controller extends Controller
{
    public function __construct(
        protected {$this->name}ApiClient \$apiClient
    ) {}

    /**
     * List all {$this->resourceSlug}s
     *
     * @queryParam page int Page number. Example: 1
     * @queryParam per_page int Items per page. Example: 15
     *
     * @response {
     *   "data": [],
     *   "meta": {"current_page": 1, "total": 0}
     * }
     */
    public function index(Request \$request): {$this->resourceName}Collection
    {
        \$items = \$this->apiClient->list([
            'page' => \$request->input('page', 1),
            'per_page' => \$request->input('per_page', 15),
        ]);

        return new {$this->resourceName}Collection(\$items);
    }

    /**
     * Get a single {$this->resourceSlug}
     *
     * @urlParam id int required The {$this->resourceSlug} ID. Example: 1
     *
     * @response {
     *   "data": {"id": 1, "name": "Example"}
     * }
     */
    public function show(int \$id): {$this->resourceName}Resource
    {
        \$item = \$this->apiClient->find(\$id);

        if (!\$item) {
            abort(404, '{$this->resourceName} not found');
        }

        return new {$this->resourceName}Resource(\$item);
    }

    /**
     * Create a new {$this->resourceSlug}
     *
     * @bodyParam name string required The name. Example: My {$this->resourceName}
     *
     * @response 201 {
     *   "data": {"id": 1, "name": "My {$this->resourceName}"}
     * }
     */
    public function store(Store{$this->resourceName}Request \$request): JsonResponse
    {
        \$item = \$this->apiClient->create(\$request->validated());

        // Dispatch webhook
        app('hooks')->doAction('{$this->slug}.{$this->resourceSlug}.created', \$item);

        return (new {$this->resourceName}Resource(\$item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a {$this->resourceSlug}
     *
     * @urlParam id int required The {$this->resourceSlug} ID. Example: 1
     */
    public function update(Store{$this->resourceName}Request \$request, int \$id): {$this->resourceName}Resource
    {
        \$item = \$this->apiClient->update(\$id, \$request->validated());

        return new {$this->resourceName}Resource(\$item);
    }

    /**
     * Delete a {$this->resourceSlug}
     *
     * @urlParam id int required The {$this->resourceSlug} ID. Example: 1
     *
     * @response 204 {}
     */
    public function destroy(int \$id): JsonResponse
    {
        \$this->apiClient->delete(\$id);

        return response()->json(null, 204);
    }
}
PHP;
    }

    protected function generateResource(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * {$this->resourceName} Resource
 *
 * @property int \$id
 * @property string \$name
 * @property string|null \$description
 * @property bool \$is_active
 * @property \Carbon\Carbon \$created_at
 * @property \Carbon\Carbon \$updated_at
 */
class {$this->resourceName}Resource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
            'name' => \$this->name,
            'description' => \$this->description,
            'is_active' => \$this->is_active,
            'created_at' => \$this->created_at?->toIso8601String(),
            'updated_at' => \$this->updated_at?->toIso8601String(),

            // Include related resources
            // 'related' => RelatedResource::collection(\$this->whenLoaded('related')),
        ];
    }
}
PHP;
    }

    protected function generateCollection(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * {$this->resourceName} Collection
 */
class {$this->resourceName}Collection extends ResourceCollection
{
    public \$collects = {$this->resourceName}Resource::class;

    public function toArray(Request \$request): array
    {
        return [
            'data' => \$this->collection,
            'meta' => [
                'total' => \$this->total(),
                'count' => \$this->count(),
                'per_page' => \$this->perPage(),
                'current_page' => \$this->currentPage(),
                'last_page' => \$this->lastPage(),
            ],
        ];
    }
}
PHP;
    }

    protected function generateStoreRequest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store {$this->resourceName} Request
 */
class Store{$this->resourceName}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
PHP;
    }

    protected function generateMiddleware(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate {$this->name} API Requests
 */
class Authenticate{$this->name}
{
    public function handle(Request \$request, Closure \$next): Response
    {
        \$apiKey = \$request->header('X-{$this->name}-Key')
            ?? \$request->header('Authorization');

        if (!\$apiKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API key is required',
            ], 401);
        }

        // Validate API key
        if (!str_starts_with(\$apiKey, 'Bearer ')) {
            \$apiKey = "Bearer {\$apiKey}";
        }

        \$token = substr(\$apiKey, 7);

        // Verify token against stored keys
        if (!config('{$this->slug}.api_key') || \$token !== config('{$this->slug}.api_key')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ], 401);
        }

        return \$next(\$request);
    }
}
PHP;
    }

    protected function generateApiClient(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * {$this->name} API Client
 *
 * HTTP client for external API communication with retry logic and caching.
 */
class {$this->name}ApiClient
{
    protected string \$baseUrl;
    protected string \$apiKey;
    protected int \$timeout = 30;
    protected int \$retries = 3;

    public function __construct(?string \$baseUrl = null, ?string \$apiKey = null)
    {
        \$this->baseUrl = \$baseUrl ?? config('{$this->slug}.api_url', '');
        \$this->apiKey = \$apiKey ?? config('{$this->slug}.api_key', '');
    }

    /**
     * List resources with pagination.
     */
    public function list(array \$params = []): array
    {
        \$response = \$this->get('/{$this->resourceSlug}s', \$params);

        return \$response['data'] ?? [];
    }

    /**
     * Find a single resource.
     */
    public function find(int \$id): ?array
    {
        \$cacheKey = "{$this->slug}.{$this->resourceSlug}.{\$id}";

        return Cache::remember(\$cacheKey, 300, function () use (\$id) {
            \$response = \$this->get("/{$this->resourceSlug}s/{\$id}");
            return \$response['data'] ?? null;
        });
    }

    /**
     * Create a new resource.
     */
    public function create(array \$data): array
    {
        \$response = \$this->post('/{$this->resourceSlug}s', \$data);

        return \$response['data'] ?? \$response;
    }

    /**
     * Update a resource.
     */
    public function update(int \$id, array \$data): array
    {
        \$response = \$this->put("/{$this->resourceSlug}s/{\$id}", \$data);

        // Clear cache
        Cache::forget("{$this->slug}.{$this->resourceSlug}.{\$id}");

        return \$response['data'] ?? \$response;
    }

    /**
     * Delete a resource.
     */
    public function delete(int \$id): bool
    {
        \$this->request('DELETE', "/{$this->resourceSlug}s/{\$id}");

        Cache::forget("{$this->slug}.{$this->resourceSlug}.{\$id}");

        return true;
    }

    /**
     * Make a GET request.
     */
    protected function get(string \$path, array \$query = []): array
    {
        return \$this->request('GET', \$path, ['query' => \$query]);
    }

    /**
     * Make a POST request.
     */
    protected function post(string \$path, array \$data = []): array
    {
        return \$this->request('POST', \$path, ['json' => \$data]);
    }

    /**
     * Make a PUT request.
     */
    protected function put(string \$path, array \$data = []): array
    {
        return \$this->request('PUT', \$path, ['json' => \$data]);
    }

    /**
     * Make an HTTP request.
     */
    protected function request(string \$method, string \$path, array \$options = []): array
    {
        \$url = rtrim(\$this->baseUrl, '/') . '/' . ltrim(\$path, '/');

        try {
            \$response = Http::withHeaders([
                    'Authorization' => "Bearer {\$this->apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(\$this->timeout)
                ->retry(\$this->retries, 100)
                ->{\$method}(\$url, \$options['json'] ?? \$options['query'] ?? []);

            if (\$response->failed()) {
                Log::error("{$this->name} API error", [
                    'url' => \$url,
                    'status' => \$response->status(),
                    'body' => \$response->body(),
                ]);

                throw new \\RuntimeException(
                    "{$this->name} API error: " . \$response->body()
                );
            }

            return \$response->json() ?? [];

        } catch (\\Exception \$e) {
            Log::error("{$this->name} API exception", [
                'url' => \$url,
                'error' => \$e->getMessage(),
            ]);

            throw \$e;
        }
    }
}
PHP;
    }

    protected function generateEvent(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * {$this->resourceName} Created Event
 */
class {$this->resourceName}Created
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array \$data
    ) {}

    public function getData(): array
    {
        return \$this->data;
    }
}
PHP;
    }

    protected function generateWebhookHandler(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Webhooks;

use App\Services\Webhooks\WebhookHandler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * {$this->resourceName} Webhook Handler
 *
 * Dispatches webhooks when {$this->resourceSlug}s are created.
 */
class {$this->resourceName}WebhookHandler extends WebhookHandler
{
    protected string \$event = '{$this->slug}.{$this->resourceSlug}.created';

    /**
     * Handle the webhook dispatch.
     */
    public function handle(array \$data): void
    {
        \$webhookUrls = \$this->getSubscribers();

        foreach (\$webhookUrls as \$url) {
            \$this->dispatch(\$url, [
                'event' => \$this->event,
                'timestamp' => now()->toIso8601String(),
                'data' => \$data,
            ]);
        }
    }

    /**
     * Dispatch webhook to a URL.
     */
    protected function dispatch(string \$url, array \$payload): void
    {
        try {
            \$response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => \$this->event,
                    'X-Webhook-Signature' => \$this->sign(\$payload),
                ])
                ->post(\$url, \$payload);

            if (\$response->failed()) {
                Log::warning("Webhook delivery failed", [
                    'url' => \$url,
                    'status' => \$response->status(),
                ]);
            }

        } catch (\\Exception \$e) {
            Log::error("Webhook dispatch error", [
                'url' => \$url,
                'error' => \$e->getMessage(),
            ]);
        }
    }

    /**
     * Sign the webhook payload.
     */
    protected function sign(array \$payload): string
    {
        \$secret = config('{$this->slug}.webhook_secret', '');
        return hash_hmac('sha256', json_encode(\$payload), \$secret);
    }

    /**
     * Get webhook subscribers.
     */
    protected function getSubscribers(): array
    {
        return config('{$this->slug}.webhook_urls', []);
    }
}
PHP;
    }

    protected function generateConfig(): string
    {
        return <<<PHP
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | {$this->name} API Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env(strtoupper(str_replace('-', '_', '{$this->slug}')) . '_ENABLED', true),

    // External API settings
    'api_url' => env(strtoupper(str_replace('-', '_', '{$this->slug}')) . '_API_URL', ''),
    'api_key' => env(strtoupper(str_replace('-', '_', '{$this->slug}')) . '_API_KEY', ''),

    // Webhook settings
    'webhook_secret' => env(strtoupper(str_replace('-', '_', '{$this->slug}')) . '_WEBHOOK_SECRET', ''),
    'webhook_urls' => [],

    // Rate limiting
    'rate_limit' => [
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000,
    ],

    // Caching
    'cache' => [
        'enabled' => true,
        'ttl' => 300, // seconds
    ],
];
PHP;
    }

    protected function generateApiRoutes(): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Plugins\\{$this->name}\\Http\\Controllers\\Api\\{$this->resourceName}Controller;
use Plugins\\{$this->name}\\Http\\Middleware\\Authenticate{$this->name};

/*
|--------------------------------------------------------------------------
| {$this->name} Plugin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/{$this->slug}')
    ->name('api.{$this->slug}.')
    ->middleware(['api', Authenticate{$this->name}::class])
    ->group(function () {
        Route::apiResource('{$this->resourceSlug}s', {$this->resourceName}Controller::class);

        // Custom endpoints
        Route::post('{$this->resourceSlug}s/{id}/sync', [{$this->resourceName}Controller::class, 'sync'])
            ->name('{$this->resourceSlug}s.sync');
    });
PHP;
    }

    protected function generateApiTest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Tests\\Feature\\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * {$this->resourceName} API Tests
 */
class {$this->resourceName}ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set API key for tests
        config(['{$this->slug}.api_key' => 'test-api-key']);
    }

    protected function apiHeaders(): array
    {
        return [
            'Authorization' => 'Bearer test-api-key',
            'Accept' => 'application/json',
        ];
    }

    public function test_requires_authentication(): void
    {
        \$response = \$this->getJson('/api/{$this->slug}/{$this->resourceSlug}s');

        \$response->assertStatus(401);
    }

    public function test_can_list_{$this->resourceSlug}s(): void
    {
        \$response = \$this->getJson('/api/{$this->slug}/{$this->resourceSlug}s', \$this->apiHeaders());

        \$response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_create_{$this->resourceSlug}(): void
    {
        \$data = [
            'name' => 'Test {$this->resourceName}',
            'description' => 'Test description',
        ];

        \$response = \$this->postJson('/api/{$this->slug}/{$this->resourceSlug}s', \$data, \$this->apiHeaders());

        \$response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test {$this->resourceName}');
    }

    public function test_validates_required_fields(): void
    {
        \$response = \$this->postJson('/api/{$this->slug}/{$this->resourceSlug}s', [], \$this->apiHeaders());

        \$response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
PHP;
    }

    protected function generateReadme(): string
    {
        $description = $this->options['description'] ?? "API integration plugin for {$this->name}.";

        return <<<MD
# {$this->name} Plugin

{$description}

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/{$this->slug}/{$this->resourceSlug}s` | List all {$this->resourceSlug}s |
| GET | `/api/{$this->slug}/{$this->resourceSlug}s/{id}` | Get a single {$this->resourceSlug} |
| POST | `/api/{$this->slug}/{$this->resourceSlug}s` | Create a new {$this->resourceSlug} |
| PUT | `/api/{$this->slug}/{$this->resourceSlug}s/{id}` | Update a {$this->resourceSlug} |
| DELETE | `/api/{$this->slug}/{$this->resourceSlug}s/{id}` | Delete a {$this->resourceSlug} |

## Authentication

All API endpoints require authentication via API key:

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \\
  https://your-domain.com/api/{$this->slug}/{$this->resourceSlug}s
```

## Webhooks

The plugin dispatches webhooks for the following events:

| Event | Description |
|-------|-------------|
| `{$this->slug}.{$this->resourceSlug}.created` | When a {$this->resourceSlug} is created |

Configure webhook URLs in `config/{$this->slug}.php`.

## Configuration

```bash
# .env
{$this->getEnvPrefix()}_API_URL=https://api.example.com
{$this->getEnvPrefix()}_API_KEY=your-api-key
{$this->getEnvPrefix()}_WEBHOOK_SECRET=your-webhook-secret
```

## Testing

```bash
php artisan plugin:test {$this->slug}
```

## License

MIT
MD;
    }

    protected function getEnvPrefix(): string
    {
        return strtoupper(str_replace('-', '_', $this->slug));
    }
}
