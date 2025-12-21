{{-- Plugin Updates (Screen 6) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', __t('plugins.updates'))
@section('page-id', 'system/plugins/updates')
@section('require-css', 'plugins')

@section('header', __t('plugins.updates'))

@section('header-actions')
<div class="flex items-center gap-3">
    <button type="button" class="btn-secondary flex items-center gap-2" onclick="checkForUpdates()">
        @include('backend.partials.icon', ['icon' => 'refreshCw'])
        <span>{{ __t('plugins.check_updates') }}</span>
    </button>
</div>
@endsection

@section('content')
<div class="updates-page">
    {{-- Updates Summary --}}
    @if($plugins->isNotEmpty())
        <div class="updates-summary">
            <div class="summary-icon">
                @include('backend.partials.icon', ['icon' => 'download'])
            </div>
            <div class="summary-text">
                <strong>{{ $plugins->count() }}</strong> {{ __t('plugins.updates_available') }}
            </div>
            <button type="button" class="btn-primary" onclick="updateAll()">
                {{ __t('plugins.update_all') }}
            </button>
        </div>
    @endif

    {{-- Updates List --}}
    @if($plugins->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'checkCircle'])
            </div>
            <h3>{{ __t('plugins.all_up_to_date') }}</h3>
            <p>{{ __t('plugins.all_up_to_date_desc') }}</p>
        </div>
    @else
        <div class="updates-list">
            @foreach($plugins as $plugin)
                @php
                    $update = $plugin->availableUpdate;
                @endphp
                <div class="update-card" data-plugin-slug="{{ $plugin->slug }}">
                    <div class="update-header">
                        <div class="update-checkbox">
                            <input type="checkbox" class="form-checkbox update-select" value="{{ $plugin->id }}">
                        </div>
                        <div class="update-info">
                            <div class="update-title">
                                <span class="plugin-name">{{ $plugin->name }}</span>
                                <span class="version-change">
                                    {{ $plugin->version }} → {{ $update->latest_version }}
                                </span>
                            </div>
                            @if($update->is_security_update)
                                <span class="badge badge-danger">
                                    @include('backend.partials.icon', ['icon' => 'shield'])
                                    {{ __t('plugins.security_update') }}
                                </span>
                            @endif
                            @if($update->is_breaking_change)
                                <span class="badge badge-warning">
                                    @include('backend.partials.icon', ['icon' => 'alertTriangle'])
                                    {{ __t('plugins.breaking_changes') }}
                                </span>
                            @endif
                        </div>
                        <div class="update-actions">
                            <a href="{{ route('admin.plugins.show', ['slug' => $plugin->slug, 'tab' => 'changelog']) }}" 
                               class="btn-secondary btn-sm">
                                {{ __t('plugins.view') }}
                            </a>
                            <button type="button" 
                                    class="btn-primary btn-sm"
                                    onclick="updatePlugin('{{ $plugin->slug }}')">
                                {{ __t('plugins.update') }}
                            </button>
                        </div>
                    </div>

                    {{-- Changelog Preview --}}
                    @if($update->changelog)
                        <div class="update-changelog">
                            <div class="changelog-header" onclick="toggleChangelog(this)">
                                <span>{{ __t('plugins.whats_new') }} {{ $update->latest_version }}:</span>
                                @include('backend.partials.icon', ['icon' => 'chevronDown'])
                            </div>
                            <div class="changelog-content">
                                {!! nl2br(e(Str::limit($update->changelog, 500))) !!}
                                @if(strlen($update->changelog) > 500)
                                    <a href="{{ route('admin.plugins.show', ['slug' => $plugin->slug, 'tab' => 'changelog']) }}">
                                        {{ __t('plugins.full_changelog') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Requirements Warning --}}
                    @if(!$update->canUpdate())
                        <div class="update-warning">
                            @include('backend.partials.icon', ['icon' => 'alertTriangle'])
                            <div>
                                <strong>{{ __t('plugins.cannot_update') }}</strong>
                                @if($update->requires_php_version && version_compare(PHP_VERSION, $update->requires_php_version, '<'))
                                    <p>{{ __t('plugins.requires_php', ['version' => $update->requires_php_version]) }}</p>
                                @endif
                                @if($update->requires_system_version && version_compare(config('app.version'), $update->requires_system_version, '<'))
                                    <p>{{ __t('plugins.requires_system', ['version' => $update->requires_system_version]) }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Last Check Info --}}
    <div class="updates-footer">
        <span class="last-check">
            {{ __t('plugins.last_checked') }}: 
            {{ $lastCheck ? $lastCheck->diffForHumans() : __t('common.never') }}
        </span>
        <label class="auto-check-toggle">
            <input type="checkbox" {{ $autoCheckEnabled ? 'checked' : '' }} onchange="toggleAutoCheck(this)">
            <span>{{ __t('plugins.auto_check') }}</span>
        </label>
    </div>
</div>

@php
    $pluginDataForJs = $plugins->mapWithKeys(function($p) {
        return [$p->slug => [
            'name' => $p->name,
            'version' => $p->version,
            'latestVersion' => $p->availableUpdate->latest_version ?? $p->latest_version ?? '',
            'isSecurityUpdate' => $p->availableUpdate->is_security_update ?? false,
            'isBreakingChange' => $p->availableUpdate->is_breaking_change ?? false,
        ]];
    });
@endphp

@push('inline-scripts')
<script nonce="{{ csp_nonce() }}">
// Store plugin data for modals
const pluginData = @json($pluginDataForJs);

function checkForUpdates() {
    const $btn = $(event.target).closest('button');
    const originalText = $btn.html();
    
    $btn.prop('disabled', true).html('<span class="spinner"></span> {{ __t("plugins.checking") }}...');
    
    $.ajax({
        url: '{{ route("admin.plugins.updates.check") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        success: function(response) {
            if (response.success) {
                Vodo.notify.success(response.message);
                if (response.updates_found > 0) {
                    // Reload to show new updates
                    if (window.Vodo && Vodo.router) {
                        Vodo.router.refresh();
                    } else {
                        location.reload();
                    }
                }
            }
        },
        error: function(xhr) {
            Vodo.notify.error(xhr.responseJSON?.message || '{{ __t("plugins.update_check_failed") }}');
        },
        complete: function() {
            $btn.prop('disabled', false).html(originalText);
        }
    });
}

/**
 * Show update confirmation modal for a single plugin
 */
function updatePlugin(slug) {
    const plugin = pluginData[slug];
    if (!plugin) {
        Vodo.notify.error('{{ __t("plugins.plugin_not_found") }}');
        return;
    }
    
    showUpdateModal({
        title: '{{ __t("plugins.update") }} ' + plugin.name,
        pluginName: plugin.name,
        currentVersion: plugin.version,
        newVersion: plugin.latestVersion,
        isSecurityUpdate: plugin.isSecurityUpdate,
        isBreakingChange: plugin.isBreakingChange,
        onConfirm: function() {
            performUpdate(slug);
        }
    });
}

/**
 * Show update confirmation modal for all plugins
 */
function updateAll() {
    const count = Object.keys(pluginData).length;
    if (count === 0) return;
    
    showUpdateModal({
        title: '{{ __t("plugins.update_all") }}',
        pluginName: count + ' {{ __t("plugins.plugins") }}',
        isMultiple: true,
        onConfirm: function() {
            performUpdateAll();
        }
    });
}

/**
 * Show the update confirmation modal
 */
function showUpdateModal(options) {
    const warningItems = [
        '{{ __t("plugins.update_warning_backup") }}',
        '{{ __t("plugins.update_warning_deactivate") }}',
        '{{ __t("plugins.update_warning_migrations") }}'
    ];
    
    let warningHtml = '<div class="update-warning-box">' +
        '<div class="warning-header">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>' +
            '<span>{{ __t("plugins.before_updating") }}:</span>' +
        '</div>' +
        '<ul class="warning-list">';
    
    warningItems.forEach(function(item) {
        warningHtml += '<li>' + item + '</li>';
    });
    warningHtml += '</ul></div>';
    
    // Version change info (only for single plugin)
    let versionHtml = '';
    if (!options.isMultiple) {
        versionHtml = '<p class="update-version-info">' +
            '{{ __t("plugins.update_from_to", ["name" => ":name", "from" => ":from", "to" => ":to"]) }}'
                .replace(':name', '<strong>' + Vodo.utils.escapeHtml(options.pluginName) + '</strong>')
                .replace(':from', '<strong>v' + Vodo.utils.escapeHtml(options.currentVersion) + '</strong>')
                .replace(':to', '<strong>v' + Vodo.utils.escapeHtml(options.newVersion) + '</strong>') +
            '</p>';
        
        // Security/Breaking badges
        if (options.isSecurityUpdate) {
            versionHtml += '<span class="badge badge-danger" style="margin-right: var(--spacing-2);">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> ' +
                '{{ __t("plugins.security_update") }}</span>';
        }
        if (options.isBreakingChange) {
            versionHtml += '<span class="badge badge-warning">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg> ' +
                '{{ __t("plugins.breaking_changes") }}</span>';
        }
    } else {
        versionHtml = '<p class="update-version-info">' +
            '{{ __t("plugins.update_multiple", ["count" => ":count"]) }}'
                .replace(':count', '<strong>' + Vodo.utils.escapeHtml(options.pluginName) + '</strong>') +
            '</p>';
    }
    
    // Checkboxes
    const checkboxesHtml = '<div class="update-checkboxes">' +
        '<label class="checkbox-item">' +
            '<input type="checkbox" id="updateConfirmBackup" class="form-checkbox">' +
            '<span>{{ __t("plugins.confirm_backup") }}</span>' +
        '</label>' +
        '<label class="checkbox-item">' +
            '<input type="checkbox" id="updateConfirmDowntime" class="form-checkbox">' +
            '<span>{{ __t("plugins.confirm_downtime") }}</span>' +
        '</label>' +
    '</div>';
    
    const contentHtml = versionHtml + warningHtml + checkboxesHtml;
    
    const modalId = Vodo.modals.open({
        title: options.title,
        content: contentHtml,
        size: 'md',
        class: 'update-confirmation-modal',
        footer: `
            <button type="button" class="btn-secondary" data-modal-close>{{ __t('common.cancel') }}</button>
            <button type="button" class="btn-primary" id="updateNowBtn" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                {{ __t('plugins.update_now') }}
            </button>
        `,
        onOpen: function(id, $modal) {
            const $confirmBtn = $modal.find('#updateNowBtn');
            const $checkbox1 = $modal.find('#updateConfirmBackup');
            const $checkbox2 = $modal.find('#updateConfirmDowntime');
            
            // Enable button only when both checkboxes are checked
            function checkCheckboxes() {
                $confirmBtn.prop('disabled', !($checkbox1.is(':checked') && $checkbox2.is(':checked')));
            }
            
            $checkbox1.on('change', checkCheckboxes);
            $checkbox2.on('change', checkCheckboxes);
            
            // Handle confirm button
            $confirmBtn.on('click', function() {
                Vodo.modals.close(id);
                if (typeof options.onConfirm === 'function') {
                    options.onConfirm();
                }
            });
        }
    });
}

/**
 * Perform the actual update for a single plugin
 */
function performUpdate(slug) {
    const $card = $(`[data-plugin-slug="${slug}"]`);
    const $btn = $card.find('.btn-primary');
    const originalText = $btn.html();
    
    $btn.prop('disabled', true).html('<span class="spinner"></span>');
    
    $.ajax({
        url: '{{ url("admin/system/plugins") }}/' + slug + '/update',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        success: function(response) {
            if (response.success) {
                Vodo.notify.success(response.message);
                $card.fadeOut(300, function() { $(this).remove(); });
                
                // Update count
                const $summary = $('.updates-summary strong');
                const count = parseInt($summary.text()) - 1;
                if (count <= 0) {
                    if (window.Vodo && Vodo.router) {
                        Vodo.router.refresh();
                    } else {
                        location.reload();
                    }
                } else {
                    $summary.text(count);
                }
            }
        },
        error: function(xhr) {
            Vodo.notify.error(xhr.responseJSON?.message || '{{ __t("plugins.update_failed") }}');
            $btn.prop('disabled', false).html(originalText);
        }
    });
}

/**
 * Perform update for all plugins
 */
function performUpdateAll() {
    const slugs = [];
    $('.update-card').each(function() {
        slugs.push($(this).data('plugin-slug'));
    });
    
    if (slugs.length === 0) return;
    
    // Show progress notification
    Vodo.notify.info('{{ __t("plugins.updating_plugins") }}...', { duration: 0, id: 'update-progress' });
    
    // Update one by one
    let current = 0;
    let failed = 0;
    
    function updateNext() {
        if (current >= slugs.length) {
            Vodo.notify.close('update-progress');
            if (failed > 0) {
                Vodo.notify.warning('{{ __t("plugins.some_updates_failed", ["count" => ":count"]) }}'.replace(':count', failed));
            } else {
                Vodo.notify.success('{{ __t("plugins.all_updated") }}');
            }
            if (window.Vodo && Vodo.router) {
                Vodo.router.refresh();
            } else {
                location.reload();
            }
            return;
        }
        
        const slug = slugs[current];
        const $card = $(`[data-plugin-slug="${slug}"]`);
        $card.addClass('updating');
        
        $.ajax({
            url: '{{ url("admin/system/plugins") }}/' + slug + '/update',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            success: function() {
                $card.removeClass('updating').addClass('updated');
            },
            error: function() {
                $card.removeClass('updating').addClass('update-failed');
                failed++;
            },
            complete: function() {
                current++;
                updateNext();
            }
        });
    }
    
    updateNext();
}

function toggleChangelog(header) {
    $(header).parent().toggleClass('expanded');
}

function toggleAutoCheck(checkbox) {
    // Save preference via AJAX
    // TODO: Implement settings save route
    console.log('Auto-check updates: ' + (checkbox.checked ? 'enabled' : 'disabled'));
}
</script>
@endpush

@push('inline-styles')
<style>
/* Update Confirmation Modal Styles */
.update-confirmation-modal .update-version-info {
    margin-bottom: var(--spacing-4);
    font-size: var(--text-base);
}

.update-confirmation-modal .update-warning-box {
    background: var(--bg-warning-subtle, rgba(234, 179, 8, 0.1));
    border: 1px solid var(--border-warning, rgba(234, 179, 8, 0.3));
    border-radius: var(--radius-md);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.update-confirmation-modal .warning-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-weight: 600;
    color: var(--text-warning, #b45309);
    margin-bottom: var(--spacing-2);
}

.update-confirmation-modal .warning-list {
    margin: 0;
    padding-left: var(--spacing-6);
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

.update-confirmation-modal .warning-list li {
    margin-bottom: var(--spacing-1);
}

.update-confirmation-modal .update-checkboxes {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
}

.update-confirmation-modal .checkbox-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    cursor: pointer;
    font-size: var(--text-sm);
}

.update-confirmation-modal .checkbox-item input {
    width: 18px;
    height: 18px;
}

.update-confirmation-modal .badge {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-1);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: 500;
    margin-top: var(--spacing-2);
}

.update-confirmation-modal .badge-danger {
    background: var(--bg-danger-subtle, rgba(239, 68, 68, 0.1));
    color: var(--text-danger, #dc2626);
}

.update-confirmation-modal .badge-warning {
    background: var(--bg-warning-subtle, rgba(234, 179, 8, 0.1));
    color: var(--text-warning, #b45309);
}
</style>
@endpush
@endsection
