{{-- Plugin Dependencies Detail View (Screen 7 Detail) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', $plugin->name . ' - ' . __t('plugins.dependencies'))
@section('page-id', 'system/plugins/' . $plugin->slug . '/dependencies')
@section('require-css', 'plugins')

@section('header', __t('plugins.dependencies') . ': ' . $plugin->name)

@section('header-actions')
<a href="{{ route('admin.plugins.show', $plugin->slug) }}" class="btn-secondary">
    @include('backend.partials.icon', ['icon' => 'arrowLeft'])
    {{ __t('common.back') }}
</a>
@endsection

@section('content')
<div class="dependencies-detail-page">
    {{-- Tabs --}}
    <div class="deps-tabs">
        <button type="button" class="tab-btn active" data-tab="dependencies">
            {{ __t('plugins.dependencies') }}
        </button>
        <button type="button" class="tab-btn" data-tab="dependents">
            {{ __t('plugins.dependents') }}
        </button>
    </div>

    {{-- Dependencies Tab --}}
    <div class="tab-pane active" id="tab-dependencies">
        @if(empty($dependencies))
            <div class="empty-state-small">
                <p>{{ __t('plugins.no_dependencies') }}</p>
            </div>
        @else
            {{-- Visual Dependency Tree --}}
            <div class="dependency-tree-visual">
                <div class="tree-root">
                    <div class="tree-node root">
                        <span class="node-name">{{ $plugin->name }}</span>
                        <span class="node-version">v{{ $plugin->version }}</span>
                    </div>
                    
                    @if(!empty($dependencies))
                        <div class="tree-children">
                            @foreach($dependencies as $dep)
                                @include('backend.plugins.partials.dependency-node', ['dep' => $dep, 'level' => 1])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Dependencies Table --}}
            <div class="section-card">
                <h3>{{ __t('plugins.dependencies_table') }}</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __t('plugins.plugin') }}</th>
                            <th>{{ __t('plugins.required') }}</th>
                            <th>{{ __t('plugins.installed') }}</th>
                            <th>{{ __t('plugins.status') }}</th>
                            <th class="text-right">{{ __t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dependencies as $dep)
                            <tr>
                                <td>
                                    <strong>{{ $dep['name'] }}</strong>
                                    @if(!empty($dep['is_optional']))
                                        <span class="badge badge-secondary">{{ __t('plugins.optional') }}</span>
                                    @endif
                                </td>
                                <td><code>{{ $dep['required_version'] }}</code></td>
                                <td>
                                    @if($dep['installed_version'])
                                        v{{ $dep['installed_version'] }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
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
                                <td class="text-right">
                                    @if($dep['status'] === 'missing')
                                        <button type="button" 
                                                class="btn-primary btn-sm"
                                                onclick="installDependency('{{ $dep['slug'] }}')">
                                            {{ __t('plugins.install') }}
                                        </button>
                                    @elseif($dep['status'] === 'inactive')
                                        <button type="button" 
                                                class="btn-secondary btn-sm"
                                                onclick="activatePlugin('{{ $dep['slug'] }}')">
                                            {{ __t('plugins.activate') }}
                                        </button>
                                    @else
                                        <a href="{{ route('admin.plugins.show', $dep['slug']) }}" class="btn-secondary btn-sm">
                                            {{ __t('plugins.view') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Dependents Tab --}}
    <div class="tab-pane" id="tab-dependents">
        @if($dependents->isEmpty())
            <div class="empty-state-small">
                <p>{{ __t('plugins.no_dependents') }}</p>
            </div>
        @else
            <div class="section-card">
                <h3>{{ __t('plugins.plugins_requiring_this') }}</h3>
                <p class="section-desc">{{ __t('plugins.dependents_warning') }}</p>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __t('plugins.plugin') }}</th>
                            <th>{{ __t('plugins.version') }}</th>
                            <th>{{ __t('plugins.status') }}</th>
                            <th class="text-right">{{ __t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dependents as $dependent)
                            <tr>
                                <td><strong>{{ $dependent->name }}</strong></td>
                                <td>v{{ $dependent->version }}</td>
                                <td>
                                    @include('backend.plugins.partials.status-badge', ['status' => $dependent->status])
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.plugins.show', $dependent->slug) }}" class="btn-secondary btn-sm">
                                        {{ __t('plugins.view') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="warning-box">
                @include('backend.partials.icon', ['icon' => 'alertTriangle'])
                <div>
                    <strong>{{ __t('plugins.uninstall_warning_title') }}</strong>
                    <p>{{ __t('plugins.uninstall_warning', ['count' => $dependents->count()]) }}</p>
                </div>
            </div>
        @endif
    </div>
</div>

@push('inline-scripts')
<script nonce="{{ csp_nonce() }}">
// Tab switching
$('.tab-btn').on('click', function() {
    const tab = $(this).data('tab');
    $('.tab-btn').removeClass('active');
    $(this).addClass('active');
    $('.tab-pane').removeClass('active');
    $('#tab-' + tab).addClass('active');
});

function installDependency(slug) {
    window.location.href = '{{ route("admin.plugins.install") }}?slug=' + slug + '&marketplace=true';
}
</script>
@endpush

@include('backend.plugins.scripts')
@endsection
