{{-- Installed Plugins List Content (Screen 2) --}}
{{-- This is the content partial included by the admin module wrapper --}}

<div class="plugins-page">
    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">
            @include('backend.partials.icon', ['icon' => 'checkCircle'])
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            @include('backend.partials.icon', ['icon' => 'alertCircle'])
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Search and Filters --}}
    <div class="plugins-toolbar">
        <div class="search-filter-group">
            <div class="search-input-wrapper">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text" 
                       id="pluginSearch" 
                       class="search-input" 
                       placeholder="{{ __t('plugins.search_installed') }}"
                       value="{{ request('search') }}">
            </div>
            
            <select id="statusFilter" class="filter-select">
                <option value="">{{ __t('plugins.all_status') }}</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __t('plugins.active') }}</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __t('plugins.inactive') }}</option>
                <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>{{ __t('plugins.error') }}</option>
            </select>

            @if(isset($categories) && $categories->isNotEmpty())
            <select id="categoryFilter" class="filter-select">
                <option value="">{{ __t('plugins.all_categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                        {{ ucfirst($category) }}
                    </option>
                @endforeach
            </select>
            @endif

            <select id="viewMode" class="filter-select">
                <option value="list">{{ __t('plugins.list_view') }}</option>
                <option value="grid">{{ __t('plugins.grid_view') }}</option>
            </select>
        </div>

        <div class="plugins-count">
            {{ __t('plugins.showing_of', ['showing' => $plugins->count(), 'total' => $stats['total'] ?? $plugins->total()]) }}
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="selectAllPlugins" class="form-checkbox">
                <span>{{ __t('plugins.select_all') }}</span>
            </label>
            <span id="selectedCount">0 {{ __t('plugins.selected') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="btn-secondary btn-sm" onclick="bulkAction('activate')">
                {{ __t('plugins.activate') }}
            </button>
            <button type="button" class="btn-secondary btn-sm" onclick="bulkAction('deactivate')">
                {{ __t('plugins.deactivate') }}
            </button>
            <button type="button" class="btn-secondary btn-sm" onclick="bulkAction('update')">
                {{ __t('plugins.update') }}
            </button>
            <button type="button" class="btn-danger btn-sm" onclick="bulkAction('delete')">
                {{ __t('plugins.delete') }}
            </button>
        </div>
    </div>

    {{-- Plugins List --}}
    @if($plugins->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'plug'])
            </div>
            <h3>{{ __t('plugins.no_plugins') }}</h3>
            <p>{{ __t('plugins.no_plugins_desc') }}</p>
            <div class="flex gap-3 justify-center mt-4">
                <a href="{{ route('admin.plugins.install') }}" class="btn-secondary" onclick="event.preventDefault(); navigateToPage(this.href)">
                    {{ __t('plugins.upload_plugin') }}
                </a>
                <a href="{{ route('admin.plugins.marketplace') }}" class="btn-primary" onclick="event.preventDefault(); navigateToPage(this.href)">
                    {{ __t('plugins.browse_marketplace') }}
                </a>
            </div>
        </div>
    @else
        <div class="plugins-list" id="pluginsList">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAllHeader" class="form-checkbox">
                        </th>
                        <th style="width: 50px;"></th>
                        <th>{{ __t('plugins.name') }}</th>
                        <th style="width: 100px;">{{ __t('plugins.status') }}</th>
                        <th style="width: 120px;">{{ __t('plugins.version') }}</th>
                        <th style="width: 120px;">{{ __t('plugins.category') }}</th>
                        <th style="width: 80px;" class="text-right">{{ __t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($plugins as $plugin)
                        <tr class="plugin-row" data-plugin-id="{{ $plugin->id }}" data-plugin-slug="{{ $plugin->slug }}">
                            <td>
                                <input type="checkbox" 
                                       class="form-checkbox plugin-checkbox" 
                                       value="{{ $plugin->id }}"
                                       {{ $plugin->is_core ? 'disabled' : '' }}>
                            </td>
                            <td>
                                <div class="plugin-icon-wrapper">
                                    @if($plugin->icon_url)
                                        <img src="{{ $plugin->icon_url }}" alt="{{ $plugin->name }}" class="plugin-icon">
                                    @else
                                        @include('backend.partials.icon', ['icon' => 'plug'])
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="plugin-info">
                                    <a href="{{ route('admin.plugins.show', $plugin->slug) }}" class="plugin-name-link" onclick="event.preventDefault(); navigateToPage(this.href)">
                                        {{ $plugin->name }}
                                        @if($plugin->is_core)
                                            <span class="core-badge">{{ __t('plugins.core') }}</span>
                                        @endif
                                    </a>
                                    @if($plugin->description)
                                        <p class="plugin-description">{{ Str::limit($plugin->description, 80) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @include('backend.plugins.partials.status-badge', ['status' => $plugin->status])
                            </td>
                            <td>
                                <span class="plugin-version">v{{ $plugin->version }}</span>
                                @if($plugin->has_update)
                                    <span class="update-available" title="{{ __t('plugins.update_to', ['version' => $plugin->latest_version]) }}">
                                        @include('backend.partials.icon', ['icon' => 'arrowUp'])
                                        {{ $plugin->latest_version }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($plugin->category)
                                    <span class="category-badge">{{ ucfirst($plugin->category) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="actions-dropdown">
                                    <button type="button" class="action-menu-btn">
                                        @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                    </button>
                                    <div class="action-menu">
                                        @if($plugin->has_settings)
                                            <a href="{{ route('admin.plugins.settings', $plugin->slug) }}" class="action-item" onclick="event.preventDefault(); navigateToPage(this.href)">
                                                @include('backend.partials.icon', ['icon' => 'settings'])
                                                {{ __t('plugins.settings') }}
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.plugins.show', $plugin->slug) }}" class="action-item" onclick="event.preventDefault(); navigateToPage(this.href)">
                                            @include('backend.partials.icon', ['icon' => 'info'])
                                            {{ __t('plugins.details') }}
                                        </a>
                                        @if($plugin->has_update)
                                            <button type="button" class="action-item" onclick="updatePlugin('{{ $plugin->slug }}')">
                                                @include('backend.partials.icon', ['icon' => 'refreshCw'])
                                                {{ __t('plugins.update') }}
                                            </button>
                                        @endif
                                        <div class="action-divider"></div>
                                        @if($plugin->isActive())
                                            @if(!$plugin->is_core)
                                                <button type="button" class="action-item" onclick="deactivatePlugin('{{ $plugin->slug }}')">
                                                    @include('backend.partials.icon', ['icon' => 'pause'])
                                                    {{ __t('plugins.deactivate') }}
                                                </button>
                                            @endif
                                        @else
                                            <button type="button" class="action-item" onclick="activatePlugin('{{ $plugin->slug }}')">
                                                @include('backend.partials.icon', ['icon' => 'play'])
                                                {{ __t('plugins.activate') }}
                                            </button>
                                        @endif
                                        @if(!$plugin->is_core)
                                            <div class="action-divider"></div>
                                            <button type="button" class="action-item danger" onclick="uninstallPlugin('{{ $plugin->slug }}', '{{ $plugin->name }}')">
                                                @include('backend.partials.icon', ['icon' => 'trash'])
                                                {{ __t('plugins.uninstall') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($plugins->hasPages())
            <div class="pagination-wrapper">
                {{ $plugins->links() }}
            </div>
        @endif
    @endif

    {{-- Quick Stats Footer --}}
    <div class="plugins-stats-bar">
        <div class="stat-item">
            <span class="stat-value">{{ $stats['total'] ?? $plugins->total() }}</span>
            <span class="stat-label">{{ __t('plugins.installed') }}</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-value stat-active">{{ $stats['active'] ?? 0 }}</span>
            <span class="stat-label">{{ __t('plugins.active') }}</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-value {{ ($stats['updates'] ?? 0) > 0 ? 'stat-warning' : '' }}">{{ $stats['updates'] ?? 0 }}</span>
            <span class="stat-label">{{ __t('plugins.updates_available') }}</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-value {{ ($stats['licenses_expiring'] ?? 0) > 0 ? 'stat-danger' : '' }}">{{ $stats['licenses_expiring'] ?? 0 }}</span>
            <span class="stat-label">{{ __t('plugins.licenses_expiring') }}</span>
        </div>
    </div>
</div>

@include('backend.plugins.scripts')
