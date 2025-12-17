@extends('admin::layouts.app', [
    'currentPage' => 'system/plugins',
    'currentPageLabel' => $plugin->name,
    'currentPageIcon' => 'plug',
])

@section('page-title', $plugin->name . ' - Plugin Details')
@section('page-header', $plugin->name)

@section('header-actions')
    <a href="{{ route('admin.plugins.index') }}" class="btn btn-secondary">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>{{ __t('plugins.back_to_plugins') }}</span>
    </a>
@endsection

@section('page-content')
    <div class="plugin-details-container">
        {{-- Plugin Info Card --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <h3>
                    @include('backend.partials.icon', ['icon' => 'info'])
                    {{ __t('plugins.plugin_information') }}
                </h3>
                <div class="plugin-status-badge {{ $plugin->status }}">
                    @if($plugin->isActive())
                        {{ __t('plugins.active') }}
                    @elseif($plugin->hasError())
                        {{ __t('plugins.error') }}
                    @else
                        {{ __t('plugins.inactive') }}
                    @endif
                </div>
            </div>
            
            <div class="detail-card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">{{ __t('plugins.name') }}</span>
                        <span class="detail-value">{{ $plugin->name }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">{{ __t('plugins.slug') }}</span>
                        <span class="detail-value"><code>{{ $plugin->slug }}</code></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">{{ __t('plugins.version') }}</span>
                        <span class="detail-value">{{ $plugin->version }}</span>
                    </div>
                    
                    @if($plugin->author)
                    <div class="detail-item">
                        <span class="detail-label">{{ __t('plugins.author') }}</span>
                        <span class="detail-value">
                            @if($plugin->author_url)
                                <a href="{{ $plugin->author_url }}" target="_blank" rel="noopener">{{ $plugin->author }}</a>
                            @else
                                {{ $plugin->author }}
                            @endif
                        </span>
                    </div>
                    @endif
                    
                    <div class="detail-item">
                        <span class="detail-label">{{ __t('plugins.installed_on') }}</span>
                        <span class="detail-value">{{ $plugin->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    
                    @if($plugin->activated_at)
                    <div class="detail-item">
                        <span class="detail-label">{{ __t('plugins.activated') }}</span>
                        <span class="detail-value">{{ $plugin->activated_at->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                    
                    <div class="detail-item full-width">
                        <span class="detail-label">{{ __t('plugins.path') }}</span>
                        <span class="detail-value"><code>{{ $plugin->getFullPath() }}</code></span>
                    </div>
                    
                    @if($plugin->description)
                    <div class="detail-item full-width">
                        <span class="detail-label">{{ __t('plugins.description') }}</span>
                        <span class="detail-value">{{ $plugin->description }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="detail-card-footer">
                @if($plugin->isActive())
                    <form action="{{ route('admin.plugins.deactivate', $plugin->slug) }}" method="POST" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-secondary">
                            @include('backend.partials.icon', ['icon' => 'powerOff'])
                            <span>{{ __t('plugins.deactivate_plugin') }}</span>
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.plugins.activate', $plugin->slug) }}" method="POST" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            @include('backend.partials.icon', ['icon' => 'power'])
                            <span>{{ __t('plugins.activate_plugin') }}</span>
                        </button>
                    </form>
                @endif
                
                <form action="{{ route('admin.plugins.destroy', $plugin->slug) }}" method="POST" class="inline-form"
                      onsubmit="return confirm('{{ __t('plugins.confirm_uninstall_warning') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        @include('backend.partials.icon', ['icon' => 'trash'])
                        <span>{{ __t('plugins.uninstall_plugin') }}</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Requirements Card --}}
        @if($plugin->requires && count($plugin->requires) > 0)
        <div class="detail-card">
            <div class="detail-card-header">
                <h3>
                    @include('backend.partials.icon', ['icon' => 'checkCircle'])
                    {{ __t('plugins.requirements') }}
                </h3>
            </div>
            
            <div class="detail-card-body">
                <div class="requirements-list">
                    @foreach($plugin->requires as $requirement => $version)
                        <div class="requirement-item">
                            <span class="requirement-name">{{ ucfirst($requirement) }}</span>
                            <span class="requirement-version">{{ $version }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Migrations Card --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <h3>
                    @include('backend.partials.icon', ['icon' => 'database'])
                    {{ __t('plugins.migrations') }}
                </h3>
            </div>
            
            <div class="detail-card-body">
                @if(empty($migrationStatus))
                    <p class="text-muted">{{ __t('plugins.no_migrations') }}</p>
                @else
                    <div class="migrations-list">
                        @foreach($migrationStatus as $migration)
                            <div class="migration-item {{ $migration['ran'] ? 'ran' : 'pending' }}">
                                <div class="migration-status">
                                    @if($migration['ran'])
                                        @include('backend.partials.icon', ['icon' => 'checkCircle'])
                                    @else
                                        @include('backend.partials.icon', ['icon' => 'clock'])
                                    @endif
                                </div>
                                <div class="migration-info">
                                    <span class="migration-name">{{ $migration['name'] }}</span>
                                    @if($migration['ran'])
                                        <span class="migration-batch">{{ __t('plugins.batch') }} {{ $migration['batch'] }}</span>
                                    @else
                                        <span class="migration-batch">{{ __t('plugins.pending') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@include('backend.plugins.styles')
