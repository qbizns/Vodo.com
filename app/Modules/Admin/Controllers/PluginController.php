<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Models\PluginLicense;
use App\Models\PluginUpdate;
use App\Models\DashboardWidget;
use App\Models\EntityDefinition;
use App\Models\ScheduledTask;
use App\Models\ApiEndpoint;
use App\Models\MenuItem;
use App\Models\WorkflowDefinition;
use App\Models\Shortcode;
use App\Models\Permission;
use App\Models\PluginDependency;
use App\Models\PluginEvent;
use App\Services\Plugins\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PluginController extends Controller
{
    public function __construct(
        protected PluginManager $pluginManager
    ) {}

    /**
     * Display installed plugins list (Screen 2).
     */
    public function index(Request $request)
    {
        // Sync plugin metadata from manifest files (cached for 5 minutes)
        // This ensures category, version, etc. stay in sync with plugin.json files
        if (!Cache::has('plugins_manifest_synced')) {
            $this->pluginManager->syncAllFromManifest();
            Cache::put('plugins_manifest_synced', true, 300);
        }

        // Build query with optional eager loading (tables might not exist yet)
        $query = Plugin::query();
        
        try {
            // Only eager load if tables exist
            if (Schema::hasTable('plugin_licenses')) {
                $query->with('license');
            }
            if (Schema::hasTable('plugin_updates')) {
                $query->with('availableUpdate');
            }
            if (Schema::hasTable('plugin_dependencies')) {
                $query->with('dependencies');
            }
        } catch (\Exception $e) {
            // Silently continue if eager loading fails
            Log::debug('Plugin eager loading skipped: ' . $e->getMessage());
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category') && Schema::hasColumn('plugins', 'category')) {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortField = $request->get('sort', 'name');
        $sortDir = $request->get('dir', 'asc');
        $query->orderBy($sortField, $sortDir);

        $plugins = $query->paginate(20);

        // Get stats with fallbacks for missing tables/columns
        $stats = [
            'total' => Plugin::count(),
            'active' => Plugin::where('status', 'active')->count(),
            'updates' => 0,
            'licenses_expiring' => 0,
        ];
        
        try {
            if (Schema::hasTable('plugin_updates')) {
                $stats['updates'] = PluginUpdate::count();
            }
            if (Schema::hasTable('plugin_licenses')) {
                $stats['licenses_expiring'] = PluginLicense::expiringSoon()->count();
            }
        } catch (\Exception $e) {
            Log::debug('Plugin stats query failed: ' . $e->getMessage());
        }

        // Get unique categories if column exists
        $categories = collect();
        try {
            if (Schema::hasColumn('plugins', 'category')) {
                $categories = Plugin::distinct()
                    ->whereNotNull('category')
                    ->where('category', '!=', '')
                    ->pluck('category');
            }
        } catch (\Exception $e) {
            Log::debug('Plugin categories query failed: ' . $e->getMessage());
        }

        $data = [
            'plugins' => $plugins,
            'stats' => $stats,
            'categories' => $categories,
            'currentPage' => 'system/plugins',
            'currentPageLabel' => __t('plugins.installed'),
            'currentPageIcon' => 'plug',
        ];

        return view('admin::plugins.index', $data);
    }

    /**
     * Display plugin marketplace (Screen 1).
     */
    public function marketplace(Request $request)
    {
        // Fetch from marketplace API
        $marketplacePlugins = $this->fetchMarketplacePlugins($request);
        
        // Get installed plugin slugs
        $installedSlugs = Plugin::pluck('slug')->toArray();

        // Get categories from marketplace
        $categories = $this->fetchMarketplaceCategories();

        $data = [
            'plugins' => $marketplacePlugins['data'] ?? [],
            'pagination' => $marketplacePlugins['meta'] ?? [],
            'categories' => $categories,
            'installed' => $installedSlugs,
            'query' => $request->get('q', ''),
            'category' => $request->get('category', 'all'),
            'sort' => $request->get('sort', 'popular'),
            'currentPage' => 'system/plugins/marketplace',
            'currentPageLabel' => __t('plugins.marketplace'),
            'currentPageIcon' => 'store',
        ];

        return view('backend.plugins.marketplace', $data);
    }

    /**
     * Display plugin details (Screen 3).
     */
    public function show(string $slug, Request $request)
    {
        $plugin = Plugin::where('slug', $slug)
            ->with(['license', 'availableUpdate', 'dependencies', 'events' => function($q) {
                $q->latest('created_at')->limit(10);
            }])
            ->first();

        $mockData = null;

        // Fallback to mock data if plugin not found locally
        if (!$plugin) {
            $mockPath = base_path('database/marketplace_plugins.json');
            if (file_exists($mockPath)) {
                $json = json_decode(file_get_contents($mockPath), true);
                if (isset($json[$slug])) {
                    $mockData = $json[$slug];
                    $plugin = new Plugin($mockData);
                    $plugin->id = 999; 
                    $plugin->setRelation('dependencies', collect([]));
                    $plugin->setRelation('license', null);
                    
                    if ($mockData['has_update'] ?? false) {
                        $update = new PluginUpdate(['latest_version' => $mockData['latest_version'] ?? '2.2.0']);
                        $plugin->setRelation('availableUpdate', $update);
                    } else {
                        $plugin->setRelation('availableUpdate', null);
                    }
                    
                    if ($mockData['has_valid_license'] ?? false) {
                         // Mock license if needed, though getHasValidLicenseAttribute checks license relation validity
                         // For now relies on attribute or could mock relation
                         $license = new PluginLicense(['status' => 'active', 'expires_at' => now()->addYear()]);
                         $plugin->setRelation('license', $license);
                    }
                }
            }
        }

        if (!$plugin) {
            abort(404);
        }

        // Get manifest data
        if ($mockData && isset($mockData['manifest'])) {
            $manifest = $mockData['manifest'];
        } else {
            $manifest = $plugin->getManifest();
        }

        // Get plugin instance for additional info
        $pluginInstance = null;
        try {
            if (!$mockData) {
                $pluginInstance = $this->pluginManager->getLoadedPlugin($slug);
            }
        } catch (\Exception $e) {
            // Plugin not loaded
        }

        // Get screenshots from manifest or mock
        $screenshots = $mockData['screenshots'] ?? ($manifest['screenshots'] ?? []);

        // Get changelog
        if ($mockData && isset($mockData['changelog'])) {
            $changelog = $mockData['changelog'];
        } else {
            $changelog = $this->getPluginChangelog($plugin);
        }

        // Get permissions registered by plugin
        if ($mockData && isset($mockData['permissions'])) {
            $permissions = $mockData['permissions'];
        } else {
            $permissions = $this->getPluginPermissions($plugin);
        }

        // Get dependency tree
        if ($mockData && isset($mockData['dependencies'])) {
            $dependencyTree = $mockData['dependencies'];
        } else {
            $dependencyTree = $this->buildDependencyTree($plugin);
        }

        // Get dependents (plugins that require this one)
        if ($mockData && isset($mockData['dependents'])) {
            $dependents = collect($mockData['dependents'])->map(fn($d) => new Plugin($d));
        } else {
            $dependents = $plugin->getDependentPlugins();
        }

        // Get active tab
        $activeTab = $request->get('tab', 'overview');

        // Fetch registered components counts
        if ($mockData && isset($mockData['components'])) {
            $components = $mockData['components'];
        } else {
            $components = [
                'permissions' => Permission::where('plugin_slug', $slug)->count(),
                'widgets' => DashboardWidget::where('plugin_slug', $slug)->count(),
                'entities' => EntityDefinition::where('plugin_slug', $slug)->count(),
                'tasks' => ScheduledTask::where('plugin_slug', $slug)->count(),
                'endpoints' => ApiEndpoint::where('plugin_slug', $slug)->count(),
                'menus' => MenuItem::where('plugin_slug', $slug)->count(),
                'workflows' => WorkflowDefinition::where('plugin_slug', $slug)->count(),
                'shortcodes' => Shortcode::where('plugin_slug', $slug)->count(),
            ];
        }

        $data = [
            'plugin' => $plugin,
            'manifest' => $manifest,
            'screenshots' => $screenshots,
            'changelog' => $changelog,
            'permissions' => $permissions,
            'dependencies' => $dependencyTree,
            'dependents' => $dependents,
            'activeTab' => $activeTab,
            'components' => $components,
            'currentPage' => 'system/plugins/' . $slug,
            'currentPageLabel' => $plugin->name,
            'currentPageIcon' => 'plug',
        ];

        return view('backend.plugins.show', $data);
    }

    /**
     * Display plugin settings (Screen 5).
     */
    public function settings(string $slug)
    {
        $plugin = Plugin::where('slug', $slug)->firstOrFail();

        // Get plugin instance to get settings fields
        $settingsFields = [];
        try {
            $pluginInstance = $this->pluginManager->getLoadedPlugin($slug);
            if ($pluginInstance && method_exists($pluginInstance, 'getSettingsFields')) {
                $settingsFields = $pluginInstance->getSettingsFields();
            }
        } catch (\Exception $e) {
            Log::warning('Could not load plugin settings fields', ['slug' => $slug, 'error' => $e->getMessage()]);
        }

        // Get current settings values
        $settings = $plugin->getPluginSettings();

        $data = [
            'plugin' => $plugin,
            'settingsFields' => $settingsFields,
            'settings' => $settings,
            'currentPage' => 'system/plugins/' . $slug . '/settings',
            'currentPageLabel' => $plugin->name . ' - ' . __t('plugins.settings'),
            'currentPageIcon' => 'settings',
        ];

        return view('backend.plugins.settings', $data);
    }

    /**
     * Save plugin settings.
     */
    public function saveSettings(string $slug, Request $request)
    {
        $plugin = Plugin::where('slug', $slug)->firstOrFail();

        $settings = $request->except(['_token', '_method']);

        foreach ($settings as $key => $value) {
            $plugin->setPluginSetting($key, $value);
        }

        // Log the event
        $plugin->logEvent('settings_changed', ['changed_keys' => array_keys($settings)]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __t('plugins.settings_saved'),
            ]);
        }

        return redirect()
            ->route('admin.plugins.settings', $slug)
            ->with('success', __t('plugins.settings_saved'));
    }

    /**
     * Display plugin updates (Screen 6).
     */
    public function updates()
    {
        $pluginsWithUpdates = Plugin::with(['availableUpdate'])
            ->hasUpdate()
            ->get();

        // Check for updates if cache is stale
        $lastCheck = Cache::get('plugins.updates.last_check');

        $data = [
            'plugins' => $pluginsWithUpdates,
            'lastCheck' => $lastCheck,
            'autoCheckEnabled' => config('plugins.auto_update_check', true),
            'currentPage' => 'system/plugins/updates',
            'currentPageLabel' => __t('plugins.updates'),
            'currentPageIcon' => 'refresh-cw',
        ];

        return view('backend.plugins.updates', $data);
    }

    /**
     * Check for updates.
     */
    public function checkUpdates(Request $request)
    {
        try {
            $updatesFound = $this->pluginManager->checkForUpdates();
            
            Cache::put('plugins.updates.last_check', now(), now()->addDay());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'updates_found' => $updatesFound,
                    'message' => $updatesFound > 0 
                        ? __t('plugins.updates_found', ['count' => $updatesFound])
                        : __t('plugins.no_updates'),
                ]);
            }

            return redirect()
                ->route('admin.plugins.updates')
                ->with('success', __t('plugins.update_check_complete'));
        } catch (\Exception $e) {
            Log::error('Update check failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __t('plugins.update_check_failed'),
                ], 500);
            }

            return redirect()
                ->route('admin.plugins.updates')
                ->with('error', __t('plugins.update_check_failed'));
        }
    }

    /**
     * Display plugin dependencies viewer (Screen 7).
     */
    public function dependencies(?string $slug = null)
    {
        if ($slug) {
            // Show dependencies for specific plugin
            $plugin = Plugin::where('slug', $slug)
                ->with(['dependencies'])
                ->firstOrFail();

            $dependencyTree = $this->buildDependencyTree($plugin);
            $dependents = $plugin->getDependentPlugins();

            $data = [
                'plugin' => $plugin,
                'dependencies' => $dependencyTree,
                'dependents' => $dependents,
                'currentPage' => 'system/plugins/' . $slug . '/dependencies',
                'currentPageLabel' => $plugin->name . ' - ' . __t('plugins.dependencies'),
                'currentPageIcon' => 'git-branch',
            ];

            return view('backend.plugins.dependencies-detail', $data);
        }

        // Show all dependencies overview
        $plugins = Plugin::with(['dependencies'])->get();
        
        $dependencyMatrix = [];
        foreach ($plugins as $plugin) {
            $dependencyMatrix[$plugin->slug] = [
                'plugin' => $plugin,
                'dependencies' => $plugin->dependencies,
                'dependents' => $plugin->getDependentPlugins(),
            ];
        }

        $data = [
            'plugins' => $plugins,
            'dependencyMatrix' => $dependencyMatrix,
            'currentPage' => 'system/plugins/dependencies',
            'currentPageLabel' => __t('plugins.dependencies'),
            'currentPageIcon' => 'git-branch',
        ];

        return view('backend.plugins.dependencies', $data);
    }

    /**
     * Display plugin licenses (Screen 8).
     */
    public function licenses()
    {
        $licenses = PluginLicense::with(['plugin'])->get();

        // Get plugins that require license but don't have one
        $unlicensedPlugins = Plugin::where('requires_license', true)
            ->whereDoesntHave('license')
            ->get();

        $stats = [
            'total' => $licenses->count(),
            'active' => $licenses->where('status', 'active')->count(),
            'expiring' => PluginLicense::expiringSoon()->count(),
            'expired' => $licenses->where('status', 'expired')->count(),
        ];

        $data = [
            'licenses' => $licenses,
            'unlicensedPlugins' => $unlicensedPlugins,
            'stats' => $stats,
            'currentPage' => 'system/plugins/licenses',
            'currentPageLabel' => __t('plugins.licenses'),
            'currentPageIcon' => 'key',
        ];

        return view('backend.plugins.licenses', $data);
    }

    /**
     * Activate a license.
     */
    public function activateLicense(Request $request)
    {
        $request->validate([
            'plugin_slug' => 'required|string|exists:plugins,slug',
            'license_key' => 'required|string',
        ]);

        $plugin = Plugin::where('slug', $request->plugin_slug)->firstOrFail();

        try {
            // Verify license with remote server
            $result = $this->verifyLicense($request->license_key, $plugin);

            if ($result['valid']) {
                // Create or update license record
                PluginLicense::updateOrCreate(
                    ['plugin_id' => $plugin->id],
                    [
                        'license_key' => $request->license_key,
                        'license_type' => $result['type'] ?? 'standard',
                        'status' => 'active',
                        'features' => $result['features'] ?? [],
                        'licensee_name' => $result['licensee_name'] ?? null,
                        'licensee_email' => $result['licensee_email'] ?? null,
                        'expires_at' => $result['expires_at'] ?? null,
                        'activated_at' => now(),
                        'last_verified_at' => now(),
                    ]
                );

                $plugin->logEvent('license_activated');

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => __t('plugins.license_activated'),
                    ]);
                }

                return redirect()
                    ->route('admin.plugins.licenses')
                    ->with('success', __t('plugins.license_activated'));
            }

            throw new \Exception($result['error'] ?? 'Invalid license');
        } catch (\Exception $e) {
            Log::error('License activation failed', [
                'plugin' => $plugin->slug,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('admin.plugins.licenses')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Deactivate a license.
     */
    public function deactivateLicense(string $slug)
    {
        $plugin = Plugin::where('slug', $slug)->firstOrFail();
        $license = $plugin->license;

        if ($license) {
            $license->update(['status' => 'suspended']);
            $plugin->logEvent('license_expired');
        }

        return redirect()
            ->route('admin.plugins.licenses')
            ->with('success', __t('plugins.license_deactivated'));
    }

    /**
     * Upload and install a new plugin.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'plugin' => [
                'required',
                'file',
                'mimes:zip',
                'max:' . config('plugins.max_upload_size', 51200),
            ],
        ]);

        try {
            $plugin = $this->pluginManager->install($request->file('plugin'));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __t('plugins.installed_success', ['name' => $plugin->name]),
                    'plugin' => $plugin,
                ]);
            }

            return redirect()
                ->route('admin.plugins.index')
                ->with('success', __t('plugins.installed_success', ['name' => $plugin->name]));
        } catch (\Throwable $e) {
            Log::error('Plugin installation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('admin.plugins.index')
                ->with('error', 'Plugin installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Activate a plugin.
     */
    public function activate(string $slug, Request $request)
    {
        try {
            $plugin = $this->pluginManager->activate($slug);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __t('plugins.activated_success', ['name' => $plugin->name]),
                ]);
            }

            return redirect()
                ->route('admin.plugins.index')
                ->with('success', __t('plugins.activated_success', ['name' => $plugin->name]));
        } catch (\Throwable $e) {
            Log::error('Plugin activation failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('admin.plugins.index')
                ->with('error', 'Plugin activation failed: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate a plugin.
     */
    public function deactivate(string $slug, Request $request)
    {
        try {
            $plugin = $this->pluginManager->deactivate($slug);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __t('plugins.deactivated_success', ['name' => $plugin->name]),
                ]);
            }

            return redirect()
                ->route('admin.plugins.index')
                ->with('success', __t('plugins.deactivated_success', ['name' => $plugin->name]));
        } catch (\Throwable $e) {
            Log::error('Plugin deactivation failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('admin.plugins.index')
                ->with('error', 'Plugin deactivation failed: ' . $e->getMessage());
        }
    }

    /**
     * Uninstall a plugin.
     */
    public function destroy(string $slug, Request $request)
    {
        try {
            $plugin = $this->pluginManager->find($slug);
            $pluginName = $plugin?->name ?? $slug;

            $this->pluginManager->uninstall($slug);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __t('plugins.uninstalled_success', ['name' => $pluginName]),
                ]);
            }

            return redirect()
                ->route('admin.plugins.index')
                ->with('success', __t('plugins.uninstalled_success', ['name' => $pluginName]));
        } catch (\Throwable $e) {
            Log::error('Plugin uninstall failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('admin.plugins.index')
                ->with('error', 'Plugin uninstall failed: ' . $e->getMessage());
        }
    }

    /**
     * Update a plugin.
     */
    public function update(string $slug, Request $request)
    {
        try {
            $plugin = Plugin::where('slug', $slug)->firstOrFail();
            $previousVersion = $plugin->version;

            // Perform update
            $this->pluginManager->update($slug);

            $plugin->refresh();
            $plugin->logEvent('updated', [], $previousVersion);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __t('plugins.updated_success', ['name' => $plugin->name]),
                    'new_version' => $plugin->version,
                ]);
            }

            return redirect()
                ->route('admin.plugins.updates')
                ->with('success', __t('plugins.updated_success', ['name' => $plugin->name]));
        } catch (\Throwable $e) {
            Log::error('Plugin update failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('admin.plugins.updates')
                ->with('error', 'Plugin update failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions on plugins.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,update,delete',
            'plugins' => 'required|array',
            'plugins.*' => 'exists:plugins,id',
        ]);

        $action = $request->action;
        $pluginIds = $request->plugins;
        $plugins = Plugin::whereIn('id', $pluginIds)->get();

        $results = ['success' => [], 'failed' => []];

        foreach ($plugins as $plugin) {
            try {
                switch ($action) {
                    case 'activate':
                        $this->pluginManager->activate($plugin->slug);
                        break;
                    case 'deactivate':
                        $this->pluginManager->deactivate($plugin->slug);
                        break;
                    case 'update':
                        if ($plugin->has_update) {
                            $this->pluginManager->update($plugin->slug);
                        }
                        break;
                    case 'delete':
                        $this->pluginManager->uninstall($plugin->slug);
                        break;
                }
                $results['success'][] = $plugin->name;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'name' => $plugin->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => count($results['failed']) === 0,
                'results' => $results,
            ]);
        }

        $message = count($results['success']) . ' plugins processed successfully.';
        if (count($results['failed']) > 0) {
            $message .= ' ' . count($results['failed']) . ' failed.';
        }

        return redirect()
            ->route('admin.plugins.index')
            ->with('success', $message);
    }

    // ==================== Private Helper Methods ====================

    /**
     * Fetch plugins from marketplace API.
     */
    protected function fetchMarketplacePlugins(Request $request): array
    {
        $cacheKey = 'marketplace.plugins.' . md5(serialize($request->all()));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($request) {
            
            // Try fetch from local mock for demo purposes
            $mockPath = base_path('database/marketplace_plugins.json');
            if (file_exists($mockPath)) {
                $json = json_decode(file_get_contents($mockPath), true);
                $items = array_values($json);
                
                // Simple filtering
                if ($q = $request->get('q')) {
                    $items = array_filter($items, fn($p) => 
                        str_contains(strtolower($p['name']), strtolower($q)) || 
                        str_contains(strtolower($p['description'] ?? ''), strtolower($q))
                    );
                }
                 if ($cat = $request->get('category')) {
                     if ($cat !== 'all') {
                        $items = array_filter($items, fn($p) => 
                            isset($p['category']) && $p['category'] === $cat
                        );
                     }
                }
                
                return [
                    'data' => array_values($items),
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'total' => count($items),
                        'per_page' => 12
                    ]
                ];
            }

            try {
                $response = Http::timeout(10)->get(config('marketplace.api_url') . '/plugins', [
                    'q' => $request->get('q'),
                    'category' => $request->get('category'),
                    'sort' => $request->get('sort', 'popular'),
                    'page' => $request->get('page', 1),
                    'per_page' => 12,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::warning('Marketplace API unavailable', ['error' => $e->getMessage()]);
            }

            return ['data' => [], 'meta' => []];
        });
    }

    /**
     * Fetch categories from marketplace.
     */
    protected function fetchMarketplaceCategories(): array
    {
        return Cache::remember('marketplace.categories', now()->addHours(6), function () {
            try {
                $response = Http::timeout(10)->get(config('marketplace.api_url') . '/categories');
                if ($response->successful()) {
                    return $response->json('data', []);
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch marketplace categories', ['error' => $e->getMessage()]);
            }

            // Default categories
            return [
                ['slug' => 'crm', 'name' => 'CRM'],
                ['slug' => 'accounting', 'name' => 'Accounting'],
                ['slug' => 'hr', 'name' => 'HR'],
                ['slug' => 'inventory', 'name' => 'Inventory'],
                ['slug' => 'reports', 'name' => 'Reports'],
                ['slug' => 'utils', 'name' => 'Utilities'],
            ];
        });
    }

    /**
     * Get changelog for a plugin.
     */
    protected function getPluginChangelog(Plugin $plugin): array
    {
        $changelogPath = $plugin->getFullPath() . '/CHANGELOG.md';

        if (!file_exists($changelogPath)) {
            return [];
        }

        $content = file_get_contents($changelogPath);
        
        // Parse markdown changelog into structured format
        $versions = [];
        $currentVersion = null;
        $currentChanges = [];

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            
            // Match version headers like "## [2.1.0] - 2024-12-10" or "## Version 2.1.0"
            if (preg_match('/^##\s+\[?v?(\d+\.\d+\.\d+)\]?\s*[-–]?\s*(.*)$/i', $line, $matches)) {
                if ($currentVersion) {
                    $versions[] = [
                        'version' => $currentVersion,
                        'date' => $currentDate ?? null,
                        'changes' => $currentChanges,
                    ];
                }
                $currentVersion = $matches[1];
                $currentDate = !empty($matches[2]) ? $matches[2] : null;
                $currentChanges = [];
            } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $matches) && $currentVersion) {
                $currentChanges[] = $matches[1];
            }
        }

        // Add last version
        if ($currentVersion) {
            $versions[] = [
                'version' => $currentVersion,
                'date' => $currentDate ?? null,
                'changes' => $currentChanges,
            ];
        }

        return $versions;
    }

    /**
     * Get permissions registered by a plugin.
     */
    protected function getPluginPermissions(Plugin $plugin): array
    {
        // Check manifest for registered permissions
        $manifest = $plugin->getManifest();
        
        return $manifest['permissions'] ?? [];
    }

    /**
     * Build dependency tree for visualization.
     */
    protected function buildDependencyTree(Plugin $plugin): array
    {
        $tree = [];

        foreach ($plugin->dependencies as $dep) {
            $depPlugin = $dep->dependency_plugin;
            
            $node = [
                'slug' => $dep->dependency_slug,
                'name' => $depPlugin?->name ?? $dep->dependency_slug,
                'required_version' => $dep->version_constraint,
                'installed_version' => $depPlugin?->version,
                'status' => $dep->status,
                'is_optional' => $dep->is_optional,
                'children' => [],
            ];

            // Recursively get dependencies of dependencies
            if ($depPlugin && $depPlugin->dependencies->isNotEmpty()) {
                $node['children'] = $this->buildDependencyTree($depPlugin);
            }

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * Verify license with remote server.
     */
    protected function verifyLicense(string $licenseKey, Plugin $plugin): array
    {
        try {
            $response = Http::timeout(10)->post(config('marketplace.license_server') . '/verify', [
                'license_key' => $licenseKey,
                'plugin' => $plugin->slug,
                'domain' => config('app.url'),
                'instance_id' => $this->getInstanceId(),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'valid' => false,
                'error' => $response->json('error', 'License verification failed'),
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Could not connect to license server',
            ];
        }
    }

    /**
     * Get unique instance ID for license validation.
     */
    protected function getInstanceId(): string
    {
        return Cache::rememberForever('system.instance_id', function () {
            return hash('sha256', config('app.url') . '|' . config('app.key'));
        });
    }

    /**
     * Serve a plugin asset file.
     * 
     * This allows plugin assets (icons, images, etc.) to be accessed via URL
     * without publishing them to the public directory.
     */
    public function serveAsset(string $slug, string $path)
    {
        $plugin = $this->pluginManager->find($slug);
        
        if (!$plugin) {
            abort(404, 'Plugin not found');
        }

        // Build the full path to the asset
        $assetPath = $plugin->getFullPath() . '/' . $path;
        
        // Security: Ensure path doesn't escape the plugin directory
        $realAssetPath = realpath($assetPath);
        $pluginBasePath = realpath($plugin->getFullPath());
        
        if (!$realAssetPath || !$pluginBasePath || !str_starts_with($realAssetPath, $pluginBasePath)) {
            abort(403, 'Access denied');
        }

        if (!file_exists($realAssetPath) || !is_file($realAssetPath)) {
            abort(404, 'Asset not found');
        }

        // Determine MIME type
        $mimeTypes = [
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];

        $extension = strtolower(pathinfo($realAssetPath, PATHINFO_EXTENSION));
        $mimeType = $mimeTypes[$extension] ?? mime_content_type($realAssetPath) ?: 'application/octet-stream';

        return response()->file($realAssetPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
        ]);
    }
}
