{{-- Settings Page Layout --}}
{{-- This is the base settings layout with two-panel design like Odoo --}}
{{-- Styles are now loaded from /backend/css/pages/settings.css via SPA CSS loader --}}

<div class="settings-container">
    {{-- Left Sidebar --}}
    <div class="settings-sidebar">
        
        <nav class="settings-nav">
            {{-- General Settings --}}
            <a href="?section=general" 
               class="settings-nav-item {{ $activeSection === 'general' ? 'active' : '' }}"
               data-section="general">
                @include('backend.partials.icon', ['icon' => 'settings'])
                <span>{{ __t('settings.general_settings') }}</span>
            </a>

            {{-- Plugins with Settings --}}
            @if(count($pluginsWithSettings) > 0)
                <div class="settings-nav-divider" style="display: none !important; visibility: hidden;">
                    <span>{{ __t('navigation.plugins') }}</span>
                </div>
                
                @foreach($pluginsWithSettings as $plugin)
                    <a href="?section=plugin:{{ $plugin['slug'] }}" 
                       class="settings-nav-item {{ $activeSection === 'plugin:' . $plugin['slug'] ? 'active' : '' }}"
                       data-section="plugin:{{ $plugin['slug'] }}">
                        @include('backend.partials.icon', ['icon' => $plugin['icon']])
                        <span>{{ $plugin['name'] }}</span>
                    </a>
                @endforeach
            @endif
        </nav>
    </div>

    {{-- Right Content Area --}}
    <div class="settings-content">
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

        <div id="settingsContent">
            @if($sectionContent['type'] === 'general')
                @include('backend.settings.partials.general', [
                    'definitions' => $sectionContent['definitions'],
                    'values' => $sectionContent['values'],
                    'saveUrl' => $sectionContent['saveUrl'],
                ])
            @elseif($sectionContent['type'] === 'plugin')
                @include('backend.settings.partials.plugin', [
                    'slug' => $sectionContent['slug'],
                    'name' => $sectionContent['name'] ?? null,
                    'fields' => $sectionContent['fields'],
                    'values' => $sectionContent['values'],
                    'saveUrl' => $sectionContent['saveUrl'],
                ])
            @endif
        </div>
    </div>
</div>

{{-- Scripts are still needed for interactive functionality --}}
@include('backend.settings.scripts')
