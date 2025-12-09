/**
 * KERNEL Platform - jQuery Application
 * Main JavaScript file containing all application logic
 */

// ============================================
// Configuration
// ============================================

const API_BASE_URL = 'http://localhost:1498';

// ============================================
// Application State
// ============================================

const AppState = {
    currentRoute: '',
    isSidebarCollapsed: false,
    darkMode: localStorage.getItem('darkMode') === 'true',
    openTabs: [{ id: 'dashboard', label: 'Dashboard', icon: 'home', path: '/dashboard', closable: false }],
    activeTabId: 'dashboard',
    notifications: [
        { id: 1, type: 'warning', title: 'Server Maintenance', message: 'Scheduled maintenance in 2 hours. Please save your work.', time: '2 minutes ago', unread: true },
        { id: 2, type: 'success', title: 'Backup Complete', message: 'All databases have been successfully backed up.', time: '1 hour ago', unread: true },
        { id: 3, type: 'info', title: 'SSL Certificate Renewed', message: 'Your SSL certificate has been automatically renewed.', time: '3 hours ago', unread: false }
    ],
    notificationTab: 'all',
    dashboardSlideIndex: 0,
    widgetOrder: ['resources', 'system', 'changelog', 'tasks', 'sessions', 'logs'],
    visibleWidgets: { resources: true, system: true, changelog: true, tasks: true, sessions: true, logs: true }
};

// ============================================
// Navigation Configuration
// ============================================

const navGroups = [
    {
        category: 'Website management',
        items: [
            { id: 'dashboard', icon: 'layoutDashboard', label: 'Dashboard' },
            { id: 'sites', icon: 'globe2', label: 'Sites' }
        ]
    },
    {
        category: 'DB',
        items: [
            { id: 'databases', icon: 'database', label: 'Databases' },
            { id: 'db-servers', icon: 'server', label: 'Database servers' }
        ]
    },
    {
        category: 'SSL',
        items: [
            { id: 'ssl-certs', icon: 'fileKey', label: 'SSL certificates' },
            { id: 'csr', icon: 'fileLock', label: 'CSR-requests' }
        ]
    },
    {
        category: 'DNS',
        items: [
            { id: 'dns', icon: 'network', label: 'DNS management' },
            { id: 'slave-servers', icon: 'serverStack', label: 'Slave servers' },
            { id: 'reserved-names', icon: 'ban', label: 'Reserved names' },
            { id: 'technical-domains', icon: 'network', label: 'Technical domains' }
        ]
    },
    {
        category: 'Tools',
        items: [
            { id: 'backup', icon: 'fileArchive', label: 'Backup copies' },
            { id: 'file-manager', icon: 'folderTree', label: 'File manager' },
            { id: 'cron', icon: 'clock', label: 'CRON jobs' }
        ]
    },
    {
        category: 'Accounts',
        items: [
            { id: 'administrators', icon: 'shieldCheck', label: 'Administrators' },
            { id: 'resellers', icon: 'users', label: 'Resellers' },
            { id: 'users', icon: 'user', label: 'Users' },
            { id: 'ftp-users', icon: 'userCheck', label: 'FTP users' },
            { id: 'templates', icon: 'fileCode', label: 'Templates' },
            { id: 'access-functions', icon: 'key', label: 'Access to functions' },
            { id: 'data-import', icon: 'fileInput', label: 'Data import' }
        ]
    },
    {
        category: 'Integration',
        items: [
            { id: 'modules', icon: 'package', label: 'Modules' },
            { id: 'antivirus', icon: 'shield', label: 'ImunifyAV' },
            { id: 'extensions', icon: 'plug', label: 'Extensions' }
        ]
    },
    {
        category: 'Logs',
        items: [
            { id: 'action-log', icon: 'fileCheck', label: 'Action log' },
            { id: 'access-log', icon: 'fileBarChart', label: 'Access log' },
            { id: 'www-logs', icon: 'globe', label: 'WWW request logs' }
        ]
    },
    {
        category: 'Monitoring',
        items: [
            { id: 'background-tasks', icon: 'zap', label: 'Background tasks' },
            { id: 'active-sessions', icon: 'activity', label: 'Active sessions' },
            { id: 'active-connections', icon: 'plugZap', label: 'Active connections' },
            { id: 'notifications-panel', icon: 'alertCircle', label: 'Notifications' },
            { id: 'resource-monitoring', icon: 'gauge', label: 'Resource monitoring' },
            { id: 'server-resources', icon: 'barChart3', label: 'Server resources' }
        ]
    },
    {
        category: 'Statistics',
        items: [
            { id: 'limitations', icon: 'ban', label: 'Limitations' },
            { id: 'user-traffic', icon: 'activity', label: 'User traffic' }
        ]
    },
    {
        category: 'Web server',
        items: [
            { id: 'php', icon: 'code', label: 'PHP' },
            { id: 'web-scripts', icon: 'boxes', label: 'Web scripts' },
            { id: 'web-server', icon: 'server', label: 'Web server settings' }
        ]
    },
    {
        category: 'Manage server',
        items: [
            { id: 'software-config', icon: 'settings', label: 'Software configuration' },
            { id: 'ip-addresses', icon: 'wifi', label: 'IP addresses' },
            { id: 'firewall', icon: 'shieldAlert', label: 'Firewall' },
            { id: 'services', icon: 'power', label: 'Services' },
            { id: 'network-services', icon: 'network', label: 'Network services' },
            { id: 'system-info', icon: 'info', label: 'System information' },
            { id: 'system-settings', icon: 'settings', label: 'System settings' },
            { id: 'execute-command', icon: 'terminal', label: 'Execute command' },
            { id: 'reboot', icon: 'power', label: 'Reboot server' },
            { id: 'shell', icon: 'terminal', label: 'Shell-client' }
        ]
    },
    {
        category: 'Panel',
        items: [
            { id: 'license', icon: 'fileText', label: 'License management' },
            { id: 'software-info', icon: 'bookOpen', label: 'Software info' },
            { id: 'changelog', icon: 'listTree', label: 'Changelog' },
            { id: 'panel-settings', icon: 'settings', label: 'Panel settings' },
            { id: 'branding', icon: 'palette', label: 'Branding settings' },
            { id: 'email-notifications', icon: 'bellRing', label: 'Notifications' },
            { id: 'logging', icon: 'fileEdit', label: 'Logging settings' },
            { id: 'policies', icon: 'fileCheck', label: 'Policies' }
        ]
    },
    {
        category: 'Demo Pages',
        items: [
            { id: 'general', icon: 'settings', label: 'General Settings' },
            { id: 'security', icon: 'lock', label: 'Security & Login' },
            { id: 'language', icon: 'globe', label: 'Language & Region' },
            { id: 'notifications', icon: 'bell', label: 'Notifications' },
            { id: 'connected', icon: 'plug', label: 'Connected Apps' },
            { id: 'designsystem', icon: 'code2', label: 'Design System' },
            { id: 'crud', icon: 'database', label: 'Crud System' }
        ]
    }
];

// ============================================
// SVG Icons
// ============================================

const Icons = {
    home: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
    globe: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
    database: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>',
    cloud: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path></svg>',
    settings: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
    users: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    server: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>',
    cpu: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>',
    memory: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><path d="M6 8h.001"></path><path d="M10 8h.001"></path><path d="M14 8h.001"></path><path d="M18 8h.001"></path><path d="M6 12h.001"></path><path d="M10 12h.001"></path><path d="M14 12h.001"></path><path d="M18 12h.001"></path><path d="M6 16h.001"></path><path d="M10 16h.001"></path><path d="M14 16h.001"></path><path d="M18 16h.001"></path></svg>',
    hardDrive: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="12" x2="2" y2="12"></line><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path><line x1="6" y1="16" x2="6.01" y2="16"></line><line x1="10" y1="16" x2="10.01" y2="16"></line></svg>',
    monitor: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>',
    info: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    fileText: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
    list: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
    users2: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    shield: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
    chevronLeft: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>',
    chevronRight: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>',
    gripVertical: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>',
    refresh: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>',
    eyeOff: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>',
    externalLink: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>',
    alertTriangle: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    check: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    plus: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    search: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
    externalLinkAlt: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>',
    edit: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    trash: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    moreVertical: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>',
    play: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>',
    zap: '<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>',
    star: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
    file: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>',
    layoutDashboard: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
    globe2: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>',
    fileKey: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"></path><circle cx="12" cy="12" r="3"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path></svg>',
    fileLock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="18" rx="2" ry="2"></rect><path d="M8 2v4M16 2v4M4 10h16M10 16h4"></path></svg>',
    network: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="16" y="16" width="6" height="6" rx="1"></rect><rect x="2" y="16" width="6" height="6" rx="1"></rect><rect x="9" y="2" width="6" height="6" rx="1"></rect><path d="M5 16v-6a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v6M12 12V8"></path></svg>',
    serverStack: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="6" rx="1"></rect><rect x="2" y="13" width="20" height="6" rx="1"></rect><line x1="6" y1="5" x2="6.01" y2="5"></line><line x1="6" y1="13" x2="6.01" y2="13"></line></svg>',
    ban: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>',
    fileArchive: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="8" x2="21" y2="8"></line><line x1="9" y1="12" x2="9" y2="22"></line><line x1="15" y1="12" x2="15" y2="22"></line></svg>',
    folderTree: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><path d="M13 2v7h7"></path></svg>',
    clock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    shieldCheck: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 11 12 14 22 4"></polyline></svg>',
    user: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
    userCheck: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>',
    fileCode: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><polyline points="10 13 8 15 10 17"></polyline><polyline points="14 13 16 15 14 17"></polyline></svg>',
    key: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>',
    fileInput: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7l-5 5v11a2 2 0 0 0 2 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>',
    package: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
    plug: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-5M9 8V2M15 8V2M6 12H2a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h4M18 12h4a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-4M6 12a2 2 0 0 0-2-2H2M18 12a2 2 0 0 1 2-2h4"></path></svg>',
    fileCheck: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><polyline points="9 15 12 18 22 8"></polyline></svg>',
    fileBarChart: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>',
    activity: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
    plugZap: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.3 20.3a2.4 2.4 0 0 0 3.4 0L12 18l-2.3-2.3a2.4 2.4 0 0 0-3.4 0l-.8.8a2.4 2.4 0 0 1-3.4 0l-.8-.8a2.4 2.4 0 0 1 0-3.4l.8-.8a2.4 2.4 0 0 0 0-3.4l.8-.8a2.4 2.4 0 0 1 3.4 0l.8.8a2.4 2.4 0 0 0 3.4 0L12 6l2.3 2.3a2.4 2.4 0 0 0 3.4 0l.8-.8a2.4 2.4 0 0 1 3.4 0l.8.8a2.4 2.4 0 0 1 0 3.4l-.8.8a2.4 2.4 0 0 0 0 3.4l.8.8a2.4 2.4 0 0 1 0 3.4l-.8.8a2.4 2.4 0 0 1-3.4 0l-.8-.8a2.4 2.4 0 0 0-3.4 0z"></path></svg>',
    alertCircle: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
    gauge: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v10M17.2 20a9 9 0 1 0-10.4 0"></path><path d="M12 12a9 9 0 0 1 9 9"></path><path d="M12 12a9 9 0 0 0-9 9"></path></svg>',
    barChart3: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>',
    code: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',
    boxes: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
    wifi: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>',
    shieldAlert: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
    power: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>',
    terminal: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>',
    bookOpen: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>',
    listTree: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2"></path><path d="M21 18v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2"></path><path d="M3 6h18"></path><path d="M7 12h.01M7 18h.01"></path></svg>',
    palette: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z"></path></svg>',
    bellRing: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path><path d="M18 8a6 6 0 0 0-12 0"></path><path d="M20.27 14C19.54 13.37 19 12.47 19 11.33a6.83 6.83 0 0 1 .81-3.12"></path><path d="M3.73 14c.73.63 1.27 1.53 1.27 2.67 0 1.13-.27 2.17-.74 3.07"></path></svg>',
    fileEdit: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    lock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
    bell: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
    code2: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>'
};

// ============================================
// Auth Service
// ============================================

const AuthService = {
    getToken() {
        return localStorage.getItem('token');
    },

    setToken(token) {
        localStorage.setItem('token', token);
    },

    removeToken() {
        localStorage.removeItem('token');
    },

    getUser() {
        try {
            const user = localStorage.getItem('user');
            return user ? JSON.parse(user) : null;
        } catch (e) {
            return null;
        }
    },

    setUser(user) {
        localStorage.setItem('user', JSON.stringify(user));
    },

    removeUser() {
        localStorage.removeItem('user');
    },

    isAuthenticated() {
        return !!this.getToken();
    },

    getUserInitials() {
        const user = this.getUser();
        if (!user || !user.name) return 'U';
        const names = user.name.split(' ');
        if (names.length >= 2) {
            return (names[0][0] + names[names.length - 1][0]).toUpperCase();
        }
        return user.name.substring(0, 2).toUpperCase();
    },

    getUserDisplayName() {
        const user = this.getUser();
        return user?.name || 'User';
    },

    getUserEmail() {
        const user = this.getUser();
        return user?.email || 'user@example.com';
    },

    getUserShortName() {
        const name = this.getUserDisplayName();
        const parts = name.split(' ');
        if (parts.length > 1) {
            return parts[0] + ' ' + parts[parts.length - 1][0] + '.';
        }
        return name;
    },

    logout() {
        this.removeToken();
        this.removeUser();
        window.location.href = 'login.html';
    },

    async login(email, password) {
        const response = await $.ajax({
            url: `${API_BASE_URL}/api/auth/login`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ email, password, tenant_slug: 'macber' })
        });

        if (response.token) {
            this.setToken(response.token);
            if (response.user) {
                this.setUser(response.user);
            }
        }

        return response;
    }
};

// ============================================
// Router
// ============================================

const Router = {
    routes: {
        '/dashboard': { title: 'Dashboard', render: renderDashboard },
        '/sites': { title: 'Sites', render: renderSitesPage },
        '/databases': { title: 'Databases', render: renderDatabasesPage },
        '/dns': { title: 'DNS', render: renderPlaceholderPage },
        '/settings': { title: 'Settings', render: renderPlaceholderPage },
        '/users': { title: 'Users', render: renderPlaceholderPage },
        '/profile': { title: 'My Profile', render: renderPlaceholderPage },
        '/navigation-board': { title: 'Navigation Board', render: renderNavigationBoardPage }
    },

    init() {
        // Check auth
        if (!AuthService.isAuthenticated()) {
            window.location.href = 'login.html';
            return;
        }

        // Listen for hash changes
        $(window).on('hashchange', () => this.handleRoute());

        // Initial route
        this.handleRoute();
    },

    navigate(path) {
        window.location.hash = path;
    },

    getCurrentPath() {
        return window.location.hash.slice(1) || '/dashboard';
    },

    handleRoute() {
        const path = this.getCurrentPath();
        AppState.currentRoute = path;

        const route = this.routes[path] || { title: 'Not Found', render: renderPlaceholderPage };

        // Update page title
        $('#pageTitle').text(route.title);
        document.title = `${route.title} - KERNEL Platform`;

        // Render page content
        route.render(path);

        // Update active nav item
        updateActiveNavItem(path);

        // Add tab if not exists
        addTabForRoute(path, route.title);
    }
};

// ============================================
// Tab Management
// ============================================

function addTabForRoute(path, title) {
    const pageId = path.replace('/', '');

    // Check if tab already exists
    const existingTab = AppState.openTabs.find(t => t.id === pageId);
    if (existingTab) {
        AppState.activeTabId = pageId;
        renderTabs();
        return;
    }

    // Get icon for the route
    let icon = 'home';
    for (const group of navGroups) {
        const item = group.items.find(i => i.id === pageId);
        if (item) {
            icon = item.icon;
            break;
        }
    }

    // Add new tab
    AppState.openTabs.push({
        id: pageId,
        label: title,
        icon: icon,
        path: path,
        closable: pageId !== 'dashboard'
    });

    AppState.activeTabId = pageId;
    renderTabs();
}

function closeTab(tabId) {
    if (tabId === 'dashboard') return; // Can't close dashboard

    const tabIndex = AppState.openTabs.findIndex(t => t.id === tabId);
    if (tabIndex === -1) return;

    AppState.openTabs.splice(tabIndex, 1);

    // If closing active tab, switch to previous tab
    if (AppState.activeTabId === tabId) {
        const newIndex = Math.min(tabIndex, AppState.openTabs.length - 1);
        const newTab = AppState.openTabs[newIndex];
        AppState.activeTabId = newTab.id;
        Router.navigate(newTab.path);
    }

    renderTabs();
}

function renderTabs() {
    const $container = $('#tabsContainer');
    const tabCount = AppState.openTabs.length;

    // Determine compact mode based on tab count
    let tabClass = '';
    if (tabCount > 8) {
        tabClass = 'tabs-icon-only';
    } else if (tabCount > 5) {
        tabClass = 'tabs-compact';
    }

    $container.removeClass('tabs-compact tabs-icon-only').addClass(tabClass);

    $container.empty();

    AppState.openTabs.forEach(tab => {
        const isActive = tab.id === AppState.activeTabId;
        const icon = Icons[tab.icon] || Icons.home;

        const $tab = $(`
            <div class="tab-item ${isActive ? 'active' : ''}" data-tab-id="${tab.id}">
                <button class="tab-btn" data-path="${tab.path}">
                    ${icon}
                    <span>${tab.label}</span>
                </button>
                <button class="tab-close-btn ${tab.closable ? '' : 'disabled'}" data-tab-id="${tab.id}">×</button>
            </div>
        `);

        $container.append($tab);
    });
}

// ============================================
// Sidebar Navigation
// ============================================

function getVisibleNavItems() {
    // Load visible nav items from localStorage
    let visibleNavItems = new Set(['dashboard', 'sites', 'databases']);
    try {
        const saved = localStorage.getItem('visibleNavItems');
        if (saved) {
            visibleNavItems = new Set(JSON.parse(saved));
        }
    } catch (e) {}
    return visibleNavItems;
}

function renderSidebar() {
    const $nav = $('#sidebarNav');
    $nav.empty();

    const visibleNavItems = getVisibleNavItems();
    let hasVisibleItems = false;

    navGroups.forEach((group, groupIndex) => {
        // Filter items by visibility
        const visibleItems = group.items.filter(item => visibleNavItems.has(item.id));
        
        // Skip group if no visible items
        if (visibleItems.length === 0) {
            return;
        }

        hasVisibleItems = true;

        // Category divider
        if (groupIndex > 0 || group.category) {
            $nav.append(`
                <div class="nav-divider">
                    <span class="nav-category">${group.category}</span>
                    <div class="nav-divider-line"></div>
                </div>
            `);
        }

        // Navigation items (only visible ones)
        visibleItems.forEach(item => {
            const icon = Icons[item.icon] || Icons.home;
            const path = `/${item.id}`;
            const isActive = AppState.currentRoute === path;

            $nav.append(`
                <a href="#${path}" class="nav-item ${isActive ? 'active' : ''}" data-nav-id="${item.id}">
                    ${icon}
                    <span>${item.label}</span>
                </a>
            `);
        });
    });

    // Show message if no items visible
    if (!hasVisibleItems) {
        $nav.append(`
            <div style="padding: var(--spacing-4); text-align: center; color: var(--text-secondary); font-size: var(--text-caption);">
                No navigation items visible. Use Navigation Board to enable items.
            </div>
        `);
    }
}

function updateActiveNavItem(path) {
    const pageId = path.replace('/', '');
    $('.nav-item').removeClass('active');
    $(`.nav-item[data-nav-id="${pageId}"]`).addClass('active');
}

// ============================================
// Notifications
// ============================================

function renderNotifications() {
    const $list = $('#notificationList');
    $list.empty();

    const notifications = AppState.notificationTab === 'all'
        ? AppState.notifications
        : AppState.notifications.filter(n => !n.unread);

    if (notifications.length === 0) {
        $list.html(`
            <div class="notification-empty">
                <div class="notification-empty-icon">📭</div>
                <div>${AppState.notificationTab === 'all' ? 'No notifications' : 'No archived notifications'}</div>
            </div>
        `);
        $('#notificationFooter').hide();
        return;
    }

    const hasUnread = notifications.some(n => n.unread);
    $('#notificationFooter').toggle(hasUnread);

    notifications.forEach(notification => {
        let iconClass = 'info';
        let icon = Icons.info;

        if (notification.type === 'warning') {
            iconClass = 'warning';
            icon = Icons.alertTriangle;
        } else if (notification.type === 'success') {
            iconClass = 'success';
            icon = Icons.check;
        }

        $list.append(`
            <div class="notification-item ${notification.unread ? 'unread' : ''}" data-id="${notification.id}">
                <div class="flex" style="gap: var(--spacing-3);">
                    <div class="notification-icon ${iconClass}">
                        ${icon}
                        <div class="notification-unread-dot"></div>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div class="notification-time">${notification.time}</div>
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                    </div>
                </div>
            </div>
        `);
    });
}

function updateNotificationBadge() {
    const hasUnread = AppState.notifications.some(n => n.unread);
    $('#notificationBadge').toggleClass('has-unread', hasUnread);
}

// ============================================
// User Profile
// ============================================

function updateUserProfile() {
    const initials = AuthService.getUserInitials();
    const displayName = AuthService.getUserDisplayName();
    const shortName = AuthService.getUserShortName();
    const email = AuthService.getUserEmail();

    $('#userAvatar').text(initials);
    $('#userAvatarLarge').text(initials);
    $('#userShortName').text(shortName);
    $('#userDisplayName').text(displayName);
    $('#userEmail').text(email);
}

// ============================================
// Dashboard Page
// ============================================

function renderDashboard() {
    const slides = [
        {
            title: 'Server Management',
            description: 'Manage your web servers with ease. Monitor performance, configure settings, and deploy applications seamlessly.',
            iconBg: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            icon: Icons.server
        },
        {
            title: 'Database Management',
            description: 'Create, manage, and monitor your databases. Support for MySQL, PostgreSQL, and MongoDB with real-time metrics.',
            iconBg: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            icon: Icons.database
        },
        {
            title: 'Security Features',
            description: 'Keep your applications secure with SSL certificates, firewall rules, and automated security updates.',
            iconBg: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            icon: Icons.shield
        }
    ];

    const quickActions = [
        { icon: 'plus', label: 'New Site', desc: 'Create a new website' },
        { icon: 'database', label: 'New Database', desc: 'Create a database' },
        { icon: 'server', label: 'New Server', desc: 'Add a server' },
        { icon: 'users', label: 'Invite User', desc: 'Add team member' }
    ];

    let slidesHTML = slides.map((slide, index) => `
        <div class="slide ${index === AppState.dashboardSlideIndex ? 'active' : ''}" data-index="${index}">
            <div class="slide-content">
                <div class="slide-icon" style="background: ${slide.iconBg};">
                    ${slide.icon}
                </div>
                <div>
                    <div class="slide-title">${slide.title}</div>
                    <div class="slide-description">${slide.description}</div>
                </div>
            </div>
        </div>
    `).join('');

    let indicatorsHTML = slides.map((_, index) => `
        <button class="slider-indicator ${index === AppState.dashboardSlideIndex ? 'active' : ''}" data-index="${index}"></button>
    `).join('');

    let quickActionsHTML = quickActions.map(action => `
        <button class="quick-action-btn">
            ${Icons[action.icon]}
            <div>
                <div class="quick-action-label">${action.label}</div>
                <div class="quick-action-desc">${action.desc}</div>
            </div>
        </button>
    `).join('');

    const widgetsHTML = `
        <div class="widgets-grid">
            ${renderWidget('resources', 'Server Resources')}
            ${renderWidget('system', 'System Information')}
            ${renderWidget('changelog', 'Changelog')}
            ${renderWidget('tasks', 'Background Tasks')}
            ${renderWidget('sessions', 'Active Sessions')}
            ${renderWidget('logs', 'Access Log')}
        </div>
    `;

    $('#pageContent').html(`
        <div class="dashboard-container">
            <!-- Slider -->
            <div class="slider-container">
                <div class="slider-wrapper">
                    ${slidesHTML}
                </div>
                <button class="slider-btn prev">${Icons.chevronLeft}</button>
                <button class="slider-btn next">${Icons.chevronRight}</button>
                <div class="slider-indicators">
                    ${indicatorsHTML}
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <h3 class="section-title" style="margin-bottom: var(--spacing-4);">Quick Actions</h3>
                <div class="quick-actions-grid">
                    ${quickActionsHTML}
                </div>
            </div>

            <!-- Widgets -->
            <div>
                <div class="widgets-header" style="margin-bottom: var(--spacing-4);">
                    <div>
                        <h3 class="section-title">Dashboard Widgets</h3>
                        <p class="widgets-hint">Drag to reorder widgets</p>
                    </div>
                    <button class="customize-btn">Customize</button>
                </div>
                ${widgetsHTML}
            </div>
        </div>
    `);

    // Auto-rotate slider
    setInterval(() => {
        if (document.hidden) return;
        const newIndex = (AppState.dashboardSlideIndex + 1) % slides.length;
        changeSlide(newIndex);
    }, 5000);
}

function renderWidget(id, title) {
    if (!AppState.visibleWidgets[id]) return '';

    let content = '';

    switch (id) {
        case 'resources':
            content = `
                <div class="resource-bar">
                    <div class="resource-info">
                        <span class="resource-label">${Icons.cpu} CPU Usage</span>
                        <span class="resource-value">45%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill accent" style="width: 45%;"></div>
                    </div>
                </div>
                <div class="resource-bar">
                    <div class="resource-info">
                        <span class="resource-label">${Icons.memory} Memory</span>
                        <span class="resource-value">6.2 GB / 8 GB</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill warning" style="width: 78%;"></div>
                    </div>
                </div>
                <div class="resource-bar">
                    <div class="resource-info">
                        <span class="resource-label">${Icons.hardDrive} Disk</span>
                        <span class="resource-value">120 GB / 500 GB</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill success" style="width: 24%;"></div>
                    </div>
                </div>
            `;
            break;

        case 'system':
            content = `
                <div class="info-row">
                    <span class="info-label">Operating System</span>
                    <span class="info-value">Ubuntu 22.04 LTS</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kernel Version</span>
                    <span class="info-value">5.15.0-76-generic</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Uptime</span>
                    <span class="info-value">45 days, 12 hours</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Updated</span>
                    <span class="info-value">2024-01-15</span>
                </div>
            `;
            break;

        case 'changelog':
            content = `
                <div style="display: flex; flex-direction: column; gap: var(--spacing-3);">
                    <div style="display: flex; gap: var(--spacing-2);">
                        <span style="background: var(--color-accent); color: white; font-size: 10px; padding: 2px 6px; border-radius: var(--border-radius);">NEW</span>
                        <span style="font-size: var(--text-caption); color: var(--text-primary);">Added dark mode support</span>
                    </div>
                    <div style="display: flex; gap: var(--spacing-2);">
                        <span style="background: var(--color-success); color: white; font-size: 10px; padding: 2px 6px; border-radius: var(--border-radius);">FIX</span>
                        <span style="font-size: var(--text-caption); color: var(--text-primary);">Fixed database backup issue</span>
                    </div>
                    <div style="display: flex; gap: var(--spacing-2);">
                        <span style="background: var(--color-warning); color: var(--text-inverse); font-size: 10px; padding: 2px 6px; border-radius: var(--border-radius);">UPD</span>
                        <span style="font-size: var(--text-caption); color: var(--text-primary);">Updated PHP to 8.2</span>
                    </div>
                </div>
                <button class="widget-link-btn">
                    View all changes ${Icons.externalLink}
                </button>
            `;
            break;

        case 'tasks':
            content = `
                <div class="task-item">
                    <div>
                        <div class="task-name">Database Backup</div>
                        <div class="task-time">Started 5 min ago</div>
                    </div>
                    <span class="task-status">Running</span>
                </div>
                <div class="task-item">
                    <div>
                        <div class="task-name">SSL Renewal</div>
                        <div class="task-time">Completed 2 hours ago</div>
                    </div>
                    <span class="task-status">Done</span>
                </div>
            `;
            break;

        case 'sessions':
            content = `
                <div class="widget-table">
                    <div class="widget-table-header">
                        <span>USER</span>
                        <span>IP ADDRESS</span>
                        <span>ACTION</span>
                    </div>
                    <div class="widget-table-row">
                        <span>admin</span>
                        <span>192.168.1.1</span>
                        <button>End</button>
                    </div>
                    <div class="widget-table-row">
                        <span>developer</span>
                        <span>10.0.0.50</span>
                        <button>End</button>
                    </div>
                </div>
                <div class="widget-pagination">
                    <span class="pagination-info">Showing 1-2 of 2</span>
                    <div class="pagination-buttons">
                        <button class="pagination-btn">1</button>
                    </div>
                </div>
            `;
            break;

        case 'logs':
            content = `
                <div class="log-entry">
                    <div class="log-header">
                        <span class="log-time">14:32:15</span>
                        <span class="log-ip">192.168.1.1</span>
                    </div>
                    <span class="log-user">GET /api/status - admin</span>
                </div>
                <div class="log-entry">
                    <div class="log-header">
                        <span class="log-time">14:30:22</span>
                        <span class="log-ip">10.0.0.50</span>
                    </div>
                    <span class="log-user">POST /api/sites - developer</span>
                </div>
                <button class="widget-link-btn">
                    View full log ${Icons.externalLink}
                </button>
            `;
            break;
    }

    return `
        <div class="widget" data-widget-id="${id}" draggable="true">
            <div class="widget-header">
                <div class="widget-title-group">
                    <span class="widget-drag-handle">${Icons.gripVertical}</span>
                    <span class="widget-title">${title}</span>
                </div>
                <div class="widget-actions">
                    <button class="widget-action-btn" title="Refresh">${Icons.refresh}</button>
                    <button class="widget-action-btn" title="Hide">${Icons.eyeOff}</button>
                </div>
            </div>
            ${content}
        </div>
    `;
}

function changeSlide(index) {
    AppState.dashboardSlideIndex = index;
    $('.slide').removeClass('active');
    $(`.slide[data-index="${index}"]`).addClass('active');
    $('.slider-indicator').removeClass('active');
    $(`.slider-indicator[data-index="${index}"]`).addClass('active');
}

// ============================================
// Sites Page
// ============================================

function renderSitesPage() {
    const sites = [
        { domain: 'example.com', status: 'active', type: 'WordPress', php: '8.2', ip: '192.168.1.10', ssl: true },
        { domain: 'myapp.io', status: 'active', type: 'Laravel', php: '8.1', ip: '192.168.1.11', ssl: true },
        { domain: 'oldsite.net', status: 'suspended', type: 'Static HTML', php: '-', ip: '192.168.1.12', ssl: false },
        { domain: 'staging.dev', status: 'active', type: 'Node.js', php: '-', ip: '192.168.1.13', ssl: true }
    ];

    const sitesHTML = sites.map(site => `
        <tr>
            <td>
                <div class="table-icon-cell">
                    <div class="table-icon">${Icons.globe}</div>
                    <div>
                        <div class="table-domain">${site.domain}</div>
                        ${site.ssl ? `<div class="table-ssl">${Icons.shield} SSL Active</div>` : ''}
                    </div>
                </div>
            </td>
            <td><span class="status-badge ${site.status}">${site.status.charAt(0).toUpperCase() + site.status.slice(1)}</span></td>
            <td>${site.type}</td>
            <td>${site.php}</td>
            <td>${site.ip}</td>
            <td class="table-actions">
                <button class="table-action-btn" title="Open site">${Icons.externalLinkAlt}</button>
                <button class="table-action-btn" title="Edit">${Icons.edit}</button>
                <button class="table-action-btn danger" title="Delete">${Icons.trash}</button>
                <button class="table-action-btn" title="More">${Icons.moreVertical}</button>
            </td>
        </tr>
    `).join('');

    $('#pageContent').html(`
        <div class="page-container">
            <div class="page-header">
                <div class="search-group">
                    <div class="search-input-wrapper">
                        ${Icons.search}
                        <input type="text" class="search-input" placeholder="Search sites...">
                    </div>
                    <button class="refresh-btn" title="Refresh">${Icons.refresh}</button>
                </div>
                <button class="add-btn">
                    ${Icons.plus}
                    <span>Add Site</span>
                </button>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>PHP</th>
                            <th>IP Address</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sitesHTML}
                    </tbody>
                </table>
            </div>
        </div>
    `);
}

// ============================================
// Databases Page
// ============================================

function renderDatabasesPage() {
    const databases = [
        { name: 'production_db', user: 'admin', type: 'MySQL', host: 'localhost', size: '2.5 GB', status: 'online' },
        { name: 'staging_db', user: 'developer', type: 'PostgreSQL', host: '10.0.0.5', size: '500 MB', status: 'online' },
        { name: 'analytics_db', user: 'analyst', type: 'MySQL', host: 'localhost', size: '15.2 GB', status: 'maintenance' },
        { name: 'cache_db', user: 'service', type: 'Redis', host: 'localhost', size: '128 MB', status: 'online' }
    ];

    const dbHTML = databases.map(db => `
        <tr>
            <td>
                <div class="table-icon-cell">
                    <div class="table-icon">${Icons.database}</div>
                    <div class="table-domain">${db.name}</div>
                </div>
            </td>
            <td>${db.user}</td>
            <td>${db.type}</td>
            <td>${db.host}</td>
            <td>${db.size}</td>
            <td>
                <div class="table-status-cell">
                    <span class="status-dot ${db.status}">●</span>
                    ${db.status.charAt(0).toUpperCase() + db.status.slice(1)}
                </div>
            </td>
            <td class="table-actions">
                <button class="table-action-btn" title="Manage">${Icons.settings}</button>
                <button class="table-action-btn" title="Edit">${Icons.edit}</button>
                <button class="table-action-btn danger" title="Delete">${Icons.trash}</button>
                <button class="table-action-btn" title="More">${Icons.moreVertical}</button>
            </td>
        </tr>
    `).join('');

    $('#pageContent').html(`
        <div class="page-container">
            <div class="page-header">
                <div class="search-group">
                    <div class="search-input-wrapper">
                        ${Icons.search}
                        <input type="text" class="search-input" placeholder="Search databases...">
                    </div>
                    <button class="refresh-btn" title="Refresh">${Icons.refresh}</button>
                </div>
                <button class="add-btn">
                    ${Icons.plus}
                    <span>Add Database</span>
                </button>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Host</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${dbHTML}
                    </tbody>
                </table>
            </div>
        </div>
    `);
}

// ============================================
// Navigation Board Page
// ============================================

function renderNavigationBoardPage() {
    const visibleNavItems = getVisibleNavItems();

    // Build all groups HTML
    let groupsHTML = navGroups.map(group => {
        const categoryIcon = Icons[group.items[0]?.icon] || '';
        
        const itemsHTML = group.items.map(item => {
            const isVisible = visibleNavItems.has(item.id);
            return `
                <div class="nav-board-item ${isVisible ? 'visible' : ''}" data-item-id="${item.id}">
                    <svg class="star-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="${isVisible ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <span>${item.label}</span>
                </div>
            `;
        }).join('');

        return `
            <div class="nav-board-group">
                <div class="nav-board-category">
                    ${categoryIcon}
                    <h4>${group.category}</h4>
                </div>
                <div class="nav-board-items">
                    ${itemsHTML}
                </div>
            </div>
        `;
    }).join('');

    $('#pageContent').html(`
        <div class="nav-board-container">
            <div class="nav-board-grid">
                ${groupsHTML}
            </div>
        </div>
    `);

    // Event handler for toggling visibility
    $(document).off('click.navboard').on('click.navboard', '.nav-board-item', function() {
        const itemId = $(this).data('item-id');
        const $item = $(this);
        const $star = $item.find('.star-icon');
        
        // Get current visible items
        const currentVisible = getVisibleNavItems();
        
        // Toggle visibility
        if (currentVisible.has(itemId)) {
            currentVisible.delete(itemId);
            $item.removeClass('visible');
            $star.attr('fill', 'none');
        } else {
            currentVisible.add(itemId);
            $item.addClass('visible');
            $star.attr('fill', 'currentColor');
        }
        
        // Save to localStorage
        localStorage.setItem('visibleNavItems', JSON.stringify([...currentVisible]));
        
        // Update sidebar to reflect changes immediately
        renderSidebar();
    });
}

function updateSidebarVisibility() {
    // Re-render sidebar with updated visibility
    renderSidebar();
}

// ============================================
// Placeholder Page
// ============================================

function renderPlaceholderPage(path) {
    const title = path.replace('/', '').replace(/-/g, ' ');
    const capitalizedTitle = title.charAt(0).toUpperCase() + title.slice(1);

    $('#pageContent').html(`
        <div class="placeholder-page">
            <div class="placeholder-icon">
                ${Icons.settings}
            </div>
            <h2 class="placeholder-title">${capitalizedTitle}</h2>
            <p class="placeholder-desc">This page is under development. Check back soon for updates.</p>
        </div>
    `);
}

// ============================================
// Dark Mode
// ============================================

function initDarkMode() {
    if (AppState.darkMode) {
        $('html').addClass('dark');
        $('#darkModeToggle').prop('checked', true);
    }
}

function toggleDarkMode() {
    AppState.darkMode = !AppState.darkMode;
    localStorage.setItem('darkMode', AppState.darkMode);
    $('html').toggleClass('dark', AppState.darkMode);
    $('#darkModeToggle').prop('checked', AppState.darkMode);
}

// ============================================
// Event Handlers
// ============================================

function initEventHandlers() {
    // Tab click
    $(document).on('click', '.tab-btn', function (e) {
        e.preventDefault();
        const path = $(this).data('path');
        Router.navigate(path);
    });

    // Tab close
    $(document).on('click', '.tab-close-btn:not(.disabled)', function (e) {
        e.stopPropagation();
        const tabId = $(this).data('tab-id');
        closeTab(tabId);
    });

    // Sidebar collapse
    $('#collapseBtn').on('click', function () {
        AppState.isSidebarCollapsed = !AppState.isSidebarCollapsed;
        $('#sidebar').toggleClass('collapsed', AppState.isSidebarCollapsed);
    });

    // Notification button
    $('#notificationBtn').on('click', function (e) {
        e.stopPropagation();
        const $panel = $('#notificationPanel');
        const isVisible = $panel.is(':visible');

        // Hide user menu if open
        $('#userMenuPanel').hide();
        $('#userMenuBtn').removeClass('active');

        $panel.toggle();
        $('#overlay').toggle(!isVisible);
    });

    // Notification tabs
    $(document).on('click', '.notification-tab', function () {
        const tab = $(this).data('tab');
        AppState.notificationTab = tab;
        $('.notification-tab').removeClass('active');
        $(this).addClass('active');
        renderNotifications();
    });

    // Mark notification as read
    $(document).on('click', '.notification-item', function () {
        const id = $(this).data('id');
        const notification = AppState.notifications.find(n => n.id === id);
        if (notification) {
            notification.unread = false;
            renderNotifications();
            updateNotificationBadge();
        }
    });

    // Mark all as read
    $('#markAllReadBtn').on('click', function () {
        AppState.notifications.forEach(n => n.unread = false);
        renderNotifications();
        updateNotificationBadge();
    });

    // User menu button
    $('#userMenuBtn').on('click', function (e) {
        e.stopPropagation();
        const $panel = $('#userMenuPanel');
        const isVisible = $panel.is(':visible');

        // Hide notification panel if open
        $('#notificationPanel').hide();

        $panel.toggle();
        $(this).toggleClass('active', !isVisible);
        $('#overlay').toggle(!isVisible);
    });

    // User menu items
    $(document).on('click', '.user-menu-item', function () {
        const action = $(this).data('action');

        switch (action) {
            case 'profile':
                Router.navigate('/profile');
                break;
            case 'settings':
                Router.navigate('/settings');
                break;
            case 'logout':
                AuthService.logout();
                break;
        }

        $('#userMenuPanel').hide();
        $('#userMenuBtn').removeClass('active');
        $('#overlay').hide();
    });

    // Dark mode toggle
    $('#darkModeToggle').on('change', function () {
        toggleDarkMode();
    });

    // Close dropdowns when clicking overlay
    $('#overlay').on('click', function () {
        $('#notificationPanel').hide();
        $('#userMenuPanel').hide();
        $('#userMenuBtn').removeClass('active');
        $(this).hide();
    });

    // Close dropdowns when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#notificationPanel, #notificationBtn').length) {
            $('#notificationPanel').hide();
        }
        if (!$(e.target).closest('#userMenuPanel, #userMenuBtn').length) {
            $('#userMenuPanel').hide();
            $('#userMenuBtn').removeClass('active');
        }
        if (!$('#notificationPanel, #userMenuPanel').is(':visible')) {
            $('#overlay').hide();
        }
    });

    // Slider navigation
    $(document).on('click', '.slider-btn.prev', function () {
        const newIndex = (AppState.dashboardSlideIndex - 1 + 3) % 3;
        changeSlide(newIndex);
    });

    $(document).on('click', '.slider-btn.next', function () {
        const newIndex = (AppState.dashboardSlideIndex + 1) % 3;
        changeSlide(newIndex);
    });

    $(document).on('click', '.slider-indicator', function () {
        const index = parseInt($(this).data('index'));
        changeSlide(index);
    });

    // Widget drag and drop
    let draggedWidget = null;

    $(document).on('dragstart', '.widget', function (e) {
        draggedWidget = this;
        $(this).addClass('dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'move';
    });

    $(document).on('dragend', '.widget', function () {
        $(this).removeClass('dragging');
        draggedWidget = null;
    });

    $(document).on('dragover', '.widget', function (e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
    });

    $(document).on('drop', '.widget', function (e) {
        e.preventDefault();
        if (draggedWidget && draggedWidget !== this) {
            const $dragged = $(draggedWidget);
            const $target = $(this);

            const draggedIndex = $dragged.index();
            const targetIndex = $target.index();

            if (draggedIndex < targetIndex) {
                $target.after($dragged);
            } else {
                $target.before($dragged);
            }
        }
    });

    // Search functionality
    $(document).on('input', '.search-input', function () {
        const query = $(this).val().toLowerCase();
        const $table = $(this).closest('.page-container').find('.data-table');

        $table.find('tbody tr').each(function () {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(query));
        });
    });
}

// ============================================
// Application Initialization
// ============================================

$(document).ready(function () {
    // Initialize dark mode
    initDarkMode();

    // Initialize router (includes auth check)
    Router.init();

    // Render UI components
    renderSidebar();
    renderTabs();
    renderNotifications();
    updateNotificationBadge();
    updateUserProfile();

    // Initialize event handlers
    initEventHandlers();
});
