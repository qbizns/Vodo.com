{{-- User Permissions (Screen 4 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Permissions for ' . $user->name)
@section('page-id', 'system/permissions/users')
@section('require-css', 'permissions')

@section('header')
<div class="flex items-center gap-3">
    <div class="user-avatar lg">
        {{ strtoupper(substr($user->name, 0, 1)) }}
    </div>
    <div>
        <span>{{ $user->name }}</span>
        <p class="text-muted text-sm">{{ $user->email }}</p>
    </div>
</div>
@endsection

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ url()->previous() }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back</span>
    </a>
    <button type="button"
            class="btn-primary flex items-center gap-2"
            id="saveBtn"
            style="display: none;">
        @include('backend.partials.icon', ['icon' => 'save'])
        <span>Save Changes</span>
    </button>
</div>
@endsection

@section('content')
<div class="user-permissions-page" x-data="userPermissions(@json($permissionData))">
    {{-- User Info --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="user-status-info">
                    <span class="status-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($user->last_login_at)
                        <span class="text-muted">
                            Last login: {{ $user->last_login_at->diffForHumans() }}
                        </span>
                    @endif
                </div>
                <div class="permission-stats">
                    <span class="stat">
                        <strong x-text="effectiveCount"></strong> effective permissions
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Assigned Roles --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3>Assigned Roles</h3>
            <button type="button" class="btn-secondary btn-sm" @click="showAddRoleModal = true">
                @include('backend.partials.icon', ['icon' => 'plus'])
                Add Role
            </button>
        </div>
        <div class="card-body">
            <div class="roles-grid">
                @forelse($userRoles as $userRole)
                    <div class="role-assignment-card">
                        <div class="role-info">
                            <div class="role-color" style="background-color: {{ $userRole->color }};"></div>
                            <div>
                                <span class="role-name">{{ $userRole->name }}</span>
                                <span class="role-perms">{{ $userRole->permissions_count }} permissions</span>
                            </div>
                        </div>
                        @if($userRole->pivot->expires_at)
                            <div class="role-expiry">
                                Expires: {{ \Carbon\Carbon::parse($userRole->pivot->expires_at)->format('M d, Y') }}
                            </div>
                        @endif
                        <button type="button"
                                class="btn-link btn-sm danger"
                                @click="removeRole({{ $userRole->id }})">
                            Remove
                        </button>
                    </div>
                @empty
                    <div class="empty-state sm">
                        <p>No roles assigned to this user.</p>
                    </div>
                @endforelse
            </div>
            <p class="text-muted mt-4">
                Effective permissions from roles: {{ $fromRolesCount }} (after merging)
            </p>
        </div>
    </div>

    {{-- Permission Overrides --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3>Permission Overrides</h3>
            <div class="card-header-actions">
                @if(count($overrides) > 0)
                    <button type="button" class="btn-link danger" @click="clearAllOverrides">
                        Clear All
                    </button>
                @endif
                <button type="button" class="btn-secondary btn-sm" @click="showAddOverrideModal = true">
                    @include('backend.partials.icon', ['icon' => 'plus'])
                    Add Override
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(count($overrides) > 0)
                <div class="alert alert-warning mb-4">
                    <div class="alert-icon">
                        @include('backend.partials.icon', ['icon' => 'alertTriangle'])
                    </div>
                    <div class="alert-content">
                        {{ count($overrides) }} individual permission override(s) are set for this user
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th>From Roles</th>
                            <th>Override</th>
                            <th>Effective</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overrides as $override)
                            <tr>
                                <td>
                                    <code>{{ $override['permission'] }}</code>
                                    @if($override['expires_at'])
                                        <span class="badge badge-info">
                                            Expires: {{ \Carbon\Carbon::parse($override['expires_at'])->format('M d, Y') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($override['from_roles'])
                                        <span class="status-badge status-active">Allowed</span>
                                    @else
                                        <span class="status-badge status-inactive">Denied</span>
                                    @endif
                                </td>
                                <td>
                                    @if($override['granted'])
                                        <span class="status-badge status-active">Grant</span>
                                    @else
                                        <span class="status-badge status-danger">Deny</span>
                                    @endif
                                </td>
                                <td>
                                    @if($override['effective'])
                                        <span class="text-green-600">
                                            @include('backend.partials.icon', ['icon' => 'check'])
                                            Allowed
                                        </span>
                                    @else
                                        <span class="text-red-600">
                                            @include('backend.partials.icon', ['icon' => 'x'])
                                            Denied
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn-link danger"
                                            @click="removeOverride('{{ $override['permission'] }}')">
                                        @include('backend.partials.icon', ['icon' => 'x'])
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state sm">
                    <p>No permission overrides set for this user.</p>
                    <button type="button"
                            class="btn-link mt-2"
                            @click="showAddOverrideModal = true">
                        Add an override
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Effective Permissions --}}
    <div class="card">
        <div class="card-header">
            <h3>Effective Permissions</h3>
            <div class="card-header-actions">
                <button type="button"
                        class="btn-secondary btn-sm"
                        @click="expandAllGroups = !expandAllGroups">
                    <span x-text="expandAllGroups ? 'Collapse All' : 'Expand All'"></span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="permission-summary mb-4">
                Total: <strong x-text="effectiveCount"></strong> permissions
                (<span x-text="fromRolesCount"></span> from roles
                <span x-show="grantedOverrides > 0">+ <span x-text="grantedOverrides"></span> granted</span>
                <span x-show="deniedOverrides > 0">- <span x-text="deniedOverrides"></span> denied</span>)
            </div>

            <div class="search-input-wrapper mb-4">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text"
                       class="search-input"
                       placeholder="Search permissions..."
                       x-model="searchQuery">
            </div>

            <div class="effective-permissions">
                @foreach($groupedEffective as $groupSlug => $group)
                    <div class="permission-group-view"
                         x-show="isGroupVisible('{{ $groupSlug }}')">
                        <div class="permission-group-header"
                             @click="toggleGroup('{{ $groupSlug }}')">
                            @include('backend.partials.icon', ['icon' => 'folder'])
                            <span class="group-name">{{ $group['name'] }}</span>
                            <span class="group-count">
                                {{ $group['allowed'] }}/{{ count($group['permissions']) }} allowed
                            </span>
                            <span class="group-chevron"
                                  :class="{ 'expanded': expandedGroups['{{ $groupSlug }}'] || expandAllGroups }">
                                @include('backend.partials.icon', ['icon' => 'chevronDown'])
                            </span>
                        </div>
                        <div class="permission-group-items"
                             x-show="expandedGroups['{{ $groupSlug }}'] || expandAllGroups">
                            @foreach($group['permissions'] as $perm)
                                <div class="effective-permission-item {{ $perm['allowed'] ? 'allowed' : 'denied' }}"
                                     x-show="isPermissionVisible('{{ $perm['slug'] }}')">
                                    <span class="permission-status">
                                        @if($perm['allowed'])
                                            @include('backend.partials.icon', ['icon' => 'check'])
                                        @else
                                            @include('backend.partials.icon', ['icon' => 'x'])
                                        @endif
                                    </span>
                                    <span class="permission-name">{{ $perm['label'] ?? $perm['slug'] }}</span>
                                    <span class="permission-source">
                                        @if($perm['source'] === 'override')
                                            <span class="source-override">
                                                Override {{ $perm['allowed'] ? 'Granted' : 'Denied' }}
                                            </span>
                                        @elseif($perm['source'])
                                            From: {{ $perm['source'] }}
                                        @else
                                            Not assigned
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Add Role Modal --}}
    <div class="modal" x-show="showAddRoleModal" style="display: none;" x-cloak>
        <div class="modal-backdrop" @click="showAddRoleModal = false"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Role</h3>
                <button type="button" class="modal-close" @click="showAddRoleModal = false">
                    @include('backend.partials.icon', ['icon' => 'x'])
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Role</label>
                    <select class="form-select" x-model="newRoleId">
                        <option value="">-- Select a role --</option>
                        @foreach($availableRoles as $role)
                            <option value="{{ $role->id }}">
                                {{ $role->name }} ({{ $role->permissions_count }} permissions)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Expiration (optional)</label>
                    <input type="date" class="form-input" x-model="newRoleExpiry">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" @click="showAddRoleModal = false">
                    Cancel
                </button>
                <button type="button"
                        class="btn-primary"
                        @click="addRole"
                        :disabled="!newRoleId">
                    Add Role
                </button>
            </div>
        </div>
    </div>

    {{-- Add Override Modal --}}
    <div class="modal" x-show="showAddOverrideModal" style="display: none;" x-cloak>
        <div class="modal-backdrop" @click="showAddOverrideModal = false"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Permission Override</h3>
                <button type="button" class="modal-close" @click="showAddOverrideModal = false">
                    @include('backend.partials.icon', ['icon' => 'x'])
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Permission</label>
                    <select class="form-select" x-model="newOverridePermission">
                        <option value="">-- Select permission --</option>
                        @foreach($allPermissions as $perm)
                            <option value="{{ $perm->slug }}">{{ $perm->label ?? $perm->slug }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Action</label>
                    <div class="flex gap-4">
                        <label class="radio-label">
                            <input type="radio" x-model="newOverrideAction" value="grant">
                            <span>Grant (allow)</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" x-model="newOverrideAction" value="deny">
                            <span>Deny (revoke)</span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Expiration (optional)</label>
                    <input type="date" class="form-input" x-model="newOverrideExpiry">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" @click="showAddOverrideModal = false">
                    Cancel
                </button>
                <button type="button"
                        class="btn-primary"
                        @click="addOverride"
                        :disabled="!newOverridePermission || !newOverrideAction">
                    Add Override
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function userPermissions(data) {
    return {
        effectiveCount: data.effectiveCount || 0,
        fromRolesCount: data.fromRolesCount || 0,
        grantedOverrides: data.grantedOverrides || 0,
        deniedOverrides: data.deniedOverrides || 0,

        // UI State
        showAddRoleModal: false,
        showAddOverrideModal: false,
        expandedGroups: {},
        expandAllGroups: false,
        searchQuery: '',

        // New role form
        newRoleId: '',
        newRoleExpiry: '',

        // New override form
        newOverridePermission: '',
        newOverrideAction: 'grant',
        newOverrideExpiry: '',

        toggleGroup(slug) {
            this.expandedGroups[slug] = !this.expandedGroups[slug];
        },

        isGroupVisible(slug) {
            if (!this.searchQuery) return true;
            // Would need to check if any permissions in group match
            return true;
        },

        isPermissionVisible(slug) {
            if (!this.searchQuery) return true;
            return slug.toLowerCase().includes(this.searchQuery.toLowerCase());
        },

        async addRole() {
            if (!this.newRoleId) return;

            try {
                const response = await Vodo.api.post('{{ route('admin.permissions.users.update', $user) }}', {
                    action: 'add_role',
                    role_id: this.newRoleId,
                    expires_at: this.newRoleExpiry || null
                });

                if (response.success) {
                    Vodo.notification.success(response.message || 'Role added');
                    this.showAddRoleModal = false;
                    location.reload();
                }
            } catch (error) {
                Vodo.notification.error(error.message || 'Failed to add role');
            }
        },

        async removeRole(roleId) {
            Vodo.modal.confirm({
                title: 'Remove Role',
                message: 'Are you sure you want to remove this role from the user?',
                confirmText: 'Remove',
                confirmClass: 'btn-danger',
                onConfirm: async () => {
                    try {
                        const response = await Vodo.api.post('{{ route('admin.permissions.users.update', $user) }}', {
                            action: 'remove_role',
                            role_id: roleId
                        });

                        if (response.success) {
                            Vodo.notification.success(response.message || 'Role removed');
                            location.reload();
                        }
                    } catch (error) {
                        Vodo.notification.error(error.message || 'Failed to remove role');
                    }
                }
            });
        },

        async addOverride() {
            if (!this.newOverridePermission || !this.newOverrideAction) return;

            try {
                const response = await Vodo.api.post('{{ route('admin.permissions.users.update', $user) }}', {
                    action: 'add_override',
                    permission: this.newOverridePermission,
                    granted: this.newOverrideAction === 'grant',
                    expires_at: this.newOverrideExpiry || null
                });

                if (response.success) {
                    Vodo.notification.success(response.message || 'Override added');
                    this.showAddOverrideModal = false;
                    location.reload();
                }
            } catch (error) {
                Vodo.notification.error(error.message || 'Failed to add override');
            }
        },

        async removeOverride(permission) {
            try {
                const response = await Vodo.api.post('{{ route('admin.permissions.users.update', $user) }}', {
                    action: 'remove_override',
                    permission: permission
                });

                if (response.success) {
                    Vodo.notification.success(response.message || 'Override removed');
                    location.reload();
                }
            } catch (error) {
                Vodo.notification.error(error.message || 'Failed to remove override');
            }
        },

        async clearAllOverrides() {
            Vodo.modal.confirm({
                title: 'Clear All Overrides',
                message: 'Are you sure you want to clear all permission overrides for this user?',
                confirmText: 'Clear All',
                confirmClass: 'btn-danger',
                onConfirm: async () => {
                    try {
                        const response = await Vodo.api.delete('{{ route('admin.permissions.users.clear-override', $user) }}');

                        if (response.success) {
                            Vodo.notification.success(response.message || 'Overrides cleared');
                            location.reload();
                        }
                    } catch (error) {
                        Vodo.notification.error(error.message || 'Failed to clear overrides');
                    }
                }
            });
        }
    };
}
</script>
@endsection
