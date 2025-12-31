<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use App\Services\Plugins\Contracts\PluginInterface;
use App\Services\Translation\TranslationService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

abstract class BasePlugin implements PluginInterface
{
    /**
     * The plugin model instance.
     */
    protected Plugin $plugin;

    /**
     * The plugin's base path.
     */
    protected string $basePath;

    /**
     * The hook manager instance.
     */
    protected HookManager $hooks;

    /**
     * The plugin manifest data from plugin.json.
     */
    protected array $manifest = [];

    /**
     * Create a new plugin instance.
     */
    public function __construct()
    {
        $this->hooks = app(HookManager::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    /**
     * {@inheritdoc}
     */
    public function setPlugin(Plugin $plugin): void
    {
        $this->plugin = $plugin;
        $this->basePath = $plugin->getFullPath();
        $this->loadManifest();
    }

    /**
     * Load the plugin manifest from plugin.json.
     */
    protected function loadManifest(): void
    {
        $manifestPath = $this->basePath . '/plugin.json';
        
        if (file_exists($manifestPath)) {
            $this->manifest = json_decode(file_get_contents($manifestPath), true) ?? [];
        }
    }

    /**
     * Get the plugin manifest.
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        // Override in child class to register services
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $this->loadViews();
        $this->loadRoutes();
        $this->loadTranslations();
        $this->loadNavigationFromManifest();
    }

    /**
     * {@inheritdoc}
     */
    public function activate(): void
    {
        // Override in child class for activation logic
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(): void
    {
        // Override in child class for deactivation logic
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void
    {
        // Override in child class for uninstall cleanup
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewsPath(): string
    {
        return $this->basePath . '/Views';
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationsPath(): string
    {
        return $this->basePath . '/migrations';
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesPath(): ?string
    {
        // Check for single routes.php file first
        $path = $this->basePath . '/routes.php';
        if (file_exists($path)) {
            return $path;
        }
        
        // Check for routes/web.php (Laravel-style routes directory)
        $webPath = $this->basePath . '/routes/web.php';
        if (file_exists($webPath)) {
            return $webPath;
        }
        
        return null;
    }

    /**
     * Get the API routes path if exists.
     */
    public function getApiRoutesPath(): ?string
    {
        $path = $this->basePath . '/routes/api.php';
        return file_exists($path) ? $path : null;
    }

    /**
     * Load the plugin's views.
     */
    protected function loadViews(): void
    {
        // Check multiple possible view paths
        $possiblePaths = [
            $this->basePath . '/resources/views',
            $this->basePath . '/Views',
            $this->basePath . '/views',
        ];
        
        foreach ($possiblePaths as $viewsPath) {
            if (is_dir($viewsPath)) {
                View::addNamespace($this->plugin->slug, $viewsPath);
                return;
            }
        }
    }

    /**
     * Load the plugin's routes.
     */
    protected function loadRoutes(): void
    {
        // Load web routes (authenticated)
        $routesPath = $this->getRoutesPath();
        if ($routesPath) {
            $this->loadRoutesFrom($routesPath);
        }

        // Load API routes if they exist
        $apiRoutesPath = $this->getApiRoutesPath();
        if ($apiRoutesPath) {
            $this->loadApiRoutesFrom($apiRoutesPath);
        }

        // Load public routes (no authentication required)
        $publicRoutesPath = $this->getPublicRoutesPath();
        if ($publicRoutesPath) {
            $this->loadPublicRoutesFrom($publicRoutesPath);
        }

        // Load storefront routes (public, store-scoped)
        $storefrontRoutesPath = $this->getStorefrontRoutesPath();
        if ($storefrontRoutesPath) {
            $this->loadStorefrontRoutesFrom($storefrontRoutesPath);
        }
    }

    /**
     * Get the public routes path if exists.
     * Public routes are accessible without authentication.
     */
    public function getPublicRoutesPath(): ?string
    {
        $path = $this->basePath . '/routes/public.php';
        return file_exists($path) ? $path : null;
    }

    /**
     * Get the storefront routes path if exists.
     * Storefront routes are public and scoped to a store.
     */
    public function getStorefrontRoutesPath(): ?string
    {
        $path = $this->basePath . '/routes/storefront.php';
        return file_exists($path) ? $path : null;
    }

    /**
     * Load public routes from a file.
     * These routes are accessible without authentication.
     */
    protected function loadPublicRoutesFrom(string $path): void
    {
        Route::middleware('web')
            ->prefix("p/{$this->plugin->slug}")
            ->name("public.{$this->plugin->slug}.")
            ->group(function () use ($path) {
                require $path;
            });
    }

    /**
     * Load storefront routes from a file.
     * These routes are public and scoped to a store slug.
     */
    protected function loadStorefrontRoutesFrom(string $path): void
    {
        Route::middleware('web')
            ->prefix("store/{store}")
            ->name("storefront.{$this->plugin->slug}.")
            ->group(function () use ($path) {
                require $path;
            });
    }

    /**
     * Load routes from a file with plugin context.
     * Routes are registered with web middleware (domain-agnostic).
     */
    protected function loadRoutesFrom(string $path): void
    {
        Route::middleware('web')
            ->prefix("plugins/{$this->plugin->slug}")
            ->name("plugins.{$this->plugin->slug}.")
            ->group(function () use ($path) {
                require $path;
            });
    }

    /**
     * Load API routes from a file with plugin context.
     * Routes are registered with api middleware.
     */
    protected function loadApiRoutesFrom(string $path): void
    {
        Route::middleware('api')
            ->prefix("api/plugins/{$this->plugin->slug}")
            ->name("api.plugins.{$this->plugin->slug}.")
            ->group(function () use ($path) {
                require $path;
            });
    }

    /**
     * Load the plugin's translations.
     * 
     * Registers translation namespace for the plugin.
     * Translations should be in: {plugin}/lang/{locale}/{group}.php
     * 
     * Usage in views: @t('plugin-slug::group.key')
     * Usage in code: __p('plugin-slug', 'group.key')
     */
    protected function loadTranslations(): void
    {
        $langPath = $this->getTranslationsPath();
        
        if (is_dir($langPath)) {
            // Register with Laravel's translator
            app('translator')->addNamespace($this->plugin->slug, $langPath);
            
            // Register with our custom TranslationService
            if (app()->bound(TranslationService::class)) {
                app(TranslationService::class)->registerPluginNamespace($this->plugin->slug, $langPath);
            }
        }
    }

    /**
     * Get the plugin's translations path.
     */
    public function getTranslationsPath(): string
    {
        return $this->basePath . '/lang';
    }

    /**
     * Load navigation items from the plugin manifest.
     */
    protected function loadNavigationFromManifest(): void
    {
        if (!isset($this->manifest['navigation'])) {
            return;
        }

        $navigation = $this->manifest['navigation'];

        // Register categories first
        if (isset($navigation['categories']) && is_array($navigation['categories'])) {
            foreach ($navigation['categories'] as $category) {
                $this->addNavigationCategory(
                    $category['name'],
                    $category['icon'] ?? 'folder',
                    $category['order'] ?? 100
                );
            }
        }

        // Register navigation items
        if (isset($navigation['items']) && is_array($navigation['items'])) {
            foreach ($navigation['items'] as $item) {
                $navItem = $this->buildNavigationItem($item);
                $category = $item['category'] ?? 'Plugins';
                $this->addNavigationItem($navItem, $category);
            }
        }
    }

    /**
     * Build a navigation item from manifest config.
     */
    protected function buildNavigationItem(array $config): array
    {
        $item = [
            'id' => $config['id'] ?? $this->plugin->slug,
            'icon' => $config['icon'] ?? 'plug',
            'label' => $config['label'] ?? $this->plugin->name,
        ];

        // Resolve URL from route name or direct URL
        if (isset($config['route'])) {
            $item['url'] = "/plugins/{$this->plugin->slug}" . ($config['route'] !== 'index' ? "/{$config['route']}" : '');
        } elseif (isset($config['url'])) {
            $item['url'] = $config['url'];
        } else {
            $item['url'] = "/plugins/{$this->plugin->slug}";
        }

        // Add order if specified
        if (isset($config['order'])) {
            $item['order'] = $config['order'];
        }

        // Add badge if specified
        if (isset($config['badge'])) {
            $item['badge'] = $config['badge'];
        }

        // Add permission if specified
        if (isset($config['permission'])) {
            $item['permission'] = $config['permission'];
        }

        // Process children/submenus
        if (isset($config['children']) && is_array($config['children'])) {
            $item['children'] = [];
            foreach ($config['children'] as $child) {
                $childItem = [
                    'id' => $child['id'] ?? $this->plugin->slug . '-' . ($child['route'] ?? 'child'),
                    'icon' => $child['icon'] ?? 'chevronRight',
                    'label' => $child['label'] ?? 'Sub Item',
                ];

                // Resolve child URL
                if (isset($child['route'])) {
                    $childItem['url'] = "/plugins/{$this->plugin->slug}/{$child['route']}";
                } elseif (isset($child['url'])) {
                    $childItem['url'] = $child['url'];
                } else {
                    $childItem['url'] = $item['url'] . '/' . ($child['id'] ?? 'child');
                }

                if (isset($child['badge'])) {
                    $childItem['badge'] = $child['badge'];
                }

                if (isset($child['permission'])) {
                    $childItem['permission'] = $child['permission'];
                }

                $item['children'][] = $childItem;
            }
        }

        return $item;
    }

    /**
     * Add an action hook.
     */
    protected function addAction(string $hook, callable $callback, int $priority = HookManager::DEFAULT_PRIORITY): void
    {
        $this->hooks->addAction($hook, $callback, $priority);
    }

    /**
     * Add a filter hook.
     */
    protected function addFilter(string $hook, callable $callback, int $priority = HookManager::DEFAULT_PRIORITY): void
    {
        $this->hooks->addFilter($hook, $callback, $priority);
    }

    /**
     * Get a plugin setting.
     */
    protected function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->plugin->settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Set a plugin setting.
     */
    protected function setSetting(string $key, mixed $value): void
    {
        $settings = $this->plugin->settings ?? [];
        $settings[$key] = $value;
        $this->plugin->settings = $settings;
        $this->plugin->save();
    }

    /**
     * Get menu items for this plugin.
     * Override in child class to provide custom menu items.
     * 
     * @return array Menu items array with structure:
     * [
     *     [
     *         'id' => 'plugin-menu',
     *         'label' => 'Plugin Menu',
     *         'icon' => 'plug',
     *         'url' => '/plugins/my-plugin',      // Direct URL
     *         'route' => 'plugins.my-plugin.index', // Or route name
     *         'permission' => 'plugin.view',      // Optional permission check
     *         'category' => 'Plugins',            // Navigation category
     *         'position' => 50,                   // Sort position
     *         'panels' => ['admin', 'console'],   // Optional panel restrictions
     *         'children' => [                     // Optional sub-menu items
     *             [
     *                 'id' => 'child-item',
     *                 'label' => 'Child Item',
     *                 'icon' => 'circle',
     *                 'url' => '/plugins/my-plugin/child',
     *             ]
     *         ],
     *     ],
     * ]
     */
    public function getMenuItems(): array
    {
        // First check if navigation items are defined in the manifest
        if (isset($this->manifest['navigation']['items']) && !empty($this->manifest['navigation']['items'])) {
            return $this->convertManifestNavigationToMenuItems($this->manifest['navigation']['items']);
        }

        return [];
    }

    /**
     * Convert manifest navigation items to the standard menu format.
     */
    protected function convertManifestNavigationToMenuItems(array $manifestItems): array
    {
        $menuItems = [];

        foreach ($manifestItems as $item) {
            // Skip children items that have a parent (they'll be processed with their parent)
            if (isset($item['parent'])) {
                continue;
            }

            $menuItem = [
                'id' => $item['id'] ?? $this->plugin->slug,
                'icon' => $item['icon'] ?? 'plug',
                'label' => $item['label'] ?? $this->plugin->name,
                'category' => $item['category'] ?? 'Plugins',
            ];

            // Set URL or route
            if (isset($item['url'])) {
                $menuItem['url'] = $item['url'];
            } elseif (isset($item['route'])) {
                $menuItem['route'] = $item['route'];
            } else {
                $menuItem['url'] = "/plugins/{$this->plugin->slug}";
            }

            // Optional fields
            if (isset($item['permission'])) {
                $menuItem['permission'] = $item['permission'];
            }
            if (isset($item['order'])) {
                $menuItem['position'] = $item['order'];
            }
            if (isset($item['panels'])) {
                $menuItem['panels'] = $item['panels'];
            }

            // Find and process children
            $children = array_filter($manifestItems, fn($i) => ($i['parent'] ?? null) === $item['id']);
            if (!empty($children)) {
                $menuItem['children'] = [];
                foreach ($children as $child) {
                    $childItem = [
                        'id' => $child['id'] ?? "{$this->plugin->slug}-child",
                        'icon' => $child['icon'] ?? 'circle',
                        'label' => $child['label'] ?? 'Item',
                    ];

                    if (isset($child['url'])) {
                        $childItem['url'] = $child['url'];
                    } elseif (isset($child['route'])) {
                        $childItem['route'] = $child['route'];
                    }

                    if (isset($child['permission'])) {
                        $childItem['permission'] = $child['permission'];
                    }

                    $menuItem['children'][] = $childItem;
                }
            }

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }

    /**
     * Add navigation items via the hook system.
     */
    protected function addNavigationItem(array $item, string $category = 'Plugins'): void
    {
        $this->addFilter('navigation_items', function (array $groups) use ($item, $category) {
            // Find or create the category
            $categoryIndex = null;
            foreach ($groups as $index => $group) {
                if ($group['category'] === $category) {
                    $categoryIndex = $index;
                    break;
                }
            }

            if ($categoryIndex !== null) {
                $groups[$categoryIndex]['items'][] = $item;
            } else {
                $groups[] = [
                    'category' => $category,
                    'items' => [$item],
                ];
            }

            return $groups;
        });
    }

    /**
     * Add a new navigation category.
     */
    protected function addNavigationCategory(string $name, string $icon = 'folder', int $order = 100): void
    {
        $this->addFilter('navigation_items', function (array $groups) use ($name, $icon, $order) {
            // Check if category already exists
            foreach ($groups as $group) {
                if ($group['category'] === $name) {
                    return $groups;
                }
            }

            // Add new category
            $groups[] = [
                'category' => $name,
                'icon' => $icon,
                'order' => $order,
                'items' => [],
            ];

            return $groups;
        }, 5); // Lower priority to run before items are added
    }

    /**
     * Add a submenu item to an existing navigation item.
     */
    protected function addSubMenuItem(string $parentId, array $subItem): void
    {
        $this->addFilter('navigation_items', function (array $groups) use ($parentId, $subItem) {
            foreach ($groups as &$group) {
                foreach ($group['items'] as &$item) {
                    if ($item['id'] === $parentId) {
                        if (!isset($item['children'])) {
                            $item['children'] = [];
                        }
                        $item['children'][] = $subItem;
                        break 2;
                    }
                }
            }
            return $groups;
        }, 15); // Higher priority to run after items are added
    }

    /**
     * Get the route URL for a plugin route.
     */
    protected function route(string $routeName): string
    {
        $fullRouteName = "plugins.{$this->plugin->slug}.{$routeName}";
        
        if (Route::has($fullRouteName)) {
            return route($fullRouteName);
        }

        // Fallback to constructed URL
        return "/plugins/{$this->plugin->slug}" . ($routeName !== 'index' ? "/{$routeName}" : '');
    }

    /**
     * Get the plugin's view name with namespace.
     */
    protected function view(string $view): string
    {
        return "{$this->plugin->slug}::{$view}";
    }

    /**
     * Render a plugin view.
     */
    protected function renderView(string $view, array $data = []): \Illuminate\Contracts\View\View
    {
        return view($this->view($view), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSettingsPage(): bool
    {
        // Check if the plugin manifest declares settings
        if (isset($this->manifest['settings']) && $this->manifest['settings'] === true) {
            return true;
        }

        // Check if plugin has settings fields defined
        $fields = $this->getSettingsFields();
        return !empty($fields);
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsView(): ?string
    {
        // Check if plugin has a custom settings view
        $viewPath = $this->getViewsPath() . '/settings.blade.php';
        
        if (file_exists($viewPath)) {
            return $this->view('settings');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFields(): array
    {
        // Check for settings fields in manifest
        if (isset($this->manifest['settings_fields']) && is_array($this->manifest['settings_fields'])) {
            return $this->manifest['settings_fields'];
        }

        // Override in child class to define settings fields
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsIcon(): string
    {
        // Check for icon in manifest
        if (isset($this->manifest['icon'])) {
            return $this->manifest['icon'];
        }

        return 'plug';
    }

    /**
     * Get all plugin settings values.
     */
    public function getAllSettings(): array
    {
        return $this->plugin->settings ?? [];
    }

    /**
     * Save multiple settings at once.
     */
    protected function saveSettings(array $settings): void
    {
        $currentSettings = $this->plugin->settings ?? [];
        $this->plugin->settings = array_merge($currentSettings, $settings);
        $this->plugin->save();
    }

    /**
     * {@inheritdoc}
     */
    public function hasDashboard(): bool
    {
        // Check if the plugin manifest declares dashboard
        if (isset($this->manifest['dashboard']['enabled']) && $this->manifest['dashboard']['enabled'] === true) {
            return true;
        }

        // Check if plugin has dashboard widgets defined
        $widgets = $this->getDashboardWidgets();
        return !empty($widgets);
    }

    /**
     * {@inheritdoc}
     */
    public function getDashboardWidgets(): array
    {
        // Check for widgets in manifest
        if (isset($this->manifest['dashboard']['widgets']) && is_array($this->manifest['dashboard']['widgets'])) {
            $widgets = [];
            foreach ($this->manifest['dashboard']['widgets'] as $widget) {
                $widgetId = $widget['id'] ?? null;
                if ($widgetId) {
                    $widgets[$widgetId] = [
                        'title' => $widget['title'] ?? ucfirst($widgetId),
                        'description' => $widget['description'] ?? '',
                        'icon' => $widget['icon'] ?? 'box',
                        'component' => $widget['component'] ?? 'custom',
                        'default_width' => $widget['default_width'] ?? 1,
                        'default_height' => $widget['default_height'] ?? 1,
                        'refreshable' => $widget['refreshable'] ?? true,
                        'configurable' => $widget['configurable'] ?? false,
                    ];
                }
            }
            return $widgets;
        }

        // Override in child class to define dashboard widgets
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDashboardIcon(): string
    {
        // Check for dashboard icon in manifest
        if (isset($this->manifest['dashboard']['icon'])) {
            return $this->manifest['dashboard']['icon'];
        }

        // Fallback to plugin icon
        if (isset($this->manifest['icon'])) {
            return $this->manifest['icon'];
        }

        return 'layoutDashboard';
    }

    /**
     * {@inheritdoc}
     */
    public function getDashboardTitle(): string
    {
        // Check for dashboard title in manifest
        if (isset($this->manifest['dashboard']['title'])) {
            return $this->manifest['dashboard']['title'];
        }

        // Fallback to plugin name + Dashboard
        return $this->plugin->name . ' Dashboard';
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetData(string $widgetId): array
    {
        // Override in child class to provide widget data
        // This method should return data for the specified widget
        return [
            'widget_id' => $widgetId,
            'data' => [],
        ];
    }

    /**
     * Register a widget data callback.
     * Helper method for plugins to register their widget data providers.
     */
    protected function registerWidgetDataCallback(string $widgetId, callable $callback): void
    {
        $this->widgetDataCallbacks[$widgetId] = $callback;
    }

    /**
     * Widget data callbacks storage.
     */
    protected array $widgetDataCallbacks = [];

    /**
     * Get widget data using registered callback.
     */
    protected function getWidgetDataFromCallback(string $widgetId): array
    {
        if (isset($this->widgetDataCallbacks[$widgetId])) {
            return call_user_func($this->widgetDataCallbacks[$widgetId]);
        }

        return [];
    }
}
