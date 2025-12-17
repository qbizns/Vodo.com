{{-- Navigation Board Page --}}
{{-- This page allows users to toggle visibility of navigation items --}}

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
                        {{-- Visibility will be determined by JavaScript from localStorage --}}
                        <div class="nav-board-item" data-item-id="{{ $item['id'] }}">
                            <svg class="star-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            <span>{{ $item['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('styles')
<style>
.nav-board-item {
    cursor: pointer;
    user-select: none;
}

.nav-board-item .star-icon {
    pointer-events: none;
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';
    
    function getVisibleNavItems() {
        let visibleItems = new Set(['dashboard', 'sites', 'databases']);
        try {
            const saved = localStorage.getItem('visibleNavItems');
            if (saved) {
                visibleItems = new Set(JSON.parse(saved));
            }
        } catch (e) {
            console.error('Error reading visible nav items:', e);
        }
        return visibleItems;
    }
    
    function saveVisibleNavItems(visibleItems) {
        try {
            localStorage.setItem('visibleNavItems', JSON.stringify(Array.from(visibleItems)));
        } catch (e) {
            console.error('Failed to save visible nav items:', e);
        }
    }
    
    function updateNavBoardItem(item, itemId, isVisible) {
        const star = item.querySelector('.star-icon');
        if (!star) return;
        
        if (isVisible) {
            item.classList.add('visible');
            star.setAttribute('fill', 'currentColor');
        } else {
            item.classList.remove('visible');
            star.setAttribute('fill', 'none');
        }
    }
    
    function initNavBoardVisibility() {
        const visibleItems = getVisibleNavItems();
        const items = document.querySelectorAll('.nav-board-item');
        
        items.forEach(function(item) {
            const itemId = item.getAttribute('data-item-id');
            if (itemId) {
                const isVisible = visibleItems.has(itemId);
                updateNavBoardItem(item, itemId, isVisible);
            }
        });
    }
    
    function handleItemClick(e) {
        const item = e.currentTarget;
        const itemId = item.getAttribute('data-item-id');
        
        if (!itemId) {
            console.warn('No item-id found on clicked element');
            return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        let visibleItems = getVisibleNavItems();
        
        if (visibleItems.has(itemId)) {
            visibleItems.delete(itemId);
        } else {
            visibleItems.add(itemId);
        }
        
        saveVisibleNavItems(visibleItems);
        updateNavBoardItem(item, itemId, visibleItems.has(itemId));
        
        if (typeof renderSidebar === 'function') {
            renderSidebar();
        }
    }
    
    function initNavBoard() {
        initNavBoardVisibility();
        
        const items = document.querySelectorAll('.nav-board-item');
        items.forEach(function(item) {
            item.addEventListener('click', handleItemClick);
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavBoard);
    } else {
        setTimeout(initNavBoard, 100);
    }
})();
</script>
@endpush
