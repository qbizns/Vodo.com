{{-- User Menu --}}
@php
    $authGuard = $guard ?? 'web';
    $user = auth($authGuard)->user();
    $userName = $user->name ?? 'User';
    $userEmail = $user->email ?? 'user@example.com';
    
    // Get user initials
    $nameParts = explode(' ', $userName);
    if (count($nameParts) >= 2) {
        $userInitials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
    } else {
        $userInitials = strtoupper(substr($userName, 0, 2));
    }
    
    // Get short name
    if (count($nameParts) > 1) {
        $shortName = $nameParts[0] . ' ' . substr(end($nameParts), 0, 1) . '.';
    } else {
        $shortName = $userName;
    }
@endphp

<div style="position: relative;">
    <button id="userMenuBtn" class="user-menu-btn">
        <div id="userAvatar" class="user-avatar">{{ $userInitials }}</div>
        <span id="userShortName" style="font-size: var(--text-caption);">{{ $shortName }}</span>
    </button>

    {{-- User Menu Panel --}}
    <div id="userMenuPanel" class="user-menu-panel" style="display: none;">
        <div class="user-profile-section">
            <div class="flex items-center" style="gap: var(--spacing-3);">
                <div id="userAvatarLarge" class="user-avatar-large">{{ $userInitials }}</div>
                <div>
                    <div id="userDisplayName" class="user-display-name">{{ $userName }}</div>
                    <div id="userEmail" class="user-email">{{ $userEmail }}</div>
                </div>
            </div>
        </div>
        <div class="user-menu-items">
            @if(isset($profileUrl))
            <a href="{{ $profileUrl }}" class="user-menu-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="5"></circle>
                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                </svg>
                <span>My Profile</span>
            </a>
            @endif
            
            @if(isset($settingsUrl))
            <a href="{{ $settingsUrl }}" class="user-menu-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <span>Settings</span>
            </a>
            @endif

            <div class="user-menu-divider"></div>
            
            <div class="dark-mode-toggle">
                <span>Dark mode</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            
            <div class="user-menu-divider"></div>
            
            <form action="{{ $logoutUrl ?? route($modulePrefix . '.logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="user-menu-item logout" style="width: 100%; border: none; background: none; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</div>
