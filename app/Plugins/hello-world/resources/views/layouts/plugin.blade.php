{{--
    Plugin Layout Template
    This layout extends the backend layout to integrate plugin pages into the admin panel
    It auto-detects the current module context (admin/console/owner)
--}}
@php
    // Detect module from current route/domain
    $routeName = request()->route()?->getName() ?? '';
    $routeParts = explode('.', $routeName);
    $detectedModule = in_array($routeParts[0] ?? '', ['admin', 'console', 'owner']) ? $routeParts[0] : 'admin';
    
    // Get module configuration
    $moduleConfig = [
        'admin' => [
            'guard' => 'admin',
            'modulePrefix' => 'admin',
            'brandName' => 'Vodo Admin',
            'splashSubtitle' => 'Admin',
            'logoutRoute' => 'admin.logout',
            'navBoardRoute' => 'admin.navigation-board',
        ],
        'console' => [
            'guard' => 'console',
            'modulePrefix' => 'console',
            'brandName' => 'Vodo Console',
            'splashSubtitle' => 'Console',
            'logoutRoute' => 'console.logout',
            'navBoardRoute' => 'console.navigation-board',
        ],
        'owner' => [
            'guard' => 'owner',
            'modulePrefix' => 'owner',
            'brandName' => 'Vodo Owner',
            'splashSubtitle' => 'Owner',
            'logoutRoute' => 'owner.logout',
            'navBoardRoute' => 'owner.navigation-board',
        ],
    ];
    
    $config = $moduleConfig[$detectedModule] ?? $moduleConfig['admin'];
@endphp

@extends('backend.layouts.app', [
    'guard' => $guard ?? $config['guard'],
    'modulePrefix' => $modulePrefix ?? $config['modulePrefix'],
    'brandName' => $brandName ?? $config['brandName'],
    'version' => $version ?? 'v.1.0.0',
    'baseUrl' => $baseUrl ?? '',
    'currentPage' => $currentPage ?? 'plugin',
    'currentPageLabel' => $currentPageLabel ?? 'Plugin',
    'currentPageIcon' => $currentPageIcon ?? 'plug',
    'splashTitle' => $splashTitle ?? 'VODO',
    'splashSubtitle' => $splashSubtitle ?? $config['splashSubtitle'],
    'splashVersion' => $splashVersion ?? 'Version 1.0.0',
    'splashCopyright' => $splashCopyright ?? '© ' . date('Y') . ' VODO Systems',
    'profileUrl' => $profileUrl ?? '/profile',
    'settingsUrl' => $settingsUrl ?? '/settings',
    'logoutUrl' => $logoutUrl ?? (Route::has($config['logoutRoute']) ? route($config['logoutRoute']) : '/logout'),
    'navBoardUrl' => $navBoardUrl ?? (Route::has($config['navBoardRoute']) ? route($config['navBoardRoute']) : '/navigation-board'),
])

@section('title')
    @yield('plugin-title', $pageTitle ?? 'Plugin')
@endsection

@section('header')
    @yield('plugin-header', $pageTitle ?? 'Plugin')
@endsection

@section('content')
    {{-- Include styles inline for PJAX navigation --}}
    @if(request()->header('X-PJAX'))
    <style>
    .plugin-content {
        padding: var(--spacing-6);
    }

    .plugin-card {
        background: var(--bg-surface-2);
        border-radius: var(--radius-lg);
        padding: var(--spacing-6);
        margin-bottom: var(--spacing-4);
    }

    .plugin-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--spacing-4);
        padding-bottom: var(--spacing-4);
        border-bottom: 1px solid var(--border-default);
    }

    .plugin-card-title {
        font-size: var(--text-lg);
        font-weight: 600;
        color: var(--text-primary);
    }

    .plugin-card-body {
        color: var(--text-secondary);
    }

    .plugin-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-4);
    }

    .plugin-stat-card {
        background: var(--bg-surface-1);
        border-radius: var(--radius-md);
        padding: var(--spacing-4);
        text-align: center;
    }

    .plugin-stat-value {
        font-size: var(--text-2xl);
        font-weight: 700;
        color: var(--color-primary);
    }

    .plugin-stat-label {
        font-size: var(--text-sm);
        color: var(--text-tertiary);
        margin-top: var(--spacing-1);
    }

    .plugin-btn {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        padding: var(--spacing-2) var(--spacing-4);
        border-radius: var(--radius-md);
        font-size: var(--text-sm);
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
    }

    .plugin-btn-primary {
        background: var(--color-primary);
        color: white;
    }

    .plugin-btn-primary:hover {
        background: var(--color-primary-hover);
    }

    .plugin-btn-secondary {
        background: var(--bg-surface-1);
        color: var(--text-primary);
        border: 1px solid var(--border-default);
    }

    .plugin-btn-secondary:hover {
        background: var(--bg-surface-2);
    }

    .plugin-btn-danger {
        background: var(--color-danger);
        color: white;
    }

    .plugin-btn-danger:hover {
        background: var(--color-danger-hover);
    }

    .plugin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .plugin-table th,
    .plugin-table td {
        padding: var(--spacing-3);
        text-align: left;
        border-bottom: 1px solid var(--border-default);
    }

    .plugin-table th {
        font-weight: 600;
        color: var(--text-primary);
        background: var(--bg-surface-1);
    }

    .plugin-table tr:hover {
        background: var(--bg-surface-1);
    }

    .plugin-alert {
        padding: var(--spacing-4);
        border-radius: var(--radius-md);
        margin-bottom: var(--spacing-4);
    }

    .plugin-alert-success {
        background: var(--color-success-bg);
        color: var(--color-success);
        border: 1px solid var(--color-success);
    }

    .plugin-alert-error {
        background: var(--color-danger-bg);
        color: var(--color-danger);
        border: 1px solid var(--color-danger);
    }

    .plugin-alert-info {
        background: var(--color-info-bg);
        color: var(--color-info);
        border: 1px solid var(--color-info);
    }

    .plugin-form-group {
        margin-bottom: var(--spacing-4);
    }

    .plugin-form-label {
        display: block;
        font-weight: 500;
        color: var(--text-primary);
        margin-bottom: var(--spacing-2);
    }

    .plugin-form-input {
        width: 100%;
        padding: var(--spacing-3);
        border: 1px solid var(--border-default);
        border-radius: var(--radius-md);
        background: var(--bg-surface-1);
        color: var(--text-primary);
        font-size: var(--text-base);
    }

    .plugin-form-input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px var(--color-primary-alpha);
    }

    .plugin-empty-state {
        text-align: center;
        padding: var(--spacing-8);
        color: var(--text-tertiary);
    }

    .plugin-empty-state-icon {
        font-size: 48px;
        margin-bottom: var(--spacing-4);
    }

    .plugin-empty-state-title {
        font-size: var(--text-lg);
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: var(--spacing-2);
    }

    /* Greetings page specific styles */
    .greetings-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-3);
    }

    .greeting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-4);
        background: var(--bg-surface-1);
        border-radius: var(--radius-md);
        transition: background 0.2s ease;
    }

    .greeting-item:hover {
        background: var(--bg-surface-2);
    }

    .greeting-content {
        flex: 1;
        min-width: 0;
    }

    .greeting-message {
        color: var(--text-primary);
        font-size: var(--text-base);
        margin-bottom: var(--spacing-1);
        word-break: break-word;
    }

    .greeting-meta {
        color: var(--text-tertiary);
        font-size: var(--text-sm);
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
    }

    .greeting-author {
        font-weight: 500;
        color: var(--text-secondary);
    }

    .greeting-separator {
        opacity: 0.5;
    }
    </style>
    {{-- Include plugin-specific scripts inline for PJAX --}}
    @stack('plugin-scripts')
    @endif
    <div class="plugin-content">
        @yield('plugin-content')
    </div>
    {{-- Include plugin-styles inline for PJAX --}}
    @if(request()->header('X-PJAX'))
    @stack('plugin-styles')
    @endif
@endsection

@section('command-bar')
    @yield('plugin-command-bar')
@endsection

@section('header-actions')
    @yield('plugin-header-actions')
@endsection

@push('styles')
<style>
.plugin-content {
    padding: var(--spacing-6);
}

.plugin-card {
    background: var(--bg-surface-2);
    border-radius: var(--radius-lg);
    padding: var(--spacing-6);
    margin-bottom: var(--spacing-4);
}

.plugin-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--spacing-4);
    padding-bottom: var(--spacing-4);
    border-bottom: 1px solid var(--border-default);
}

.plugin-card-title {
    font-size: var(--text-lg);
    font-weight: 600;
    color: var(--text-primary);
}

.plugin-card-body {
    color: var(--text-secondary);
}

.plugin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-4);
}

.plugin-stat-card {
    background: var(--bg-surface-1);
    border-radius: var(--radius-md);
    padding: var(--spacing-4);
    text-align: center;
}

.plugin-stat-value {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--color-primary);
}

.plugin-stat-label {
    font-size: var(--text-sm);
    color: var(--text-tertiary);
    margin-top: var(--spacing-1);
}

.plugin-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-4);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
}

.plugin-btn-primary {
    background: var(--color-primary);
    color: white;
}

.plugin-btn-primary:hover {
    background: var(--color-primary-hover);
}

.plugin-btn-secondary {
    background: var(--bg-surface-1);
    color: var(--text-primary);
    border: 1px solid var(--border-default);
}

.plugin-btn-secondary:hover {
    background: var(--bg-surface-2);
}

.plugin-btn-danger {
    background: var(--color-danger);
    color: white;
}

.plugin-btn-danger:hover {
    background: var(--color-danger-hover);
}

.plugin-table {
    width: 100%;
    border-collapse: collapse;
}

.plugin-table th,
.plugin-table td {
    padding: var(--spacing-3);
    text-align: left;
    border-bottom: 1px solid var(--border-default);
}

.plugin-table th {
    font-weight: 600;
    color: var(--text-primary);
    background: var(--bg-surface-1);
}

.plugin-table tr:hover {
    background: var(--bg-surface-1);
}

.plugin-alert {
    padding: var(--spacing-4);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-4);
}

.plugin-alert-success {
    background: var(--color-success-bg);
    color: var(--color-success);
    border: 1px solid var(--color-success);
}

.plugin-alert-error {
    background: var(--color-danger-bg);
    color: var(--color-danger);
    border: 1px solid var(--color-danger);
}

.plugin-alert-info {
    background: var(--color-info-bg);
    color: var(--color-info);
    border: 1px solid var(--color-info);
}

.plugin-form-group {
    margin-bottom: var(--spacing-4);
}

.plugin-form-label {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: var(--spacing-2);
}

.plugin-form-input {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface-1);
    color: var(--text-primary);
    font-size: var(--text-base);
}

.plugin-form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px var(--color-primary-alpha);
}

.plugin-empty-state {
    text-align: center;
    padding: var(--spacing-8);
    color: var(--text-tertiary);
}

.plugin-empty-state-icon {
    font-size: 48px;
    margin-bottom: var(--spacing-4);
}

.plugin-empty-state-title {
    font-size: var(--text-lg);
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: var(--spacing-2);
}
</style>
@stack('plugin-styles')
@endpush

@push('scripts')
@stack('plugin-scripts')
@endpush
