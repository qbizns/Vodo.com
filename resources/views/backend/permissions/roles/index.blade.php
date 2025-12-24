{{-- Roles List (Screen 1 - Permissions & Access Control) --}}
{{-- Matching SCREENS.md Wireframe --}}

@extends('backend.layouts.pjax')

@section('title', 'Roles & Permissions')
@section('page-id', 'system/roles')
@section('require-css', 'permissions')

@section('header', 'Roles & Permissions')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.compare') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'gitCompare'])
        <span>Compare Roles</span>
    </a>
    <a href="{{ route('admin.roles.create') }}" class="btn-primary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>Create Role</span>
    </a>
</div>
@endsection

@section('content')
<div class="roles-page">
    {{-- Tabs Navigation --}}
    <div class="tabs-nav" style="display: flex; gap: 4px; margin-bottom: 24px; border-bottom: 2px solid var(--border-color, #e5e7eb);">
        <a href="{{ route('admin.roles.index') }}" class="tab-item active" style="padding: 12px 20px; font-weight: 500; color: var(--color-accent, #6366f1); border-bottom: 2px solid var(--color-accent, #6366f1); margin-bottom: -2px; text-decoration: none;">
            Roles
        </a>
        <a href="{{ route('admin.permissions.matrix') }}" class="tab-item" style="padding: 12px 20px; font-weight: 500; color: var(--text-secondary, #6b7280); text-decoration: none;">
            Permission Matrix
        </a>
        <a href="{{ route('admin.permissions.rules') }}" class="tab-item" style="padding: 12px 20px; font-weight: 500; color: var(--text-secondary, #6b7280); text-decoration: none;">
            Access Rules
        </a>
        <a href="{{ route('admin.permissions.audit') }}" class="tab-item" style="padding: 12px 20px; font-weight: 500; color: var(--text-secondary, #6b7280); text-decoration: none;">
            Audit Log
        </a>
    </div>

    {{-- Search and Filters --}}
    <div class="toolbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; flex-wrap: wrap;">
        <div class="search-filter-group" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div class="search-input-wrapper" style="flex: 1; min-width: 250px;">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text"
                       id="roleSearch"
                       class="search-input"
                       placeholder="Search roles..."
                       value="{{ $filters['search'] ?? '' }}"
                       style="width: 100%;">
            </div>

            <select id="statusFilter" class="filter-select">
                <option value="">Status ▼</option>
                <option value="1" {{ ($filters['active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ ($filters['active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>

            <select id="pluginFilter" class="filter-select">
                <option value="">Plugin ▼</option>
                <option value="core" {{ ($filters['plugin'] ?? '') === 'core' ? 'selected' : '' }}>Core</option>
                @foreach($plugins ?? [] as $plugin)
                    <option value="{{ $plugin->slug }}" {{ ($filters['plugin'] ?? '') === $plugin->slug ? 'selected' : '' }}>{{ $plugin->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="item-count" style="font-size: 13px; color: var(--text-secondary, #6b7280);">
            Showing {{ $roles->count() }} of {{ $roles->total() }} roles
        </div>
    </div>

    {{-- Roles Grid/Cards --}}
    @if($roles->isEmpty())
        <div class="empty-state" style="padding: 60px 40px; text-align: center; background: var(--bg-surface-1, #fff); border: 1px solid var(--border-color, #e5e7eb); border-radius: 12px;">
            <div style="width: 64px; height: 64px; margin: 0 auto 16px; background: var(--bg-surface-2, #f3f4f6); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: var(--text-tertiary, #9ca3af);">
                @include('backend.partials.icon', ['icon' => 'shield'])
            </div>
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 600;">No roles found</h3>
            <p style="margin: 0 0 20px; color: var(--text-secondary, #6b7280);">Create your first role to start managing access control.</p>
            <a href="{{ route('admin.roles.create') }}" class="btn-primary">
                @include('backend.partials.icon', ['icon' => 'plus'])
                Create Role
            </a>
        </div>
    @else
        <div class="roles-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
            @foreach($roles as $role)
            <div class="role-card" data-role-id="{{ $role->id }}" style="background: var(--bg-surface-1, #fff); border: 1px solid var(--border-color, #e5e7eb); border-radius: 12px; overflow: hidden; transition: box-shadow 0.2s, transform 0.2s;">
                {{-- Card Header with Color Bar --}}
                <div style="height: 4px; background: {{ $role->color }};"></div>
                
                <div style="padding: 20px;">
                    {{-- Role Header --}}
                    <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;">
                        <div class="role-color-indicator" style="background-color: {{ $role->color }}; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                            @include('backend.partials.icon', ['icon' => $role->icon ?? 'shield'])
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <a href="{{ route('admin.roles.show', $role) }}" style="font-size: 16px; font-weight: 600; color: var(--text-primary, #1f2937); text-decoration: none;">
                                    {{ $role->name }}
                                </a>
                                @if($role->is_system)
                                    <span class="system-badge">System</span>
                                @endif
                                @if($role->is_default)
                                    <span class="default-badge">Default</span>
                                @endif
                            </div>
                            @if($role->parent)
                                <div style="font-size: 12px; color: var(--text-tertiary, #9ca3af); margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                                    <span style="width: 12px; height: 12px;">@include('backend.partials.icon', ['icon' => 'cornerDownRight'])</span>
                                    Inherits: {{ $role->parent->name }}
                                </div>
                            @endif
                        </div>
                        {{-- Actions Dropdown --}}
                        <div class="actions-dropdown" style="position: relative;">
                            <button type="button" class="action-menu-btn" data-action="toggle-menu" style="padding: 6px; background: transparent; border: none; border-radius: 6px; cursor: pointer; color: var(--text-secondary, #6b7280);">
                                @include('backend.partials.icon', ['icon' => 'moreVertical'])
                            </button>
                            <div class="action-menu" data-menu style="display: none; position: absolute; top: 100%; right: 0; z-index: 50; min-width: 180px; background: var(--bg-surface-1, #fff); border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); padding: 8px; margin-top: 4px;">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="action-item" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 6px; color: var(--text-primary, #1f2937); text-decoration: none; font-size: 14px;">
                                    @include('backend.partials.icon', ['icon' => 'edit'])
                                    Edit
                                </a>
                                <a href="{{ route('admin.roles.show', $role) }}" class="action-item" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 6px; color: var(--text-primary, #1f2937); text-decoration: none; font-size: 14px;">
                                    @include('backend.partials.icon', ['icon' => 'eye'])
                                    View Details
                                </a>
                                <button type="button" class="action-item" data-action="duplicate" data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 6px; color: var(--text-primary, #1f2937); font-size: 14px; width: 100%; text-align: left; background: none; border: none; cursor: pointer;">
                                    @include('backend.partials.icon', ['icon' => 'copy'])
                                    Duplicate
                                </button>
                                <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="action-item" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 6px; color: var(--text-primary, #1f2937); text-decoration: none; font-size: 14px;">
                                    @include('backend.partials.icon', ['icon' => 'users'])
                                    Assign Users
                                </a>
                                @if(!$role->is_system)
                                    <div style="height: 1px; background: var(--border-color, #e5e7eb); margin: 8px 0;"></div>
                                    <button type="button" class="action-item" data-action="delete" data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 6px; color: #ef4444; font-size: 14px; width: 100%; text-align: left; background: none; border: none; cursor: pointer;">
                                        @include('backend.partials.icon', ['icon' => 'trash'])
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($role->description)
                        <p style="font-size: 13px; color: var(--text-secondary, #6b7280); margin: 0 0 16px; line-height: 1.5;">
                            {{ Str::limit($role->description, 80) }}
                        </p>
                    @endif

                    {{-- Stats --}}
                    <div style="display: flex; gap: 16px; padding-top: 16px; border-top: 1px solid var(--border-color, #e5e7eb);">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="color: var(--text-tertiary, #9ca3af);">@include('backend.partials.icon', ['icon' => 'key'])</span>
                            <span style="font-size: 14px;">
                                <strong>{{ $role->grantedPermissions->count() }}</strong>
                                <span style="color: var(--text-secondary, #6b7280);">permissions</span>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="color: var(--text-tertiary, #9ca3af);">@include('backend.partials.icon', ['icon' => 'users'])</span>
                            <span style="font-size: 14px;">
                                <strong>{{ $role->users_count }}</strong>
                                <span style="color: var(--text-secondary, #6b7280);">users</span>
                            </span>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div style="display: flex; gap: 8px; margin-top: 16px;">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn-secondary btn-sm" style="flex: 1; justify-content: center;">
                            Edit
                        </a>
                        <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="btn-secondary btn-sm" style="flex: 1; justify-content: center;">
                            Users
                        </a>
                        @if(!$role->is_system)
                            <button type="button" class="btn-secondary btn-sm" data-action="duplicate" data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}" style="flex: 1; justify-content: center;">
                                Duplicate
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($roles->hasPages())
            <div class="pagination-wrapper" style="margin-top: 24px; display: flex; justify-content: center;">
                {{ $roles->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Duplicate Modal --}}
<div id="duplicateModal" style="display: none; position: fixed; inset: 0; z-index: 100; align-items: center; justify-content: center;">
    <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);" onclick="window.VodoRoles.closeDuplicateModal()"></div>
    <div style="position: relative; background: var(--bg-surface-1, #fff); border-radius: 12px; width: 100%; max-width: 400px; margin: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border-color, #e5e7eb);">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Duplicate Role</h3>
            <button type="button" onclick="window.VodoRoles.closeDuplicateModal()" style="background: none; border: none; cursor: pointer; padding: 4px; color: var(--text-secondary);">
                @include('backend.partials.icon', ['icon' => 'x'])
            </button>
        </div>
        <form id="duplicateForm" onsubmit="window.VodoRoles.submitDuplicate(event)">
            <div style="padding: 20px;">
                <div class="form-group">
                    <label for="duplicateName" class="form-label">New Role Name</label>
                    <input type="text" id="duplicateName" name="name" class="form-input" required>
                </div>
                <div class="form-group" style="margin-top: 16px;">
                    <label for="duplicateSlug" class="form-label">Slug (optional)</label>
                    <input type="text" id="duplicateSlug" name="slug" class="form-input" placeholder="auto-generated">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px; padding: 16px 20px; border-top: 1px solid var(--border-color, #e5e7eb);">
                <button type="button" class="btn-secondary" onclick="window.VodoRoles.closeDuplicateModal()">Cancel</button>
                <button type="submit" class="btn-primary">Duplicate</button>
            </div>
        </form>
    </div>
</div>

<script>
// Namespace for PJAX compatibility
window.VodoRoles = window.VodoRoles || {};

(function(VodoRoles) {
    'use strict';
    
    VodoRoles.currentDuplicateRoleId = null;
    
    VodoRoles.init = function() {
        // Use event delegation for dropdown menus (works with PJAX)
        document.removeEventListener('click', VodoRoles.handleClick);
        document.addEventListener('click', VodoRoles.handleClick);
    };
    
    VodoRoles.handleClick = function(e) {
        var rolesPage = document.querySelector('.roles-page');
        if (!rolesPage) return;
        
        // Toggle menu
        var toggleBtn = e.target.closest('[data-action="toggle-menu"]');
        if (toggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            var dropdown = toggleBtn.closest('.actions-dropdown');
            var menu = dropdown.querySelector('[data-menu]');
            
            // Close all other menus first
            document.querySelectorAll('.roles-page [data-menu]').forEach(function(m) {
                if (m !== menu) m.style.display = 'none';
            });
            
            // Toggle this menu
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            return;
        }
        
        // Duplicate action
        var duplicateBtn = e.target.closest('[data-action="duplicate"]');
        if (duplicateBtn) {
            e.preventDefault();
            var roleId = duplicateBtn.dataset.roleId;
            var roleName = duplicateBtn.dataset.roleName;
            VodoRoles.openDuplicateModal(roleId, roleName);
            // Close menu
            var menu = e.target.closest('[data-menu]');
            if (menu) menu.style.display = 'none';
            return;
        }
        
        // Delete action
        var deleteBtn = e.target.closest('[data-action="delete"]');
        if (deleteBtn) {
            e.preventDefault();
            var roleId = deleteBtn.dataset.roleId;
            var roleName = deleteBtn.dataset.roleName;
            VodoRoles.deleteRole(roleId, roleName);
            // Close menu
            var menu = e.target.closest('[data-menu]');
            if (menu) menu.style.display = 'none';
            return;
        }
        
        // Close menus when clicking outside
        if (!e.target.closest('.actions-dropdown')) {
            document.querySelectorAll('.roles-page [data-menu]').forEach(function(m) {
                m.style.display = 'none';
            });
        }
    };
    
    VodoRoles.openDuplicateModal = function(roleId, roleName) {
        VodoRoles.currentDuplicateRoleId = roleId;
        document.getElementById('duplicateName').value = (roleName || '') + ' (Copy)';
        document.getElementById('duplicateSlug').value = '';
        document.getElementById('duplicateModal').style.display = 'flex';
    };
    
    VodoRoles.closeDuplicateModal = function() {
        document.getElementById('duplicateModal').style.display = 'none';
        VodoRoles.currentDuplicateRoleId = null;
    };
    
    VodoRoles.submitDuplicate = function(event) {
        event.preventDefault();
        if (!VodoRoles.currentDuplicateRoleId) return;
        
        var form = document.getElementById('duplicateForm');
        var formData = new FormData(form);
        
        Vodo.api.post('{{ url("admin/system/roles") }}/' + VodoRoles.currentDuplicateRoleId + '/duplicate', {
            name: formData.get('name'),
            slug: formData.get('slug') || null
        }).then(function(response) {
            if (response.success) {
                Vodo.notifications.success(response.message || 'Role duplicated successfully');
                VodoRoles.closeDuplicateModal();
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    location.reload();
                }
            }
        }).catch(function(error) {
            Vodo.notifications.error(error.message || 'Failed to duplicate role');
        });
    };
    
    VodoRoles.deleteRole = function(roleId, roleName) {
        if (!confirm('Are you sure you want to delete the role "' + roleName + '"? This action cannot be undone.')) {
            return;
        }
        
        Vodo.api.delete('{{ url("admin/system/roles") }}/' + roleId).then(function(response) {
            if (response.success) {
                Vodo.notifications.success(response.message || 'Role deleted successfully');
                location.reload();
            }
        }).catch(function(error) {
            Vodo.notifications.error(error.message || 'Failed to delete role');
        });
    };
    
    // Initialize on load and PJAX
    VodoRoles.init();
    
})(window.VodoRoles);

// Search and filter handling
(function() {
    'use strict';
    
    var roleSearch = document.getElementById('roleSearch');
    var pluginFilter = document.getElementById('pluginFilter');
    var statusFilter = document.getElementById('statusFilter');
    var debounceTimer;
    
    function applyFilters() {
        var params = new URLSearchParams();
        if (roleSearch && roleSearch.value) params.set('search', roleSearch.value);
        if (pluginFilter && pluginFilter.value) params.set('plugin', pluginFilter.value);
        if (statusFilter && statusFilter.value) params.set('active', statusFilter.value);
        
        window.location.href = '{{ route("admin.roles.index") }}?' + params.toString();
    }
    
    if (roleSearch) {
        roleSearch.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyFilters, 400);
        });
    }
    
    if (pluginFilter) pluginFilter.addEventListener('change', applyFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);
})();
</script>

<style>
/* Role card hover effects */
.role-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

/* Action menu item hover */
.action-item:hover {
    background: var(--bg-surface-2, #f3f4f6);
}

/* Tab hover */
.tab-item:hover:not(.active) {
    color: var(--text-primary, #1f2937);
    background: var(--bg-surface-2, #f3f4f6);
    border-radius: 6px 6px 0 0;
}

/* Icon sizing in cards */
.role-card svg {
    width: 16px;
    height: 16px;
}

.role-color-indicator svg {
    width: 20px;
    height: 20px;
}
</style>
@endsection
