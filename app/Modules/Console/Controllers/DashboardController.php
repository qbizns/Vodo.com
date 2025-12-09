<?php

namespace App\Modules\Console\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Get the full navigation groups for the navigation board
     */
    protected function getAllNavGroups(): array
    {
        return [
            [
                'category' => 'Website management',
                'items' => [
                    ['id' => 'dashboard', 'icon' => 'layoutDashboard', 'label' => 'Dashboard'],
                    ['id' => 'sites', 'icon' => 'globe2', 'label' => 'Sites'],
                ]
            ],
            [
                'category' => 'DB',
                'items' => [
                    ['id' => 'databases', 'icon' => 'database', 'label' => 'Databases'],
                    ['id' => 'db-servers', 'icon' => 'server', 'label' => 'Database servers'],
                ]
            ],
            [
                'category' => 'SSL',
                'items' => [
                    ['id' => 'ssl-certs', 'icon' => 'fileKey', 'label' => 'SSL certificates'],
                    ['id' => 'csr', 'icon' => 'fileLock', 'label' => 'CSR-requests'],
                ]
            ],
            [
                'category' => 'DNS',
                'items' => [
                    ['id' => 'dns', 'icon' => 'network', 'label' => 'DNS management'],
                    ['id' => 'slave-servers', 'icon' => 'serverStack', 'label' => 'Slave servers'],
                    ['id' => 'reserved-names', 'icon' => 'ban', 'label' => 'Reserved names'],
                    ['id' => 'technical-domains', 'icon' => 'network', 'label' => 'Technical domains'],
                ]
            ],
            [
                'category' => 'Tools',
                'items' => [
                    ['id' => 'backup', 'icon' => 'fileArchive', 'label' => 'Backup copies'],
                    ['id' => 'file-manager', 'icon' => 'folderTree', 'label' => 'File manager'],
                    ['id' => 'cron', 'icon' => 'clock', 'label' => 'CRON jobs'],
                ]
            ],
            [
                'category' => 'Accounts',
                'items' => [
                    ['id' => 'administrators', 'icon' => 'shieldCheck', 'label' => 'Administrators'],
                    ['id' => 'resellers', 'icon' => 'users', 'label' => 'Resellers'],
                    ['id' => 'users', 'icon' => 'user', 'label' => 'Users'],
                    ['id' => 'ftp-users', 'icon' => 'userCheck', 'label' => 'FTP users'],
                    ['id' => 'templates', 'icon' => 'fileCode', 'label' => 'Templates'],
                    ['id' => 'access-functions', 'icon' => 'key', 'label' => 'Access to functions'],
                    ['id' => 'data-import', 'icon' => 'fileInput', 'label' => 'Data import'],
                ]
            ],
            [
                'category' => 'Integration',
                'items' => [
                    ['id' => 'modules', 'icon' => 'package', 'label' => 'Modules'],
                    ['id' => 'antivirus', 'icon' => 'shield', 'label' => 'ImunifyAV'],
                    ['id' => 'extensions', 'icon' => 'plug', 'label' => 'Extensions'],
                ]
            ],
            [
                'category' => 'Logs',
                'items' => [
                    ['id' => 'action-log', 'icon' => 'fileCheck', 'label' => 'Action log'],
                    ['id' => 'access-log', 'icon' => 'fileBarChart', 'label' => 'Access log'],
                    ['id' => 'www-logs', 'icon' => 'globe', 'label' => 'WWW request logs'],
                ]
            ],
            [
                'category' => 'Monitoring',
                'items' => [
                    ['id' => 'background-tasks', 'icon' => 'zap', 'label' => 'Background tasks'],
                    ['id' => 'active-sessions', 'icon' => 'activity', 'label' => 'Active sessions'],
                    ['id' => 'active-connections', 'icon' => 'plugZap', 'label' => 'Active connections'],
                    ['id' => 'notifications-panel', 'icon' => 'alertCircle', 'label' => 'Notifications'],
                    ['id' => 'resource-monitoring', 'icon' => 'gauge', 'label' => 'Resource monitoring'],
                    ['id' => 'server-resources', 'icon' => 'barChart3', 'label' => 'Server resources'],
                ]
            ],
            [
                'category' => 'Statistics',
                'items' => [
                    ['id' => 'limitations', 'icon' => 'ban', 'label' => 'Limitations'],
                    ['id' => 'user-traffic', 'icon' => 'activity', 'label' => 'User traffic'],
                ]
            ],
            [
                'category' => 'Web server',
                'items' => [
                    ['id' => 'php', 'icon' => 'code', 'label' => 'PHP'],
                    ['id' => 'web-scripts', 'icon' => 'boxes', 'label' => 'Web scripts'],
                    ['id' => 'web-server', 'icon' => 'server', 'label' => 'Web server settings'],
                ]
            ],
            [
                'category' => 'Manage server',
                'items' => [
                    ['id' => 'software-config', 'icon' => 'settings', 'label' => 'Software configuration'],
                    ['id' => 'ip-addresses', 'icon' => 'wifi', 'label' => 'IP addresses'],
                    ['id' => 'firewall', 'icon' => 'shieldAlert', 'label' => 'Firewall'],
                    ['id' => 'services', 'icon' => 'power', 'label' => 'Services'],
                    ['id' => 'network-services', 'icon' => 'network', 'label' => 'Network services'],
                    ['id' => 'system-info', 'icon' => 'info', 'label' => 'System information'],
                    ['id' => 'system-settings', 'icon' => 'settings', 'label' => 'System settings'],
                    ['id' => 'execute-command', 'icon' => 'terminal', 'label' => 'Execute command'],
                    ['id' => 'reboot', 'icon' => 'power', 'label' => 'Reboot server'],
                    ['id' => 'shell', 'icon' => 'terminal', 'label' => 'Shell-client'],
                ]
            ],
            [
                'category' => 'Panel',
                'items' => [
                    ['id' => 'license', 'icon' => 'fileText', 'label' => 'License management'],
                    ['id' => 'software-info', 'icon' => 'bookOpen', 'label' => 'Software info'],
                    ['id' => 'changelog', 'icon' => 'listTree', 'label' => 'Changelog'],
                    ['id' => 'panel-settings', 'icon' => 'settings', 'label' => 'Panel settings'],
                    ['id' => 'branding', 'icon' => 'palette', 'label' => 'Branding settings'],
                    ['id' => 'email-notifications', 'icon' => 'bellRing', 'label' => 'Notifications'],
                    ['id' => 'logging', 'icon' => 'fileEdit', 'label' => 'Logging settings'],
                    ['id' => 'policies', 'icon' => 'fileCheck', 'label' => 'Policies'],
                ]
            ],
            [
                'category' => 'Demo Pages',
                'items' => [
                    ['id' => 'general', 'icon' => 'settings', 'label' => 'General Settings'],
                    ['id' => 'security', 'icon' => 'lock', 'label' => 'Security & Login'],
                    ['id' => 'language', 'icon' => 'globe', 'label' => 'Language & Region'],
                    ['id' => 'notifications', 'icon' => 'bell', 'label' => 'Notifications'],
                    ['id' => 'connected', 'icon' => 'plug', 'label' => 'Connected Apps'],
                    ['id' => 'designsystem', 'icon' => 'code2', 'label' => 'Design System'],
                    ['id' => 'crud', 'icon' => 'database', 'label' => 'Crud System'],
                ]
            ],
        ];
    }

    public function index()
    {
        return view('console::dashboard');
    }

    public function navigationBoard()
    {
        return view('console::navigation-board', [
            'allNavGroups' => $this->getAllNavGroups(),
            'visibleItems' => ['dashboard', 'sites', 'databases'], // Default visible items
        ]);
    }

    public function placeholder(string $page)
    {
        // Convert slug to title
        $pageTitle = ucwords(str_replace('-', ' ', $page));
        
        return view('console::placeholder', [
            'pageSlug' => $page,
            'pageTitle' => $pageTitle,
        ]);
    }
}
