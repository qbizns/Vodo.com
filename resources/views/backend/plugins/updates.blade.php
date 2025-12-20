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

@push('inline-scripts')
<script>
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
                showNotification('success', response.message);
                if (response.updates_found > 0) {
                    // Reload to show new updates
                    location.reload();
                }
            }
        },
        error: function(xhr) {
            showNotification('error', xhr.responseJSON?.message || '{{ __t("plugins.update_check_failed") }}');
        },
        complete: function() {
            $btn.prop('disabled', false).html(originalText);
        }
    });
}

function updatePlugin(slug) {
    if (!confirm('{{ __t("plugins.confirm_update") }}')) return;
    
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
                showNotification('success', response.message);
                $card.fadeOut(300, function() { $(this).remove(); });
                
                // Update count
                const $summary = $('.updates-summary strong');
                const count = parseInt($summary.text()) - 1;
                if (count <= 0) {
                    location.reload();
                } else {
                    $summary.text(count);
                }
            }
        },
        error: function(xhr) {
            showNotification('error', xhr.responseJSON?.message || '{{ __t("plugins.update_failed") }}');
            $btn.prop('disabled', false).html(originalText);
        }
    });
}

function updateAll() {
    if (!confirm('{{ __t("plugins.confirm_update_all") }}')) return;
    
    const slugs = [];
    $('.update-card').each(function() {
        slugs.push($(this).data('plugin-slug'));
    });
    
    // Update one by one
    let current = 0;
    function updateNext() {
        if (current >= slugs.length) {
            showNotification('success', '{{ __t("plugins.all_updated") }}');
            location.reload();
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
    $.post('{{ route("admin.settings.save") }}', {
        _token: '{{ csrf_token() }}',
        'plugins.auto_update_check': checkbox.checked ? '1' : '0',
    });
}

function showNotification(type, message) {
    // Use existing notification system
    if (typeof window.showAlert === 'function') {
        window.showAlert(type, message);
    } else {
        alert(message);
    }
}
</script>
@endpush
@endsection
