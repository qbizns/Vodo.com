{{-- Shared Plugins Content View --}}
{{-- Styles are loaded from /backend/css/pages/plugins.css via SPA CSS loader --}}

@if(session('success'))
    <div class="alert alert-success">
        @include('backend.partials.icon', ['icon' => 'checkCircle'])
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        @include('backend.partials.icon', ['icon' => 'alertCircle'])
        <span>{{ session('error') }}</span>
    </div>
@endif

<div class="plugins-container">
    @if($plugins->isEmpty())
        <div class="empty-state">
            <h3>{{ __t('plugins.upload_plugin') }}</h3>
            
            <div class="upload-section">
                <form action="{{ route($routePrefix . '.plugins.upload') }}" method="POST" enctype="multipart/form-data" class="upload-form-inline" id="uploadForm">
                    @csrf
                    <input type="file" name="plugin" id="pluginFile" accept=".zip" required class="file-input-inline">
                    <div class="file-selected" id="fileSelectedName" style="display: none;">
                        <span id="fileNameDisplay"></span>
                    </div>
                    <button type="submit" class="btn-large" id="uploadBtn">
                        {{ __t('plugins.upload_install') }}
                    </button>
                </form>
                <p class="upload-hint">{{ __t('plugins.max_file_size') }}: {{ config('plugins.max_upload_size', 10240) / 1024 }}MB</p>
            </div>
        </div>
    @else
        {{-- Upload Form at Top --}}
        <div style="margin-bottom: 32px; padding: 24px; background: var(--bg-surface-2, #f9f9f9); border-radius: 8px;">
            <form action="{{ route($routePrefix . '.plugins.upload') }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 12px; align-items: center;">
                @csrf
                <input type="file" name="plugin" id="pluginFile" accept=".zip" required style="flex: 1; padding: 12px; font-size: 14px; background: var(--bg-surface-1, white); border: none; border-radius: 6px;">
                <button type="submit" class="btn-large" style="width: auto; padding: 12px 24px;">
                    {{ __t('plugins.upload_install') }}
                </button>
            </form>
        </div>

        {{-- Plugins List --}}
        <div class="plugins-grid">
            @foreach($plugins as $plugin)
                <div class="plugin-card">
                    <div class="plugin-header">
                        <h3 class="plugin-name">{{ $plugin->name }}</h3>
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
                    
                    <div class="plugin-body">
                        <p class="plugin-version">{{ __t('plugins.version') }} {{ $plugin->version }}</p>
                        @if($plugin->description)
                            <p class="plugin-description">{{ Str::limit($plugin->description, 150) }}</p>
                        @endif
                    </div>
                    
                    <div class="plugin-actions">
                        @if($plugin->isActive())
                            <form action="{{ route($routePrefix . '.plugins.deactivate', $plugin->slug) }}" method="POST" class="inline-form">
                                @csrf
                                <button type="submit" class="btn btn-secondary">
                                    {{ __t('plugins.deactivate') }}
                                </button>
                            </form>
                        @else
                            <form action="{{ route($routePrefix . '.plugins.activate', $plugin->slug) }}" method="POST" class="inline-form">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    {{ __t('plugins.activate') }}
                                </button>
                            </form>
                        @endif
                        
                        <form action="{{ route($routePrefix . '.plugins.destroy', $plugin->slug) }}" method="POST" class="inline-form" 
                              data-confirm="{{ __t('plugins.confirm_uninstall') }}"
                              data-confirm-title="{{ __t('plugins.uninstall_plugin') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                {{ __t('plugins.uninstall') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
