{{-- Top Bar --}}
<div id="topBar" class="flex items-center justify-between" style="height: 48px; background-color: #2B2B2B; border-bottom: 1px solid #1A1A1A; padding: 0 var(--spacing-4);">
    {{-- Left - Logo/Brand + Tabs --}}
    <div class="flex items-center flex-1" style="gap: var(--spacing-4); min-width: 0; overflow: hidden;">
        <div class="flex items-center" style="gap: var(--spacing-4); flex-shrink: 0;">
            <div style="font-size: 15px; font-weight: var(--font-weight-semibold); color: #FFFFFF; letter-spacing: -0.02em;">
                {{ $brandName ?? 'VODO' }}
            </div>
            <div style="width: 1px; height: 20px; background-color: #404040;"></div>
            <div style="font-size: var(--text-caption); color: #A0A0A0;">{{ $version ?? 'v.1.0.0' }}</div>
        </div>

        {{-- Navigation Tabs --}}
        <div id="tabsContainer" 
             class="flex items-center" 
             style="gap: 2px; margin-left: var(--spacing-4); overflow: hidden; flex: 1 1 auto; min-width: 0;"
             data-current-page="{{ $currentPage ?? 'dashboard' }}"
             data-current-label="{{ $currentPageLabel ?? __t('dashboard.title') }}"
             data-current-icon="{{ $currentPageIcon ?? 'layoutDashboard' }}"
             data-base-url="{{ $baseUrl ?? '' }}">
            {{-- Tabs will be rendered dynamically by JavaScript --}}
        </div>
    </div>

    {{-- Right - Icons --}}
    <div class="flex items-center" style="gap: var(--spacing-2); flex-shrink: 0;">
        <button class="topbar-icon-btn" title="{{ __t('dashboard.search') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </button>
        <button class="topbar-icon-btn" title="{{ __t('dashboard.help_center') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </button>
        
        {{-- Notifications --}}
        @include('backend.partials.notifications')

        <div style="width: 1px; height: 20px; background-color: #404040; margin: 0 var(--spacing-2);"></div>

        {{-- User Menu --}}
        @include('backend.partials.user-menu')
    </div>
</div>
