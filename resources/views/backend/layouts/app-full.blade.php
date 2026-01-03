{{--
    Full Page Layout for PJAX fallback
    This is used when a normal (non-PJAX) request is made to a page that uses the pjax layout.
    Content is passed as variables instead of using @yield.
--}}
<!DOCTYPE html>
<html lang="{{ current_locale() }}" dir="{{ text_direction() }}" class="lang-{{ current_locale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? __t('dashboard.title') }} - {{ $brandName ?? 'VODO' }}</title>
    {{-- Tailwind CSS with JIT compiler for full class support --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        accent: '#0078D7',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('backend/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/css/skeleton.css') }}">
    @if(is_rtl())
    <link rel="stylesheet" href="{{ asset('backend/css/rtl.css') }}">
    @endif
    
    {{-- Page-specific CSS --}}
    @php
        $currentPage = $currentPage ?? 'dashboard';
        
        // Direct page mappings
        $pageCssMap = [
            'system/settings' => 'settings',
            'system/plugins' => 'plugins',
            'settings' => 'settings',
            'plugins' => 'plugins',
            'system/roles' => 'permissions',
            'system/permissions' => 'permissions',
        ];
        
        // First check direct mapping
        $resolvedCss = $pageCssMap[$currentPage] ?? null;
        
        // If no direct mapping, check prefix-based mapping for sub-pages
        if (!$resolvedCss) {
            $prefixCssMap = [
                'system/plugins/' => 'plugins',
                'system/settings/' => 'settings',
                'system/roles/' => 'permissions',
                'system/permissions/' => 'permissions',
            ];
            foreach ($prefixCssMap as $prefix => $css) {
                if (str_starts_with($currentPage, $prefix)) {
                    $resolvedCss = $css;
                    break;
                }
            }
        }
        
        // Use provided requireCss or fallback to resolved
        $finalCss = $requireCss ?? $resolvedCss;
    @endphp
    @if($finalCss)
    <link rel="stylesheet" href="{{ asset('backend/css/pages/' . $finalCss . '.css') }}">
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
                {{-- Page Title Bar --}}
                <div class="page-title-bar flex items-center justify-between">
                    <h2 id="pageTitle" class="page-title">{!! $pageHeader ?: $pageTitle !!}</h2>
                    <div class="header-actions-container flex items-center" style="gap: var(--spacing-3);">
                        {!! $headerActions ?? '' !!}
                    </div>
                </div>

                {{-- Scrollable Content --}}
                <div id="pageContent" class="page-content flex-1 overflow-y-auto">
                    {!! $pageContent !!}
                </div>

                {{-- Bottom Command Bar (optional) --}}
                @if(!empty($commandBar))
                <div class="command-bar flex items-center justify-between">
                    {!! $commandBar !!}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Overlay for dropdowns --}}
    <div id="overlay" class="overlay" style="display: none;"></div>

    {{-- Backend Configuration for JavaScript --}}
    <script nonce="{{ csp_nonce() }}">
        window.BackendConfig = {
            modulePrefix: '{{ $modulePrefix ?? "" }}',
            baseUrl: '{{ $baseUrl ?? "" }}',
            csrfToken: '{{ csrf_token() }}',
            currentPage: '{{ $currentPage ?? "dashboard" }}',
            currentPageLabel: '{{ $pageTitle ?? __t("dashboard.title") }}',
            currentPageIcon: '{{ $currentPageIcon ?? "layoutDashboard" }}',
            navGroups: @json($navGroups ?? []),
            favMenus: @json($favMenus ?? ['dashboard', 'sites', 'databases']),
            locale: '{{ current_locale() }}',
            direction: '{{ text_direction() }}',
            isRtl: {{ is_rtl() ? 'true' : 'false' }}
        };
    </script>

    {{-- Translation JavaScript Helper --}}
    @translationsScript
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/translations.js') }}"></script>

    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/jquery-3.7.1.min.js') }}"></script>

    {{-- Vodo Framework Modules --}}
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.core.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.events.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.storage.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.skeleton.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.ajax.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.router.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.forms.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.components.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.modals.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.notifications.js') }}"></script>
    
    {{-- Page-specific JS modules --}}
    @if($finalCss === 'permissions')
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/vodo.permissions.js') }}"></script>
    @endif
    
    <script nonce="{{ csp_nonce() }}">
        // Initialize Vodo framework
        Vodo.init();
        Vodo.ready(function() {
            // Initialize all modules
            if (Vodo.storage) Vodo.storage.init();
            if (Vodo.ajax) Vodo.ajax.init();
            if (Vodo.router) Vodo.router.init();
            if (Vodo.forms) Vodo.forms.init();
            if (Vodo.components) Vodo.components.setup();
            if (Vodo.modals) Vodo.modals.init();
            if (Vodo.notify) Vodo.notify.init();
        });
    </script>

    {{-- Legacy Scripts (keeping for backward compatibility) --}}
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/plugins.js') }}"></script>
    <script nonce="{{ csp_nonce() }}" src="{{ asset('backend/js/main.js') }}"></script>
    
    {{-- Pass notifications to JS if available --}}
    @if(isset($notifications) && count($notifications) > 0)
    <script nonce="{{ csp_nonce() }}">
        $(document).ready(function() {
            window.setNotifications(@json($notifications));
        });
    </script>
    @endif

    @stack('scripts')
    @stack('inline-scripts')
</body>
</html>

