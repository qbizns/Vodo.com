<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ $brandName ?? 'VODO' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('backend/css/style.css') }}">
    @stack('styles')
</head>
<body>
    <div id="app" class="flex flex-col h-screen" style="background-color: var(--bg-window); font-family: var(--font-family-base);">
        
        {{-- Top Bar --}}
        @include('backend.partials.topbar')

        {{-- Main Content Area --}}
        <div class="flex flex-1" style="overflow: hidden;">
            {{-- Sidebar --}}
            @include('backend.partials.sidebar')

            {{-- Main Content --}}
            <div class="flex-1 flex flex-col" style="background-color: var(--bg-surface-1);">
                {{-- Page Title Bar --}}
                <div class="page-title-bar flex items-center justify-between">
                    <h2 id="pageTitle" class="page-title">@yield('header', 'Dashboard')</h2>
                    @hasSection('header-actions')
                        <div class="flex items-center" style="gap: var(--spacing-3);">
                            @yield('header-actions')
                        </div>
                    @endif
                </div>

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
            currentPageLabel: '{{ $currentPageLabel ?? "Dashboard" }}',
            currentPageIcon: '{{ $currentPageIcon ?? "layoutDashboard" }}',
            navGroups: @json($navGroups ?? [])
        };
    </script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
</body>
</html>
