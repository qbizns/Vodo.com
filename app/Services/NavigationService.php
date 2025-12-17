<?php

namespace App\Services;

use App\Services\Plugins\HookManager;

class NavigationService
{
    /**
     * The hook manager instance.
     */
    protected HookManager $hooks;

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

        // Apply plugin navigation_items filter
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

                // Ensure URL is set if not provided
                if (!isset($item['url'])) {
                    $item['url'] = '/' . $item['id'];
                }

                // Resolve children recursively
                if (isset($item['children']) && is_array($item['children'])) {
                    foreach ($item['children'] as &$child) {
                        if (isset($child['badge']) && is_callable($child['badge'])) {
                            $child['badge'] = call_user_func($child['badge']);
                        }
                        if (!isset($child['url'])) {
                            $child['url'] = $item['url'] . '/' . ($child['route'] ?? $child['id']);
                        }
                    }
                }
            }
        }

        return $navGroups;
    }

    /**
     * Get the base navigation groups for the system.
     */
    public function getBaseNavGroups(string $modulePrefix = ''): array
    {
        return [
            [
                'category' => 'Website management',
                'items' => [
                    ['id' => 'dashboard', 'icon' => 'layoutDashboard', 'label' => 'Dashboard', 'url' => '/'],
                    ['id' => 'sites', 'icon' => 'globe2', 'label' => 'Sites', 'url' => '/sites'],
                ]
            ],
            [
                'category' => 'DB',
                'items' => [
                    ['id' => 'databases', 'icon' => 'database', 'label' => 'Databases', 'url' => '/databases'],
                    ['id' => 'db-servers', 'icon' => 'server', 'label' => 'Database servers', 'url' => '/db-servers'],
                ]
            ],
            [
                'category' => 'SSL',
                'items' => [
                    ['id' => 'ssl-certs', 'icon' => 'fileKey', 'label' => 'SSL certificates', 'url' => '/ssl-certs'],
                    ['id' => 'csr', 'icon' => 'fileLock', 'label' => 'CSR-requests', 'url' => '/csr'],
                ]
            ],
            [
                'category' => 'DNS',
                'items' => [
                    ['id' => 'dns', 'icon' => 'network', 'label' => 'DNS management', 'url' => '/dns'],
                    ['id' => 'slave-servers', 'icon' => 'serverStack', 'label' => 'Slave servers', 'url' => '/slave-servers'],
                    ['id' => 'reserved-names', 'icon' => 'ban', 'label' => 'Reserved names', 'url' => '/reserved-names'],
                    ['id' => 'technical-domains', 'icon' => 'network', 'label' => 'Technical domains', 'url' => '/technical-domains'],
                ]
            ],
            [
                'category' => 'Tools',
                'items' => [
                    ['id' => 'backup', 'icon' => 'fileArchive', 'label' => 'Backup copies', 'url' => '/backup'],
                    ['id' => 'file-manager', 'icon' => 'folderTree', 'label' => 'File manager', 'url' => '/file-manager'],
                    ['id' => 'cron', 'icon' => 'clock', 'label' => 'CRON jobs', 'url' => '/cron'],
                ]
            ],
            [
                'category' => 'Accounts',
                'items' => [
                    ['id' => 'administrators', 'icon' => 'shieldCheck', 'label' => 'Administrators', 'url' => '/administrators'],
                    ['id' => 'resellers', 'icon' => 'users', 'label' => 'Resellers', 'url' => '/resellers'],
                    ['id' => 'users', 'icon' => 'user', 'label' => 'Users', 'url' => '/users'],
                    ['id' => 'ftp-users', 'icon' => 'userCheck', 'label' => 'FTP users', 'url' => '/ftp-users'],
                    ['id' => 'templates', 'icon' => 'fileCode', 'label' => 'Templates', 'url' => '/templates'],
                    ['id' => 'access-functions', 'icon' => 'key', 'label' => 'Access to functions', 'url' => '/access-functions'],
                    ['id' => 'data-import', 'icon' => 'fileInput', 'label' => 'Data import', 'url' => '/data-import'],
                ]
            ],
            [
                'category' => 'Integration',
                'items' => [
                    ['id' => 'modules', 'icon' => 'package', 'label' => 'Modules', 'url' => '/modules'],
                    ['id' => 'antivirus', 'icon' => 'shield', 'label' => 'ImunifyAV', 'url' => '/antivirus'],
                    ['id' => 'extensions', 'icon' => 'plug', 'label' => 'Extensions', 'url' => '/extensions'],
                ]
            ],
            [
                'category' => 'Logs',
                'items' => [
                    ['id' => 'action-log', 'icon' => 'fileCheck', 'label' => 'Action log', 'url' => '/action-log'],
                    ['id' => 'access-log', 'icon' => 'fileBarChart', 'label' => 'Access log', 'url' => '/access-log'],
                    ['id' => 'www-logs', 'icon' => 'globe', 'label' => 'WWW request logs', 'url' => '/www-logs'],
                ]
            ],
            [
                'category' => 'Monitoring',
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
                'items' => [
                    ['id' => 'limitations', 'icon' => 'ban', 'label' => 'Limitations', 'url' => '/limitations'],
                    ['id' => 'user-traffic', 'icon' => 'activity', 'label' => 'User traffic', 'url' => '/user-traffic'],
                ]
            ],
            [
                'category' => 'Web server',
                'items' => [
                    ['id' => 'php', 'icon' => 'code', 'label' => 'PHP', 'url' => '/php'],
                    ['id' => 'web-scripts', 'icon' => 'boxes', 'label' => 'Web scripts', 'url' => '/web-scripts'],
                    ['id' => 'web-server', 'icon' => 'server', 'label' => 'Web server settings', 'url' => '/web-server'],
                ]
            ],
            [
                'category' => 'Manage server',
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
            [
                'category' => 'Demo Pages',
                'items' => [
                    ['id' => 'general', 'icon' => 'settings', 'label' => 'General Settings', 'url' => '/general'],
                    ['id' => 'security', 'icon' => 'lock', 'label' => 'Security & Login', 'url' => '/security'],
                    ['id' => 'language', 'icon' => 'globe', 'label' => 'Language & Region', 'url' => '/language'],
                    ['id' => 'notifications', 'icon' => 'bell', 'label' => 'Notifications', 'url' => '/notifications'],
                    ['id' => 'connected', 'icon' => 'plug', 'label' => 'Connected Apps', 'url' => '/connected'],
                    ['id' => 'designsystem', 'icon' => 'code2', 'label' => 'Design System', 'url' => '/designsystem'],
                    ['id' => 'crud', 'icon' => 'database', 'label' => 'Crud System', 'url' => '/crud'],
                ]
            ],
            [
                'category' => 'System',
                'items' => [
                    ['id' => 'system/settings', 'icon' => 'settings', 'label' => 'Settings', 'url' => '/system/settings'],
                    ['id' => 'system/plugins', 'icon' => 'plug', 'label' => 'Plugins Management', 'url' => '/system/plugins'],
                ]
            ],
        ];
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
}
