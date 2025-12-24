{{-- Navigation Board Page --}}
{{-- This page allows users to toggle visibility of navigation items --}}
{{-- Star icon: toggle favorite (saves to DB) --}}
{{-- Menu text: navigate via PJAX (no save) --}}

<div class="nav-board-container">
    <div class="nav-board-grid">
        @foreach($allNavGroups as $group)
            <div class="nav-board-group">
                <div class="nav-board-category">
                    @php
                        $firstIcon = $group['items'][0]['icon'] ?? 'layoutDashboard';
                    @endphp
                    @include('backend.partials.icon', ['icon' => $firstIcon])
                    <h4>{{ $group['category'] }}</h4>
                </div>
                <div class="nav-board-items">
                    @foreach($group['items'] as $item)
                        @php
                            $isFavorite = in_array($item['id'], $userFavMenus ?? []);
                        @endphp
                        <div class="nav-board-item {{ $isFavorite ? 'visible' : '' }}" 
                             data-item-id="{{ $item['id'] }}"
                             data-item-url="{{ $item['url'] ?? '#' }}">
                            <button type="button" class="star-btn" title="Toggle favorite">
                                <svg class="star-icon" 
                                     xmlns="http://www.w3.org/2000/svg" 
                                     width="16" 
                                     height="16" 
                                     viewBox="0 0 24 24" 
                                     fill="{{ $isFavorite ? 'currentColor' : 'none' }}" 
                                     stroke="currentColor" 
                                     stroke-width="2" 
                                     stroke-linecap="round" 
                                     stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                            </button>
                            <a href="{{ $item['url'] ?? '#' }}" class="nav-board-label">{{ $item['label'] }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
.nav-board-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    transition: background-color 0.15s ease;
}

.nav-board-item:hover {
    background-color: var(--bg-surface-2, #f3f4f6);
}

.nav-board-item .star-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.15s ease;
}

.nav-board-item .star-btn:hover {
    background-color: rgba(245, 158, 11, 0.1);
}

.nav-board-item .star-icon {
    flex-shrink: 0;
    color: var(--text-tertiary, #9ca3af);
    transition: color 0.15s ease, transform 0.15s ease;
}

.nav-board-item .star-btn:hover .star-icon {
    color: var(--warning-color, #f59e0b);
    transform: scale(1.1);
}

.nav-board-item.visible .star-icon {
    color: var(--warning-color, #f59e0b);
}

.nav-board-item .nav-board-label {
    flex: 1;
    color: var(--text-primary, #1f2937);
    text-decoration: none;
    transition: color 0.15s ease;
    cursor: pointer;
    pointer-events: auto;
}

.nav-board-item .nav-board-label:hover {
    color: var(--primary, #6366f1);
    text-decoration: underline;
}
</style>

<script>
(function() {
    'use strict';
    
    /**
     * Update the visual state of a nav board item
     */
    function updateNavBoardItem(item, isFavorite) {
        var star = item.querySelector('.star-icon');
        if (!star) return;
        
        if (isFavorite) {
            item.classList.add('visible');
            star.setAttribute('fill', 'currentColor');
        } else {
            item.classList.remove('visible');
            star.setAttribute('fill', 'none');
        }
    }
    
    /**
     * Handle click on the star button - toggle favorite in DB
     */
    function handleStarClick(e) {
        var btn = e.target.closest('.star-btn');
        if (!btn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        // Prevent double-clicks
        if (btn.dataset.loading === 'true') {
            return;
        }
        btn.dataset.loading = 'true';
        
        var item = btn.closest('.nav-board-item');
        var itemId = item.getAttribute('data-item-id');
        
        if (!itemId) {
            btn.dataset.loading = 'false';
            return;
        }
        
        // Optimistically update UI
        var wasVisible = item.classList.contains('visible');
        updateNavBoardItem(item, !wasVisible);
        
        // Send to server using fetch API
        var apiUrl = (window.BackendConfig && window.BackendConfig.baseUrl || '') + '/api/user/fav-menus/toggle';
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ item_id: itemId })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(response) {
            btn.dataset.loading = 'false';
            
            if (response.success) {
                // Use server's is_favorite to set the correct visual state
                updateNavBoardItem(item, response.is_favorite);
                
                // Update BackendConfig with new favorites
                if (window.BackendConfig) {
                    window.BackendConfig.favMenus = response.fav_menus;
                }
                
                // Re-render sidebar to reflect changes
                if (typeof renderSidebar === 'function') {
                    renderSidebar();
                }
                
                if (window.Vodo && Vodo.notify) {
                    Vodo.notify.success(response.message);
                }
            } else {
                // Revert on failure
                updateNavBoardItem(item, wasVisible);
                if (window.Vodo && Vodo.notify) {
                    Vodo.notify.error(response.message || 'Failed to update favorites');
                }
            }
        })
        .catch(function(error) {
            btn.dataset.loading = 'false';
            // Revert on error
            updateNavBoardItem(item, wasVisible);
            if (window.Vodo && Vodo.notify) {
                Vodo.notify.error('Failed to update favorites');
            }
            console.error('Toggle error:', error);
        });
    }
    
    /**
     * Initialize the navigation board
     */
    function initNavBoard() {
        var container = document.querySelector('.nav-board-container');
        if (!container) return;
        
        // Prevent duplicate initialization
        if (container.dataset.initialized === 'true') {
            return;
        }
        container.dataset.initialized = 'true';
        
        // Attach click handler using event delegation (vanilla JS)
        container.addEventListener('click', function(e) {
            if (e.target.closest('.star-btn')) {
                handleStarClick(e);
            }
        });
    }
    
    // Initialize immediately
    initNavBoard();
})();
</script>
