@extends('owner::layouts.app', [
    'currentPage' => 'system/plugins',
    'currentPageLabel' => $plugin->name,
    'currentPageIcon' => 'plug',
])

@section('page-title', $plugin->name . ' - Plugin Details')
@section('page-header', $plugin->name)

@section('header-actions')
    <a href="{{ route('owner.plugins.index') }}" class="btn btn-secondary">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Plugins</span>
    </a>
@endsection

@section('page-content')
    <div class="plugin-details-container">
        {{-- Plugin Info Card --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <h3>
                    @include('backend.partials.icon', ['icon' => 'info'])
                    Plugin Information
                </h3>
                <div class="plugin-status-badge {{ $plugin->status }}">
                    @if($plugin->isActive())
                        Active
                    @elseif($plugin->hasError())
                        Error
                    @else
                        Inactive
                    @endif
                </div>
            </div>
            
            <div class="detail-card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Name</span>
                        <span class="detail-value">{{ $plugin->name }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Slug</span>
                        <span class="detail-value"><code>{{ $plugin->slug }}</code></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Version</span>
                        <span class="detail-value">{{ $plugin->version }}</span>
                    </div>
                    
                    @if($plugin->author)
                    <div class="detail-item">
                        <span class="detail-label">Author</span>
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
                        <span class="detail-label">Installed</span>
                        <span class="detail-value">{{ $plugin->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    
                    @if($plugin->activated_at)
                    <div class="detail-item">
                        <span class="detail-label">Activated</span>
                        <span class="detail-value">{{ $plugin->activated_at->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                    
                    <div class="detail-item full-width">
                        <span class="detail-label">Path</span>
                        <span class="detail-value"><code>{{ $plugin->getFullPath() }}</code></span>
                    </div>
                    
                    @if($plugin->description)
                    <div class="detail-item full-width">
                        <span class="detail-label">Description</span>
                        <span class="detail-value">{{ $plugin->description }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="detail-card-footer">
                @if($plugin->isActive())
                    <form action="{{ route('owner.plugins.deactivate', $plugin->slug) }}" method="POST" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-secondary">
                            @include('backend.partials.icon', ['icon' => 'powerOff'])
                            <span>Deactivate Plugin</span>
                        </button>
                    </form>
                @else
                    <form action="{{ route('owner.plugins.activate', $plugin->slug) }}" method="POST" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            @include('backend.partials.icon', ['icon' => 'power'])
                            <span>Activate Plugin</span>
                        </button>
                    </form>
                @endif
                
                <form action="{{ route('owner.plugins.destroy', $plugin->slug) }}" method="POST" class="inline-form"
                      onsubmit="return confirm('Are you sure you want to uninstall this plugin? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        @include('backend.partials.icon', ['icon' => 'trash'])
                        <span>Uninstall Plugin</span>
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
                    Requirements
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
                    Migrations
                </h3>
            </div>
            
            <div class="detail-card-body">
                @if(empty($migrationStatus))
                    <p class="text-muted">No migrations found for this plugin.</p>
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
                                        <span class="migration-batch">Batch {{ $migration['batch'] }}</span>
                                    @else
                                        <span class="migration-batch">Pending</span>
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
