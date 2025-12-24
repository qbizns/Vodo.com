{{-- User Permissions (Screen 4 - Permissions & Access Control) --}}
{{-- Uses vanilla JS, no Alpine --}}

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
@php
$permConfig = [
    'effectiveCount' => $permissionData['effectiveCount'] ?? 0,
    'fromRolesCount' => $permissionData['fromRolesCount'] ?? 0,
    'grantedOverrides' => $permissionData['grantedOverrides'] ?? 0,
    'deniedOverrides' => $permissionData['deniedOverrides'] ?? 0
];
@endphp
<div class="user-permissions-page" 
     data-component="user-permissions"
     data-config="{{ json_encode($permConfig) }}"
     data-update-url="{{ route('admin.permissions.users.update', $user) }}"
     data-clear-url="{{ route('admin.permissions.users.clear-override', $user) }}">
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
                        <strong data-stat="effective">{{ $permConfig['effectiveCount'] }}</strong> effective permissions
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Assigned Roles --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3>Assigned Roles</h3>
            <button type="button" class="btn-secondary btn-sm" data-action="show-add-role">
                @include('backend.partials.icon', ['icon' => 'plus'])
                Add Role
            </button>
        </div>
        <div class="card-body">
            <div class="roles-grid">
                @forelse($userRoles as $userRole)
                    <div class="role-assignment-card" data-role-id="{{ $userRole->id }}">
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
                                data-action="remove-role"
                                data-role-id="{{ $userRole->id }}">
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
                    <button type="button" class="btn-link danger" data-action="clear-overrides">
                        Clear All
                    </button>
                @endif
                <button type="button" class="btn-secondary btn-sm" data-action="show-add-override">
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
                            <tr data-permission="{{ $override['permission'] }}">
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
                                            data-action="remove-override"
                                            data-permission="{{ $override['permission'] }}">
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
                            data-action="show-add-override">
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
                        data-action="expand-all">
                    <span class="expand-text">Expand All</span>
                    <span class="collapse-text" style="display: none;">Collapse All</span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="permission-summary mb-4">
                Total: <strong data-stat="effective">{{ $permConfig['effectiveCount'] }}</strong> permissions
                (<span data-stat="from-roles">{{ $permConfig['fromRolesCount'] }}</span> from roles
                @if($permConfig['grantedOverrides'] > 0)
                    + <span data-stat="granted">{{ $permConfig['grantedOverrides'] }}</span> granted
                @endif
                @if($permConfig['deniedOverrides'] > 0)
                    - <span data-stat="denied">{{ $permConfig['deniedOverrides'] }}</span> denied
                @endif
                )
            </div>

            <div class="search-input-wrapper mb-4">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text"
                       class="search-input"
                       placeholder="Search permissions..."
                       data-role="search">
            </div>

            <div class="effective-permissions">
                @foreach($groupedEffective as $groupSlug => $group)
                    <div class="permission-group-view" data-group="{{ $groupSlug }}">
                        <div class="permission-group-header" data-group="{{ $groupSlug }}">
                            @include('backend.partials.icon', ['icon' => 'folder'])
                            <span class="group-name">{{ $group['name'] }}</span>
                            <span class="group-count">
                                {{ $group['allowed'] }}/{{ count($group['permissions']) }} allowed
                            </span>
                            <span class="group-chevron">
                                @include('backend.partials.icon', ['icon' => 'chevronDown'])
                            </span>
                        </div>
                        <div class="permission-group-items" style="display: none;">
                            @foreach($group['permissions'] as $perm)
                                <div class="effective-permission-item {{ $perm['allowed'] ? 'allowed' : 'denied' }}"
                                     data-slug="{{ $perm['slug'] }}">
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
    <div class="modal" id="addRoleModal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Role</h3>
                <button type="button" class="modal-close">
                    @include('backend.partials.icon', ['icon' => 'x'])
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Role</label>
                    <select class="form-select" id="newRoleId">
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
                    <input type="date" class="form-input" id="newRoleExpiry">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary modal-close">
                    Cancel
                </button>
                <button type="button"
                        class="btn-primary"
                        data-action="add-role"
                        disabled>
                    Add Role
                </button>
            </div>
        </div>
    </div>

    {{-- Add Override Modal --}}
    <div class="modal" id="addOverrideModal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Permission Override</h3>
                <button type="button" class="modal-close">
                    @include('backend.partials.icon', ['icon' => 'x'])
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Permission</label>
                    <select class="form-select" id="newOverridePermission">
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
                            <input type="radio" name="override_action" value="grant" checked>
                            <span>Grant (allow)</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="override_action" value="deny">
                            <span>Deny (revoke)</span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Expiration (optional)</label>
                    <input type="date" class="form-input" id="newOverrideExpiry">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary modal-close">
                    Cancel
                </button>
                <button type="button"
                        class="btn-primary"
                        data-action="add-override"
                        disabled>
                    Add Override
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function initUserPermissions() {
        var container = document.querySelector('.user-permissions-page[data-component="user-permissions"]');
        if (!container || container.dataset.initialized) return;
        
        var expandedGroups = {};
        var expandAllGroups = false;
        var updateUrl = container.dataset.updateUrl;
        var clearUrl = container.dataset.clearUrl;
        
        // Group toggles
        container.addEventListener('click', function(e) {
            var header = e.target.closest('.permission-group-header');
            if (header) {
                var slug = header.dataset.group;
                expandedGroups[slug] = !expandedGroups[slug];
                updateGroupVisibility(slug);
            }
        });
        
        // Expand/collapse all
        container.querySelector('[data-action="expand-all"]')?.addEventListener('click', function() {
            expandAllGroups = !expandAllGroups;
            var btn = this;
            btn.querySelector('.expand-text').style.display = expandAllGroups ? 'none' : '';
            btn.querySelector('.collapse-text').style.display = expandAllGroups ? '' : 'none';
            
            container.querySelectorAll('.permission-group-view').forEach(function(group) {
                var slug = group.dataset.group;
                expandedGroups[slug] = expandAllGroups;
                updateGroupVisibility(slug);
            });
        });
        
        // Search
        var searchInput = container.querySelector('[data-role="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var query = searchInput.value.toLowerCase();
                
                container.querySelectorAll('.effective-permission-item').forEach(function(item) {
                    var slug = item.dataset.slug?.toLowerCase() || '';
                    item.style.display = !query || slug.includes(query) ? '' : 'none';
                });
            });
        }
        
        // Show add role modal
        container.querySelectorAll('[data-action="show-add-role"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('addRoleModal').style.display = 'flex';
            });
        });
        
        // Show add override modal
        container.querySelectorAll('[data-action="show-add-override"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('addOverrideModal').style.display = 'flex';
            });
        });
        
        // Close modals
        container.querySelectorAll('.modal-close, .modal-backdrop').forEach(function(el) {
            el.addEventListener('click', function() {
                container.querySelectorAll('.modal').forEach(function(m) {
                    m.style.display = 'none';
                });
            });
        });
        
        // Enable/disable add role button
        var newRoleSelect = container.querySelector('#newRoleId');
        var addRoleBtn = container.querySelector('[data-action="add-role"]');
        if (newRoleSelect && addRoleBtn) {
            newRoleSelect.addEventListener('change', function() {
                addRoleBtn.disabled = !newRoleSelect.value;
            });
        }
        
        // Enable/disable add override button
        var newOverrideSelect = container.querySelector('#newOverridePermission');
        var addOverrideBtn = container.querySelector('[data-action="add-override"]');
        if (newOverrideSelect && addOverrideBtn) {
            newOverrideSelect.addEventListener('change', function() {
                addOverrideBtn.disabled = !newOverrideSelect.value;
            });
        }
        
        // Add role
        if (addRoleBtn) {
            addRoleBtn.addEventListener('click', function() {
                var roleId = newRoleSelect.value;
                var expiry = container.querySelector('#newRoleExpiry')?.value || null;
                
                if (!roleId) return;
                
                Vodo.api.post(updateUrl, {
                    action: 'add_role',
                    role_id: roleId,
                    expires_at: expiry
                }).then(function(response) {
                    if (response.success) {
                        Vodo.notifications.success(response.message || 'Role added');
                        location.reload();
                    }
                }).catch(function(error) {
                    Vodo.notifications.error(error.message || 'Failed to add role');
                });
            });
        }
        
        // Remove role
        container.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action="remove-role"]');
            if (btn) {
                if (!confirm('Are you sure you want to remove this role from the user?')) return;
                
                var roleId = btn.dataset.roleId;
                
                Vodo.api.post(updateUrl, {
                    action: 'remove_role',
                    role_id: roleId
                }).then(function(response) {
                    if (response.success) {
                        Vodo.notifications.success(response.message || 'Role removed');
                        location.reload();
                    }
                }).catch(function(error) {
                    Vodo.notifications.error(error.message || 'Failed to remove role');
                });
            }
        });
        
        // Add override
        if (addOverrideBtn) {
            addOverrideBtn.addEventListener('click', function() {
                var permission = newOverrideSelect.value;
                var action = container.querySelector('[name="override_action"]:checked')?.value || 'grant';
                var expiry = container.querySelector('#newOverrideExpiry')?.value || null;
                
                if (!permission) return;
                
                Vodo.api.post(updateUrl, {
                    action: 'add_override',
                    permission: permission,
                    granted: action === 'grant',
                    expires_at: expiry
                }).then(function(response) {
                    if (response.success) {
                        Vodo.notifications.success(response.message || 'Override added');
                        location.reload();
                    }
                }).catch(function(error) {
                    Vodo.notifications.error(error.message || 'Failed to add override');
                });
            });
        }
        
        // Remove override
        container.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action="remove-override"]');
            if (btn) {
                var permission = btn.dataset.permission;
                
                Vodo.api.post(updateUrl, {
                    action: 'remove_override',
                    permission: permission
                }).then(function(response) {
                    if (response.success) {
                        Vodo.notifications.success(response.message || 'Override removed');
                        location.reload();
                    }
                }).catch(function(error) {
                    Vodo.notifications.error(error.message || 'Failed to remove override');
                });
            }
        });
        
        // Clear all overrides
        container.querySelector('[data-action="clear-overrides"]')?.addEventListener('click', function() {
            if (!confirm('Are you sure you want to clear all permission overrides for this user?')) return;
            
            Vodo.api.delete(clearUrl).then(function(response) {
                if (response.success) {
                    Vodo.notifications.success(response.message || 'Overrides cleared');
                    location.reload();
                }
            }).catch(function(error) {
                Vodo.notifications.error(error.message || 'Failed to clear overrides');
            });
        });
        
        function updateGroupVisibility(slug) {
            var group = container.querySelector('.permission-group-view[data-group="' + slug + '"]');
            if (group) {
                var content = group.querySelector('.permission-group-items');
                var chevron = group.querySelector('.group-chevron');
                var isExpanded = expandedGroups[slug] || expandAllGroups;
                
                if (content) content.style.display = isExpanded ? '' : 'none';
                if (chevron) chevron.classList.toggle('expanded', isExpanded);
            }
        }
        
        container.dataset.initialized = 'true';
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initUserPermissions, 0);
    } else {
        document.addEventListener('DOMContentLoaded', initUserPermissions);
    }
    document.addEventListener('pjax:complete', initUserPermissions);
})();
</script>
@endsection
