{{-- Permission Audit Log (Screen 6 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Permission Audit Log')
@section('page-id', 'system/permissions/audit')
@section('require-css', 'permissions')

@section('header', 'Permission Audit Log')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.index') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Roles</span>
    </a>
    <button type="button" class="btn-secondary flex items-center gap-2" onclick="exportAuditLog()">
        @include('backend.partials.icon', ['icon' => 'download'])
        <span>Export CSV</span>
    </button>
</div>
@endsection

@section('content')
<div class="audit-log-page">
    {{-- Filters --}}
    <div class="toolbar mb-6">
        <div class="search-filter-group">
            <div class="date-range-filter">
                <input type="date"
                       id="dateFrom"
                       class="form-input"
                       value="{{ request('date_from') }}"
                       placeholder="From">
                <span class="text-muted">to</span>
                <input type="date"
                       id="dateTo"
                       class="form-input"
                       value="{{ request('date_to') }}"
                       placeholder="To">
            </div>

            <select id="actionFilter" class="filter-select">
                <option value="">All Actions</option>
                @foreach($actions as $action => $label)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <select id="userFilter" class="filter-select">
                <option value="">All Users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>

            <select id="targetFilter" class="filter-select">
                <option value="">All Target Types</option>
                <option value="role" {{ request('target_type') === 'role' ? 'selected' : '' }}>Roles</option>
                <option value="permission" {{ request('target_type') === 'permission' ? 'selected' : '' }}>Permissions</option>
                <option value="user" {{ request('target_type') === 'user' ? 'selected' : '' }}>Users</option>
                <option value="access_rule" {{ request('target_type') === 'access_rule' ? 'selected' : '' }}>Access Rules</option>
            </select>

            <div class="search-input-wrapper">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text"
                       id="searchInput"
                       class="search-input"
                       placeholder="Search..."
                       value="{{ request('search') }}">
            </div>

            <button type="button" class="btn-secondary" onclick="applyFilters()">
                Apply Filters
            </button>
        </div>

        <div class="item-count">
            Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries
        </div>
    </div>

    {{-- Audit Table --}}
    @if($logs->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'fileText'])
            </div>
            <h3>No Audit Entries</h3>
            <p>No permission-related changes have been logged yet.</p>
        </div>
    @else
        <div class="data-table-container">
            <table class="data-table audit-table">
                <thead>
                    <tr>
                        <th style="width: 160px;">Time</th>
                        <th style="width: 140px;">User</th>
                        <th style="width: 150px;">Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr class="audit-row severity-{{ $log->severity }}">
                            <td>
                                <span class="audit-time" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </td>
                            <td>
                                <div class="audit-user">
                                    @if($log->user)
                                        <span class="user-name">{{ $log->user->name }}</span>
                                    @else
                                        <span class="user-system">System</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="audit-action action-{{ $log->action }}">
                                    <span class="action-icon">
                                        @include('backend.partials.icon', ['icon' => $log->action_icon])
                                    </span>
                                    {{ $log->action_label }}
                                </span>
                            </td>
                            <td>
                                <div class="audit-details">
                                    <span class="audit-target">
                                        @if($log->target_type === 'role')
                                            Role: {{ $log->metadata['role_name'] ?? $log->target_id }}
                                        @elseif($log->target_type === 'user')
                                            User: {{ $log->metadata['user_email'] ?? $log->target_id }}
                                        @elseif($log->target_type === 'access_rule')
                                            Rule: {{ $log->metadata['rule_name'] ?? $log->target_id }}
                                        @else
                                            {{ ucfirst($log->target_type) }}: {{ $log->target_id }}
                                        @endif
                                    </span>
                                    @if($log->description)
                                        <p class="audit-description">{{ Str::limit($log->description, 80) }}</p>
                                    @endif
                                    <button type="button"
                                            class="btn-link btn-sm"
                                            onclick="showDetails({{ $log->id }})">
                                        View Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="pagination-wrapper">
                {{ $logs->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Details Modal --}}
<div id="detailsModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="closeDetailsModal()"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Audit Entry Details</h3>
            <button type="button" class="modal-close" onclick="closeDetailsModal()">
                @include('backend.partials.icon', ['icon' => 'x'])
            </button>
        </div>
        <div class="modal-body" id="detailsContent">
            <div class="loading-spinner">Loading...</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeDetailsModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const params = new URLSearchParams();

    const dateFrom = document.getElementById('dateFrom')?.value;
    const dateTo = document.getElementById('dateTo')?.value;
    const action = document.getElementById('actionFilter')?.value;
    const userId = document.getElementById('userFilter')?.value;
    const targetType = document.getElementById('targetFilter')?.value;
    const search = document.getElementById('searchInput')?.value;

    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    if (action) params.set('action', action);
    if (userId) params.set('user_id', userId);
    if (targetType) params.set('target_type', targetType);
    if (search) params.set('search', search);

    const url = `{{ route('admin.permissions.audit') }}?${params.toString()}`;
    Vodo.pjax.load(url);
}

function exportAuditLog() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'csv');
    window.location.href = `{{ route('admin.permissions.audit') }}?${params.toString()}`;
}

let currentLogData = null;

function showDetails(logId) {
    document.getElementById('detailsModal').style.display = 'flex';
    document.getElementById('detailsContent').innerHTML = '<div class="loading-spinner">Loading...</div>';

    // In a real implementation, fetch the log details via API
    // For now, we'll use inline data
    Vodo.api.get(`{{ url('admin/system/permissions/audit') }}/${logId}`).then(response => {
        if (response.success && response.log) {
            currentLogData = response.log;
            renderDetails(response.log);
        }
    }).catch(error => {
        document.getElementById('detailsContent').innerHTML = '<p class="text-danger">Failed to load details</p>';
    });
}

function renderDetails(log) {
    let html = `
        <dl class="info-list">
            <div class="info-item">
                <dt>Action</dt>
                <dd><span class="audit-action action-${log.action}">${log.action_label}</span></dd>
            </div>
            <div class="info-item">
                <dt>Time</dt>
                <dd>${log.created_at_formatted}</dd>
            </div>
            <div class="info-item">
                <dt>User</dt>
                <dd>${log.user_name || 'System'} ${log.user_email ? '(' + log.user_email + ')' : ''}</dd>
            </div>
            <div class="info-item">
                <dt>IP Address</dt>
                <dd>${log.ip_address || 'N/A'}</dd>
            </div>
            <div class="info-item">
                <dt>Target</dt>
                <dd>${log.target_type}: ${log.target_name || log.target_id}</dd>
            </div>
        </dl>
    `;

    if (log.changes && Object.keys(log.changes).length > 0) {
        html += '<h4 class="mt-4 mb-2">Changes</h4>';
        html += '<div class="changes-panel">';

        if (log.changes.permissions_added && log.changes.permissions_added.length > 0) {
            html += '<div class="change-section added"><h5>Permissions Added (+' + log.changes.permissions_added.length + ')</h5><ul>';
            log.changes.permissions_added.forEach(p => {
                html += `<li>${p}</li>`;
            });
            html += '</ul></div>';
        }

        if (log.changes.permissions_removed && log.changes.permissions_removed.length > 0) {
            html += '<div class="change-section removed"><h5>Permissions Removed (-' + log.changes.permissions_removed.length + ')</h5><ul>';
            log.changes.permissions_removed.forEach(p => {
                html += `<li>${p}</li>`;
            });
            html += '</ul></div>';
        }

        html += '</div>';
    }

    if (log.affected_users_count) {
        html += `<p class="mt-4 text-muted">Affected Users: ${log.affected_users_count} users with this role</p>`;
    }

    document.getElementById('detailsContent').innerHTML = html;
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
    currentLogData = null;
}

// Keyboard shortcut
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('detailsModal').style.display !== 'none') {
        closeDetailsModal();
    }
});
</script>
@endsection
