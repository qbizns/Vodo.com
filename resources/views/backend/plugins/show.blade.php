{{-- Plugin Details (Screen 3) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', $plugin->name)
@section('page-id', 'system/plugins/' . $plugin->slug)
@section('require-css', 'plugins')

@section('header', $plugin->name)

@section('header-actions')
<div class="flex items-center gap-3">
    @if($plugin->has_settings)
        <a href="{{ route('admin.plugins.settings', $plugin->slug) }}" class="btn-secondary flex items-center gap-2">
            @include('backend.partials.icon', ['icon' => 'settings'])
            <span>{{ __t('plugins.settings') }}</span>
        </a>
    @endif
    
    @if($plugin->isActive() && !$plugin->is_core)
        <button type="button" class="btn-secondary" onclick="deactivatePlugin('{{ $plugin->slug }}')">
            {{ __t('plugins.deactivate') }}
        </button>
    @elseif(!$plugin->isActive())
        <button type="button" class="btn-primary" onclick="activatePlugin('{{ $plugin->slug }}')">
            {{ __t('plugins.activate') }}
        </button>
    @endif
    
    @if(!$plugin->is_core)
        <button type="button" class="btn-danger" onclick="uninstallPlugin('{{ $plugin->slug }}', '{{ $plugin->name }}')">
            {{ __t('plugins.uninstall') }}
        </button>
    @endif
</div>
@endsection

@section('content')
<div class="plugin-details-page">
    {{-- Plugin Header Card --}}
    <div class="plugin-header-card">
        <div class="plugin-icon-large">
            @if($plugin->icon_url)
                <img src="{{ $plugin->icon_url }}" alt="{{ $plugin->name }}">
            @else
                @include('backend.partials.icon', ['icon' => 'plug'])
            @endif
        </div>
        
        <div class="plugin-header-info">
            <div class="plugin-title-row">
                <h1 class="plugin-title">{{ $plugin->name }}</h1>
                @if($plugin->is_core)
                    <span class="core-badge">{{ __t('plugins.core') }}</span>
                @endif
                @if($plugin->is_premium)
                    <span class="premium-badge">{{ __t('plugins.premium') }}</span>
                @endif
            </div>
            
            <div class="plugin-meta-row">
                @if($plugin->author)
                    <span class="meta-item">
                        {{ __t('plugins.by') }} 
                        @if($plugin->author_url)
                            <a href="{{ $plugin->author_url }}" target="_blank" rel="noopener">{{ $plugin->author }}</a>
                        @else
                            {{ $plugin->author }}
                        @endif
                    </span>
                @endif
                <span class="meta-item">v{{ $plugin->version }}</span>
                @if($plugin->category)
                    <span class="meta-item">{{ ucfirst($plugin->category) }}</span>
                @endif
            </div>
            
            <div class="plugin-status-row">
                @include('backend.plugins.partials.status-badge', ['status' => $plugin->status])
                
                @if($plugin->has_update)
                    <span class="update-badge">
                        @include('backend.partials.icon', ['icon' => 'arrowUp'])
                        {{ __t('plugins.update_available') }}: v{{ $plugin->latest_version }}
                    </span>
                @endif
                
                @if($plugin->requires_license)
                    @if($plugin->has_valid_license)
                        <span class="license-badge valid">
                            @include('backend.partials.icon', ['icon' => 'key'])
                            {{ __t('plugins.licensed') }}
                        </span>
                    @else
                        <span class="license-badge invalid">
                            @include('backend.partials.icon', ['icon' => 'alertTriangle'])
                            {{ __t('plugins.license_required') }}
                        </span>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="plugin-tabs">
        <button type="button" class="tab-btn {{ $activeTab === 'overview' ? 'active' : '' }}" data-tab="overview">
            {{ __t('plugins.overview') }}
        </button>
        @if(!empty($screenshots))
            <button type="button" class="tab-btn {{ $activeTab === 'screenshots' ? 'active' : '' }}" data-tab="screenshots">
                {{ __t('plugins.screenshots') }}
            </button>
        @endif
        @if(!empty($changelog))
            <button type="button" class="tab-btn {{ $activeTab === 'changelog' ? 'active' : '' }}" data-tab="changelog">
                {{ __t('plugins.changelog') }}
            </button>
        @endif
        @if(!empty($permissions))
            <button type="button" class="tab-btn {{ $activeTab === 'permissions' ? 'active' : '' }}" data-tab="permissions">
                {{ __t('plugins.permissions') }}
            </button>
        @endif
        <button type="button" class="tab-btn {{ $activeTab === 'dependencies' ? 'active' : '' }}" data-tab="dependencies">
            {{ __t('plugins.dependencies') }}
        </button>
        <button type="button" class="tab-btn {{ $activeTab === 'support' ? 'active' : '' }}" data-tab="support">
            {{ __t('plugins.support') }}
        </button>
    </div>

    {{-- Tab Content --}}
    <div class="plugin-tab-content">
        {{-- Overview Tab --}}
        <div class="tab-pane {{ $activeTab === 'overview' ? 'active' : '' }}" id="tab-overview">
            {{-- Registered Components --}}
            @if(isset($components) && array_sum($components) > 0)
                <div class="section-card mb-4">
                    <h3>{{ __t('plugins.registered_components') }}</h3>
                    <div class="components-grid">
                        <div class="component-item">
                            <span class="comp-label">
                                @include('backend.partials.icon', ['icon' => 'lock'])
                                {{ __t('plugins.comp_permissions') }}
                            </span>
                            <span class="comp-value">{{ $components['permissions'] }}</span>
                        </div>
                        <div class="component-item">
                            <span class="comp-label">
                                @include('backend.partials.icon', ['icon' => 'barChart'])
                                {{ __t('plugins.comp_widgets') }}
                            </span>
                            <span class="comp-value">{{ $components['widgets'] }}</span>
                        </div>
                        <div class="component-item">
                            <span class="comp-label">
                                @include('backend.partials.icon', ['icon' => 'database'])
                                {{ __t('plugins.comp_entities') }}
                            </span>
                            <span class="comp-value">{{ $components['entities'] }}</span>
                        </div>
                        <div class="component-item">
                            <span class="comp-label">
                                @include('backend.partials.icon', ['icon' => 'calendar'])
                                {{ __t('plugins.comp_tasks') }}
                            </span>
                            <span class="comp-value">{{ $components['tasks'] }}</span>
                        </div>
                        <div class="component-item">
                            <span class="comp-label">
                                @include('backend.partials.icon', ['icon' => 'link'])
                                {{ __t('plugins.comp_endpoints') }}
                            </span>
                            <span class="comp-value">{{ $components['endpoints'] }}</span>
                        </div>
                        <div class="component-item">
                            <span class="comp-label">
                                @include('backend.partials.icon', ['icon' => 'menu'])
                                {{ __t('plugins.comp_menus') }}
                            </span>
                            <span class="comp-value">{{ $components['menus'] }}</span>
                        </div>
                        <div class="component-item">
                            <span class="comp-label">
                                @include('backend.partials.icon', ['icon' => 'gitMerge'])
                                {{ __t('plugins.comp_workflows') }}
                            </span>
                            <span class="comp-value">{{ $components['workflows'] }}</span>
                        </div>
                        <div class="component-item">
                            <span class="comp-label">
                                @include('backend.partials.icon', ['icon' => 'code'])
                                {{ __t('plugins.comp_shortcodes') }}
                            </span>
                            <span class="comp-value">{{ $components['shortcodes'] }}</span>
                        </div>
                    </div>
                </div>
            @endif
            <div class="overview-grid">
                <div class="overview-main">
                    <div class="section-card">
                        <h3>{{ __t('plugins.description') }}</h3>
                        <div class="plugin-description">
                            {!! nl2br(e($plugin->description ?? $manifest['description'] ?? __t('plugins.no_description'))) !!}
                        </div>
                    </div>

                    @if(!empty($manifest['features']))
                        <div class="section-card">
                            <h3>{{ __t('plugins.features') }}</h3>
                            <ul class="features-list">
                                @foreach($manifest['features'] as $feature)
                                    <li>
                                        @include('backend.partials.icon', ['icon' => 'check'])
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="overview-sidebar">
                    <div class="section-card">
                        <h3>{{ __t('plugins.information') }}</h3>
                        <dl class="info-list">
                            <dt>{{ __t('plugins.version') }}</dt>
                            <dd>{{ $plugin->version }}</dd>
                            
                            @if($plugin->installed_at)
                                <dt>{{ __t('plugins.installed') }}</dt>
                                <dd>{{ $plugin->installed_at->format('M d, Y') }}</dd>
                            @endif
                            
                            @if($plugin->activated_at)
                                <dt>{{ __t('plugins.last_activated') }}</dt>
                                <dd>{{ $plugin->activated_at->diffForHumans() }}</dd>
                            @endif
                            
                            @if($plugin->homepage)
                                <dt>{{ __t('plugins.homepage') }}</dt>
                                <dd><a href="{{ $plugin->homepage }}" target="_blank" rel="noopener">{{ __t('plugins.visit_website') }}</a></dd>
                            @endif
                        </dl>
                    </div>

                    <div class="section-card">
                        <h3>{{ __t('plugins.requirements') }}</h3>
                        <dl class="info-list">
                            @if($plugin->min_system_version)
                                <dt>{{ __t('plugins.system') }}</dt>
                                <dd>{{ $plugin->min_system_version }}+</dd>
                            @endif
                            
                            @if($plugin->min_php_version)
                                <dt>PHP</dt>
                                <dd>{{ $plugin->min_php_version }}+</dd>
                            @endif
                            
                            @if($plugin->dependencies->isNotEmpty())
                                <dt>{{ __t('plugins.dependencies') }}</dt>
                                <dd>{{ $plugin->dependencies->count() }} {{ __t('plugins.plugins') }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Screenshots Tab --}}
        @if(!empty($screenshots))
            <div class="tab-pane {{ $activeTab === 'screenshots' ? 'active' : '' }}" id="tab-screenshots">
                <div class="screenshots-grid">
                    @foreach($screenshots as $index => $screenshot)
                        <div class="screenshot-item" onclick="openLightbox({{ $index }})">
                            <img src="{{ $screenshot['url'] ?? $screenshot }}" alt="{{ $screenshot['caption'] ?? 'Screenshot ' . ($index + 1) }}">
                            @if(!empty($screenshot['caption']))
                                <span class="screenshot-caption">{{ $screenshot['caption'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Changelog Tab --}}
        @if(!empty($changelog))
            <div class="tab-pane {{ $activeTab === 'changelog' ? 'active' : '' }}" id="tab-changelog">
                <div class="changelog-list">
                    @foreach($changelog as $version)
                        <div class="changelog-item">
                            <div class="changelog-header">
                                <span class="changelog-version">{{ __t('plugins.version') }} {{ $version['version'] }}</span>
                                @if($version['date'])
                                    <span class="changelog-date">{{ $version['date'] }}</span>
                                @endif
                            </div>
                            <ul class="changelog-changes">
                                @foreach($version['changes'] as $change)
                                    <li>{{ $change }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Permissions Tab --}}
        @if(!empty($permissions))
            <div class="tab-pane {{ $activeTab === 'permissions' ? 'active' : '' }}" id="tab-permissions">
                <p class="permissions-intro">{{ __t('plugins.permissions_intro') }}</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __t('plugins.permission') }}</th>
                            <th>{{ __t('plugins.description') }}</th>
                            <th>{{ __t('plugins.default') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $permission)
                            <tr>
                                <td><code>{{ $permission['name'] ?? $permission }}</code></td>
                                <td>{{ $permission['description'] ?? '' }}</td>
                                <td>{{ $permission['default'] ?? 'Admins' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Dependencies Tab --}}
        <div class="tab-pane {{ $activeTab === 'dependencies' ? 'active' : '' }}" id="tab-dependencies">
            @if(empty($dependencies))
                <div class="empty-state-small">
                    <p>{{ __t('plugins.no_dependencies') }}</p>
                </div>
            @else
                <div class="dependencies-section">
                    <h3>{{ __t('plugins.required_dependencies') }}</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __t('plugins.plugin') }}</th>
                                <th>{{ __t('plugins.required') }}</th>
                                <th>{{ __t('plugins.installed') }}</th>
                                <th>{{ __t('plugins.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dependencies as $dep)
                                <tr>
                                    <td>{{ $dep['name'] }}</td>
                                    <td>{{ $dep['required_version'] }}</td>
                                    <td>{{ $dep['installed_version'] ?? '—' }}</td>
                                    <td>
                                        @if($dep['status'] === 'satisfied')
                                            <span class="badge badge-success">✓ {{ __t('plugins.compatible') }}</span>
                                        @elseif($dep['status'] === 'missing')
                                            <span class="badge badge-danger">{{ __t('plugins.missing') }}</span>
                                        @elseif($dep['status'] === 'inactive')
                                            <span class="badge badge-warning">{{ __t('plugins.inactive') }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ __t('plugins.version_mismatch') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($dependents->isNotEmpty())
                <div class="dependencies-section">
                    <h3>{{ __t('plugins.dependent_plugins') }}</h3>
                    <p class="section-intro">{{ __t('plugins.dependent_plugins_desc') }}</p>
                    <ul class="dependents-list">
                        @foreach($dependents as $dep)
                            <li>
                                <a href="{{ route('admin.plugins.show', $dep->slug) }}">{{ $dep->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="warning-box">
                        @include('backend.partials.icon', ['icon' => 'alertTriangle'])
                        {{ __t('plugins.uninstall_warning', ['count' => $dependents->count()]) }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Support Tab --}}
        <div class="tab-pane {{ $activeTab === 'support' ? 'active' : '' }}" id="tab-support">
            <div class="support-section">
                <div class="section-card">
                    <h3>{{ __t('plugins.get_help') }}</h3>
                    <p class="mb-4">{{ __t('plugins.support_intro', ['name' => $plugin->name]) }}</p>
                    
                    <div class="support-links">
                        @if($plugin->homepage)
                            <a href="{{ $plugin->homepage }}" target="_blank" rel="noopener" class="btn-secondary flex items-center gap-2 mb-2">
                                @include('backend.partials.icon', ['icon' => 'globe'])
                                {{ __t('plugins.visit_website') }}
                            </a>
                        @endif
                        
                        @if(!empty($manifest['docs_url']))
                            <a href="{{ $manifest['docs_url'] }}" target="_blank" rel="noopener" class="btn-secondary flex items-center gap-2 mb-2">
                                @include('backend.partials.icon', ['icon' => 'book'])
                                {{ __t('plugins.documentation') }}
                            </a>
                        @endif
                        
                        @if(!empty($manifest['support_url']))
                            <a href="{{ $manifest['support_url'] }}" target="_blank" rel="noopener" class="btn-secondary flex items-center gap-2 mb-2">
                                @include('backend.partials.icon', ['icon' => 'lifeBuoy'])
                                {{ __t('plugins.support_forum') }}
                            </a>
                        @endif
                        
                        @if(!empty($manifest['support_email']))
                            <a href="mailto:{{ $manifest['support_email'] }}" class="btn-secondary flex items-center gap-2 mb-2">
                                @include('backend.partials.icon', ['icon' => 'mail'])
                                {{ __t('plugins.email_support') }}
                            </a>
                        @endif
                    </div>
                </div>
                
                <div class="section-card mt-4">
                    <h3>{{ __t('plugins.troubleshooting') }}</h3>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><a href="#" onclick="checkPluginUpdates('{{ $plugin->slug }}'); return false;">{{ __t('plugins.check_updates') }}</a></li>
                        <li><a href="{{ route('admin.system.logs') }}">{{ __t('plugins.view_logs') }}</a></li>
                        @if($plugin->has_settings)
                            <li><a href="{{ route('admin.plugins.settings', $plugin->slug) }}">{{ __t('plugins.check_settings') }}</a></li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('inline-scripts')
<script>
// Tab switching
$('.tab-btn').on('click', function() {
    const tab = $(this).data('tab');
    $('.tab-btn').removeClass('active');
    $(this).addClass('active');
    $('.tab-pane').removeClass('active');
    $('#tab-' + tab).addClass('active');
    
    // Update URL without navigation
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    history.replaceState(null, '', url.toString());
});
</script>
@endpush

@include('backend.plugins.scripts')
@endsection
