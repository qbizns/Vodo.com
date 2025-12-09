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

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize visibility state from localStorage
    initNavBoardVisibility();
});

function initNavBoardVisibility() {
    // Get visible items from localStorage
    let visibleItems = new Set(['dashboard', 'sites', 'databases']); // Default
    try {
        const saved = localStorage.getItem('visibleNavItems');
        if (saved) {
            visibleItems = new Set(JSON.parse(saved));
        }
    } catch (e) {}
    
    // Update each nav board item's state
    $('.nav-board-item').each(function() {
        const itemId = $(this).data('item-id');
        const isVisible = visibleItems.has(itemId);
        const $item = $(this);
        const $star = $item.find('.star-icon');
        
        if (isVisible) {
            $item.addClass('visible');
            $star.attr('fill', 'currentColor');
        } else {
            $item.removeClass('visible');
            $star.attr('fill', 'none');
        }
    });
}
</script>
@endpush
