<?php

namespace App\Services;

use App\Services\Plugins\HookManager;
use App\Services\Plugins\PluginManager;
use App\Models\Plugin;

class NavigationService
{
    /**
     * The hook manager instance.
     */
    protected HookManager $hooks;

    /**
     * The plugin manager instance.
     */
    protected ?PluginManager $pluginManager = null;

    /**
     * Cached navigation groups by module.
     */
    protected array $cachedNavGroups = [];

    /**
     * Create a new NavigationService instance.
     */
    public function __construct(HookManager $hooks)
    {
        $this->hooks = $hooks;
    }

    /**
     * Get the plugin manager instance.
     */
    protected function getPluginManager(): ?PluginManager
    {
        if ($this->pluginManager === null && app()->bound(PluginManager::class)) {
            $this->pluginManager = app(PluginManager::class);
        }
        return $this->pluginManager;
    }

    /**
     * Get navigation groups for the sidebar.
     * Applies plugin filters to include plugin navigation items.
     */
    public function getNavGroups(string $modulePrefix = ''): array
    {
        $cacheKey = $modulePrefix ?: 'default';

        if (isset($this->cachedNavGroups[$cacheKey])) {
            return $this->cachedNavGroups[$cacheKey];
        }

        // Get base navigation groups
        $navGroups = $this->getBaseNavGroups($modulePrefix);

        // Add menu items from all active plugins
        $navGroups = $this->injectPluginMenuItems($navGroups, $modulePrefix);

        // Apply plugin navigation_items filter (for additional customization)
        $navGroups = $this->hooks->applyFilters('navigation_items', $navGroups);

        // Resolve dynamic values (badges, urls)
        $navGroups = $this->resolveNavGroups($navGroups, $modulePrefix);

        // Cache the result
        $this->cachedNavGroups[$cacheKey] = $navGroups;

        return $navGroups;
    }

    /**
     * Get navigation groups for the Navigation Board page.
     * Same as getNavGroups but formatted for the board display.
     */
    public function getNavGroupsForNavBoard(string $modulePrefix = ''): array
    {
        return $this->getNavGroups($modulePrefix);
    }

    /**
     * Clear the navigation cache.
     */
    public function clearCache(): void
    {
        $this->cachedNavGroups = [];
    }

    /**
     * Resolve dynamic values in navigation groups.
     */
    protected function resolveNavGroups(array $navGroups, string $modulePrefix): array
    {
        foreach ($navGroups as &$group) {
            foreach ($group['items'] as &$item) {
                // Resolve badge if it's a callable
                if (isset($item['badge']) && is_callable($item['badge'])) {
                    $item['badge'] = call_user_func($item['badge']);
                }

                // Resolve URL from route if not provided
                if (!isset($item['url'])) {
                    if (isset($item['route'])) {
                        $item['url'] = $this->resolveRouteToUrl($item['route'], $item['plugin_slug'] ?? null);
                    } else {
                        $item['url'] = '/' . $item['id'];
                    }
                }

                // Resolve children recursively
                if (isset($item['children']) && is_array($item['children'])) {
                    foreach ($item['children'] as &$child) {
                        if (isset($child['badge']) && is_callable($child['badge'])) {
                            $child['badge'] = call_user_func($child['badge']);
                        }
                        if (!isset($child['url'])) {
                            if (isset($child['route'])) {
                                $child['url'] = $this->resolveRouteToUrl($child['route'], $item['plugin_slug'] ?? null);
                            } else {
                                $child['url'] = $item['url'] . '/' . $child['id'];
                            }
                        }
                    }
                }
            }
        }

        return $navGroups;
    }

    /**
     * Resolve a route name to a URL.
     */
    protected function resolveRouteToUrl(string $routeName, ?string $pluginSlug = null): string
    {
        try {
            // Try multiple route name patterns
            $routePatterns = [
                $routeName,
            ];

            // Add plugin-specific patterns if plugin slug is available
            if ($pluginSlug) {
                $routePatterns[] = "plugins.{$pluginSlug}.{$routeName}";
                $routePatterns[] = "admin.plugins.{$pluginSlug}.{$routeName}";
            }

            // Add module prefixes
            $routePatterns[] = "admin.{$routeName}";
            $routePatterns[] = "console.{$routeName}";
            $routePatterns[] = "owner.{$routeName}";

            foreach ($routePatterns as $pattern) {
                if (\Illuminate\Support\Facades\Route::has($pattern)) {
                    return route($pattern);
                }
            }

            // Fallback: convert route name to URL path
            $parts = explode('.', $routeName);
            
            // Remove common suffixes from the end
            $suffixesToRemove = ['index', 'show', 'list'];
            while (!empty($parts) && in_array(end($parts), $suffixesToRemove)) {
                array_pop($parts);
            }
            
            return '/' . implode('/', $parts);
        } catch (\Exception $e) {
            return '/' . str_replace('.', '/', $routeName);
        }
    }

    /**
     * Get the base navigation groups for the system.
     */
    public function getBaseNavGroups(string $modulePrefix = ''): array
    {
        $allGroups = [
            /*
            [
                'category' => 'Website management',
                'panels' => ['console', 'admin', 'owner'],
                'items' => [
                    ['id' => 'dashboard', 'icon' => 'layoutDashboard', 'label' => 'Dashboard', 'url' => '/'],
                    ['id' => 'sites', 'icon' => 'globe2', 'label' => 'Sites', 'url' => '/sites'],
                ]
            ],
            [
                'category' => 'DB',
                'panels' => ['console', 'admin', 'owner'],
                'items' => [
                    ['id' => 'databases', 'icon' => 'database', 'label' => 'Databases', 'url' => '/databases'],
                    ['id' => 'db-servers', 'icon' => 'server', 'label' => 'Database servers', 'url' => '/db-servers', 'panels' => ['console']],
                ]
            ],
            [
                'category' => 'SSL',
                'panels' => ['console', 'admin', 'owner'],
                'items' => [
                    ['id' => 'ssl-certs', 'icon' => 'fileKey', 'label' => 'SSL certificates', 'url' => '/ssl-certs'],
                    ['id' => 'csr', 'icon' => 'fileLock', 'label' => 'CSR-requests', 'url' => '/csr'],
                ]
            ],
            [
                'category' => 'DNS',
                'panels' => ['console', 'admin'],
                'items' => [
                    ['id' => 'dns', 'icon' => 'network', 'label' => 'DNS management', 'url' => '/dns'],
                    ['id' => 'slave-servers', 'icon' => 'serverStack', 'label' => 'Slave servers', 'url' => '/slave-servers', 'panels' => ['console']],
                    ['id' => 'reserved-names', 'icon' => 'ban', 'label' => 'Reserved names', 'url' => '/reserved-names', 'panels' => ['console']],
                    ['id' => 'technical-domains', 'icon' => 'network', 'label' => 'Technical domains', 'url' => '/technical-domains', 'panels' => ['console']],
                ]
            ],
            [
                'category' => 'Tools',
                'panels' => ['console', 'admin', 'owner'],
                'items' => [
                    ['id' => 'backup', 'icon' => 'fileArchive', 'label' => 'Backup copies', 'url' => '/backup'],
                    ['id' => 'file-manager', 'icon' => 'folderTree', 'label' => 'File manager', 'url' => '/file-manager'],
                    ['id' => 'cron', 'icon' => 'clock', 'label' => 'CRON jobs', 'url' => '/cron'],
                ]
            ],
            [
                'category' => 'Accounts',
                'panels' => ['console', 'admin'],
                'items' => [
                    ['id' => 'administrators', 'icon' => 'shieldCheck', 'label' => 'Administrators', 'url' => '/administrators', 'panels' => ['console']],
                    ['id' => 'resellers', 'icon' => 'users', 'label' => 'Resellers', 'url' => '/resellers', 'panels' => ['console']],
                    ['id' => 'users', 'icon' => 'user', 'label' => 'Users', 'url' => '/users'],
                    ['id' => 'ftp-users', 'icon' => 'userCheck', 'label' => 'FTP users', 'url' => '/ftp-users'],
                    ['id' => 'templates', 'icon' => 'fileCode', 'label' => 'Templates', 'url' => '/templates', 'panels' => ['console']],
                    ['id' => 'access-functions', 'icon' => 'key', 'label' => 'Access to functions', 'url' => '/access-functions', 'panels' => ['console']],
                    ['id' => 'data-import', 'icon' => 'fileInput', 'label' => 'Data import', 'url' => '/data-import', 'panels' => ['console', 'admin']],
                ]
            ],
            [
                'category' => 'Integration',
                'panels' => ['console', 'admin'],
                'items' => [
                    ['id' => 'modules', 'icon' => 'package', 'label' => 'Modules', 'url' => '/modules', 'panels' => ['console']],
                    ['id' => 'antivirus', 'icon' => 'shield', 'label' => 'ImunifyAV', 'url' => '/antivirus'],
                    ['id' => 'extensions', 'icon' => 'plug', 'label' => 'Extensions', 'url' => '/extensions'],
                ]
            ],
            [
                'category' => 'Logs',
                'panels' => ['console', 'admin'],
                'items' => [
                    ['id' => 'action-log', 'icon' => 'fileCheck', 'label' => 'Action log', 'url' => '/action-log'],
                    ['id' => 'access-log', 'icon' => 'fileBarChart', 'label' => 'Access log', 'url' => '/access-log'],
                    ['id' => 'www-logs', 'icon' => 'globe', 'label' => 'WWW request logs', 'url' => '/www-logs'],
                ]
            ],
            [
                'category' => 'Monitoring',
                'panels' => ['admin','console'],
                'items' => [
                    ['id' => 'background-tasks', 'icon' => 'zap', 'label' => 'Background tasks', 'url' => '/background-tasks'],
                    ['id' => 'active-sessions', 'icon' => 'activity', 'label' => 'Active sessions', 'url' => '/active-sessions'],
                    ['id' => 'active-connections', 'icon' => 'plugZap', 'label' => 'Active connections', 'url' => '/active-connections'],
                    ['id' => 'notifications-panel', 'icon' => 'alertCircle', 'label' => 'Notifications', 'url' => '/notifications-panel'],
                    ['id' => 'resource-monitoring', 'icon' => 'gauge', 'label' => 'Resource monitoring', 'url' => '/resource-monitoring'],
                    ['id' => 'server-resources', 'icon' => 'barChart3', 'label' => 'Server resources', 'url' => '/server-resources'],
                ]
            ],
            [
                'category' => 'Statistics',
                'panels' => ['console', 'admin', 'owner'],
                'items' => [
                    ['id' => 'limitations', 'icon' => 'ban', 'label' => 'Limitations', 'url' => '/limitations'],
                    ['id' => 'user-traffic', 'icon' => 'activity', 'label' => 'User traffic', 'url' => '/user-traffic'],
                ]
            ],
            [
                'category' => 'Web server',
                'panels' => ['console', 'admin'],
                'items' => [
                    ['id' => 'php', 'icon' => 'code', 'label' => 'PHP', 'url' => '/php'],
                    ['id' => 'web-scripts', 'icon' => 'boxes', 'label' => 'Web scripts', 'url' => '/web-scripts'],
                    ['id' => 'web-server', 'icon' => 'server', 'label' => 'Web server settings', 'url' => '/web-server'],
                ]
            ],
            [
                'category' => 'Manage server',
                'panels' => ['admin','console'],
                'items' => [
                    ['id' => 'software-config', 'icon' => 'settings', 'label' => 'Software configuration', 'url' => '/software-config'],
                    ['id' => 'ip-addresses', 'icon' => 'wifi', 'label' => 'IP addresses', 'url' => '/ip-addresses'],
                    ['id' => 'firewall', 'icon' => 'shieldAlert', 'label' => 'Firewall', 'url' => '/firewall'],
                    ['id' => 'services', 'icon' => 'power', 'label' => 'Services', 'url' => '/services'],
                    ['id' => 'network-services', 'icon' => 'network', 'label' => 'Network services', 'url' => '/network-services'],
                    ['id' => 'system-info', 'icon' => 'info', 'label' => 'System information', 'url' => '/system-info'],
                    ['id' => 'system-settings', 'icon' => 'settings', 'label' => 'System settings', 'url' => '/system-settings'],
                    ['id' => 'execute-command', 'icon' => 'terminal', 'label' => 'Execute command', 'url' => '/execute-command'],
                    ['id' => 'reboot', 'icon' => 'power', 'label' => 'Reboot server', 'url' => '/reboot'],
                    ['id' => 'shell', 'icon' => 'terminal', 'label' => 'Shell-client', 'url' => '/shell'],
                ]
            ],
            [
                'category' => 'Panel',
                'panels' => ['admin','console'],
                'items' => [
                    ['id' => 'license', 'icon' => 'fileText', 'label' => 'License management', 'url' => '/license'],
                    ['id' => 'software-info', 'icon' => 'bookOpen', 'label' => 'Software info', 'url' => '/software-info'],
                    ['id' => 'changelog', 'icon' => 'listTree', 'label' => 'Changelog', 'url' => '/changelog'],
                    ['id' => 'panel-settings', 'icon' => 'settings', 'label' => 'Panel settings', 'url' => '/panel-settings'],
                    ['id' => 'branding', 'icon' => 'palette', 'label' => 'Branding settings', 'url' => '/branding'],
                    ['id' => 'email-notifications', 'icon' => 'bellRing', 'label' => 'Notifications', 'url' => '/email-notifications'],
                    ['id' => 'logging', 'icon' => 'fileEdit', 'label' => 'Logging settings', 'url' => '/logging'],
                    ['id' => 'policies', 'icon' => 'fileCheck', 'label' => 'Policies', 'url' => '/policies'],
                ]
            ],
            */
            [
                'category' => 'System',
                'panels' => ['admin', 'console'],
                'items' => [
                    ['id' => 'system/settings', 'icon' => 'settings', 'label' => 'Settings', 'url' => '/system/settings'],
                    ['id' => 'system/plugins', 'icon' => 'plug', 'label' => 'Plugins Management', 'url' => '/system/plugins'],
                    [
                        'id' => 'system/roles',
                        'icon' => 'shield',
                        'label' => 'Permissions & Access',
                        'url' => '/system/roles',
                        'children' => [
                            ['id' => 'roles', 'icon' => 'shield', 'label' => 'Roles', 'url' => '/system/roles'],
                            ['id' => 'matrix', 'icon' => 'grid', 'label' => 'Permission Matrix', 'url' => '/system/permissions/matrix'],
                            ['id' => 'rules', 'icon' => 'shieldAlert', 'label' => 'Access Rules', 'url' => '/system/permissions/rules'],
                            ['id' => 'audit', 'icon' => 'fileText', 'label' => 'Audit Log', 'url' => '/system/permissions/audit'],
                        ]
                    ],
                ]
            ],
        ];

        if (empty($modulePrefix)) {
            return $allGroups;
        }

        // Filter categories and items by panel
        $filteredGroups = [];
        foreach ($allGroups as $group) {
            // Check if category is allowed for this panel
            if (isset($group['panels']) && !in_array($modulePrefix, $group['panels'])) {
                continue;
            }

            $filteredItems = [];
            foreach ($group['items'] as $item) {
                // Check if specific item is allowed for this panel
                if (isset($item['panels']) && !in_array($modulePrefix, $item['panels'])) {
                    continue;
                }
                $filteredItems[] = $item;
            }

            if (!empty($filteredItems)) {
                $group['items'] = $filteredItems;
                unset($group['panels']); // Clean up internal metadata
                $filteredGroups[] = $group;
            }
        }

        return $filteredGroups;
    }

    /**
     * Find a category index in navigation groups.
     */
    public function findCategoryIndex(array $groups, string $categoryName): ?int
    {
        foreach ($groups as $index => $group) {
            if ($group['category'] === $categoryName) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Add an item to a specific category.
     */
    public function addItemToCategory(array &$groups, string $category, array $item): void
    {
        $index = $this->findCategoryIndex($groups, $category);

        if ($index !== null) {
            $groups[$index]['items'][] = $item;
        } else {
            // Create new category
            $groups[] = [
                'category' => $category,
                'items' => [$item],
            ];
        }
    }

    /**
     * Add a new category to navigation groups.
     */
    public function addCategory(array &$groups, string $name, string $icon = 'folder', int $order = 100): void
    {
        // Check if category already exists
        if ($this->findCategoryIndex($groups, $name) === null) {
            $groups[] = [
                'category' => $name,
                'icon' => $icon,
                'order' => $order,
                'items' => [],
            ];
        }
    }

    /**
     * Inject menu items from all active plugins into navigation groups.
     */
    protected function injectPluginMenuItems(array $navGroups, string $modulePrefix = ''): array
    {
        $pluginManager = $this->getPluginManager();
        
        if (!$pluginManager) {
            return $navGroups;
        }

        // Get all active plugins from loaded instances
        $loadedPlugins = $pluginManager->getLoadedPlugins();

        // Also get active plugins from database for those not yet instantiated
        try {
            $activePlugins = Plugin::active()->get();
        } catch (\Exception $e) {
            // Database might not be available during boot
            $activePlugins = collect();
        }

        $pluginMenuItems = [];

        // Collect menu items from loaded plugin instances
        foreach ($loadedPlugins as $slug => $pluginInstance) {
            if (method_exists($pluginInstance, 'getMenuItems')) {
                $menuItems = $pluginInstance->getMenuItems();
                if (!empty($menuItems)) {
                    $pluginMenuItems[$slug] = [
                        'items' => $menuItems,
                        'plugin' => $activePlugins->firstWhere('slug', $slug),
                    ];
                }
            }
        }

        // For active plugins not yet loaded, try to get menu items from plugin.json
        foreach ($activePlugins as $plugin) {
            if (!isset($pluginMenuItems[$plugin->slug])) {
                $manifestMenuItems = $this->getMenuItemsFromManifest($plugin);
                if (!empty($manifestMenuItems)) {
                    $pluginMenuItems[$plugin->slug] = [
                        'items' => $manifestMenuItems,
                        'plugin' => $plugin,
                    ];
                }
            }
        }

        // Now inject plugin menu items into navigation groups
        foreach ($pluginMenuItems as $slug => $data) {
            $navGroups = $this->mergePluginMenuItems($navGroups, $data['items'], $data['plugin'], $modulePrefix);
        }

        return $navGroups;
    }

    /**
     * Get menu items from plugin.json manifest.
     */
    protected function getMenuItemsFromManifest(Plugin $plugin): array
    {
        try {
            $manifestPath = $plugin->getFullPath() . '/plugin.json';
            
            if (!file_exists($manifestPath)) {
                return [];
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }

            // Convert navigation items from manifest format to internal format
            $items = $manifest['navigation']['items'] ?? [];
            
            return $this->convertManifestItemsToMenuFormat($items, $plugin);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Convert manifest navigation items to internal menu format.
     */
    protected function convertManifestItemsToMenuFormat(array $manifestItems, Plugin $plugin): array
    {
        $menuItems = [];
        $slug = $plugin->slug;

        foreach ($manifestItems as $item) {
            // Skip children items that have a parent (they'll be added to their parent)
            if (isset($item['parent'])) {
                continue;
            }

            // Resolve URL from route or direct URL
            $url = $item['url'] ?? null;
            if (!$url && isset($item['route'])) {
                $url = "/plugins/{$slug}" . ($item['route'] !== 'index' ? "/{$item['route']}" : '');
            }
            if (!$url) {
                $url = "/plugins/{$slug}";
            }

            $menuItem = [
                'id' => $item['id'] ?? "{$slug}-" . ($item['label'] ?? 'item'),
                'icon' => $item['icon'] ?? 'plug',
                'label' => $item['label'] ?? $plugin->name,
                'url' => $url,
                'plugin_slug' => $slug,
                'position' => $item['order'] ?? 50,
                'category' => $item['category'] ?? 'Plugins',
            ];

            if (isset($item['permission'])) {
                $menuItem['permission'] = $item['permission'];
            }

            // Process inline children array (plugin.json format)
            if (isset($item['children']) && is_array($item['children'])) {
                $menuItem['children'] = [];
                foreach ($item['children'] as $child) {
                    // Resolve child URL
                    $childUrl = $child['url'] ?? null;
                    if (!$childUrl && isset($child['route'])) {
                        $childUrl = "/plugins/{$slug}" . ($child['route'] !== 'index' ? "/{$child['route']}" : '');
                    }
                    if (!$childUrl) {
                        $childUrl = $url . '/' . ($child['id'] ?? 'child');
                    }

                    $childItem = [
                        'id' => $child['id'] ?? "{$slug}-child",
                        'icon' => $child['icon'] ?? 'circle',
                        'label' => $child['label'] ?? 'Item',
                        'url' => $childUrl,
                    ];
                    if (isset($child['permission'])) {
                        $childItem['permission'] = $child['permission'];
                    }
                    $menuItem['children'][] = $childItem;
                }
            } else {
                // Fallback: Find children using parent property pattern
                $children = array_filter($manifestItems, fn($i) => ($i['parent'] ?? null) === $item['id']);
                if (!empty($children)) {
                    $menuItem['children'] = [];
                    foreach ($children as $child) {
                        $childUrl = $child['url'] ?? null;
                        if (!$childUrl && isset($child['route'])) {
                            $childUrl = "/plugins/{$slug}" . ($child['route'] !== 'index' ? "/{$child['route']}" : '');
                        }
                        if (!$childUrl) {
                            $childUrl = $url . '/' . ($child['id'] ?? 'item');
                        }

                        $childItem = [
                            'id' => $child['id'] ?? "{$slug}-child",
                            'icon' => $child['icon'] ?? 'circle',
                            'label' => $child['label'] ?? 'Item',
                            'url' => $childUrl,
                        ];
                        if (isset($child['permission'])) {
                            $childItem['permission'] = $child['permission'];
                        }
                        $menuItem['children'][] = $childItem;
                    }
                }
            }

            $menuItems[] = $menuItem;
        }

        return $menuItems;
    }

    /**
     * Merge plugin menu items into navigation groups.
     */
    protected function mergePluginMenuItems(array $navGroups, array $menuItems, ?Plugin $plugin, string $modulePrefix): array
    {
        $slug = $plugin?->slug ?? 'plugin';

        foreach ($menuItems as $item) {
            // Determine which category to add the item to
            $category = $item['category'] ?? 'Plugins';

            // Check if item has panel restrictions
            if (isset($item['panels']) && !empty($modulePrefix)) {
                if (!in_array($modulePrefix, $item['panels'])) {
                    continue;
                }
            }

            // Resolve URL from route or direct URL
            $url = $this->resolvePluginMenuUrl($item, $slug);

            // Build the navigation item
            $navItem = [
                'id' => $item['id'] ?? "{$slug}-menu",
                'icon' => $item['icon'] ?? 'plug',
                'label' => $item['label'] ?? $plugin?->name ?? 'Plugin',
                'url' => $url,
                'plugin_slug' => $slug,
            ];

            // Add permission if specified
            if (isset($item['permission'])) {
                $navItem['permission'] = $item['permission'];
            }

            // Add position for sorting
            if (isset($item['position'])) {
                $navItem['position'] = $item['position'];
            }

            // Add children if present
            if (isset($item['children']) && is_array($item['children'])) {
                $navItem['children'] = [];
                foreach ($item['children'] as $child) {
                    $childUrl = $this->resolvePluginMenuUrl($child, $slug, $url);
                    
                    $childItem = [
                        'id' => $child['id'] ?? "{$slug}-child",
                        'icon' => $child['icon'] ?? 'circle',
                        'label' => $child['label'] ?? 'Item',
                        'url' => $childUrl,
                    ];
                    if (isset($child['permission'])) {
                        $childItem['permission'] = $child['permission'];
                    }
                    $navItem['children'][] = $childItem;
                }
            }

            // Add to appropriate category
            $this->addItemToCategory($navGroups, $category, $navItem);
        }

        return $navGroups;
    }

    /**
     * Resolve a plugin menu item URL from route name or direct URL.
     */
    protected function resolvePluginMenuUrl(array $item, string $pluginSlug, ?string $parentUrl = null): string
    {
        // If URL is directly provided, use it
        if (isset($item['url'])) {
            return $item['url'];
        }

        // If route name is provided, try to resolve it
        if (isset($item['route'])) {
            $routeName = $item['route'];
            
            try {
                // Try multiple route name patterns
                $routePatterns = [
                    $routeName,                                          // Direct: plugins.ums.users.index
                    "admin.{$routeName}",                                // Admin prefixed: admin.plugins.ums.users.index
                    "console.{$routeName}",                              // Console prefixed
                    "owner.{$routeName}",                                // Owner prefixed
                    "plugins.{$pluginSlug}.{$routeName}",               // Plugin prefixed
                    "admin.plugins.{$pluginSlug}.{$routeName}",         // Admin + plugin prefixed
                ];

                foreach ($routePatterns as $pattern) {
                    if (\Illuminate\Support\Facades\Route::has($pattern)) {
                        return route($pattern);
                    }
                }

                // Fallback: convert route name to URL path
                // e.g., "plugins.ums.users.index" becomes "/plugins/ums/users"
                $parts = explode('.', $routeName);
                
                // Remove common suffixes from the end
                $suffixesToRemove = ['index', 'show', 'list'];
                while (!empty($parts) && in_array(end($parts), $suffixesToRemove)) {
                    array_pop($parts);
                }
                
                // Build URL path
                $path = '/' . implode('/', $parts);
                
                return $path;
            } catch (\Exception $e) {
                \Log::warning("Failed to resolve plugin menu URL", [
                    'route' => $routeName,
                    'plugin' => $pluginSlug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Default fallback
        if ($parentUrl) {
            return $parentUrl . '/' . ($item['id'] ?? 'item');
        }

        return "/plugins/{$pluginSlug}";
    }
}
