{{-- Plugin Settings (Screen 5) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', $plugin->name . ' - ' . __t('plugins.settings'))
@section('page-id', 'system/plugins/' . $plugin->slug . '/settings')
@section('require-css', 'plugins,settings')

@section('header', __t('plugins.plugin_settings', ['name' => $plugin->name]))

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.plugins.show', $plugin->slug) }}" class="btn-secondary">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        {{ __t('common.back') }}
    </a>
    <button type="submit" form="settingsForm" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'save'])
        {{ __t('common.save_changes') }}
    </button>
</div>
@endsection

@section('content')
<div class="plugin-settings-page">
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

    @if(empty($settingsFields))
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'settings'])
            </div>
            <h3>{{ __t('plugins.no_settings') }}</h3>
            <p>{{ __t('plugins.no_settings_desc') }}</p>
        </div>
    @else
        <div class="settings-container">
            {{-- Settings Tabs (if defined) --}}
            @if(!empty($settingsFields['tabs']))
                <div class="settings-sidebar">
                    <nav class="settings-nav">
                        @foreach($settingsFields['tabs'] as $tabKey => $tab)
                            <a href="#tab-{{ $tabKey }}" 
                               class="settings-nav-item {{ $loop->first ? 'active' : '' }}"
                               data-tab="{{ $tabKey }}">
                                @if(!empty($tab['icon']))
                                    @include('backend.partials.icon', ['icon' => $tab['icon']])
                                @endif
                                <span>{{ $tab['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endif

            {{-- Settings Form --}}
            <div class="settings-content">
                <form id="settingsForm" 
                      action="{{ route('admin.plugins.settings.save', $plugin->slug) }}" 
                      method="POST"
                      class="settings-form">
                    @csrf

                    @if(!empty($settingsFields['tabs']))
                        @foreach($settingsFields['tabs'] as $tabKey => $tab)
                            <div class="settings-tab-pane {{ $loop->first ? 'active' : '' }}" id="tab-{{ $tabKey }}">
                                <h2 class="settings-section-title">{{ $tab['label'] }}</h2>
                                
                                @foreach($settingsFields['fields'] ?? [] as $field)
                                    @if(($field['tab'] ?? 'general') === $tabKey)
                                        @include('backend.plugins.partials.settings-field', [
                                            'field' => $field,
                                            'value' => $settings[$field['key']] ?? $field['default'] ?? null,
                                        ])
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        {{-- No tabs, just render all fields --}}
                        @foreach($settingsFields['fields'] ?? $settingsFields as $field)
                            @include('backend.plugins.partials.settings-field', [
                                'field' => $field,
                                'value' => $settings[$field['key']] ?? $field['default'] ?? null,
                            ])
                        @endforeach
                    @endif

                    <div class="settings-actions">
                        <button type="button" class="btn-secondary" onclick="resetToDefaults()">
                            {{ __t('plugins.reset_defaults') }}
                        </button>
                        <button type="submit" class="btn-primary">
                            {{ __t('common.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

@push('inline-scripts')
<script nonce="{{ csp_nonce() }}">
// Tab switching
$('.settings-nav-item').on('click', function(e) {
    e.preventDefault();
    const tab = $(this).data('tab');
    
    $('.settings-nav-item').removeClass('active');
    $(this).addClass('active');
    
    $('.settings-tab-pane').removeClass('active');
    $('#tab-' + tab).addClass('active');
});

// AJAX form submission
$('#settingsForm').on('submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    const originalText = $btn.html();
    
    $btn.prop('disabled', true).html('<span class="spinner"></span> {{ __t("common.saving") }}...');
    
    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message || '{{ __t("plugins.settings_saved") }}');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || '{{ __t("common.error_occurred") }}';
            showNotification('error', message);
        },
        complete: function() {
            $btn.prop('disabled', false).html(originalText);
        }
    });
});

function resetToDefaults() {
    if (confirm('{{ __t("plugins.confirm_reset_defaults") }}')) {
        // Reset all form fields to their default values
        $('#settingsForm')[0].reset();
        showNotification('info', '{{ __t("plugins.defaults_restored") }}');
    }
}

function showNotification(type, message) {
    // Use existing notification system if available
    if (typeof window.showAlert === 'function') {
        window.showAlert(type, message);
    } else {
        alert(message);
    }
}
</script>
@endpush
@endsection
