{{-- Sidebar --}}
<div id="sidebar" class="sidebar flex flex-col">
    {{-- Scrollable Navigation - Populated by JavaScript based on visibility --}}
    <nav id="sidebarNav" class="sidebar-nav flex-1">
        {{-- Navigation items will be rendered by JavaScript --}}
        {{-- This allows dynamic filtering based on localStorage visibility settings --}}
        <div style="padding: var(--spacing-4); text-align: center; color: var(--text-tertiary); font-size: var(--text-caption);">
            {{ __t('common.loading') }}
        </div>
    </nav>

    {{-- Bottom Actions --}}
    <div class="sidebar-footer">
        @if(isset($navBoardUrl))
        {{-- Navigation handled by Vodo.router --}}
        <a href="{{ $navBoardUrl }}" id="navBoardBtn" class="nav-board-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>{{ __t('navigation.navigation') }}</span>
        </a>
        @endif
        <button id="collapseBtn" class="collapse-btn" title="{{ __t('dashboard.collapse_sidebar') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
    </div>
</div>
