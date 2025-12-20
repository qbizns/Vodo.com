<!DOCTYPE html>
<html lang="{{ current_locale() }}" dir="{{ text_direction() }}" class="lang-{{ current_locale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __t('dashboard.title')) - {{ $brandName ?? 'VODO' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('backend/css/style.css') }}">
    @if(is_rtl())
    <link rel="stylesheet" href="{{ asset('backend/css/rtl.css') }}">
    @endif
    
    {{-- Page-specific CSS for SPA navigation --}}
    {{-- These are loaded dynamically via JS during AJAX navigation, but need to be included for initial page loads --}}
    @php
        $currentPage = $currentPage ?? 'dashboard';
        
        // Direct page mappings
        $pageCssMap = [
            'system/settings' => 'settings',
            'system/plugins' => 'plugins',
            'settings' => 'settings',
            'plugins' => 'plugins',
        ];
        
        // First check direct mapping
        $requiredCss = $pageCssMap[$currentPage] ?? null;
        
        // If no direct mapping, check prefix-based mapping for sub-pages
        if (!$requiredCss) {
            $prefixCssMap = [
                'system/plugins/' => 'plugins',  // system/plugins/marketplace, system/plugins/install, etc.
                'system/settings/' => 'settings',
            ];
            foreach ($prefixCssMap as $prefix => $css) {
                if (str_starts_with($currentPage, $prefix)) {
                    $requiredCss = $css;
                    break;
                }
            }
        }
    @endphp
    @if($requiredCss)
    <link rel="stylesheet" href="{{ asset('backend/css/pages/' . $requiredCss . '.css') }}">
    @endif
    
    @stack('styles')
</head>
<body class="{{ is_rtl() ? 'rtl' : 'ltr' }}">
    <div id="app" class="flex flex-col h-screen" style="background-color: var(--bg-window); font-family: var(--font-family-base);">
        
        {{-- Top Bar --}}
        @include('backend.partials.topbar')

        {{-- Main Content Area --}}
        <div class="flex flex-1 {{ is_rtl() ? 'flex-row-reverse' : '' }}" style="overflow: hidden;">
            {{-- Sidebar --}}
            @include('backend.partials.sidebar')

            {{-- Main Content --}}
            <div class="flex-1 flex flex-col" style="background-color: var(--bg-surface-1);">
                {{-- Page Title Bar (hidden when hidePageTitle is set) --}}
                @if(!($hidePageTitle ?? false))
                <div class="page-title-bar flex items-center justify-between">
                    <h2 id="pageTitle" class="page-title">@yield('header', __t('dashboard.title'))</h2>
                        <div class="header-actions-container flex items-center" style="gap: var(--spacing-3);">
                        @yield('header-actions')
                    </div>
                </div>
                @endif

                {{-- Scrollable Content --}}
                <div id="pageContent" class="page-content flex-1 overflow-y-auto">
                    @yield('content')
                </div>

                {{-- Bottom Command Bar (optional) --}}
                @hasSection('command-bar')
                <div class="command-bar flex items-center justify-between">
                    @yield('command-bar')
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Overlay for dropdowns --}}
    <div id="overlay" class="overlay" style="display: none;"></div>

    {{-- Backend Configuration for JavaScript --}}
    <script>
        window.BackendConfig = {
            modulePrefix: '{{ $modulePrefix ?? "" }}',
            baseUrl: '{{ $baseUrl ?? "" }}',
            csrfToken: '{{ csrf_token() }}',
            currentPage: '{{ $currentPage ?? "dashboard" }}',
            currentPageLabel: '{{ $currentPageLabel ?? __t("dashboard.title") }}',
            currentPageIcon: '{{ $currentPageIcon ?? "layoutDashboard" }}',
            navGroups: @json($navGroups ?? []),
            locale: '{{ current_locale() }}',
            direction: '{{ text_direction() }}',
            isRtl: {{ is_rtl() ? 'true' : 'false' }}
        };
    </script>

    {{-- Translation JavaScript Helper --}}
    @translationsScript
    <script src="{{ asset('backend/js/translations.js') }}"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('backend/js/plugins.js') }}"></script>
    <script src="{{ asset('backend/js/main.js') }}"></script>
    
    {{-- Pass notifications to JS if available --}}
    @if(isset($notifications) && count($notifications) > 0)
    <script>
        $(document).ready(function() {
            window.setNotifications(@json($notifications));
        });
    </script>
    @endif

    @stack('scripts')
    
    {{-- Inline scripts from page views (used by plugins, settings, etc.) --}}
    @stack('inline-scripts')
</body>
</html>
