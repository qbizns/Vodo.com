{{-- Plugin Dependencies Overview (Screen 7) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', __t('plugins.dependencies'))
@section('page-id', 'system/plugins/dependencies')
@section('require-css', 'plugins')

@section('header', __t('plugins.dependencies'))

@section('content')
<div class="dependencies-page">
    {{-- Dependencies Overview --}}
    @if($plugins->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'gitBranch'])
            </div>
            <h3>{{ __t('plugins.no_plugins') }}</h3>
            <p>{{ __t('plugins.no_dependencies_desc') }}</p>
        </div>
    @else
        {{-- Quick Stats --}}
        @php
            $totalDeps = 0;
            $missingDeps = 0;
            foreach ($dependencyMatrix as $item) {
                $totalDeps += count($item['dependencies']);
                foreach ($item['dependencies'] as $dep) {
                    if ($dep->status === 'missing') $missingDeps++;
                }
            }
        @endphp
        
        @if($missingDeps > 0)
            <div class="alert alert-warning mb-4">
                @include('backend.partials.icon', ['icon' => 'alertTriangle'])
                <span>{{ __t('plugins.missing_dependencies', ['count' => $missingDeps]) }}</span>
            </div>
        @endif

        <div class="dependencies-grid">
            @foreach($plugins as $plugin)
                @php
                    $matrix = $dependencyMatrix[$plugin->slug] ?? null;
                    $deps = $matrix['dependencies'] ?? collect();
                    $dependents = $matrix['dependents'] ?? collect();
                @endphp
                
                <div class="dependency-card">
                    <div class="dependency-card-header">
                        <div class="plugin-info">
                            <a href="{{ route('admin.plugins.show', $plugin->slug) }}" class="plugin-name">
                                {{ $plugin->name }}
                            </a>
                            <span class="plugin-version">v{{ $plugin->version }}</span>
                        </div>
                        @include('backend.plugins.partials.status-badge', ['status' => $plugin->status])
                    </div>

                    <div class="dependency-card-body">
                        {{-- Dependencies (what this plugin needs) --}}
                        @if($deps->isNotEmpty())
                            <div class="deps-section">
                                <h4>
                                    @include('backend.partials.icon', ['icon' => 'arrowDown'])
                                    {{ __t('plugins.requires') }} ({{ $deps->count() }})
                                </h4>
                                <ul class="deps-list">
                                    @foreach($deps as $dep)
                                        <li class="dep-item dep-{{ $dep->status }}">
                                            <span class="dep-name">{{ $dep->dependency_slug }}</span>
                                            <span class="dep-version">{{ $dep->version_constraint }}</span>
                                            @if($dep->status === 'satisfied')
                                                <span class="dep-status status-ok">✓</span>
                                            @elseif($dep->status === 'missing')
                                                <span class="dep-status status-missing">✗</span>
                                            @elseif($dep->status === 'inactive')
                                                <span class="dep-status status-inactive">○</span>
                                            @else
                                                <span class="dep-status status-mismatch">⚠</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Dependents (what depends on this plugin) --}}
                        @if($dependents->isNotEmpty())
                            <div class="deps-section">
                                <h4>
                                    @include('backend.partials.icon', ['icon' => 'arrowUp'])
                                    {{ __t('plugins.required_by') }} ({{ $dependents->count() }})
                                </h4>
                                <ul class="deps-list dependents">
                                    @foreach($dependents as $dependent)
                                        <li class="dep-item">
                                            <a href="{{ route('admin.plugins.show', $dependent->slug) }}">
                                                {{ $dependent->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($deps->isEmpty() && $dependents->isEmpty())
                            <p class="no-deps">{{ __t('plugins.no_dependencies') }}</p>
                        @endif
                    </div>

                    <div class="dependency-card-footer">
                        <a href="{{ route('admin.plugins.dependencies.detail', $plugin->slug) }}" class="btn-link">
                            {{ __t('plugins.view_tree') }}
                            @include('backend.partials.icon', ['icon' => 'arrowRight'])
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
