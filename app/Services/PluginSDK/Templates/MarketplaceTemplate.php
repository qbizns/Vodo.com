<?php

declare(strict_types=1);

namespace App\Services\PluginSDK\Templates;

use Illuminate\Support\Str;

/**
 * Marketplace Plugin Template
 *
 * Full-featured plugin for marketplace distribution with settings UI,
 * OAuth integration, billing support, and comprehensive documentation.
 * Similar to Salla.sa partner apps.
 */
class MarketplaceTemplate extends PluginTemplate
{
    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        // Configure manifest for marketplace
        $this->manifest->set('marketplace.listed', true);
        $this->manifest->set('marketplace.pricing', $options['pricing'] ?? 'free');
        $this->manifest->set('marketplace.trial_days', $options['trial_days'] ?? 14);
        $this->manifest->set('marketplace.categories', [$options['category'] ?? 'utilities']);

        // Add settings schema
        $this->manifest->set('settings_schema', $this->getDefaultSettingsSchema());
    }

    public function getType(): string
    {
        return 'marketplace';
    }

    public function getDescription(): string
    {
        return 'Full-featured marketplace plugin with settings UI, OAuth, billing, and comprehensive structure.';
    }

    public function getDefaultScopes(): array
    {
        return [
            'entities:read',
            'entities:write',
            'hooks:subscribe',
            'hooks:dispatch',
            'api:read',
            'api:write',
            'settings:read',
            'settings:write',
            'users:read',
        ];
    }

    public function getDirectoryStructure(): array
    {
        return [
            'config',
            'database/migrations',
            'database/seeders',
            'routes',
            'src/Http/Controllers',
            'src/Http/Controllers/Api',
            'src/Http/Middleware',
            'src/Http/Resources',
            'src/Http/Requests',
            'src/Models',
            'src/Services',
            'src/Events',
            'src/Listeners',
            'src/Jobs',
            'src/Notifications',
            'src/Webhooks',
            'src/Settings',
            'tests/Feature',
            'tests/Unit',
            'Resources/views/settings',
            'Resources/views/components',
            'Resources/lang/en',
            'Resources/assets/css',
            'Resources/assets/js',
            'docs',
        ];
    }

    public function getFiles(): array
    {
        $timestamp = date('Y_m_d_His');

        return [
            // Core files
            "src/{$this->name}Plugin.php" => $this->generatePluginClass(),
            "src/{$this->name}ServiceProvider.php" => $this->generateServiceProvider(),

            // Settings
            'src/Settings/SettingsManager.php' => $this->generateSettingsManager(),
            'src/Settings/SettingsValidator.php' => $this->generateSettingsValidator(),
            'src/Http/Controllers/SettingsController.php' => $this->generateSettingsController(),

            // API
            'src/Http/Controllers/Api/WebhookController.php' => $this->generateWebhookController(),
            'src/Http/Middleware/VerifyWebhookSignature.php' => $this->generateWebhookMiddleware(),

            // OAuth
            'src/Services/OAuthService.php' => $this->generateOAuthService(),
            'src/Http/Controllers/OAuthController.php' => $this->generateOAuthController(),

            // Billing
            'src/Services/BillingService.php' => $this->generateBillingService(),
            'src/Http/Controllers/BillingController.php' => $this->generateBillingController(),

            // Jobs & Events
            "src/Jobs/Process{$this->name}Job.php" => $this->generateJob(),
            "src/Events/{$this->name}Installed.php" => $this->generateInstalledEvent(),
            "src/Listeners/Handle{$this->name}Installation.php" => $this->generateInstallationListener(),

            // Database
            "database/migrations/{$timestamp}_create_{$this->slug}_settings_table.php" => $this->generateSettingsMigration(),
            "database/migrations/{$timestamp}_create_{$this->slug}_oauth_tokens_table.php" => $this->generateOAuthMigration(),

            // Config & Routes
            "config/{$this->slug}.php" => $this->generateConfig(),
            'routes/web.php' => $this->generateWebRoutes(),
            'routes/api.php' => $this->generateApiRoutes(),

            // Views
            'Resources/views/settings/index.blade.php' => $this->generateSettingsView(),
            'Resources/views/settings/oauth.blade.php' => $this->generateOAuthView(),

            // Tests
            'tests/Feature/SettingsTest.php' => $this->generateSettingsTest(),
            'tests/Feature/OAuthTest.php' => $this->generateOAuthTest(),
            'tests/Feature/WebhookTest.php' => $this->generateWebhookTest(),

            // Documentation
            'docs/README.md' => $this->generateDocsReadme(),
            'docs/API.md' => $this->generateApiDocs(),
            'docs/CHANGELOG.md' => $this->generateChangelog(),

            // Marketplace assets
            'composer.json' => $this->generateComposerJson(),
            'plugin.json' => $this->manifest->toJson(),
            'README.md' => $this->generateReadme(),
            '.gitignore' => $this->generateGitignore(),
            'Resources/lang/en/messages.php' => $this->generateLangFile(),
        ];
    }

    protected function getDefaultSettingsSchema(): array
    {
        return [
            'general' => [
                'label' => 'General Settings',
                'fields' => [
                    'enabled' => [
                        'type' => 'boolean',
                        'label' => 'Enable Plugin',
                        'default' => true,
                    ],
                    'api_mode' => [
                        'type' => 'select',
                        'label' => 'API Mode',
                        'options' => ['sandbox', 'production'],
                        'default' => 'sandbox',
                    ],
                ],
            ],
            'api' => [
                'label' => 'API Configuration',
                'fields' => [
                    'api_key' => [
                        'type' => 'password',
                        'label' => 'API Key',
                        'encrypted' => true,
                    ],
                    'webhook_url' => [
                        'type' => 'url',
                        'label' => 'Webhook URL',
                        'readonly' => true,
                    ],
                ],
            ],
        ];
    }

    protected function generatePluginClass(): string
    {
        $description = $this->options['description'] ?? "The {$this->name} marketplace plugin.";
        $version = $this->options['version'] ?? '1.0.0';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name};

use App\Services\Plugins\BasePlugin;
use App\Services\Hooks\HookManager;
use App\Services\Entity\EntityRegistry;
use Plugins\\{$this->name}\\Settings\\SettingsManager;
use Plugins\\{$this->name}\\Services\\OAuthService;
use Plugins\\{$this->name}\\Services\\BillingService;
use Plugins\\{$this->name}\\Events\\{$this->name}Installed;

/**
 * {$this->name} Plugin
 *
 * {$description}
 *
 * @marketplace
 */
class {$this->name}Plugin extends BasePlugin
{
    protected string \$identifier = '{$this->slug}';
    protected string \$name = '{$this->name}';
    protected string \$version = '{$version}';
    protected string \$description = '{$description}';
    protected array \$dependencies = [];

    /**
     * Required scopes for this plugin.
     */
    protected array \$requiredScopes = [
        'entities:read',
        'entities:write',
        'settings:read',
        'settings:write',
    ];

    public function boot(): void
    {
        \$this->registerServices();
        \$this->registerHooks();
        \$this->registerMenuItems();
        \$this->registerSettings();
    }

    public function install(): void
    {
        \$this->runMigrations();
        \$this->seedDefaults();

        event(new {$this->name}Installed(\$this));
    }

    public function uninstall(): void
    {
        // Revoke OAuth tokens
        app(OAuthService::class)->revokeAllTokens();

        // Cancel subscriptions
        app(BillingService::class)->cancelSubscription();
    }

    public function activate(): void
    {
        \$settings = app(SettingsManager::class);
        \$settings->set('active', true);
    }

    public function deactivate(): void
    {
        \$settings = app(SettingsManager::class);
        \$settings->set('active', false);
    }

    protected function registerServices(): void
    {
        app()->singleton(SettingsManager::class, function () {
            return new SettingsManager('{$this->slug}');
        });

        app()->singleton(OAuthService::class, function () {
            return new OAuthService(
                config('{$this->slug}.oauth.client_id'),
                config('{$this->slug}.oauth.client_secret'),
                config('{$this->slug}.oauth.redirect_uri')
            );
        });

        app()->singleton(BillingService::class, function () {
            return new BillingService('{$this->slug}');
        });
    }

    protected function registerHooks(): void
    {
        \$hooks = app(HookManager::class);

        // Listen for tenant events
        \$hooks->addAction('tenant.created', function (\$tenant) {
            \$this->onTenantCreated(\$tenant);
        });

        // Listen for user events
        \$hooks->addAction('user.login', function (\$user) {
            \$this->onUserLogin(\$user);
        });
    }

    protected function registerMenuItems(): void
    {
        \$this->registerMenu([
            [
                'id' => '{$this->slug}',
                'label' => '{$this->name}',
                'icon' => 'puzzle-piece',
                'sequence' => 80,
                'children' => [
                    [
                        'id' => '{$this->slug}.dashboard',
                        'label' => 'Dashboard',
                        'route' => '{$this->slug}.dashboard',
                        'icon' => 'chart-bar',
                    ],
                    [
                        'id' => '{$this->slug}.settings',
                        'label' => 'Settings',
                        'route' => '{$this->slug}.settings',
                        'icon' => 'cog',
                    ],
                ],
            ],
        ]);
    }

    protected function registerSettings(): void
    {
        \$settings = app(SettingsManager::class);

        // Register default settings
        \$settings->registerDefaults([
            'enabled' => true,
            'api_mode' => 'sandbox',
            'sync_interval' => 'hourly',
        ]);
    }

    protected function onTenantCreated(mixed \$tenant): void
    {
        // Initialize plugin for new tenant
    }

    protected function onUserLogin(mixed \$user): void
    {
        // Track user login
    }

    protected function runMigrations(): void
    {
        \$migrator = app('migrator');
        \$migrator->run([__DIR__ . '/database/migrations']);
    }

    protected function seedDefaults(): void
    {
        // Seed default configuration
    }
}
PHP;
    }

    protected function generateSettingsManager(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

/**
 * Settings Manager
 *
 * Manages plugin settings with encryption support and caching.
 */
class SettingsManager
{
    protected string \$pluginSlug;
    protected array \$defaults = [];
    protected array \$encrypted = ['api_key', 'api_secret', 'client_secret'];
    protected ?int \$tenantId = null;

    public function __construct(string \$pluginSlug)
    {
        \$this->pluginSlug = \$pluginSlug;
        \$this->tenantId = app('tenant')->id ?? null;
    }

    /**
     * Get a setting value.
     */
    public function get(string \$key, mixed \$default = null): mixed
    {
        \$cacheKey = \$this->getCacheKey(\$key);

        return Cache::remember(\$cacheKey, 3600, function () use (\$key, \$default) {
            \$record = DB::table('{$this->slug}_settings')
                ->where('tenant_id', \$this->tenantId)
                ->where('key', \$key)
                ->first();

            if (!\$record) {
                return \$this->defaults[\$key] ?? \$default;
            }

            \$value = \$record->value;

            // Decrypt if needed
            if (in_array(\$key, \$this->encrypted) && \$value) {
                \$value = Crypt::decryptString(\$value);
            }

            return \$value;
        });
    }

    /**
     * Set a setting value.
     */
    public function set(string \$key, mixed \$value): void
    {
        // Encrypt if needed
        if (in_array(\$key, \$this->encrypted) && \$value) {
            \$value = Crypt::encryptString(\$value);
        }

        DB::table('{$this->slug}_settings')->updateOrInsert(
            [
                'tenant_id' => \$this->tenantId,
                'key' => \$key,
            ],
            [
                'value' => \$value,
                'updated_at' => now(),
            ]
        );

        Cache::forget(\$this->getCacheKey(\$key));
    }

    /**
     * Get all settings.
     */
    public function all(): array
    {
        \$records = DB::table('{$this->slug}_settings')
            ->where('tenant_id', \$this->tenantId)
            ->get();

        \$settings = \$this->defaults;

        foreach (\$records as \$record) {
            \$value = \$record->value;

            if (in_array(\$record->key, \$this->encrypted) && \$value) {
                \$value = Crypt::decryptString(\$value);
            }

            \$settings[\$record->key] = \$value;
        }

        return \$settings;
    }

    /**
     * Register default settings.
     */
    public function registerDefaults(array \$defaults): void
    {
        \$this->defaults = array_merge(\$this->defaults, \$defaults);
    }

    /**
     * Delete a setting.
     */
    public function delete(string \$key): void
    {
        DB::table('{$this->slug}_settings')
            ->where('tenant_id', \$this->tenantId)
            ->where('key', \$key)
            ->delete();

        Cache::forget(\$this->getCacheKey(\$key));
    }

    protected function getCacheKey(string \$key): string
    {
        return "{$this->slug}.settings.{\$this->tenantId}.{\$key}";
    }
}
PHP;
    }

    protected function generateSettingsValidator(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Settings;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Settings Validator
 */
class SettingsValidator
{
    protected array \$rules = [
        'enabled' => ['boolean'],
        'api_mode' => ['in:sandbox,production'],
        'api_key' => ['nullable', 'string', 'max:255'],
        'sync_interval' => ['in:realtime,hourly,daily,weekly'],
    ];

    protected array \$messages = [
        'api_mode.in' => 'API mode must be either sandbox or production.',
    ];

    /**
     * Validate settings data.
     *
     * @throws ValidationException
     */
    public function validate(array \$data): array
    {
        \$validator = Validator::make(\$data, \$this->rules, \$this->messages);

        if (\$validator->fails()) {
            throw new ValidationException(\$validator);
        }

        return \$validator->validated();
    }

    /**
     * Validate a single setting.
     */
    public function validateSingle(string \$key, mixed \$value): bool
    {
        if (!isset(\$this->rules[\$key])) {
            return true;
        }

        \$validator = Validator::make(
            [\$key => \$value],
            [\$key => \$this->rules[\$key]]
        );

        return \$validator->passes();
    }
}
PHP;
    }

    protected function generateSettingsController(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Plugins\\{$this->name}\\Settings\\SettingsManager;
use Plugins\\{$this->name}\\Settings\\SettingsValidator;

/**
 * Settings Controller
 */
class SettingsController extends Controller
{
    public function __construct(
        protected SettingsManager \$settings,
        protected SettingsValidator \$validator
    ) {}

    /**
     * Show settings page.
     */
    public function index(): View
    {
        return view('{$this->slug}::settings.index', [
            'settings' => \$this->settings->all(),
            'schema' => \$this->getSettingsSchema(),
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request \$request): RedirectResponse
    {
        \$validated = \$this->validator->validate(\$request->all());

        foreach (\$validated as \$key => \$value) {
            \$this->settings->set(\$key, \$value);
        }

        return redirect()
            ->route('{$this->slug}.settings')
            ->with('success', 'Settings saved successfully.');
    }

    /**
     * Test API connection.
     */
    public function testConnection(): \Illuminate\Http\JsonResponse
    {
        try {
            // Test API connection logic here
            \$connected = true;
            \$message = 'Connection successful';
        } catch (\\Exception \$e) {
            \$connected = false;
            \$message = \$e->getMessage();
        }

        return response()->json([
            'connected' => \$connected,
            'message' => \$message,
        ]);
    }

    protected function getSettingsSchema(): array
    {
        return config('{$this->slug}.settings_schema', []);
    }
}
PHP;
    }

    protected function generateOAuthService(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

/**
 * OAuth Service
 *
 * Handles OAuth 2.0 authentication flow.
 */
class OAuthService
{
    protected string \$clientId;
    protected string \$clientSecret;
    protected string \$redirectUri;
    protected string \$authorizeUrl;
    protected string \$tokenUrl;

    public function __construct(
        string \$clientId,
        string \$clientSecret,
        string \$redirectUri
    ) {
        \$this->clientId = \$clientId;
        \$this->clientSecret = \$clientSecret;
        \$this->redirectUri = \$redirectUri;
        \$this->authorizeUrl = config('{$this->slug}.oauth.authorize_url');
        \$this->tokenUrl = config('{$this->slug}.oauth.token_url');
    }

    /**
     * Get authorization URL.
     */
    public function getAuthorizationUrl(array \$scopes = [], ?string \$state = null): string
    {
        \$state = \$state ?? bin2hex(random_bytes(16));

        \$params = [
            'client_id' => \$this->clientId,
            'redirect_uri' => \$this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', \$scopes),
            'state' => \$state,
        ];

        // Store state for verification
        session(['{$this->slug}.oauth_state' => \$state]);

        return \$this->authorizeUrl . '?' . http_build_query(\$params);
    }

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCode(string \$code): array
    {
        \$response = Http::asForm()->post(\$this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'client_id' => \$this->clientId,
            'client_secret' => \$this->clientSecret,
            'redirect_uri' => \$this->redirectUri,
            'code' => \$code,
        ]);

        if (\$response->failed()) {
            throw new \\RuntimeException('Failed to exchange authorization code: ' . \$response->body());
        }

        \$tokens = \$response->json();

        // Store tokens
        \$this->storeTokens(\$tokens);

        return \$tokens;
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(): array
    {
        \$refreshToken = \$this->getStoredRefreshToken();

        if (!\$refreshToken) {
            throw new \\RuntimeException('No refresh token available');
        }

        \$response = Http::asForm()->post(\$this->tokenUrl, [
            'grant_type' => 'refresh_token',
            'client_id' => \$this->clientId,
            'client_secret' => \$this->clientSecret,
            'refresh_token' => \$refreshToken,
        ]);

        if (\$response->failed()) {
            throw new \\RuntimeException('Failed to refresh token: ' . \$response->body());
        }

        \$tokens = \$response->json();
        \$this->storeTokens(\$tokens);

        return \$tokens;
    }

    /**
     * Get valid access token.
     */
    public function getAccessToken(): ?string
    {
        \$token = \$this->getStoredToken();

        if (!\$token) {
            return null;
        }

        // Check if expired
        if (\$token['expires_at'] && now()->greaterThan(\$token['expires_at'])) {
            \$tokens = \$this->refreshToken();
            return \$tokens['access_token'];
        }

        return \$token['access_token'];
    }

    /**
     * Revoke all tokens.
     */
    public function revokeAllTokens(): void
    {
        \$tenantId = app('tenant')->id ?? null;

        DB::table('{$this->slug}_oauth_tokens')
            ->where('tenant_id', \$tenantId)
            ->delete();
    }

    protected function storeTokens(array \$tokens): void
    {
        \$tenantId = app('tenant')->id ?? null;

        DB::table('{$this->slug}_oauth_tokens')->updateOrInsert(
            ['tenant_id' => \$tenantId],
            [
                'access_token' => Crypt::encryptString(\$tokens['access_token']),
                'refresh_token' => isset(\$tokens['refresh_token'])
                    ? Crypt::encryptString(\$tokens['refresh_token'])
                    : null,
                'expires_at' => isset(\$tokens['expires_in'])
                    ? now()->addSeconds(\$tokens['expires_in'])
                    : null,
                'updated_at' => now(),
            ]
        );
    }

    protected function getStoredToken(): ?array
    {
        \$tenantId = app('tenant')->id ?? null;

        \$record = DB::table('{$this->slug}_oauth_tokens')
            ->where('tenant_id', \$tenantId)
            ->first();

        if (!\$record) {
            return null;
        }

        return [
            'access_token' => Crypt::decryptString(\$record->access_token),
            'expires_at' => \$record->expires_at,
        ];
    }

    protected function getStoredRefreshToken(): ?string
    {
        \$tenantId = app('tenant')->id ?? null;

        \$record = DB::table('{$this->slug}_oauth_tokens')
            ->where('tenant_id', \$tenantId)
            ->first();

        if (!\$record || !\$record->refresh_token) {
            return null;
        }

        return Crypt::decryptString(\$record->refresh_token);
    }
}
PHP;
    }

    protected function generateOAuthController(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Plugins\\{$this->name}\\Services\\OAuthService;

/**
 * OAuth Controller
 */
class OAuthController extends Controller
{
    public function __construct(
        protected OAuthService \$oauth
    ) {}

    /**
     * Show OAuth connection page.
     */
    public function index(): View
    {
        \$connected = (bool) \$this->oauth->getAccessToken();

        return view('{$this->slug}::settings.oauth', [
            'connected' => \$connected,
        ]);
    }

    /**
     * Initiate OAuth flow.
     */
    public function connect(): RedirectResponse
    {
        \$scopes = config('{$this->slug}.oauth.scopes', []);
        \$url = \$this->oauth->getAuthorizationUrl(\$scopes);

        return redirect(\$url);
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request \$request): RedirectResponse
    {
        // Verify state
        \$state = session('{$this->slug}.oauth_state');
        if (\$request->input('state') !== \$state) {
            return redirect()
                ->route('{$this->slug}.oauth')
                ->with('error', 'Invalid state parameter');
        }

        // Check for errors
        if (\$request->has('error')) {
            return redirect()
                ->route('{$this->slug}.oauth')
                ->with('error', \$request->input('error_description', 'Authorization failed'));
        }

        // Exchange code for tokens
        try {
            \$this->oauth->exchangeCode(\$request->input('code'));

            return redirect()
                ->route('{$this->slug}.oauth')
                ->with('success', 'Connected successfully!');
        } catch (\\Exception \$e) {
            return redirect()
                ->route('{$this->slug}.oauth')
                ->with('error', 'Failed to connect: ' . \$e->getMessage());
        }
    }

    /**
     * Disconnect OAuth.
     */
    public function disconnect(): RedirectResponse
    {
        \$this->oauth->revokeAllTokens();

        return redirect()
            ->route('{$this->slug}.oauth')
            ->with('success', 'Disconnected successfully');
    }
}
PHP;
    }

    protected function generateBillingService(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Services;

use Illuminate\Support\Facades\DB;

/**
 * Billing Service
 *
 * Handles plugin subscription and billing.
 */
class BillingService
{
    protected string \$pluginSlug;

    public function __construct(string \$pluginSlug)
    {
        \$this->pluginSlug = \$pluginSlug;
    }

    /**
     * Check if tenant has active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        \$tenantId = app('tenant')->id ?? null;

        // Check marketplace subscription status
        \$subscription = DB::table('marketplace_subscriptions')
            ->where('tenant_id', \$tenantId)
            ->where('plugin_slug', \$this->pluginSlug)
            ->where('status', 'active')
            ->first();

        return (bool) \$subscription;
    }

    /**
     * Check if in trial period.
     */
    public function isInTrial(): bool
    {
        \$tenantId = app('tenant')->id ?? null;

        \$subscription = DB::table('marketplace_subscriptions')
            ->where('tenant_id', \$tenantId)
            ->where('plugin_slug', \$this->pluginSlug)
            ->first();

        if (!\$subscription) {
            return false;
        }

        return \$subscription->status === 'trial'
            && now()->lessThan(\$subscription->trial_ends_at);
    }

    /**
     * Get subscription details.
     */
    public function getSubscription(): ?object
    {
        \$tenantId = app('tenant')->id ?? null;

        return DB::table('marketplace_subscriptions')
            ->where('tenant_id', \$tenantId)
            ->where('plugin_slug', \$this->pluginSlug)
            ->first();
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(): bool
    {
        \$tenantId = app('tenant')->id ?? null;

        return DB::table('marketplace_subscriptions')
            ->where('tenant_id', \$tenantId)
            ->where('plugin_slug', \$this->pluginSlug)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]) > 0;
    }

    /**
     * Check feature access.
     */
    public function hasFeature(string \$feature): bool
    {
        if (!\$this->hasActiveSubscription() && !\$this->isInTrial()) {
            return false;
        }

        \$subscription = \$this->getSubscription();

        if (!\$subscription) {
            return false;
        }

        \$features = json_decode(\$subscription->features ?? '[]', true);

        return in_array(\$feature, \$features);
    }
}
PHP;
    }

    protected function generateBillingController(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Plugins\\{$this->name}\\Services\\BillingService;

/**
 * Billing Controller
 */
class BillingController extends Controller
{
    public function __construct(
        protected BillingService \$billing
    ) {}

    /**
     * Get subscription status.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'has_subscription' => \$this->billing->hasActiveSubscription(),
            'is_trial' => \$this->billing->isInTrial(),
            'subscription' => \$this->billing->getSubscription(),
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(): JsonResponse
    {
        \$cancelled = \$this->billing->cancelSubscription();

        return response()->json([
            'success' => \$cancelled,
            'message' => \$cancelled ? 'Subscription cancelled' : 'Failed to cancel',
        ]);
    }
}
PHP;
    }

    protected function generateWebhookController(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Controllers\\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Controller
 *
 * Handles incoming webhooks from external services.
 */
class WebhookController extends Controller
{
    /**
     * Handle incoming webhook.
     */
    public function handle(Request \$request): JsonResponse
    {
        \$event = \$request->input('event');
        \$payload = \$request->all();

        Log::info("{$this->name} webhook received", [
            'event' => \$event,
            'payload' => \$payload,
        ]);

        try {
            \$this->processWebhook(\$event, \$payload);

            return response()->json(['status' => 'processed']);
        } catch (\\Exception \$e) {
            Log::error("{$this->name} webhook failed", [
                'event' => \$event,
                'error' => \$e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    protected function processWebhook(string \$event, array \$payload): void
    {
        match (\$event) {
            'subscription.created' => \$this->handleSubscriptionCreated(\$payload),
            'subscription.cancelled' => \$this->handleSubscriptionCancelled(\$payload),
            'payment.completed' => \$this->handlePaymentCompleted(\$payload),
            default => Log::info("Unhandled webhook event: {\$event}"),
        };
    }

    protected function handleSubscriptionCreated(array \$payload): void
    {
        // Handle subscription creation
    }

    protected function handleSubscriptionCancelled(array \$payload): void
    {
        // Handle subscription cancellation
    }

    protected function handlePaymentCompleted(array \$payload): void
    {
        // Handle payment completion
    }
}
PHP;
    }

    protected function generateWebhookMiddleware(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify Webhook Signature
 */
class VerifyWebhookSignature
{
    public function handle(Request \$request, Closure \$next): Response
    {
        \$signature = \$request->header('X-Webhook-Signature');
        \$secret = config('{$this->slug}.webhook_secret');

        if (!\$signature || !\$secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        \$payload = \$request->getContent();
        \$expectedSignature = hash_hmac('sha256', \$payload, \$secret);

        if (!hash_equals(\$expectedSignature, \$signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return \$next(\$request);
    }
}
PHP;
    }

    protected function generateJob(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process {$this->name} Job
 */
class Process{$this->name}Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int \$tries = 3;
    public int \$backoff = 60;

    public function __construct(
        protected array \$data
    ) {}

    public function handle(): void
    {
        Log::info("{$this->name} job processing", \$this->data);

        // Process the job
    }

    public function failed(\\Throwable \$exception): void
    {
        Log::error("{$this->name} job failed", [
            'data' => \$this->data,
            'error' => \$exception->getMessage(),
        ]);
    }
}
PHP;
    }

    protected function generateInstalledEvent(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Services\Plugins\BasePlugin;

/**
 * {$this->name} Installed Event
 */
class {$this->name}Installed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BasePlugin \$plugin
    ) {}
}
PHP;
    }

    protected function generateInstallationListener(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Listeners;

use Illuminate\Support\Facades\Log;
use Plugins\\{$this->name}\\Events\\{$this->name}Installed;

/**
 * Handle {$this->name} Installation
 */
class Handle{$this->name}Installation
{
    public function handle({$this->name}Installed \$event): void
    {
        Log::info("{$this->name} plugin installed", [
            'version' => \$event->plugin->getVersion(),
        ]);

        // Perform post-installation tasks
    }
}
PHP;
    }

    protected function generateSettingsMigration(): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$this->slug}_settings', function (Blueprint \$table) {
            \$table->id();
            \$table->unsignedBigInteger('tenant_id')->nullable()->index();
            \$table->string('key', 100);
            \$table->text('value')->nullable();
            \$table->timestamps();

            \$table->unique(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$this->slug}_settings');
    }
};
PHP;
    }

    protected function generateOAuthMigration(): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$this->slug}_oauth_tokens', function (Blueprint \$table) {
            \$table->id();
            \$table->unsignedBigInteger('tenant_id')->nullable()->index();
            \$table->text('access_token');
            \$table->text('refresh_token')->nullable();
            \$table->timestamp('expires_at')->nullable();
            \$table->timestamps();

            \$table->unique(['tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$this->slug}_oauth_tokens');
    }
};
PHP;
    }

    protected function generateConfig(): string
    {
        $envPrefix = strtoupper(str_replace('-', '_', $this->slug));

        return <<<PHP
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | {$this->name} Plugin Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('{$envPrefix}_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | OAuth Configuration
    |--------------------------------------------------------------------------
    */
    'oauth' => [
        'client_id' => env('{$envPrefix}_CLIENT_ID'),
        'client_secret' => env('{$envPrefix}_CLIENT_SECRET'),
        'redirect_uri' => env('{$envPrefix}_REDIRECT_URI'),
        'authorize_url' => env('{$envPrefix}_AUTHORIZE_URL'),
        'token_url' => env('{$envPrefix}_TOKEN_URL'),
        'scopes' => ['read', 'write'],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'base_url' => env('{$envPrefix}_API_URL'),
        'timeout' => 30,
        'retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook_secret' => env('{$envPrefix}_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Settings Schema
    |--------------------------------------------------------------------------
    */
    'settings_schema' => [
        'general' => [
            'label' => 'General Settings',
            'fields' => [
                'enabled' => [
                    'type' => 'boolean',
                    'label' => 'Enable Plugin',
                    'default' => true,
                ],
                'api_mode' => [
                    'type' => 'select',
                    'label' => 'API Mode',
                    'options' => ['sandbox' => 'Sandbox', 'production' => 'Production'],
                    'default' => 'sandbox',
                ],
            ],
        ],
    ],
];
PHP;
    }

    protected function generateWebRoutes(): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Plugins\\{$this->name}\\Http\\Controllers\\SettingsController;
use Plugins\\{$this->name}\\Http\\Controllers\\OAuthController;
use Plugins\\{$this->name}\\Http\\Controllers\\BillingController;

/*
|--------------------------------------------------------------------------
| {$this->name} Plugin Web Routes
|--------------------------------------------------------------------------
*/

Route::prefix('{$this->slug}')
    ->name('{$this->slug}.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        // Dashboard
        Route::view('/', '{$this->slug}::dashboard')->name('dashboard');

        // Settings
        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/test', [SettingsController::class, 'testConnection'])->name('settings.test');

        // OAuth
        Route::get('oauth', [OAuthController::class, 'index'])->name('oauth');
        Route::get('oauth/connect', [OAuthController::class, 'connect'])->name('oauth.connect');
        Route::get('oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
        Route::post('oauth/disconnect', [OAuthController::class, 'disconnect'])->name('oauth.disconnect');

        // Billing
        Route::get('billing/status', [BillingController::class, 'status'])->name('billing.status');
        Route::post('billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    });
PHP;
    }

    protected function generateApiRoutes(): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Plugins\\{$this->name}\\Http\\Controllers\\Api\\WebhookController;
use Plugins\\{$this->name}\\Http\\Middleware\\VerifyWebhookSignature;

/*
|--------------------------------------------------------------------------
| {$this->name} Plugin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/{$this->slug}')
    ->name('api.{$this->slug}.')
    ->group(function () {
        // Webhooks (with signature verification)
        Route::post('webhooks', [WebhookController::class, 'handle'])
            ->middleware(VerifyWebhookSignature::class)
            ->name('webhooks');
    });
PHP;
    }

    protected function generateSettingsView(): string
    {
        return <<<BLADE
@extends('layouts.plugin')

@section('title', '{$this->name} Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">{$this->name} Settings</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('{$this->slug}.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        @foreach(\$schema as \$section => \$sectionData)
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold mb-4">{{ \$sectionData['label'] }}</h2>

                @foreach(\$sectionData['fields'] as \$field => \$fieldData)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ \$fieldData['label'] }}
                        </label>

                        @if(\$fieldData['type'] === 'boolean')
                            <input type="checkbox" name="{{ \$field }}" value="1"
                                {{ (\$settings[\$field] ?? \$fieldData['default'] ?? false) ? 'checked' : '' }}
                                class="rounded border-gray-300">
                        @elseif(\$fieldData['type'] === 'select')
                            <select name="{{ \$field }}" class="mt-1 block w-full rounded-md border-gray-300">
                                @foreach(\$fieldData['options'] as \$value => \$label)
                                    <option value="{{ \$value }}"
                                        {{ (\$settings[\$field] ?? '') === \$value ? 'selected' : '' }}>
                                        {{ is_string(\$label) ? \$label : \$value }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif(\$fieldData['type'] === 'password')
                            <input type="password" name="{{ \$field }}"
                                value="{{ \$settings[\$field] ?? '' }}"
                                class="mt-1 block w-full rounded-md border-gray-300">
                        @else
                            <input type="text" name="{{ \$field }}"
                                value="{{ \$settings[\$field] ?? \$fieldData['default'] ?? '' }}"
                                {{ (\$fieldData['readonly'] ?? false) ? 'readonly' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300">
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="flex justify-end">
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
BLADE;
    }

    protected function generateOAuthView(): string
    {
        return <<<BLADE
@extends('layouts.plugin')

@section('title', '{$this->name} - Connect')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Connect {$this->name}</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-6">
        @if(\$connected)
            <div class="flex items-center mb-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    Connected
                </span>
            </div>

            <p class="text-gray-600 mb-4">
                Your account is connected. You can disconnect at any time.
            </p>

            <form action="{{ route('{$this->slug}.oauth.disconnect') }}" method="POST">
                @csrf
                <button type="submit"
                    class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                    Disconnect
                </button>
            </form>
        @else
            <p class="text-gray-600 mb-4">
                Connect your account to enable synchronization and advanced features.
            </p>

            <a href="{{ route('{$this->slug}.oauth.connect') }}"
                class="inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Connect Account
            </a>
        @endif
    </div>
</div>
@endsection
BLADE;
    }

    protected function generateSettingsTest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Tests\\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_settings_page(): void
    {
        \$this->actingAs(\$this->createUser());

        \$response = \$this->get(route('{$this->slug}.settings'));

        \$response->assertStatus(200);
    }

    public function test_can_update_settings(): void
    {
        \$this->actingAs(\$this->createUser());

        \$response = \$this->post(route('{$this->slug}.settings.update'), [
            'enabled' => true,
            'api_mode' => 'sandbox',
        ]);

        \$response->assertRedirect(route('{$this->slug}.settings'));
    }

    protected function createUser()
    {
        return \App\Models\User::factory()->create();
    }
}
PHP;
    }

    protected function generateOAuthTest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Tests\\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_oauth_page(): void
    {
        \$this->actingAs(\$this->createUser());

        \$response = \$this->get(route('{$this->slug}.oauth'));

        \$response->assertStatus(200);
    }

    public function test_can_initiate_oauth_flow(): void
    {
        \$this->actingAs(\$this->createUser());

        config(['{$this->slug}.oauth.authorize_url' => 'https://example.com/oauth/authorize']);
        config(['{$this->slug}.oauth.client_id' => 'test-client']);

        \$response = \$this->get(route('{$this->slug}.oauth.connect'));

        \$response->assertRedirect();
    }

    protected function createUser()
    {
        return \App\Models\User::factory()->create();
    }
}
PHP;
    }

    protected function generateWebhookTest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Tests\\Feature;

use Tests\TestCase;

class WebhookTest extends TestCase
{
    public function test_webhook_requires_signature(): void
    {
        \$response = \$this->postJson('/api/{$this->slug}/webhooks', [
            'event' => 'test',
        ]);

        \$response->assertStatus(401);
    }

    public function test_webhook_validates_signature(): void
    {
        config(['{$this->slug}.webhook_secret' => 'test-secret']);

        \$payload = json_encode(['event' => 'test']);
        \$signature = hash_hmac('sha256', \$payload, 'test-secret');

        \$response = \$this->postJson('/api/{$this->slug}/webhooks', [
            'event' => 'test',
        ], [
            'X-Webhook-Signature' => \$signature,
        ]);

        \$response->assertStatus(200);
    }
}
PHP;
    }

    protected function generateDocsReadme(): string
    {
        return <<<MD
# {$this->name} Plugin Documentation

## Overview

{$this->options['description'] ?? "The {$this->name} plugin for the Vodo platform."}

## Installation

1. Install from marketplace or copy to plugins directory
2. Run `php artisan plugin:activate {$this->slug}`
3. Configure settings at Settings > {$this->name}

## Configuration

See [API.md](API.md) for API documentation.

## Support

For support, please contact support@example.com.
MD;
    }

    protected function generateApiDocs(): string
    {
        return <<<MD
# {$this->name} API Documentation

## Authentication

All API endpoints require authentication via Bearer token.

## Endpoints

### Webhooks

**POST** `/api/{$this->slug}/webhooks`

Receive webhook events from external services.

**Headers:**
- `X-Webhook-Signature`: HMAC-SHA256 signature of the payload

**Payload:**
```json
{
    "event": "event.name",
    "data": {}
}
```

## Events

| Event | Description |
|-------|-------------|
| `subscription.created` | New subscription created |
| `subscription.cancelled` | Subscription cancelled |
| `payment.completed` | Payment completed |
MD;
    }

    protected function generateChangelog(): string
    {
        $date = date('Y-m-d');

        return <<<MD
# Changelog

## [1.0.0] - {$date}

### Added
- Initial release
- Settings management
- OAuth integration
- Webhook support
- Billing integration
MD;
    }

    protected function generateLangFile(): string
    {
        return <<<PHP
<?php

return [
    'plugin_name' => '{$this->name}',
    'settings' => [
        'title' => 'Settings',
        'saved' => 'Settings saved successfully',
    ],
    'oauth' => [
        'connected' => 'Connected',
        'disconnected' => 'Disconnected',
        'connect' => 'Connect Account',
        'disconnect' => 'Disconnect',
    ],
];
PHP;
    }
}
