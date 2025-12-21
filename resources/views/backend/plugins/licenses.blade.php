{{-- Plugin Licenses (Screen 8) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', __t('plugins.licenses'))
@section('page-id', 'system/plugins/licenses')
@section('require-css', 'plugins')

@section('header', __t('plugins.licenses'))

@section('header-actions')
<div class="flex items-center gap-3">
    <button type="button" class="btn-primary flex items-center gap-2" data-action="add-license">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>{{ __t('plugins.add_license') }}</span>
    </button>
</div>
@endsection

@section('content')
<div class="licenses-page">
    {{-- License Overview Stats --}}
    <div class="license-stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">{{ __t('plugins.total') }}</div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-value">{{ $stats['active'] }}</div>
            <div class="stat-label">{{ __t('plugins.active') }}</div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-value">{{ $stats['expiring'] }}</div>
            <div class="stat-label">{{ __t('plugins.expiring_soon') }}</div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-value">{{ $stats['expired'] }}</div>
            <div class="stat-label">{{ __t('plugins.expired') }}</div>
        </div>
    </div>

    {{-- Licenses Table --}}
    @if($licenses->isEmpty() && $unlicensedPlugins->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'key'])
            </div>
            <h3>{{ __t('plugins.no_licenses') }}</h3>
            <p>{{ __t('plugins.no_licenses_desc') }}</p>
        </div>
    @else
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __t('plugins.plugin') }}</th>
                        <th>{{ __t('plugins.license_key') }}</th>
                        <th>{{ __t('plugins.status') }}</th>
                        <th>{{ __t('plugins.expires') }}</th>
                        <th class="text-right">{{ __t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($licenses as $license)
                        <tr>
                            <td>
                                <div class="plugin-cell">
                                    <strong>{{ $license->plugin->name ?? __t('common.unknown') }}</strong>
                                    @if($license->license_type !== 'standard')
                                        <span class="license-type">{{ ucfirst($license->license_type) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <code class="license-key">{{ $license->masked_key ?? Str::limit($license->license_key, 20, '****') }}</code>
                            </td>
                            <td>
                                @if($license->status === 'active')
                                    <span class="badge badge-success">
                                        <span class="status-dot"></span>
                                        {{ __t('plugins.active') }}
                                    </span>
                                @elseif($license->status === 'expired')
                                    <span class="badge badge-danger">
                                        {{ __t('plugins.expired') }}
                                    </span>
                                @elseif($license->status === 'suspended')
                                    <span class="badge badge-warning">
                                        {{ __t('plugins.suspended') }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        {{ ucfirst($license->status) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($license->expires_at)
                                    @if($license->is_expired)
                                        <span class="text-danger">{{ __t('plugins.expired') }}</span>
                                    @elseif($license->is_expiring_soon)
                                        <span class="text-warning">{{ $license->days_until_expiration }} {{ __t('common.days') }}</span>
                                    @else
                                        {{ $license->expires_at->format('M Y') }}
                                    @endif
                                @else
                                    <span class="text-muted">{{ __t('plugins.lifetime') }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="actions-dropdown">
                                    <button type="button" class="action-menu-btn">
                                        @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                    </button>
                                    <div class="action-menu">
                                        <button type="button" class="action-item" data-action="view-license" data-license-id="{{ $license->id }}">
                                            @include('backend.partials.icon', ['icon' => 'info'])
                                            {{ __t('plugins.view_details') }}
                                        </button>
                                        @if($license->status === 'active')
                                            <button type="button" class="action-item" data-action="deactivate-license" data-plugin-slug="{{ $license->plugin->slug }}">
                                                @include('backend.partials.icon', ['icon' => 'pause'])
                                                {{ __t('plugins.deactivate') }}
                                            </button>
                                        @endif
                                        @if($license->expires_at && $license->is_expiring_soon)
                                            <a href="{{ config('marketplace.renew_url') }}?license={{ $license->license_key }}" 
                                               target="_blank"
                                               class="action-item">
                                                @include('backend.partials.icon', ['icon' => 'refresh'])
                                                {{ __t('plugins.renew') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Unlicensed Premium Plugins --}}
                    @foreach($unlicensedPlugins as $plugin)
                        <tr class="unlicensed-row">
                            <td>
                                <div class="plugin-cell">
                                    <strong>{{ $plugin->name }}</strong>
                                    <span class="license-type premium">{{ __t('plugins.premium') }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted">{{ __t('plugins.not_activated') }}</span>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ __t('plugins.no_license') }}
                                </span>
                            </td>
                            <td>—</td>
                            <td class="text-right">
                                <button type="button" 
                                        class="btn-primary btn-sm"
                                        data-action="add-license"
                                        data-plugin-slug="{{ $plugin->slug }}">
                                    {{ __t('plugins.activate') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@php
// Prepare unlicensed plugins data for JavaScript
$unlicensedPluginsData = $unlicensedPlugins->mapWithKeys(function($plugin) {
    return [$plugin->slug => ['slug' => $plugin->slug, 'name' => $plugin->name]];
})->toArray();
@endphp

@push('inline-scripts')
<script nonce="{{ csp_nonce() }}">
var unlicensedPlugins = @json($unlicensedPluginsData);

function showAddLicenseModal(preselectedSlug) {
    preselectedSlug = preselectedSlug || null;
    
    // Build plugin options
    var optionsHtml = '<option value="">{{ __t("plugins.select_plugin") }}...</option>';
    Object.keys(unlicensedPlugins).forEach(function(slug) {
        var plugin = unlicensedPlugins[slug];
        var selected = preselectedSlug === slug ? ' selected' : '';
        optionsHtml += '<option value="' + Vodo.utils.escapeHtml(slug) + '"' + selected + '>' + Vodo.utils.escapeHtml(plugin.name) + '</option>';
    });
    
    var formHtml = '<form id="addLicenseForm">' +
        '<div class="form-group">' +
            '<label for="licensePlugin">{{ __t("plugins.plugin") }}</label>' +
            '<select id="licensePlugin" name="plugin_slug" class="form-select" required>' + optionsHtml + '</select>' +
        '</div>' +
        '<div class="form-group">' +
            '<label for="licenseKey">{{ __t("plugins.license_key") }}</label>' +
            '<input type="text" id="licenseKey" name="license_key" class="form-input" placeholder="XXXX-XXXX-XXXX-XXXX" required>' +
            '<p class="form-hint">{{ __t("plugins.license_key_hint") }}</p>' +
        '</div>' +
    '</form>';
    
    var modalId = Vodo.modals.open({
        title: '{{ __t("plugins.activate_license") }}',
        content: formHtml,
        size: 'md',
        footer: '<button type="button" class="btn-secondary" data-modal-close>{{ __t("common.cancel") }}</button>' +
                '<button type="button" class="btn-primary" id="submitLicenseBtn">{{ __t("plugins.activate_license") }}</button>',
        onOpen: function(id, $modal) {
            $modal.find('#submitLicenseBtn').on('click', function() {
                submitLicense(id);
            });
            
            // Also submit on enter key in the form
            $modal.find('#addLicenseForm').on('submit', function(e) {
                e.preventDefault();
                submitLicense(id);
            });
        }
    });
}

function submitLicense(modalId) {
    var $modal = $('[data-modal-id="' + modalId + '"]');
    var $form = $modal.find('#addLicenseForm');
    var $btn = $modal.find('#submitLicenseBtn');
    var originalText = $btn.html();
    
    var pluginSlug = $form.find('#licensePlugin').val();
    var licenseKey = $form.find('#licenseKey').val();
    
    if (!pluginSlug || !licenseKey) {
        Vodo.notify.error('{{ __t("plugins.please_fill_all_fields") }}');
        return;
    }
    
    $btn.prop('disabled', true).html('<span class="spinner"></span> {{ __t("plugins.activating") }}...');
    
    $.ajax({
        url: '{{ route("admin.plugins.licenses.activate") }}',
        method: 'POST',
        data: {
            plugin_slug: pluginSlug,
            license_key: licenseKey
        },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        success: function(response) {
            if (response.success) {
                Vodo.notify.success(response.message || '{{ __t("plugins.license_activated") }}');
                Vodo.modals.close(modalId);
                Vodo.router.refresh();
            } else {
                Vodo.notify.error(response.message || '{{ __t("plugins.license_activation_failed") }}');
            }
        },
        error: function(xhr) {
            Vodo.notify.error(xhr.responseJSON?.message || '{{ __t("plugins.license_activation_failed") }}');
        },
        complete: function() {
            $btn.prop('disabled', false).html(originalText);
        }
    });
}

function deactivateLicense(slug) {
    Vodo.modals.confirm('{{ __t("plugins.confirm_deactivate_license") }}', {
        title: '{{ __t("plugins.deactivate_license") }}',
        confirmText: '{{ __t("plugins.deactivate") }}',
        confirmClass: 'btn-danger'
    }).then(function(confirmed) {
        if (!confirmed) return;
        
        $.ajax({
            url: '{{ url("admin/system/plugins/licenses") }}/' + slug + '/deactivate',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            success: function(response) {
                Vodo.notify.success(response.message || '{{ __t("plugins.license_deactivated") }}');
                Vodo.router.refresh();
            },
            error: function(xhr) {
                Vodo.notify.error(xhr.responseJSON?.message || '{{ __t("common.error_occurred") }}');
            }
        });
    });
}

function viewLicenseDetails(id) {
    Vodo.notify.info('{{ __t("plugins.license_details_coming_soon") }}');
}

// Event delegation for license actions (namespaced to prevent duplicates)
$(document).off('click.licenses').on('click.licenses', '[data-action="add-license"], [data-action="view-license"], [data-action="deactivate-license"]', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var $el = $(this);
    var action = $el.data('action');
    
    if (action === 'add-license') {
        showAddLicenseModal($el.data('plugin-slug') || null);
    } else if (action === 'view-license') {
        viewLicenseDetails($el.data('license-id'));
    } else if (action === 'deactivate-license') {
        deactivateLicense($el.data('plugin-slug'));
    }
});
</script>
@endpush
@endsection
