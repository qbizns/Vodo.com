@extends('hello-world::layouts.plugin', [
    'currentPage' => 'hello-world',
    'currentPageLabel' => __p('hello-world', 'messages.title'),
    'currentPageIcon' => 'smile',
    'pageTitle' => __p('hello-world', 'messages.title'),
])

@section('plugin-title', __p('hello-world', 'messages.title'))

@section('plugin-header', __p('hello-world', 'messages.title'))

@section('plugin-header-actions')
    <a href="{{ route('plugins.hello-world.greetings') }}" class="plugin-btn plugin-btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        {{ __p('hello-world', 'messages.view_greetings') }}
    </a>
@endsection

@section('plugin-content')
    <div class="plugin-stats">
        <div class="plugin-stat-card">
            <div class="plugin-stat-value">{{ $greetingsCount }}</div>
            <div class="plugin-stat-label">{{ __p('hello-world', 'messages.total_greetings') }}</div>
        </div>
        <div class="plugin-stat-card">
            <div class="plugin-stat-value">{{ $plugin->version }}</div>
            <div class="plugin-stat-label">{{ __p('hello-world', 'messages.plugin_version') }}</div>
        </div>
        <div class="plugin-stat-card">
            <div class="plugin-stat-value">{{ $plugin->isActive() ? __p('hello-world', 'messages.active') : __p('hello-world', 'messages.inactive') }}</div>
            <div class="plugin-stat-label">{{ __p('hello-world', 'messages.status') }}</div>
        </div>
    </div>

    <div class="plugin-card" style="margin-top: var(--spacing-6);">
        <div class="plugin-card-header">
            <h3 class="plugin-card-title">{{ __p('hello-world', 'messages.current_greeting') }}</h3>
        </div>
        <div class="plugin-card-body">
            <div style="display: flex; align-items: center; gap: var(--spacing-4);">
                <div style="font-size: 64px;">👋</div>
                <div>
                    <h2 style="font-size: var(--text-2xl); color: var(--text-primary); margin-bottom: var(--spacing-2);">
                        {{ $greeting }}
                    </h2>
                    <p style="color: var(--text-secondary);">
                        {{ __p('hello-world', 'messages.welcome_message') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="plugin-card">
        <div class="plugin-card-header">
            <h3 class="plugin-card-title">{{ __p('hello-world', 'messages.about_title') }}</h3>
        </div>
        <div class="plugin-card-body">
            <p style="line-height: 1.7; color: var(--text-secondary);">
                {{ __p('hello-world', 'messages.about_description') }}
            </p>
            <ul style="margin-top: var(--spacing-4); margin-left: var(--spacing-6); color: var(--text-secondary); line-height: 2;">
                <li><strong>Routes:</strong> {{ __p('hello-world', 'messages.feature_routes') }} <code>/plugins/hello-world</code></li>
                <li><strong>Views:</strong> {{ __p('hello-world', 'messages.feature_views') }}</li>
                <li><strong>Migrations:</strong> {{ __p('hello-world', 'messages.feature_migrations') }}</li>
                <li><strong>Models:</strong> {{ __p('hello-world', 'messages.feature_models') }}</li>
                <li><strong>Navigation:</strong> {{ __p('hello-world', 'messages.feature_navigation') }}</li>
                <li><strong>Hooks:</strong> {{ __p('hello-world', 'messages.feature_hooks') }}</li>
            </ul>
        </div>
    </div>

    <div class="plugin-card">
        <div class="plugin-card-header">
            <h3 class="plugin-card-title">{{ __p('hello-world', 'messages.plugin_info_title') }}</h3>
        </div>
        <div class="plugin-card-body">
            <table class="plugin-table">
                <tr>
                    <th style="width: 200px;">{{ __p('hello-world', 'messages.name') }}</th>
                    <td>{{ $plugin->name }}</td>
                </tr>
                <tr>
                    <th>{{ __p('hello-world', 'messages.slug') }}</th>
                    <td><code>{{ $plugin->slug }}</code></td>
                </tr>
                <tr>
                    <th>{{ __p('hello-world', 'messages.version') }}</th>
                    <td>{{ $plugin->version }}</td>
                </tr>
                <tr>
                    <th>{{ __p('hello-world', 'messages.author') }}</th>
                    <td>{{ $plugin->author ?? 'Vodo' }}</td>
                </tr>
                <tr>
                    <th>{{ __p('hello-world', 'messages.description') }}</th>
                    <td>{{ $plugin->description }}</td>
                </tr>
                <tr>
                    <th>{{ __p('hello-world', 'messages.activated_at') }}</th>
                    <td>{{ $plugin->activated_at?->format('F j, Y, g:i a') ?? __p('hello-world', 'messages.not_activated') }}</td>
                </tr>
            </table>
        </div>
    </div>
@endsection

@push('plugin-styles')
<style>
    code {
        background: var(--bg-surface-1);
        padding: 2px 6px;
        border-radius: var(--radius-sm);
        font-family: monospace;
        font-size: 0.9em;
    }
</style>
@endpush
