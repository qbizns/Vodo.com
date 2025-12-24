@extends('backend.layouts.pjax')

@section('title', 'Roles')
@section('page-id', 'ums/roles')
@section('require-css', 'ums')

@section('header', 'Roles')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('plugins.ums.roles.create') }}" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>Add Role</span>
    </a>
</div>
@endsection

@section('content')
<div class="roles-page">
    <div class="card">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Level</th>
                        <th>Permissions</th>
                        <th>Users</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td>
                            <div class="role-cell">
                                <div class="role-icon" style="background: {{ $role->color }}20; color: {{ $role->color }}">
                                    @include('backend.partials.icon', ['icon' => $role->icon ?? 'shield'])
                                </div>
                                <div class="role-info">
                                    <div class="role-name">{{ $role->name }}</div>
                                    <div class="role-slug">{{ $role->slug }}</div>
                                </div>
                                @if($role->is_system)
                                    <span class="badge badge--info">System</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="level-badge">{{ $role->level }}</span>
                        </td>
                        <td>
                            <span class="text-secondary">{{ $role->permissions_count }} permissions</span>
                        </td>
                        <td>
                            <span class="text-secondary">{{ $role->users_count }} users</span>
                        </td>
                        <td class="text-right">
                            <div class="actions-dropdown">
                                <button type="button" class="btn-icon" data-dropdown-trigger>
                                    @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                </button>
                                <div class="dropdown-menu">
                                    <a href="{{ route('plugins.ums.roles.edit', $role) }}" class="dropdown-item">
                                        @include('backend.partials.icon', ['icon' => 'edit'])
                                        <span>Edit</span>
                                    </a>
                                    <a href="{{ route('plugins.ums.roles.show', $role) }}" class="dropdown-item">
                                        @include('backend.partials.icon', ['icon' => 'eye'])
                                        <span>View Details</span>
                                    </a>
                                    @if(!$role->is_system)
                                    <hr class="dropdown-divider">
                                    <button type="button" class="dropdown-item dropdown-item--danger" onclick="deleteRole({{ $role->id }})">
                                        @include('backend.partials.icon', ['icon' => 'trash'])
                                        <span>Delete</span>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    @include('backend.partials.icon', ['icon' => 'shield'])
                                </div>
                                <h3>No Roles Found</h3>
                                <p>Create your first role to get started.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($roles->hasPages())
        <div class="card-footer">
            {{ $roles->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function deleteRole(roleId) {
    Vodo.modal.confirm({
        title: 'Delete Role',
        message: 'Are you sure you want to delete this role? This action cannot be undone.',
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        onConfirm: () => {
            Vodo.api.delete(`/admin/ums/roles/${roleId}`)
                .then(response => {
                    if (response.success) {
                        Vodo.notification.success(response.message);
                        Vodo.pjax.reload();
                    }
                })
                .catch(error => {
                    Vodo.notification.error(error.message);
                });
        }
    });
}
</script>

<style>
.role-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.role-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.role-icon svg {
    width: 20px;
    height: 20px;
}

.role-name {
    font-weight: 500;
    color: var(--text-primary, #1f2937);
}

.role-slug {
    font-size: 0.75rem;
    color: var(--text-tertiary, #9ca3af);
    font-family: monospace;
}

.level-badge {
    display: inline-block;
    padding: 2px 8px;
    background: var(--bg-surface-2, #f3f4f6);
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary, #6b7280);
}

.badge--info {
    background: #3b82f620;
    color: #3b82f6;
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 4px;
}
</style>
@endsection

