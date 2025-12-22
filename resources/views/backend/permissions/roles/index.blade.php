{{-- Roles List (Screen 1 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Roles')
@section('page-id', 'system/roles')
@section('require-css', 'permissions')

@section('header', 'Roles')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.compare') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'gitCompare'])
        <span>Compare</span>
    </a>
    <a href="{{ route('admin.roles.create') }}" class="btn-primary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>Create Role</span>
    </a>
</div>
@endsection

@section('content')
<div class="roles-page">
    {{-- Search and Filters --}}
    <div class="toolbar">
        <div class="search-filter-group">
            <div class="search-input-wrapper">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text"
                       id="roleSearch"
                       class="search-input"
                       placeholder="Search roles..."
                       value="{{ $filters['search'] ?? '' }}">
            </div>

            <select id="pluginFilter" class="filter-select">
                <option value="">All Sources</option>
                <option value="core" {{ ($filters['plugin'] ?? '') === 'core' ? 'selected' : '' }}>Core</option>
                {{-- Plugin options would be populated dynamically --}}
            </select>

            <select id="statusFilter" class="filter-select">
                <option value="">All Status</option>
                <option value="1" {{ ($filters['active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ ($filters['active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="item-count">
            Showing {{ $roles->count() }} of {{ $roles->total() }} roles
        </div>
    </div>

    {{-- Roles Table --}}
    @if($roles->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'shield'])
            </div>
            <h3>No roles found</h3>
            <p>Create your first role to start managing access control.</p>
            <a href="{{ route('admin.roles.create') }}" class="btn-primary mt-4">
                Create Role
            </a>
        </div>
    @else
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Role</th>
                        <th style="width: 100px;">Level</th>
                        <th style="width: 100px;">Users</th>
                        <th style="width: 120px;">Permissions</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 80px;" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr class="role-row" data-role-id="{{ $role->id }}">
                            <td>
                                <div class="role-color-indicator" style="background-color: {{ $role->color }};">
                                    @include('backend.partials.icon', ['icon' => $role->icon])
                                </div>
                            </td>
                            <td>
                                <div class="role-info">
                                    <a href="{{ route('admin.roles.show', $role) }}" class="role-name-link">
                                        {{ $role->name }}
                                        @if($role->is_system)
                                            <span class="system-badge">System</span>
                                        @endif
                                        @if($role->is_default)
                                            <span class="default-badge">Default</span>
                                        @endif
                                    </a>
                                    @if($role->description)
                                        <p class="role-description">{{ Str::limit($role->description, 60) }}</p>
                                    @endif
                                    @if($role->parent)
                                        <span class="role-parent">
                                            @include('backend.partials.icon', ['icon' => 'cornerDownRight'])
                                            Inherits from {{ $role->parent->name }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="role-level">{{ $role->level }}</span>
                            </td>
                            <td>
                                <span class="user-count">{{ $role->users_count }}</span>
                            </td>
                            <td>
                                <span class="permission-count">{{ $role->grantedPermissions->count() }}</span>
                                @if($role->parent)
                                    <span class="inherited-count">
                                        (+{{ $role->getInheritedPermissions()->count() }} inherited)
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($role->is_active)
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="actions-dropdown">
                                    <button type="button" class="action-menu-btn" data-role="{{ $role->slug }}">
                                        @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                    </button>
                                    <div class="action-menu">
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="action-item">
                                            @include('backend.partials.icon', ['icon' => 'edit'])
                                            Edit
                                        </a>
                                        <a href="{{ route('admin.roles.show', $role) }}" class="action-item">
                                            @include('backend.partials.icon', ['icon' => 'eye'])
                                            View Details
                                        </a>
                                        <button type="button" class="action-item" onclick="duplicateRole({{ $role->id }})">
                                            @include('backend.partials.icon', ['icon' => 'copy'])
                                            Duplicate
                                        </button>
                                        <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="action-item">
                                            @include('backend.partials.icon', ['icon' => 'users'])
                                            Assign Users
                                        </a>
                                        <a href="{{ route('admin.roles.export', $role) }}" class="action-item">
                                            @include('backend.partials.icon', ['icon' => 'download'])
                                            Export
                                        </a>
                                        @if(!$role->is_system)
                                            <div class="action-divider"></div>
                                            <button type="button" class="action-item danger" onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')">
                                                @include('backend.partials.icon', ['icon' => 'trash'])
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($roles->hasPages())
            <div class="pagination-wrapper">
                {{ $roles->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Duplicate Modal --}}
<div id="duplicateModal" class="modal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Duplicate Role</h3>
            <button type="button" class="modal-close" onclick="closeDuplicateModal()">
                @include('backend.partials.icon', ['icon' => 'x'])
            </button>
        </div>
        <form id="duplicateForm" onsubmit="submitDuplicate(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label for="duplicateName">New Role Name</label>
                    <input type="text" id="duplicateName" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="duplicateSlug">Slug (optional)</label>
                    <input type="text" id="duplicateSlug" name="slug" class="form-input" placeholder="auto-generated">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeDuplicateModal()">Cancel</button>
                <button type="submit" class="btn-primary">Duplicate</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentDuplicateRoleId = null;

function duplicateRole(roleId) {
    currentDuplicateRoleId = roleId;
    document.getElementById('duplicateName').value = '';
    document.getElementById('duplicateSlug').value = '';
    document.getElementById('duplicateModal').style.display = 'flex';
}

function closeDuplicateModal() {
    document.getElementById('duplicateModal').style.display = 'none';
    currentDuplicateRoleId = null;
}

function submitDuplicate(event) {
    event.preventDefault();
    if (!currentDuplicateRoleId) return;

    const formData = new FormData(document.getElementById('duplicateForm'));

    Vodo.api.post(`{{ url('admin/system/roles') }}/${currentDuplicateRoleId}/duplicate`, {
        name: formData.get('name'),
        slug: formData.get('slug') || null
    }).then(response => {
        if (response.success) {
            Vodo.notification.success(response.message);
            if (response.redirect) {
                Vodo.pjax.load(response.redirect);
            } else {
                location.reload();
            }
        }
    }).catch(error => {
        Vodo.notification.error(error.message || 'Failed to duplicate role');
    });
}

function deleteRole(roleId, roleName) {
    Vodo.modal.confirm({
        title: 'Delete Role',
        message: `Are you sure you want to delete the role "${roleName}"? This action cannot be undone.`,
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        onConfirm: () => {
            Vodo.api.delete(`{{ url('admin/system/roles') }}/${roleId}`).then(response => {
                if (response.success) {
                    Vodo.notification.success(response.message);
                    location.reload();
                }
            }).catch(error => {
                Vodo.notification.error(error.message || 'Failed to delete role');
            });
        }
    });
}

// Search and filter handling
document.getElementById('roleSearch')?.addEventListener('input', debounce(function() {
    applyFilters();
}, 300));

document.getElementById('pluginFilter')?.addEventListener('change', applyFilters);
document.getElementById('statusFilter')?.addEventListener('change', applyFilters);

function applyFilters() {
    const params = new URLSearchParams();
    const search = document.getElementById('roleSearch')?.value;
    const plugin = document.getElementById('pluginFilter')?.value;
    const status = document.getElementById('statusFilter')?.value;

    if (search) params.set('search', search);
    if (plugin) params.set('plugin', plugin);
    if (status) params.set('active', status);

    const url = `{{ route('admin.roles.index') }}?${params.toString()}`;
    Vodo.pjax.load(url);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
</script>
@endsection
