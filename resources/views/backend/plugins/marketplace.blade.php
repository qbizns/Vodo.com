{{-- Plugin Marketplace (Screen 1) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', __t('plugins.marketplace'))
@section('page-id', 'system/plugins/marketplace')
@section('require-css', 'plugins')

@section('header', __t('plugins.marketplace'))

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.plugins.install') }}" class="btn-primary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'upload'])
        <span>{{ __t('plugins.upload_plugin') }}</span>
    </a>
</div>
@endsection

@section('content')
<div class="marketplace-page">
    {{-- Search and Filters --}}
    <div class="marketplace-toolbar">
        <div class="search-filter-row">
            <div class="search-input-wrapper search-large">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text" 
                       id="marketplaceSearch" 
                       class="search-input" 
                       placeholder="{{ __t('plugins.search_marketplace') }}"
                       value="{{ $query }}">
            </div>
            
            <select id="categoryFilter" class="filter-select">
                <option value="all">{{ __t('plugins.all_categories') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat['slug'] }}" {{ $category === $cat['slug'] ? 'selected' : '' }}>
                        {{ $cat['name'] }}
                    </option>
                @endforeach
            </select>

            <select id="sortFilter" class="filter-select">
                <option value="popular" {{ $sort === 'popular' ? 'selected' : '' }}>{{ __t('plugins.sort_popular') }}</option>
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ __t('plugins.sort_newest') }}</option>
                <option value="rating" {{ $sort === 'rating' ? 'selected' : '' }}>{{ __t('plugins.sort_rating') }}</option>
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>{{ __t('plugins.sort_name') }}</option>
            </select>
        </div>
    </div>

    {{-- Category Pills --}}
    <div class="category-pills">
        <button type="button" 
                class="category-pill {{ $category === 'all' ? 'active' : '' }}" 
                onclick="filterByCategory('all')">
            {{ __t('common.all') }}
        </button>
        @foreach($categories as $cat)
            <button type="button" 
                    class="category-pill {{ $category === $cat['slug'] ? 'active' : '' }}" 
                    onclick="filterByCategory('{{ $cat['slug'] }}')">
                {{ $cat['name'] }}
            </button>
        @endforeach
    </div>

    {{-- Plugins Grid --}}
    @if(empty($plugins))
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'store'])
            </div>
            <h3>{{ __t('plugins.no_plugins_found') }}</h3>
            <p>{{ __t('plugins.try_different_search') }}</p>
        </div>
    @else
        <div class="marketplace-grid" id="marketplaceGrid">
            @foreach($plugins as $plugin)
                <div class="marketplace-card" data-slug="{{ $plugin['slug'] }}">
                    <div class="card-icon">
                        @if(!empty($plugin['icon']))
                            <img src="{{ $plugin['icon'] }}" alt="{{ $plugin['name'] }}">
                        @else
                            @include('backend.partials.icon', ['icon' => 'plug'])
                        @endif
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title">{{ $plugin['name'] }}</h3>
                        
                        <div class="card-meta">
                            <div class="card-rating">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="star {{ $i <= round($plugin['rating'] ?? 0) ? 'filled' : '' }}">★</span>
                                @endfor
                                <span class="rating-count">({{ $plugin['reviews_count'] ?? 0 }})</span>
                            </div>
                            <span class="card-author">{{ __t('plugins.by') }} {{ $plugin['author']['name'] ?? 'Unknown' }}</span>
                        </div>
                        
                        <p class="card-description">{{ Str::limit($plugin['short_description'] ?? $plugin['description'] ?? '', 100) }}</p>
                        
                        <div class="card-footer">
                            <div class="card-info">
                                <span class="version">v{{ $plugin['version'] }}</span>
                                <span class="downloads">{{ number_format($plugin['downloads'] ?? 0) }} {{ __t('plugins.installs') }}</span>
                            </div>
                            
                            <div class="card-compatibility">
                                @if(($plugin['compatibility'] ?? 'compatible') === 'compatible')
                                    <span class="badge badge-success">
                                        @include('backend.partials.icon', ['icon' => 'check'])
                                        {{ __t('plugins.compatible') }}
                                    </span>
                                @elseif(($plugin['compatibility'] ?? '') === 'requires_update')
                                    <span class="badge badge-warning">
                                        @include('backend.partials.icon', ['icon' => 'alertTriangle'])
                                        {{ __t('plugins.requires_update') }}
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        @include('backend.partials.icon', ['icon' => 'x'])
                                        {{ __t('plugins.incompatible') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <a href="{{ route('admin.plugins.show', $plugin['slug']) }}" 
                           class="btn-secondary btn-sm">
                            {{ __t('plugins.view') }}
                        </a>
                        @if(in_array($plugin['slug'], $installed))
                            <span class="badge badge-installed">{{ __t('plugins.installed') }}</span>
                        @else
                            <button type="button" 
                                    class="btn-primary btn-sm" 
                                    onclick="installFromMarketplace('{{ $plugin['slug'] }}')">
                                {{ __t('plugins.install') }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if(!empty($pagination['last_page']) && $pagination['last_page'] > 1)
            <div class="marketplace-pagination">
                @for($i = 1; $i <= min($pagination['last_page'], 10); $i++)
                    <button type="button" 
                            class="page-btn {{ ($pagination['current_page'] ?? 1) == $i ? 'active' : '' }}"
                            onclick="goToPage({{ $i }})">
                        {{ $i }}
                    </button>
                @endfor
                @if($pagination['last_page'] > 10)
                    <span class="page-ellipsis">...</span>
                    <button type="button" 
                            class="page-btn" 
                            onclick="goToPage({{ $pagination['last_page'] }})">
                        {{ $pagination['last_page'] }}
                    </button>
                @endif
            </div>
        @endif
    @endif
</div>

@push('inline-scripts')
<script nonce="{{ csp_nonce() }}">
// Initialize immediately (works for both initial load and PJAX)
(function() {
    initMarketplaceSearch();
    initMarketplaceFilters();
    console.log('[Marketplace] Script initialized');
})();

function navigateMarketplace(url) {
    if (typeof navigateToPage === 'function') {
        navigateToPage(url.toString(), 'system/plugins/marketplace', '{{ __t("plugins.marketplace") }}', 'store');
    } else {
        window.location.href = url.toString();
    }
}

function filterByCategory(category) {
    const url = new URL(window.location.href);
    url.searchParams.set('category', category);
    url.searchParams.delete('page');
    navigateMarketplace(url);
}

function goToPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    navigateMarketplace(url);
}

function installFromMarketplace(slug) {
    const installUrl = '{{ route("admin.plugins.install") }}?slug=' + encodeURIComponent(slug) + '&marketplace=true';
    if (typeof navigateToPage === 'function') {
        navigateToPage(installUrl, 'system/plugins/install', '{{ __t("plugins.install") }}', 'upload');
    } else {
        window.location.href = installUrl;
    }
}

function initMarketplaceSearch() {
    let searchTimeout;
    
    // Search input with debounce
    $(document).off('input', '#marketplaceSearch').on('input', '#marketplaceSearch', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        
        searchTimeout = setTimeout(function() {
            const url = new URL(window.location.href);
            if (query && query.trim()) {
                url.searchParams.set('q', query.trim());
            } else {
                url.searchParams.delete('q');
            }
            url.searchParams.delete('page');
            navigateMarketplace(url);
        }, 400);
    });
    
    // Handle Enter key
    $(document).off('keydown', '#marketplaceSearch').on('keydown', '#marketplaceSearch', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimeout);
            const query = $(this).val();
            const url = new URL(window.location.href);
            if (query && query.trim()) {
                url.searchParams.set('q', query.trim());
            } else {
                url.searchParams.delete('q');
            }
            url.searchParams.delete('page');
            navigateMarketplace(url);
        }
    });
}

function initMarketplaceFilters() {
    // Category and sort filters
    $(document).off('change', '#categoryFilter, #sortFilter').on('change', '#categoryFilter, #sortFilter', function() {
        const url = new URL(window.location.href);
        const paramName = this.id.replace('Filter', '');
        url.searchParams.set(paramName, this.value);
        url.searchParams.delete('page');
        navigateMarketplace(url);
    });
}

// Make functions globally available
window.filterByCategory = filterByCategory;
window.goToPage = goToPage;
window.installFromMarketplace = installFromMarketplace;
</script>
@endpush
@endsection
