@extends('backend.layouts.app', [
    'guard' => 'owner',
    'modulePrefix' => 'owner',
    'brandName' => 'Vodo Owner',
    'version' => 'v.1.0.0',
    'baseUrl' => '',
    'currentPage' => $currentPage ?? 'dashboard',
    'currentPageLabel' => $currentPageLabel ?? 'Dashboard',
    'currentPageIcon' => $currentPageIcon ?? 'layoutDashboard',
    'splashTitle' => 'VODO',
    'splashSubtitle' => 'Owner',
    'splashVersion' => 'Version 1.0.0',
    'splashCopyright' => '© ' . date('Y') . ' VODO Systems',
    'profileUrl' => '/profile',
    'settingsUrl' => '/settings',
    'logoutUrl' => route('owner.logout'),
    'navBoardUrl' => route('owner.navigation-board'),
    'navGroups' => [
        [
            'category' => 'Website management',
            'items' => [
                ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layoutDashboard', 'url' => '/'],
                ['id' => 'sites', 'label' => 'Sites', 'icon' => 'globe2', 'url' => '/sites'],
            ]
        ],
        [
            'category' => 'DB',
            'items' => [
                ['id' => 'databases', 'label' => 'Databases', 'icon' => 'database', 'url' => '/databases'],
                ['id' => 'db-servers', 'label' => 'Database servers', 'icon' => 'server', 'url' => '/db-servers'],
            ]
        ],
        [
            'category' => 'SSL',
            'items' => [
                ['id' => 'ssl-certs', 'label' => 'SSL certificates', 'icon' => 'fileKey', 'url' => '/ssl-certs'],
                ['id' => 'csr', 'label' => 'CSR-requests', 'icon' => 'fileLock', 'url' => '/csr'],
            ]
        ],
        [
            'category' => 'DNS',
            'items' => [
                ['id' => 'dns', 'label' => 'DNS management', 'icon' => 'network', 'url' => '/dns'],
                ['id' => 'slave-servers', 'label' => 'Slave servers', 'icon' => 'serverStack', 'url' => '/slave-servers'],
                ['id' => 'reserved-names', 'label' => 'Reserved names', 'icon' => 'ban', 'url' => '/reserved-names'],
                ['id' => 'technical-domains', 'label' => 'Technical domains', 'icon' => 'network', 'url' => '/technical-domains'],
            ]
        ],
        [
            'category' => 'Tools',
            'items' => [
                ['id' => 'backup', 'label' => 'Backup copies', 'icon' => 'fileArchive', 'url' => '/backup'],
                ['id' => 'file-manager', 'label' => 'File manager', 'icon' => 'folderTree', 'url' => '/file-manager'],
                ['id' => 'cron', 'label' => 'CRON jobs', 'icon' => 'clock', 'url' => '/cron'],
            ]
        ],
        [
            'category' => 'Accounts',
            'items' => [
                ['id' => 'administrators', 'label' => 'Administrators', 'icon' => 'shieldCheck', 'url' => '/administrators'],
                ['id' => 'resellers', 'label' => 'Resellers', 'icon' => 'users', 'url' => '/resellers'],
                ['id' => 'users', 'label' => 'Users', 'icon' => 'user', 'url' => '/users'],
                ['id' => 'ftp-users', 'label' => 'FTP users', 'icon' => 'userCheck', 'url' => '/ftp-users'],
                ['id' => 'templates', 'label' => 'Templates', 'icon' => 'fileCode', 'url' => '/templates'],
                ['id' => 'access-functions', 'label' => 'Access to functions', 'icon' => 'key', 'url' => '/access-functions'],
                ['id' => 'data-import', 'label' => 'Data import', 'icon' => 'fileInput', 'url' => '/data-import'],
            ]
        ],
        [
            'category' => 'Integration',
            'items' => [
                ['id' => 'modules', 'label' => 'Modules', 'icon' => 'package', 'url' => '/modules'],
                ['id' => 'antivirus', 'label' => 'ImunifyAV', 'icon' => 'shield', 'url' => '/antivirus'],
                ['id' => 'extensions', 'label' => 'Extensions', 'icon' => 'plug', 'url' => '/extensions'],
            ]
        ],
        [
            'category' => 'Logs',
            'items' => [
                ['id' => 'action-log', 'label' => 'Action log', 'icon' => 'fileCheck', 'url' => '/action-log'],
                ['id' => 'access-log', 'label' => 'Access log', 'icon' => 'fileBarChart', 'url' => '/access-log'],
                ['id' => 'www-logs', 'label' => 'WWW request logs', 'icon' => 'globe', 'url' => '/www-logs'],
            ]
        ],
        [
            'category' => 'Monitoring',
            'items' => [
                ['id' => 'background-tasks', 'label' => 'Background tasks', 'icon' => 'zap', 'url' => '/background-tasks'],
                ['id' => 'active-sessions', 'label' => 'Active sessions', 'icon' => 'activity', 'url' => '/active-sessions'],
                ['id' => 'active-connections', 'label' => 'Active connections', 'icon' => 'plugZap', 'url' => '/active-connections'],
                ['id' => 'notifications-panel', 'label' => 'Notifications', 'icon' => 'alertCircle', 'url' => '/notifications-panel'],
                ['id' => 'resource-monitoring', 'label' => 'Resource monitoring', 'icon' => 'gauge', 'url' => '/resource-monitoring'],
                ['id' => 'server-resources', 'label' => 'Server resources', 'icon' => 'barChart3', 'url' => '/server-resources'],
            ]
        ],
        [
            'category' => 'Statistics',
            'items' => [
                ['id' => 'limitations', 'label' => 'Limitations', 'icon' => 'ban', 'url' => '/limitations'],
                ['id' => 'user-traffic', 'label' => 'User traffic', 'icon' => 'activity', 'url' => '/user-traffic'],
            ]
        ],
        [
            'category' => 'Web server',
            'items' => [
                ['id' => 'php', 'label' => 'PHP', 'icon' => 'code', 'url' => '/php'],
                ['id' => 'web-scripts', 'label' => 'Web scripts', 'icon' => 'boxes', 'url' => '/web-scripts'],
                ['id' => 'web-server', 'label' => 'Web server settings', 'icon' => 'server', 'url' => '/web-server'],
            ]
        ],
        [
            'category' => 'Manage server',
            'items' => [
                ['id' => 'software-config', 'label' => 'Software configuration', 'icon' => 'settings', 'url' => '/software-config'],
                ['id' => 'ip-addresses', 'label' => 'IP addresses', 'icon' => 'wifi', 'url' => '/ip-addresses'],
                ['id' => 'firewall', 'label' => 'Firewall', 'icon' => 'shieldAlert', 'url' => '/firewall'],
                ['id' => 'services', 'label' => 'Services', 'icon' => 'power', 'url' => '/services'],
                ['id' => 'network-services', 'label' => 'Network services', 'icon' => 'network', 'url' => '/network-services'],
                ['id' => 'system-info', 'label' => 'System information', 'icon' => 'info', 'url' => '/system-info'],
                ['id' => 'system-settings', 'label' => 'System settings', 'icon' => 'settings', 'url' => '/system-settings'],
                ['id' => 'execute-command', 'label' => 'Execute command', 'icon' => 'terminal', 'url' => '/execute-command'],
                ['id' => 'reboot', 'label' => 'Reboot server', 'icon' => 'power', 'url' => '/reboot'],
                ['id' => 'shell', 'label' => 'Shell-client', 'icon' => 'terminal', 'url' => '/shell'],
            ]
        ],
        [
            'category' => 'Panel',
            'items' => [
                ['id' => 'license', 'label' => 'License management', 'icon' => 'fileText', 'url' => '/license'],
                ['id' => 'software-info', 'label' => 'Software info', 'icon' => 'bookOpen', 'url' => '/software-info'],
                ['id' => 'changelog', 'label' => 'Changelog', 'icon' => 'listTree', 'url' => '/changelog'],
                ['id' => 'panel-settings', 'label' => 'Panel settings', 'icon' => 'settings', 'url' => '/panel-settings'],
                ['id' => 'branding', 'label' => 'Branding settings', 'icon' => 'palette', 'url' => '/branding'],
                ['id' => 'email-notifications', 'label' => 'Notifications', 'icon' => 'bellRing', 'url' => '/email-notifications'],
                ['id' => 'logging', 'label' => 'Logging settings', 'icon' => 'fileEdit', 'url' => '/logging'],
                ['id' => 'policies', 'label' => 'Policies', 'icon' => 'fileCheck', 'url' => '/policies'],
            ]
        ],
        [
            'category' => 'Demo Pages',
            'items' => [
                ['id' => 'general', 'label' => 'General Settings', 'icon' => 'settings', 'url' => '/general'],
                ['id' => 'security', 'label' => 'Security & Login', 'icon' => 'lock', 'url' => '/security'],
                ['id' => 'language', 'label' => 'Language & Region', 'icon' => 'globe', 'url' => '/language'],
                ['id' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'url' => '/notifications'],
                ['id' => 'connected', 'label' => 'Connected Apps', 'icon' => 'plug', 'url' => '/connected'],
                ['id' => 'designsystem', 'label' => 'Design System', 'icon' => 'code2', 'url' => '/designsystem'],
                ['id' => 'crud', 'label' => 'Crud System', 'icon' => 'database', 'url' => '/crud'],
            ]
        ],
    ],
])

@section('title')
    @yield('page-title', 'Dashboard')
@endsection

@section('header')
    @yield('page-header', 'Dashboard')
@endsection

@section('content')
    @yield('page-content')
@endsection

@section('command-bar')
    @yield('page-command-bar')
@endsection

@section('header-actions')
    @yield('page-header-actions')
@endsection
