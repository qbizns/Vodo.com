{{-- Role Details View (Screen 2 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Role: ' . $role->name)
@section('page-id', 'system/roles/show')
@section('require-css', 'permissions')

@section('header')
<div class="flex items-center gap-3">
    <div class="role-color-indicator lg" style="background-color: {{ $role->color }};">
        @include('backend.partials.icon', ['icon' => $role->icon ?? 'shield'])
    </div>
    <div>
        <span>{{ $role->name }}</span>
        @if($role->is_system)
            <span class="system-badge">System</span>
        @endif
        @if($role->is_default)
            <span class="default-badge">Default</span>
        @endif
    </div>
</div>
@endsection

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.index') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Roles</span>
    </a>
    <a href="{{ route('admin.roles.edit', $role) }}" class="btn-primary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'edit'])
        <span>Edit Role</span>
    </a>
</div>
@endsection

@section('content')
<div class="role-details-page">
    {{-- Role Summary Cards --}}
    <div class="stats-grid mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-blue-500">
                @include('backend.partials.icon', ['icon' => 'key'])
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $permissionStats['total'] }}</span>
                <span class="stat-label">Total Permissions</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-green-500">
                @include('backend.partials.icon', ['icon' => 'checkCircle'])
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $permissionStats['direct'] }}</span>
                <span class="stat-label">Direct Permissions</span>
            </div>
        </div>

        @if($role->parent)
            <div class="stat-card">
                <div class="stat-icon bg-purple-500">
                    @include('backend.partials.icon', ['icon' => 'cornerDownRight'])
                </div>
                <div class="stat-content">
                    <span class="stat-value">{{ $permissionStats['inherited'] }}</span>
                    <span class="stat-label">Inherited from {{ $role->parent->name }}</span>
                </div>
            </div>
        @endif

        <div class="stat-card">
            <div class="stat-icon bg-indigo-500">
                @include('backend.partials.icon', ['icon' => 'users'])
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $role->users_count }}</span>
                <span class="stat-label">Users with this Role</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2">
            {{-- Role Info --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3>Role Information</h3>
                </div>
                <div class="card-body">
                    <dl class="info-list">
                        <div class="info-item">
                            <dt>Name</dt>
                            <dd>{{ $role->name }}</dd>
                        </div>
                        <div class="info-item">
                            <dt>Slug</dt>
                            <dd><code>{{ $role->slug }}</code></dd>
                        </div>
                        @if($role->description)
                            <div class="info-item">
                                <dt>Description</dt>
                                <dd>{{ $role->description }}</dd>
                            </div>
                        @endif
                        <div class="info-item">
                            <dt>Access Level</dt>
                            <dd>{{ $role->level }}</dd>
                        </div>
                        <div class="info-item">
                            <dt>Status</dt>
                            <dd>
                                @if($role->is_active)
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </dd>
                        </div>
                        @if($role->parent)
                            <div class="info-item">
                                <dt>Inherits From</dt>
                                <dd>
                                    <a href="{{ route('admin.roles.show', $role->parent) }}" class="link">
                                        {{ $role->parent->name }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                        @if($role->plugin)
                            <div class="info-item">
                                <dt>Plugin</dt>
                                <dd>{{ $role->plugin }}</dd>
                            </div>
                        @endif
                        <div class="info-item">
                            <dt>Created</dt>
                            <dd>{{ $role->created_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                        <div class="info-item">
                            <dt>Last Updated</dt>
                            <dd>{{ $role->updated_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Permissions List --}}
            <div class="card">
                <div class="card-header">
                    <h3>Permissions ({{ $permissionStats['total'] }})</h3>
                    <div class="card-header-actions">
                        <input type="text"
                               class="search-input sm"
                               placeholder="Search permissions..."
                               id="permissionSearch">
                    </div>
                </div>
                <div class="card-body p-0">
                    @foreach($groupedPermissions as $groupSlug => $group)
                        <div class="permission-group-view" data-group="{{ $groupSlug }}">
                            <div class="permission-group-header">
                                <span class="group-name">
                                    @include('backend.partials.icon', ['icon' => 'folder'])
                                    {{ $group['name'] }}
                                </span>
                                <span class="group-count">{{ count($group['permissions']) }}</span>
                            </div>
                            <div class="permission-group-items">
                                @foreach($group['permissions'] as $permission)
                                    <div class="permission-view-item"
                                         data-permission="{{ $permission['slug'] }}">
                                        <span class="permission-name">
                                            {{ $permission['label'] ?? $permission['slug'] }}
                                            @if($permission['is_dangerous'] ?? false)
                                                <span class="badge badge-danger" title="Dangerous">!</span>
                                            @endif
                                        </span>
                                        <span class="permission-source">
                                            @if($permission['inherited'] ?? false)
                                                <span class="source-inherited">
                                                    @include('backend.partials.icon', ['icon' => 'cornerDownRight'])
                                                    Inherited
                                                </span>
                                            @else
                                                <span class="source-direct">
                                                    @include('backend.partials.icon', ['icon' => 'check'])
                                                    Direct
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if(empty($groupedPermissions))
                        <div class="empty-state">
                            <p>No permissions assigned to this role.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div>
            {{-- Quick Actions --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="action-list">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="action-list-item">
                            @include('backend.partials.icon', ['icon' => 'edit'])
                            Edit Role
                        </a>
                        <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="action-list-item">
                            @include('backend.partials.icon', ['icon' => 'users'])
                            Assign Users
                        </a>
                        <button type="button" class="action-list-item" onclick="duplicateRole()">
                            @include('backend.partials.icon', ['icon' => 'copy'])
                            Duplicate Role
                        </button>
                        <a href="{{ route('admin.roles.export', $role) }}" class="action-list-item">
                            @include('backend.partials.icon', ['icon' => 'download'])
                            Export Role
                        </a>
                        <a href="{{ route('admin.roles.compare') }}?roles[]={{ $role->id }}" class="action-list-item">
                            @include('backend.partials.icon', ['icon' => 'gitCompare'])
                            Compare with Others
                        </a>
                        @if(!$role->is_system)
                            <button type="button" class="action-list-item danger" onclick="deleteRole()">
                                @include('backend.partials.icon', ['icon' => 'trash'])
                                Delete Role
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Users with this Role --}}
            <div class="card">
                <div class="card-header">
                    <h3>Users ({{ $role->users_count }})</h3>
                    @if($role->users_count > 5)
                        <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="btn-link">View All</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($users->isEmpty())
                        <div class="empty-state sm">
                            <p>No users assigned to this role.</p>
                            <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="btn-link mt-2">
                                Assign Users
                            </a>
                        </div>
                    @else
                        <ul class="user-list">
                            @foreach($users as $user)
                                <li class="user-list-item">
                                    <div class="user-avatar">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name">{{ $user->name }}</span>
                                        <span class="user-email">{{ $user->email }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
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
// Permission search
document.getElementById('permissionSearch')?.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const items = document.querySelectorAll('.permission-view-item');

    items.forEach(item => {
        const name = item.dataset.permission.toLowerCase();
        const text = item.textContent.toLowerCase();
        item.style.display = (name.includes(query) || text.includes(query)) ? '' : 'none';
    });

    // Hide empty groups
    document.querySelectorAll('.permission-group-view').forEach(group => {
        const visibleItems = group.querySelectorAll('.permission-view-item[style=""]');
        group.style.display = visibleItems.length > 0 || !query ? '' : 'none';
    });
});

function duplicateRole() {
    document.getElementById('duplicateName').value = '{{ $role->name }} (Copy)';
    document.getElementById('duplicateSlug').value = '';
    document.getElementById('duplicateModal').style.display = 'flex';
}

function closeDuplicateModal() {
    document.getElementById('duplicateModal').style.display = 'none';
}

function submitDuplicate(event) {
    event.preventDefault();

    const formData = new FormData(document.getElementById('duplicateForm'));

    Vodo.api.post('{{ route('admin.roles.duplicate', $role) }}', {
        name: formData.get('name'),
        slug: formData.get('slug') || null
    }).then(response => {
        if (response.success) {
            Vodo.notification.success(response.message);
            closeDuplicateModal();
            if (response.redirect) {
                Vodo.pjax.load(response.redirect);
            }
        }
    }).catch(error => {
        Vodo.notification.error(error.message || 'Failed to duplicate role');
    });
}

function deleteRole() {
    Vodo.modal.confirm({
        title: 'Delete Role',
        message: 'Are you sure you want to delete the role "{{ $role->name }}"? This action cannot be undone.',
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        onConfirm: () => {
            Vodo.api.delete('{{ route('admin.roles.destroy', $role) }}').then(response => {
                if (response.success) {
                    Vodo.notification.success(response.message);
                    Vodo.pjax.load('{{ route('admin.roles.index') }}');
                }
            }).catch(error => {
                Vodo.notification.error(error.message || 'Failed to delete role');
            });
        }
    });
}
</script>
@endsection
