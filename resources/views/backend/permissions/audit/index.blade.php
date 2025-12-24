{{-- Permission Audit Log (Screen 6 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Permission Audit Log')
@section('page-id', 'system/permissions/audit')
@section('require-css', 'permissions')

@section('header')
Permission Audit Log
<span class="badge badge-info">{{ $logs->total() }} entries</span>
@endsection

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
    {{-- Stats Summary --}}
    <div class="stats-grid mb-6">
        @foreach($actionCounts as $action => $count)
            <div class="stat-card">
                <div class="stat-icon stat-icon-{{ $action }}">
                    @php
                        $iconMap = [
                            'role_created' => 'userPlus',
                            'role_updated' => 'edit',
                            'role_deleted' => 'trash',
                            'permission_granted' => 'checkCircle',
                            'permission_revoked' => 'xCircle',
                            'user_role_assigned' => 'link',
                            'user_role_removed' => 'unlink',
                            'access_rule_created' => 'plus',
                            'access_rule_triggered' => 'alertTriangle',
                        ];
                    @endphp
                    @include('backend.partials.icon', ['icon' => $iconMap[$action] ?? 'activity'])
                </div>
                <div class="stat-content">
                    <span class="stat-value">{{ $count }}</span>
                    <span class="stat-label">{{ ucwords(str_replace('_', ' ', $action)) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tabs for navigation --}}
    <div class="permission-tabs mb-6">
        <a href="{{ route('admin.roles.index') }}" class="permission-tab">
            @include('backend.partials.icon', ['icon' => 'users'])
            <span>Roles</span>
        </a>
        <a href="{{ route('admin.permissions.matrix') }}" class="permission-tab">
            @include('backend.partials.icon', ['icon' => 'grid'])
            <span>Permission Matrix</span>
        </a>
        <a href="{{ route('admin.permissions.rules') }}" class="permission-tab">
            @include('backend.partials.icon', ['icon' => 'shield'])
            <span>Access Rules</span>
        </a>
        <a href="{{ route('admin.permissions.audit') }}" class="permission-tab active">
            @include('backend.partials.icon', ['icon' => 'clipboardList'])
            <span>Audit Log</span>
        </a>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="filters-row">
                <div class="filter-group">
                    <label class="filter-label">Date Range</label>
                    <div class="date-range-inputs">
                        <input type="date"
                               id="dateFrom"
                               class="form-input"
                               value="{{ request('date_from') }}"
                               placeholder="From">
                        <span class="date-separator">to</span>
                        <input type="date"
                               id="dateTo"
                               class="form-input"
                               value="{{ request('date_to') }}"
                               placeholder="To">
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Action</label>
                    <select id="actionFilter" class="form-select">
                        <option value="">All Actions</option>
                        @foreach($actions as $action => $label)
                            <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">User</label>
                    <select id="userFilter" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Target Type</label>
                    <select id="targetFilter" class="form-select">
                        <option value="">All Types</option>
                        <option value="role" {{ request('target_type') === 'role' ? 'selected' : '' }}>Roles</option>
                        <option value="permission" {{ request('target_type') === 'permission' ? 'selected' : '' }}>Permissions</option>
                        <option value="user" {{ request('target_type') === 'user' ? 'selected' : '' }}>Users</option>
                        <option value="access_rule" {{ request('target_type') === 'access_rule' ? 'selected' : '' }}>Access Rules</option>
                    </select>
                </div>

                <div class="filter-group filter-group-search">
                    <label class="filter-label">Search</label>
                    <div class="search-input-wrapper">
                        @include('backend.partials.icon', ['icon' => 'search'])
                        <input type="text"
                               id="searchInput"
                               class="form-input search-input"
                               placeholder="Search entries..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" class="btn-primary" onclick="applyFilters()">
                        @include('backend.partials.icon', ['icon' => 'filter'])
                        Apply
                    </button>
                    <button type="button" class="btn-secondary" onclick="clearFilters()">
                        Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Audit Table --}}
    @if($logs->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'clipboardList'])
            </div>
            <h3>No Audit Entries</h3>
            <p>No permission-related changes have been logged yet.</p>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Activity Log</h3>
                <span class="item-count">
                    Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="data-table audit-table">
                    <thead>
                        <tr>
                            <th class="col-time">Timestamp</th>
                            <th class="col-user">User</th>
                            <th class="col-action">Action</th>
                            <th class="col-target">Target</th>
                            <th class="col-details">Details</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            @php
                                $severityClass = match($log->action) {
                                    'role_deleted', 'permission_revoked', 'user_role_removed' => 'severity-danger',
                                    'access_rule_triggered' => 'severity-warning',
                                    'role_created', 'permission_granted', 'user_role_assigned' => 'severity-success',
                                    default => 'severity-info',
                                };
                                $actionIcon = match($log->action) {
                                    'role_created' => 'userPlus',
                                    'role_updated' => 'edit',
                                    'role_deleted' => 'trash',
                                    'permission_granted' => 'checkCircle',
                                    'permission_revoked' => 'xCircle',
                                    'user_role_assigned' => 'link',
                                    'user_role_removed' => 'unlink',
                                    'access_rule_created' => 'plus',
                                    'access_rule_updated' => 'edit',
                                    'access_rule_deleted' => 'trash',
                                    'access_rule_triggered' => 'alertTriangle',
                                    default => 'activity',
                                };
                            @endphp
                            <tr class="audit-row {{ $severityClass }}">
                                <td class="col-time">
                                    <div class="timestamp">
                                        <span class="time-relative" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                            {{ $log->created_at->diffForHumans() }}
                                        </span>
                                        <span class="time-exact">
                                            {{ $log->created_at->format('M j, H:i') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="col-user">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            @if($log->user)
                                                {{ strtoupper(substr($log->user->name, 0, 2)) }}
                                            @else
                                                SY
                                            @endif
                                        </div>
                                        <div class="user-details">
                                            <span class="user-name">{{ $log->user?->name ?? 'System' }}</span>
                                            @if($log->ip_address)
                                                <span class="user-ip">{{ $log->ip_address }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="col-action">
                                    <span class="action-badge action-{{ $log->action }}">
                                        @include('backend.partials.icon', ['icon' => $actionIcon])
                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                </td>
                                <td class="col-target">
                                    <div class="target-info">
                                        <span class="target-type">{{ ucfirst($log->target_type) }}</span>
                                        <span class="target-name">{{ $log->target_name ?? $log->target_id }}</span>
                                    </div>
                                </td>
                                <td class="col-details">
                                    @if($log->changes)
                                        @php
                                            $changes = is_array($log->changes) ? $log->changes : json_decode($log->changes, true);
                                        @endphp
                                        @if(!empty($changes['permissions_added']))
                                            <span class="change-badge change-added">
                                                +{{ count($changes['permissions_added']) }} permissions
                                            </span>
                                        @endif
                                        @if(!empty($changes['permissions_removed']))
                                            <span class="change-badge change-removed">
                                                -{{ count($changes['permissions_removed']) }} permissions
                                            </span>
                                        @endif
                                        @if(empty($changes['permissions_added']) && empty($changes['permissions_removed']))
                                            <span class="text-muted">—</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="col-actions">
                                    <button type="button"
                                            class="btn-icon"
                                            onclick="showDetails({{ $log->id }})"
                                            title="View Details">
                                        @include('backend.partials.icon', ['icon' => 'eye'])
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($logs->hasPages())
                <div class="card-footer">
                    <div class="pagination-wrapper">
                        {{ $logs->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

<script>
(function() {
    // Apply filters
    window.applyFilters = function() {
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
        window.location.href = url;
    };

    // Clear filters
    window.clearFilters = function() {
        window.location.href = '{{ route('admin.permissions.audit') }}';
    };

    // Export audit log
    window.exportAuditLog = function() {
        const params = new URLSearchParams(window.location.search);
        params.set('format', 'csv');
        window.location.href = `{{ route('admin.permissions.audit') }}?${params.toString()}`;
    };

    // Show details in modal
    window.showDetails = function(logId) {
        // Show modal with loading state
        var modalId = Vodo.modals.open({
            title: 'Audit Entry Details',
            content: '<div class="loading-state"><div class="spinner"></div><p>Loading details...</p></div>',
            size: 'lg',
            footer: '<button type="button" class="btn-secondary" data-modal-close>Close</button>'
        });

        // Fetch the log details
        Vodo.ajax.get(`{{ url('system/permissions/audit') }}/${logId}`)
            .then(function(response) {
                if (response.success && response.log) {
                    const content = renderDetailsContent(response.log);
                    // Update modal content using Vodo.modals.update
                    Vodo.modals.update(modalId, { content: content });
                } else {
                    Vodo.modals.update(modalId, { content: '<div class="alert alert-danger">Failed to load audit details</div>' });
                }
            })
            .catch(function(error) {
                console.error('Error loading audit details:', error);
                Vodo.modals.update(modalId, { content: '<div class="alert alert-danger">Failed to load audit details. Please try again.</div>' });
            });
    };

    // Render details content
    function renderDetailsContent(log) {
        let html = `
            <div class="audit-detail-view">
                <div class="detail-section">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Action</label>
                            <div class="action-badge action-${log.action}">
                                ${log.action_label}
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Timestamp</label>
                            <div>
                                <strong>${log.created_at_formatted}</strong>
                                <span class="text-muted">(${log.created_at_relative})</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>User</label>
                            <div>
                                <strong>${log.user_name}</strong>
                                ${log.user_email ? '<span class="text-muted">(' + log.user_email + ')</span>' : ''}
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>IP Address</label>
                            <div><code>${log.ip_address || 'N/A'}</code></div>
                        </div>
                        <div class="detail-item">
                            <label>Target Type</label>
                            <div><span class="badge">${log.target_type}</span></div>
                        </div>
                        <div class="detail-item">
                            <label>Target</label>
                            <div><strong>${log.target_name || log.target_id}</strong></div>
                        </div>
                    </div>
                </div>
        `;

        // Changes section
        if (log.changes && Object.keys(log.changes).length > 0) {
            html += `<div class="detail-section"><h4>Changes Made</h4><div class="changes-panel">`;

            if (log.changes.permissions_added && log.changes.permissions_added.length > 0) {
                html += `
                    <div class="change-group change-added">
                        <h5><span class="change-icon">+</span> Permissions Added (${log.changes.permissions_added.length})</h5>
                        <ul class="change-list">
                            ${log.changes.permissions_added.map(p => `<li><code>${p}</code></li>`).join('')}
                        </ul>
                    </div>
                `;
            }

            if (log.changes.permissions_removed && log.changes.permissions_removed.length > 0) {
                html += `
                    <div class="change-group change-removed">
                        <h5><span class="change-icon">−</span> Permissions Removed (${log.changes.permissions_removed.length})</h5>
                        <ul class="change-list">
                            ${log.changes.permissions_removed.map(p => `<li><code>${p}</code></li>`).join('')}
                        </ul>
                    </div>
                `;
            }

            // Handle other change types
            Object.keys(log.changes).forEach(function(key) {
                if (key !== 'permissions_added' && key !== 'permissions_removed') {
                    const value = log.changes[key];
                    if (typeof value === 'object') {
                        html += `
                            <div class="change-group">
                                <h5>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</h5>
                                <pre class="change-json">${JSON.stringify(value, null, 2)}</pre>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="change-item">
                                <span class="change-key">${key.replace(/_/g, ' ')}:</span>
                                <span class="change-value">${value}</span>
                            </div>
                        `;
                    }
                }
            });

            html += '</div></div>';
        }

        // User agent
        if (log.user_agent) {
            html += `
                <div class="detail-section">
                    <h4>Browser Information</h4>
                    <code class="user-agent">${log.user_agent}</code>
                </div>
            `;
        }

        // Affected users
        if (log.affected_users_count) {
            html += `
                <div class="detail-section">
                    <div class="alert alert-info">
                        This change affected <strong>${log.affected_users_count}</strong> user(s) with this role.
                    </div>
                </div>
            `;
        }

        html += '</div>';
        return html;
    }

    // Enter key to apply filters
    document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
})();
</script>
@endsection
