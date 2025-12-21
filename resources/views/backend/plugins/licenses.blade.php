{{-- Plugin Licenses (Screen 8) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', __t('plugins.licenses'))
@section('page-id', 'system/plugins/licenses')
@section('require-css', 'plugins')

@section('header', __t('plugins.licenses'))

@section('header-actions')
<div class="flex items-center gap-3">
    <button type="button" class="btn-primary flex items-center gap-2" onclick="showAddLicenseModal()">
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
                                        <button type="button" class="action-item" onclick="viewLicenseDetails({{ $license->id }})">
                                            @include('backend.partials.icon', ['icon' => 'info'])
                                            {{ __t('plugins.view_details') }}
                                        </button>
                                        @if($license->status === 'active')
                                            <button type="button" class="action-item" onclick="deactivateLicense('{{ $license->plugin->slug }}')">
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
                                        onclick="showAddLicenseModal('{{ $plugin->slug }}')">
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

{{-- Add License Modal --}}
<div id="addLicenseModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="closeModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>{{ __t('plugins.activate_license') }}</h3>
            <button type="button" class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form id="addLicenseForm" onsubmit="submitLicense(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label for="licensePlugin">{{ __t('plugins.plugin') }}</label>
                    <select id="licensePlugin" name="plugin_slug" class="form-select" required>
                        <option value="">{{ __t('plugins.select_plugin') }}...</option>
                        @foreach($unlicensedPlugins as $plugin)
                            <option value="{{ $plugin->slug }}">{{ $plugin->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="licenseKey">{{ __t('plugins.license_key') }}</label>
                    <input type="text" 
                           id="licenseKey" 
                           name="license_key" 
                           class="form-input"
                           placeholder="XXXX-XXXX-XXXX-XXXX"
                           required>
                    <p class="form-hint">{{ __t('plugins.license_key_hint') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">
                    {{ __t('common.cancel') }}
                </button>
                <button type="submit" class="btn-primary">
                    {{ __t('plugins.activate_license') }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('inline-scripts')
<script nonce="{{ csp_nonce() }}">
function showAddLicenseModal(slug = null) {
    if (slug) {
        $('#licensePlugin').val(slug);
    }
    $('#addLicenseModal').fadeIn(200);
}

function closeModal() {
    $('.modal').fadeOut(200);
}

function submitLicense(e) {
    e.preventDefault();
    
    const $form = $('#addLicenseForm');
    const $btn = $form.find('button[type="submit"]');
    const originalText = $btn.html();
    
    $btn.prop('disabled', true).html('<span class="spinner"></span> {{ __t("plugins.activating") }}...');
    
    $.ajax({
        url: '{{ route("admin.plugins.licenses.activate") }}',
        method: 'POST',
        data: $form.serialize(),
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message);
                closeModal();
                location.reload();
            }
        },
        error: function(xhr) {
            showNotification('error', xhr.responseJSON?.message || '{{ __t("plugins.license_activation_failed") }}');
        },
        complete: function() {
            $btn.prop('disabled', false).html(originalText);
        }
    });
}

function deactivateLicense(slug) {
    if (!confirm('{{ __t("plugins.confirm_deactivate_license") }}')) return;
    
    $.ajax({
        url: '{{ url("admin/system/plugins/licenses") }}/' + slug + '/deactivate',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        success: function(response) {
            showNotification('success', response.message || '{{ __t("plugins.license_deactivated") }}');
            location.reload();
        },
        error: function(xhr) {
            showNotification('error', xhr.responseJSON?.message || '{{ __t("common.error_occurred") }}');
        }
    });
}

function viewLicenseDetails(id) {
    // Could open a modal with full license details
    alert('License details view - to be implemented');
}

function showNotification(type, message) {
    if (typeof window.showAlert === 'function') {
        window.showAlert(type, message);
    } else {
        alert(message);
    }
}

// Close modal on escape
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endpush
@endsection
