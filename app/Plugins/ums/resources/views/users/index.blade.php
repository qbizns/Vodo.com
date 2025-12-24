@extends('backend.layouts.pjax')

@section('title', 'Users')
@section('page-id', 'ums/users')
@section('require-css', 'ums')

@section('header', 'Users')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('plugins.ums.users.create') }}" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'userPlus'])
        <span>Add User</span>
    </a>
</div>
@endsection

@section('content')
<div class="users-page">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="filters-form">
                <div class="filters-row">
                    <div class="filter-group">
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                               placeholder="Search users..." class="form-input">
                    </div>
                    <div class="filter-group">
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" {{ ($filters['role'] ?? '') === $role->slug ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" {{ ($filters['status'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ ($filters['status'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary">
                        @include('backend.partials.icon', ['icon' => 'search'])
                        <span>Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar">
                                    @if($user->avatar)
                                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}">
                                    @else
                                        <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    @endif
                                </div>
                                <div class="user-info">
                                    <div class="user-name">{{ $user->name }}</div>
                                    <div class="user-email">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="roles-list">
                                @foreach($user->roles as $role)
                                    <span class="badge" style="background: {{ $role->color }}20; color: {{ $role->color }}">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="status-badge status-badge--success">Active</span>
                            @else
                                <span class="status-badge status-badge--danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-secondary">{{ $user->created_at->format('M d, Y') }}</span>
                        </td>
                        <td class="text-right">
                            <div class="actions-dropdown">
                                <button type="button" class="btn-icon" data-dropdown-trigger>
                                    @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                </button>
                                <div class="dropdown-menu">
                                    <a href="{{ route('plugins.ums.users.edit', $user) }}" class="dropdown-item">
                                        @include('backend.partials.icon', ['icon' => 'edit'])
                                        <span>Edit</span>
                                    </a>
                                    <button type="button" class="dropdown-item" onclick="toggleUserStatus({{ $user->id }})">
                                        @include('backend.partials.icon', ['icon' => $user->is_active ? 'userX' : 'userCheck'])
                                        <span>{{ $user->is_active ? 'Deactivate' : 'Activate' }}</span>
                                    </button>
                                    <hr class="dropdown-divider">
                                    <button type="button" class="dropdown-item dropdown-item--danger" onclick="deleteUser({{ $user->id }})">
                                        @include('backend.partials.icon', ['icon' => 'trash'])
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    @include('backend.partials.icon', ['icon' => 'users'])
                                </div>
                                <h3>No Users Found</h3>
                                <p>No users match your criteria.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="card-footer">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function toggleUserStatus(userId) {
    Vodo.modal.confirm({
        title: 'Toggle User Status',
        message: 'Are you sure you want to change this user\'s status?',
        confirmText: 'Yes, Toggle',
        onConfirm: () => {
            Vodo.api.post(`/admin/ums/users/${userId}/toggle-status`)
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

function deleteUser(userId) {
    Vodo.modal.confirm({
        title: 'Delete User',
        message: 'Are you sure you want to delete this user? This action cannot be undone.',
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        onConfirm: () => {
            Vodo.api.delete(`/admin/ums/users/${userId}`)
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
@endsection

