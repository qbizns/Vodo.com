/**
 * VODO Platform - Backend Theme JavaScript
 * Adapted from KERNEL Platform for Laravel backend modules
 */

// ============================================
// Application State
// ============================================

const AppState = {
    isSidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    darkMode: localStorage.getItem('darkMode') === 'true',
    openTabs: [],
    activeTabId: null,
    notifications: [],
    notificationTab: 'all',
    dashboardSlideIndex: 0,
    widgetOrder: ['resources', 'system', 'changelog', 'tasks', 'sessions', 'logs'],
    visibleWidgets: { resources: true, system: true, changelog: true, tasks: true, sessions: true, logs: true }
};

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
    edit: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    trash: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    moreVertical: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>',
    zap: '<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>',
    star: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
    layoutDashboard: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
    lock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
    bell: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
    user: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
    activity: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
    clock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    key: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>',
    package: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
    terminal: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>',
    code: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',
    network: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="16" y="16" width="6" height="6" rx="1"></rect><rect x="2" y="16" width="6" height="6" rx="1"></rect><rect x="9" y="2" width="6" height="6" rx="1"></rect><path d="M5 16v-6a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v6M12 12V8"></path></svg>',
    power: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>',
    barChart: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>',
    file: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>',
    folder: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>',
    mail: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
    creditCard: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
    helpCircle: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    logOut: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>'
};

// Make Icons globally available
window.Icons = Icons;

// ============================================
// CSS Loader for SPA Navigation
// ============================================

// Track loaded CSS files to avoid duplicates
const loadedCss = new Set(['style', 'rtl']);

/**
 * Dynamically load a page-specific CSS file
 * @param {string} cssName - Name of the CSS file (without .css extension)
 * @returns {Promise} - Resolves when CSS is loaded
 */
function loadPageCss(cssName) {
    if (!cssName || loadedCss.has(cssName)) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `/backend/css/pages/${cssName}.css`;
        link.onload = () => {
            loadedCss.add(cssName);
            resolve();
        };
        link.onerror = () => {
            console.warn(`Failed to load CSS: ${cssName}`);
            resolve(); // Don't reject, just continue
        };
        document.head.appendChild(link);
    });
}

/**
 * Load multiple CSS files
 * @param {string} cssNames - Comma-separated CSS file names
 * @returns {Promise}
 */
function loadPageCssMultiple(cssNames) {
    if (!cssNames) return Promise.resolve();
    
    const names = cssNames.split(',').map(s => s.trim()).filter(Boolean);
    return Promise.all(names.map(loadPageCss));
}

/**
 * Execute inline scripts from dynamically loaded content
 * @param {jQuery} $container - Container with potential inline scripts
 */
function executeInlineScripts($container) {
    $container.find('script').each(function() {
        const script = document.createElement('script');
        if (this.src) {
            script.src = this.src;
        } else {
            script.textContent = this.textContent;
        }
        document.head.appendChild(script);
        document.head.removeChild(script);
    });
}

// ============================================
// Tab Management
// ============================================

// Get tab storage key based on module
function getTabStorageKey() {
    const baseUrl = document.getElementById('tabsContainer')?.getAttribute('data-base-url') || '';
    return 'openTabs_' + baseUrl.replace(/\//g, '_');
}

function initTabs() {
    const container = document.getElementById('tabsContainer');
    if (!container) return;

    // Use getAttribute for reliable string values (not jQuery .data() which caches/converts)
    const currentPageId = String(container.getAttribute('data-current-page') || 'dashboard');
    const currentPageLabel = String(container.getAttribute('data-current-label') || 'Dashboard');
    const currentPageIcon = String(container.getAttribute('data-current-icon') || 'layoutDashboard');
    const baseUrl = String(container.getAttribute('data-base-url') || '');
    const storageKey = getTabStorageKey();

    // Load tabs from session storage
    let savedTabs = [];
    try {
        const stored = sessionStorage.getItem(storageKey);
        if (stored) {
            savedTabs = JSON.parse(stored);
            // Ensure all tab ids are strings
            savedTabs = savedTabs.map(t => ({ ...t, id: String(t.id) }));
        }
    } catch (e) {
        savedTabs = [];
    }

    // Always have dashboard tab
    const dashboardUrl = baseUrl || '/';
    const dashboardExists = savedTabs.some(t => t.id === 'dashboard');
    if (!dashboardExists) {
        savedTabs.unshift({
            id: 'dashboard',
            label: 'Dashboard',
            icon: 'layoutDashboard',
            url: dashboardUrl,
            closable: false
        });
    }

    // Check if current page tab exists
    const currentTabExists = savedTabs.some(t => t.id === currentPageId);

    if (!currentTabExists && currentPageId !== 'dashboard') {
        // Add current page as new tab
        const currentTabUrl = baseUrl + '/' + currentPageId;
        savedTabs.push({
            id: currentPageId,
            label: currentPageLabel,
            icon: currentPageIcon,
            url: currentTabUrl,
            closable: true
        });
    }

    // Remove duplicates by id (keep first occurrence)
    const uniqueTabs = [];
    const seenIds = new Set();
    for (const tab of savedTabs) {
        const tabId = String(tab.id);
        if (!seenIds.has(tabId)) {
            seenIds.add(tabId);
            uniqueTabs.push({ ...tab, id: tabId });
        }
    }

    AppState.openTabs = uniqueTabs;
    AppState.activeTabId = currentPageId;

    // Save to storage
    sessionStorage.setItem(storageKey, JSON.stringify(uniqueTabs));

    renderTabs();
}

function renderTabs() {
    const container = document.getElementById('tabsContainer');
    if (!container) return;

    const $container = $(container);
    const baseUrl = container.getAttribute('data-base-url') || '';
    const tabCount = AppState.openTabs.length;

    let tabClass = '';
    if (tabCount > 8) {
        tabClass = 'tabs-icon-only';
    } else if (tabCount > 5) {
        tabClass = 'tabs-compact';
    }

    $container.removeClass('tabs-compact tabs-icon-only').addClass(tabClass);
    $container.empty();

    AppState.openTabs.forEach(tab => {
        const tabId = String(tab.id);
        const isActive = tabId === AppState.activeTabId;
        const icon = Icons[tab.icon] || Icons.layoutDashboard;
        const tabUrl = tab.url || (tabId === 'dashboard' ? baseUrl || '/' : baseUrl + '/' + tabId);

        // Use template and set attributes directly to avoid jQuery data caching issues
        const tabHtml = `
            <div class="tab-item ${isActive ? 'active' : ''}">
                <a href="${tabUrl}" class="tab-btn">
                    ${icon}
                    <span>${tab.label}</span>
                </a>
                <button class="tab-close-btn ${tab.closable === false ? 'disabled' : ''}">×</button>
            </div>
        `;

        const $tab = $(tabHtml);
        // Set data using attr for consistency
        $tab.attr('data-tab-id', tabId);
        $tab.find('.tab-btn').attr('data-tab-id', tabId);
        $tab.find('.tab-close-btn').attr('data-tab-id', tabId);

        $container.append($tab);
    });
}

function getTabById(tabId) {
    const id = String(tabId);
    return AppState.openTabs.find(t => String(t.id) === id);
}

function isTabOpen(tabId) {
    return !!getTabById(tabId);
}

function isTabActive(tabId) {
    return String(AppState.activeTabId) === String(tabId);
}

function closeTab(tabId) {
    const id = String(tabId);
    if (id === 'dashboard') return;

    const tabIndex = AppState.openTabs.findIndex(t => String(t.id) === id);
    if (tabIndex === -1) return;

    // Remove the tab
    AppState.openTabs.splice(tabIndex, 1);

    // Save to session storage immediately
    const storageKey = getTabStorageKey();
    sessionStorage.setItem(storageKey, JSON.stringify(AppState.openTabs));

    // If closing active tab, navigate to previous tab
    if (String(AppState.activeTabId) === id) {
        const newIndex = Math.min(tabIndex, AppState.openTabs.length - 1);
        const newTab = AppState.openTabs[newIndex];
        if (newTab) {
            const container = document.getElementById('tabsContainer');
            const baseUrl = container?.getAttribute('data-base-url') || '';
            const newUrl = newTab.url || (newTab.id === 'dashboard' ? baseUrl || '/' : baseUrl + '/' + newTab.id);
            window.location.href = newUrl;
            return;
        }
    }

    renderTabs();
}

// ============================================
// Notifications
// ============================================

function renderNotifications() {
    const $list = $('#notificationList');
    if (!$list.length) return;

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

    $list.empty();
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
// Sidebar
// ============================================

function initSidebar() {
    if (AppState.isSidebarCollapsed) {
        $('#sidebar').addClass('collapsed');
    }

    // Render sidebar with visibility filtering
    renderSidebar();
}

// ============================================
// Splash Screen
// ============================================

function initSplash() {
    const $splash = $('#splashScreen');
    if (!$splash.length) return;

    const minDisplayTime = 3000; // 3 seconds minimum
    const startTime = Date.now();

    let progress = 0;
    const progressBar = $('#splashProgressBar');

    // Animate progress bar over 3 seconds
    const progressInterval = setInterval(function () {
        if (progress >= 100) {
            clearInterval(progressInterval);
            return;
        }
        progress += (100 / 30); // 30 steps over 3 seconds (100ms intervals)
        progressBar.css('width', Math.min(progress, 100) + '%');
    }, 100);

    // Hide splash after minimum time AND page load
    function hideSplash() {
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, minDisplayTime - elapsed);

        setTimeout(function () {
            clearInterval(progressInterval);
            progressBar.css('width', '100%');

            setTimeout(function () {
                $splash.addClass('splash-hidden');
                setTimeout(function () {
                    $splash.remove();
                }, 500);
            }, 200);
        }, remaining);
    }

    // Wait for page load
    if (document.readyState === 'complete') {
        hideSplash();
    } else {
        $(window).on('load', hideSplash);
    }
}

// ============================================
// Dashboard Widgets
// ============================================

function initDashboardSlider() {
    const $slides = $('.slide');
    if (!$slides.length) return;

    // Auto-rotate slider
    setInterval(function () {
        if (document.hidden) return;
        const slideCount = $slides.length;
        const newIndex = (AppState.dashboardSlideIndex + 1) % slideCount;
        changeSlide(newIndex);
    }, 5000);
}

function changeSlide(index) {
    AppState.dashboardSlideIndex = index;
    $('.slide').removeClass('active');
    $(`.slide[data-index="${index}"]`).addClass('active');
    $('.slider-indicator').removeClass('active');
    $(`.slider-indicator[data-index="${index}"]`).addClass('active');
}

// ============================================
// SPA Navigation
// ============================================

let isNavigating = false;

function navigateToPage(url, pageId, pageLabel, pageIcon, pushState = true) {
    if (isNavigating) return;

    isNavigating = true;

    // Show loading state
    const $pageContent = $('#pageContent');
    const $pageTitle = $('#pageTitle');

    $pageContent.css('opacity', '0.5');

    // Make AJAX request
    $.ajax({
        url: url,
        type: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-PJAX': 'true'
        },
        success: function (response) {
            // Parse response
            let content = response;
            let title = pageLabel;
            let hidePageTitle = false;
            let requiredCss = '';

            // If response is HTML, try to extract content
            if (typeof response === 'string') {
                const $response = $('<div>').html(response);

                // Check for PJAX response first (preferred)
                const $pjaxContent = $response.find('#pjax-content');
                if ($pjaxContent.length) {
                    // PJAX response - includes inline styles and content
                    content = $pjaxContent.html();
                    
                    // Get page title from PJAX data attribute
                    const pjaxTitle = $pjaxContent.attr('data-page-title');
                    if (pjaxTitle) {
                        title = pjaxTitle;
                    }
                    
                    // Get required CSS from PJAX data attribute
                    requiredCss = $pjaxContent.attr('data-require-css') || '';
                    
                    // PJAX responses typically hide the page title bar
                    hidePageTitle = true;
                } else {
                    // Fallback: Try to find page-content section
                    const $content = $response.find('#pageContent');
                    if ($content.length) {
                        content = $content.html();
                    } else {
                        // Use entire response
                        content = response;
                    }

                    // Check if page title should be hidden
                    const $titleBar = $response.find('.page-title-bar');
                    hidePageTitle = $titleBar.length === 0;
                    
                    // Try to extract required CSS from head (fallback)
                    const $requireCssMeta = $response.find('meta[name="require-css"]');
                    if ($requireCssMeta.length) {
                        requiredCss = $requireCssMeta.attr('content') || '';
                    }
                }
            }

            // Load required CSS files before updating content
            const cssLoadPromise = requiredCss ? loadPageCssMultiple(requiredCss) : Promise.resolve();
            
            cssLoadPromise.then(() => {
                // Update page content
                $pageContent.html(content);

                // Update page title
                if (!hidePageTitle && $pageTitle.length) {
                    $pageTitle.text(title);
                    $('.page-title-bar').show();
                } else {
                    $('.page-title-bar').hide();
                }

                // Update document title
                const brandName = window.BackendConfig?.brandName || 'VODO Admin';
                document.title = `${title} - ${brandName}`;

                // Update BackendConfig
                if (window.BackendConfig) {
                    window.BackendConfig.currentPage = pageId;
                    window.BackendConfig.currentPageLabel = pageLabel;
                    window.BackendConfig.currentPageIcon = pageIcon;
                }

                // Update browser history
                if (pushState) {
                    const state = {
                        pageId: pageId,
                        pageLabel: pageLabel,
                        pageIcon: pageIcon,
                        url: url
                    };
                    history.pushState(state, title, url);
                }

                // Update active tab
                AppState.activeTabId = pageId;

                // Update tabs
                renderTabs();

                // Update sidebar active state
                $('.nav-item').removeClass('active');
                $(`.nav-item[data-nav-id="${pageId}"]`).addClass('active');

                // Restore opacity
                $pageContent.css('opacity', '1');

                // Scroll to top
                $pageContent.scrollTop(0);

                // Re-initialize any page-specific scripts
                if (typeof initDashboardSlider === 'function') {
                    initDashboardSlider();
                }
                
                // Execute any inline scripts from the loaded content
                executeInlineScripts($pageContent);

                isNavigating = false;
            });
        },
        error: function (xhr, status, error) {
            console.error('Navigation error:', error);

            // Show error message
            $pageContent.html(`
                <div style="padding: var(--spacing-6); text-align: center;">
                    <div style="color: var(--text-error); margin-bottom: var(--spacing-4);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h3 style="margin-bottom: var(--spacing-2);">Failed to load page</h3>
                    <p style="color: var(--text-secondary); margin-bottom: var(--spacing-4);">
                        ${xhr.status === 404 ? 'Page not found' : 'An error occurred while loading the page'}
                    </p>
                    <button onclick="location.reload()" class="btn-primary" style="padding: var(--spacing-2) var(--spacing-4); background: var(--bg-primary); color: white; border: none; border-radius: var(--radius-md); cursor: pointer;">
                        Reload Page
                    </button>
                </div>
            `);

            $pageContent.css('opacity', '1');
            isNavigating = false;
        }
    });
}

// Handle browser back/forward buttons
window.addEventListener('popstate', function (event) {
    if (event.state) {
        const { pageId, pageLabel, pageIcon, url } = event.state;
        navigateToPage(url, pageId, pageLabel, pageIcon, false);
    }
});

// ============================================
// Event Handlers
// ============================================

function initEventHandlers() {
    // Tab button click - handle tab activation with AJAX
    $(document).on('click', '.tab-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const tabId = String($(this).attr('data-tab-id'));

        // If this tab is already active, do nothing
        if (isTabActive(tabId)) {
            return false;
        }

        // Find the tab
        const tab = getTabById(tabId);
        if (tab) {
            navigateToPage(tab.url, tab.id, tab.label, tab.icon);
        }

        return false;
    });

    // Tab close
    $(document).on('click', '.tab-close-btn:not(.disabled)', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const tabId = String($(this).attr('data-tab-id'));
        closeTab(tabId);
    });

    // Sidebar nav item click - use AJAX navigation
    $(document).on('click', '.nav-item', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const navId = String($(this).attr('data-nav-id'));
        const navUrl = $(this).attr('href');
        const navLabel = $(this).find('span').last().text();

        // Check if already on this page
        if (isTabActive(navId)) {
            return false;
        }

        // Get icon from the nav item
        const navIcon = getIconNameFromNav(navId);

        // Check if tab exists
        if (!isTabOpen(navId)) {
            // Add new tab
            const container = document.getElementById('tabsContainer');
            const baseUrl = container?.getAttribute('data-base-url') || '';

            AppState.openTabs.push({
                id: navId,
                label: navLabel,
                icon: navIcon,
                url: navUrl,
                closable: true
            });

            // Save to storage
            const storageKey = getTabStorageKey();
            sessionStorage.setItem(storageKey, JSON.stringify(AppState.openTabs));
        }

        // Navigate to page
        navigateToPage(navUrl, navId, navLabel, navIcon);

        return false;
    });

    // Nav board button - use AJAX navigation
    $(document).on('click', '#navBoardBtn', function (e) {
        e.preventDefault();
        const url = $(this).attr('href');
        navigateToPage(url, 'navigation-board', 'Navigation Board', 'layoutDashboard');
        return false;
    });

    // Sidebar collapse
    $('#collapseBtn').on('click', function () {
        AppState.isSidebarCollapsed = !AppState.isSidebarCollapsed;
        localStorage.setItem('sidebarCollapsed', AppState.isSidebarCollapsed);
        $('#sidebar').toggleClass('collapsed', AppState.isSidebarCollapsed);
    });

    // Notification button
    $('#notificationBtn').on('click', function (e) {
        e.stopPropagation();
        const $panel = $('#notificationPanel');
        const isVisible = $panel.is(':visible');

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

        $('#notificationPanel').hide();

        $panel.toggle();
        $(this).toggleClass('active', !isVisible);
        $('#overlay').toggle(!isVisible);
    });

    // Dark mode toggle
    $('#darkModeToggle').on('change', function () {
        toggleDarkMode();
    });

    // Language selector toggle
    $('#languageToggle').on('click', function (e) {
        e.stopPropagation();
        const $dropdown = $('#languageDropdown');
        const $selector = $(this).closest('.language-selector');
        const isVisible = $dropdown.is(':visible');
        
        $dropdown.toggle();
        $selector.toggleClass('open', !isVisible);
    });

    // Language option click - set cookie and reload
    $(document).on('click', '.language-option', function (e) {
        e.preventDefault();
        const lang = $(this).data('lang');
        
        // Set cookie for 1 year
        const expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        document.cookie = `locale=${lang}; expires=${expires.toUTCString()}; path=/`;
        
        // Reload page with lang parameter (middleware will handle it)
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    });

    // Close language dropdown when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.language-selector').length) {
            $('#languageDropdown').hide();
            $('.language-selector').removeClass('open');
        }
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
        const slideCount = $('.slide').length;
        const newIndex = (AppState.dashboardSlideIndex - 1 + slideCount) % slideCount;
        changeSlide(newIndex);
    });

    $(document).on('click', '.slider-btn.next', function () {
        const slideCount = $('.slide').length;
        const newIndex = (AppState.dashboardSlideIndex + 1) % slideCount;
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

    // Navigation board item toggle
    $(document).on('click', '.nav-board-item', function () {
        const itemId = $(this).data('item-id');
        const $item = $(this);
        const $star = $item.find('.star-icon');

        // Get current visible items
        const visibleItems = getVisibleNavItems();

        // Toggle visibility
        if (visibleItems.has(itemId)) {
            visibleItems.delete(itemId);
            $item.removeClass('visible');
            $star.attr('fill', 'none');
        } else {
            visibleItems.add(itemId);
            $item.addClass('visible');
            $star.attr('fill', 'currentColor');
        }

        // Save to localStorage
        localStorage.setItem('visibleNavItems', JSON.stringify([...visibleItems]));

        // Update sidebar to reflect changes immediately
        renderSidebar();
    });
}

// ============================================
// Visible Nav Items Management
// ============================================

function getVisibleNavItems() {
    // Load visible nav items from localStorage
    // Default visible items: dashboard, sites, databases
    let visibleNavItems = new Set(['dashboard', 'sites', 'databases']);
    try {
        const saved = localStorage.getItem('visibleNavItems');
        if (saved) {
            visibleNavItems = new Set(JSON.parse(saved));
        }
    } catch (e) { }
    return visibleNavItems;
}

// ============================================
// Sidebar Rendering
// ============================================

function renderSidebar() {
    const $nav = $('#sidebarNav');
    if (!$nav.length) return;

    // Get navGroups from BackendConfig
    const navGroups = window.BackendConfig?.navGroups || [];
    if (navGroups.length === 0) return;

    const visibleNavItems = getVisibleNavItems();
    const currentPage = window.BackendConfig?.currentPage || 'dashboard';

    // Clear existing nav items
    $nav.empty();

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
                <div class="nav-divider" data-category="${group.category}">
                    <span class="nav-category">${group.category}</span>
                    <div class="nav-divider-line"></div>
                </div>
            `);
        }

        // Navigation items (only visible ones)
        visibleItems.forEach(item => {
            const isActive = currentPage === item.id;
            const iconHtml = getNavIcon(item.icon);

            $nav.append(`
                <a href="${item.url}" class="nav-item ${isActive ? 'active' : ''}" data-nav-id="${item.id}">
                    <span class="nav-icon">${iconHtml}</span>
                    <span>${item.label}</span>
                </a>
            `);
        });
    });

    // Show message if no items visible
    if (!hasVisibleItems) {
        $nav.append(`
            <div style="padding: var(--spacing-4); text-align: center; color: var(--text-secondary); font-size: var(--text-caption);">
                No navigation items visible.<br>Use Navigation Board to enable items.
            </div>
        `);
    }
}

function getIconNameFromNav(navId) {
    // Map common nav IDs to icon names
    const iconMap = {
        'dashboard': 'layoutDashboard',
        'sites': 'globe',
        'databases': 'database',
        'ssl': 'fileLock',
        'dns': 'network',
        'firewall': 'ban',
        'backups': 'fileArchive',
        'files': 'folderTree',
        'cron': 'clock',
        'security': 'shieldCheck',
        'users': 'users',
        'admins': 'userCheck',
        'ssh': 'fileCode',
        'api': 'key',
        'logs': 'fileInput',
        'packages': 'package',
        'services': 'shield',
        'plugins': 'plug',
        'updates': 'fileCheck',
        'monitoring': 'fileBarChart',
        'performance': 'zap',
        'analytics': 'activity',
        'integrations': 'plugZap',
        'alerts': 'alertCircle',
        'metrics': 'gauge',
        'reports': 'barChart3',
        'developers': 'code',
        'modules': 'boxes',
        'settings': 'settings',
        'network': 'wifi',
        'audit': 'shieldAlert',
        'system': 'power',
        'info': 'info',
        'cli': 'terminal',
        'documentation': 'fileText',
        'guides': 'bookOpen',
        'changelog': 'listTree',
        'themes': 'palette',
        'notifications': 'bellRing',
        'templates': 'fileEdit',
        'permissions': 'lock',
        'webhooks': 'bell',
        'code': 'code2',
        'support': 'smile',
        'feedback': 'messageSquare',
        'home': 'home'
    };

    return iconMap[navId] || 'layoutDashboard';
}

function getNavIcon(iconName) {
    const icons = {
        layoutDashboard: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
        globe: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
        globe2: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
        database: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>',
        server: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>',
        serverStack: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>',
        fileKey: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><circle cx="10" cy="13" r="2"></circle><path d="M10 15v3"></path></svg>',
        fileLock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><rect x="8" y="12" width="8" height="6" rx="1"></rect><path d="M15 12v-2a3 3 0 0 0-6 0v2"></path></svg>',
        network: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="16" y="16" width="6" height="6" rx="1"></rect><rect x="2" y="16" width="6" height="6" rx="1"></rect><rect x="9" y="2" width="6" height="6" rx="1"></rect><path d="M5 16v-6a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v6M12 12V8"></path></svg>',
        ban: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>',
        fileArchive: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M10 9h1v2h-1zM10 13h1v2h-1z"></path></svg>',
        folderTree: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><path d="M8 13v4"></path><path d="M12 17v0"></path><path d="M8 17h4"></path></svg>',
        clock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
        shieldCheck: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg>',
        users: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        user: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        userCheck: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>',
        fileCode: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M10 13l-2 2 2 2"></path><path d="M14 13l2 2-2 2"></path></svg>',
        key: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>',
        fileInput: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M12 18v-6"></path><path d="M9 15l3-3 3 3"></path></svg>',
        package: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
        shield: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
        plug: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-5"></path><path d="M9 8V2"></path><path d="M15 8V2"></path><path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z"></path></svg>',
        fileCheck: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M9 15l2 2 4-4"></path></svg>',
        fileBarChart: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M12 18v-4"></path><path d="M8 18v-2"></path><path d="M16 18v-6"></path></svg>',
        zap: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>',
        activity: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
        plugZap: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.3 20.3a2.4 2.4 0 0 0 3.4 0L12 18l-2-2-3.7 3.7a2.4 2.4 0 0 1 0 .6z"></path><path d="M14.8 11.6L18 8.4a2.4 2.4 0 1 0-3.4-3.4L11.4 8.2"></path><path d="M8 14l4-4"></path><path d="M12 22V17"></path><path d="M7 9V2"></path><path d="M17 9V2"></path></svg>',
        alertCircle: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
        gauge: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg>',
        barChart3: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="M18 17V9"></path><path d="M13 17V5"></path><path d="M8 17v-3"></path></svg>',
        code: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',
        boxes: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z"></path><path d="m7 16.5-4.74-2.85"></path><path d="m7 16.5 5-3"></path><path d="M7 16.5v5.17"></path><path d="M12 13.5V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z"></path><path d="m17 16.5-5-3"></path><path d="m17 16.5 4.74-2.85"></path><path d="M17 16.5v5.17"></path><path d="M7.97 4.42A2 2 0 0 0 7 6.13v4.37l5 3 5-3V6.13a2 2 0 0 0-.97-1.71l-3-1.8a2 2 0 0 0-2.06 0l-3 1.8Z"></path><path d="M12 8 7.26 5.15"></path><path d="m12 8 4.74-2.85"></path><path d="M12 13.5V8"></path></svg>',
        settings: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        wifi: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>',
        shieldAlert: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>',
        power: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>',
        terminal: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>',
        fileText: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>',
        bookOpen: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>',
        listTree: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12h-8"></path><path d="M21 6H8"></path><path d="M21 18h-8"></path><path d="M3 6v4c0 1.1.9 2 2 2h3"></path><path d="M3 10v6c0 1.1.9 2 2 2h3"></path></svg>',
        palette: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z"></path></svg>',
        bellRing: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path><path d="M4 2C2.8 3.7 2 5.7 2 8"></path><path d="M22 8c0-2.3-.8-4.3-2-6"></path></svg>',
        fileEdit: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13.5V4a2 2 0 0 1 2-2h8.5L20 7.5V20a2 2 0 0 1-2 2h-5.5"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M10.42 12.61a2.1 2.1 0 1 1 2.97 2.97L7.95 21 4 22l.99-3.95 5.43-5.44Z"></path></svg>',
        lock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
        bell: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path></svg>',
        code2: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>',
        smile: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>',
        messageSquare: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
        home: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>'
    };

    return icons[iconName] || icons.layoutDashboard;
}

// ============================================
// Set Notifications from Server
// ============================================

window.setNotifications = function (notifications) {
    AppState.notifications = notifications;
    renderNotifications();
    updateNotificationBadge();
};

// ============================================
// Application Initialization
// ============================================

$(document).ready(function () {
    // Initialize splash screen first
    initSplash();

    // Initialize dark mode
    initDarkMode();

    // Initialize sidebar
    initSidebar();

    // Initialize tabs
    initTabs();

    // Initialize notifications
    renderNotifications();
    updateNotificationBadge();

    // Initialize dashboard slider if on dashboard
    initDashboardSlider();

    // Initialize event handlers
    initEventHandlers();

    // Initialize history state for SPA
    if (window.BackendConfig && !history.state) {
        const initialState = {
            pageId: window.BackendConfig.currentPage,
            pageLabel: window.BackendConfig.currentPageLabel,
            pageIcon: window.BackendConfig.currentPageIcon,
            url: window.location.href
        };
        history.replaceState(initialState, document.title, window.location.href);
    }
});
