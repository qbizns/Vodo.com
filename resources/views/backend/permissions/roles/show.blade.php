{{-- Role Details View - Matching SCREENS.md Wireframe Style --}}
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
        <span style="font-size: 18px; font-weight: 600;">{{ $role->name }}</span>
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
    {{-- Stats Summary Bar --}}
    <div class="stats-grid mb-6">
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6;">
                @include('backend.partials.icon', ['icon' => 'key'])
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $permissionStats['total'] ?? 0 }}</span>
                <span class="stat-label">Total Permissions</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #10b981;">
                @include('backend.partials.icon', ['icon' => 'checkCircle'])
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $permissionStats['direct'] ?? 0 }}</span>
                <span class="stat-label">Direct Permissions</span>
            </div>
        </div>

        @if($role->parent)
        <div class="stat-card">
            <div class="stat-icon" style="background: #8b5cf6;">
                @include('backend.partials.icon', ['icon' => 'cornerDownRight'])
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $permissionStats['inherited'] ?? 0 }}</span>
                <span class="stat-label">Inherited from {{ $role->parent->name }}</span>
            </div>
        </div>
        @endif

        <div class="stat-card">
            <div class="stat-icon" style="background: #6366f1;">
                @include('backend.partials.icon', ['icon' => 'users'])
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $role->users_count ?? 0 }}</span>
                <span class="stat-label">Users with this Role</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2">
            {{-- Role Information Section --}}
            <div class="form-section mb-6">
                <div class="form-section-header">
                    <h3>Role Information</h3>
                </div>
                <div class="form-section-body">
                    <div class="info-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="info-item-box">
                            <label style="font-size: 12px; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.05em;">Name</label>
                            <div style="font-size: 16px; font-weight: 500; margin-top: 4px;">{{ $role->name }}</div>
                        </div>
                        <div class="info-item-box">
                            <label style="font-size: 12px; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.05em;">Slug</label>
                            <div style="font-family: monospace; font-size: 14px; margin-top: 4px; background: var(--bg-surface-2, #f3f4f6); padding: 6px 10px; border-radius: 4px; display: inline-block;">{{ $role->slug }}</div>
                        </div>
                    </div>

                    @if($role->description)
                    <div class="info-item-box" style="margin-top: 20px;">
                        <label style="font-size: 12px; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.05em;">Description</label>
                        <div style="font-size: 14px; margin-top: 4px; color: var(--text-secondary, #6b7280);">{{ $role->description }}</div>
                    </div>
                    @endif

                    <div class="info-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px;">
                        <div class="info-item-box">
                            <label style="font-size: 12px; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.05em;">Access Level</label>
                            <div style="font-size: 16px; font-weight: 500; margin-top: 4px;">{{ $role->level ?? 1 }}</div>
                        </div>
                        <div class="info-item-box">
                            <label style="font-size: 12px; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.05em;">Status</label>
                            <div style="margin-top: 4px;">
                                @if($role->is_active ?? true)
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 9999px; font-size: 13px; font-weight: 500;">
                                        <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span>
                                        Active
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(107, 114, 128, 0.1); color: #6b7280; border-radius: 9999px; font-size: 13px; font-weight: 500;">
                                        <span style="width: 8px; height: 8px; background: #9ca3af; border-radius: 50%;"></span>
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($role->parent)
                    <div class="info-item-box" style="margin-top: 20px;">
                        <label style="font-size: 12px; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.05em;">Inherits From</label>
                        <div style="margin-top: 4px;">
                            <a href="{{ route('admin.roles.show', $role->parent) }}" style="display: inline-flex; align-items: center; gap: 8px; color: var(--color-accent, #6366f1); text-decoration: none; font-weight: 500;">
                                <span style="width: 12px; height: 12px; background: {{ $role->parent->color }}; border-radius: 50%;"></span>
                                {{ $role->parent->name }}
                            </a>
                        </div>
                    </div>
                    @endif

                    <div class="info-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color, #e5e7eb);">
                        <div class="info-item-box">
                            <label style="font-size: 12px; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.05em;">Created</label>
                            <div style="font-size: 13px; margin-top: 4px; color: var(--text-secondary, #6b7280);">{{ $role->created_at->format('M d, Y \a\t g:i A') }}</div>
                        </div>
                        <div class="info-item-box">
                            <label style="font-size: 12px; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.05em;">Last Updated</label>
                            <div style="font-size: 13px; margin-top: 4px; color: var(--text-secondary, #6b7280);">{{ $role->updated_at->format('M d, Y \a\t g:i A') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Permissions Section --}}
            <div class="form-section">
                <div class="form-section-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Permissions ({{ $permissionStats['total'] ?? 0 }})</h3>
                    <div class="search-input-wrapper">
                        @include('backend.partials.icon', ['icon' => 'search'])
                        <input type="text"
                               class="search-input"
                               placeholder="Search permissions..."
                               id="permissionSearch"
                               style="width: 200px;">
                    </div>
                </div>
                <div class="form-section-body" style="padding: 0;">
                    {{-- Permission Groups --}}
                    <div class="permission-groups" style="border: none; border-radius: 0;">
                        @foreach($groupedPermissions ?? [] as $groupSlug => $group)
                        @php
                            $groupPermissions = $group['permissions'] ?? [];
                        @endphp
                        <div class="permission-group" data-group-slug="{{ $groupSlug }}">
                            {{-- Group Header --}}
                            <button type="button" class="permission-group-header" data-action="toggle-group" data-slug="{{ $groupSlug }}">
                                <span class="group-toggle-icon" style="transition: transform 0.2s;">▶</span>
                                <span class="group-icon">
                                    @include('backend.partials.icon', ['icon' => 'folder'])
                                </span>
                                <span class="group-name">{{ $group['name'] ?? $groupSlug }}</span>
                                <span class="group-count">({{ count($groupPermissions) }} permissions)</span>
                                @if(isset($group['plugin']) && $group['plugin'])
                                    <span class="group-plugin">{{ $group['plugin'] }}</span>
                                @endif
                            </button>

                            {{-- Group Content (Permissions List) --}}
                            <div class="permission-group-content" style="display: none;">
                                <div style="border-top: 1px solid var(--border-color, #e5e7eb);">
                                    @foreach($groupPermissions as $permission)
                                    @php
                                        $isInherited = $permission['inherited'] ?? false;
                                        $isDangerous = $permission['is_dangerous'] ?? false;
                                        $permSlug = $permission['slug'] ?? '';
                                        $permLabel = $permission['label'] ?? $permission['name'] ?? $permission['slug'];
                                    @endphp
                                    <div class="permission-row" 
                                         data-permission="{{ $permSlug }}"
                                         style="display: flex; align-items: center; padding: 10px 16px; border-bottom: 1px solid var(--border-color, #e5e7eb); {{ $isDangerous ? 'background: rgba(239, 68, 68, 0.03);' : '' }}">
                                        {{-- Status Icon --}}
                                        <div style="width: 30px;">
                                            @if($isInherited)
                                                <span style="color: #8b5cf6;" title="Inherited">
                                                    @include('backend.partials.icon', ['icon' => 'cornerDownRight'])
                                                </span>
                                            @else
                                                <span style="color: #10b981;" title="Direct">
                                                    @include('backend.partials.icon', ['icon' => 'check'])
                                                </span>
                                            @endif
                                        </div>
                                        
                                        {{-- Permission Slug --}}
                                        <div style="width: 200px; font-family: monospace; font-size: 13px; color: var(--text-primary);">
                                            {{ $permSlug }}
                                        </div>
                                        
                                        {{-- Permission Label/Description --}}
                                        <div style="flex: 1;">
                                            <span>{{ $permLabel }}</span>
                                        </div>
                                        
                                        {{-- Status --}}
                                        <div style="text-align: right; font-size: 12px;">
                                            @if($isInherited)
                                                <span style="color: #8b5cf6;">Inherited</span>
                                            @else
                                                <span style="color: #10b981;">✓ Direct</span>
                                            @endif
                                            
                                            @if($isDangerous)
                                                <span style="color: #ef4444; margin-left: 8px;" title="Dangerous permission">⚠️ Danger</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach

                        @if(empty($groupedPermissions))
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <p>No permissions assigned to this role.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div>
            {{-- Quick Actions --}}
            <div class="form-section mb-6">
                <div class="form-section-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="form-section-body" style="padding: 0;">
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
                        <a href="{{ route('admin.roles.compare') }}?roles[]={{ $role->id }}" class="action-list-item">
                            @include('backend.partials.icon', ['icon' => 'gitCompare'])
                            Compare with Others
                        </a>
                        @if(!$role->is_system)
                        <div style="height: 1px; background: var(--border-color, #e5e7eb); margin: 4px 0;"></div>
                        <button type="button" class="action-list-item danger" onclick="deleteRole()">
                            @include('backend.partials.icon', ['icon' => 'trash'])
                            Delete Role
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Users with this Role --}}
            <div class="form-section">
                <div class="form-section-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Users ({{ $role->users_count ?? 0 }})</h3>
                    @if(($role->users_count ?? 0) > 5)
                        <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="btn-link" style="font-size: 13px;">View All</a>
                    @endif
                </div>
                <div class="form-section-body" style="padding: 0;">
                    @if(($users ?? collect())->isEmpty())
                        <div style="padding: 32px; text-align: center;">
                            <div style="color: var(--text-tertiary, #9ca3af); margin-bottom: 12px;">
                                @include('backend.partials.icon', ['icon' => 'users'])
                            </div>
                            <p style="color: var(--text-secondary, #6b7280); margin: 0 0 12px;">No users assigned to this role.</p>
                            <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="btn-link">
                                Assign Users
                            </a>
                        </div>
                    @else
                        <ul class="user-list" style="list-style: none; padding: 0; margin: 0;">
                            @foreach($users ?? [] as $user)
                            <li style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--border-color, #e5e7eb);">
                                <div class="user-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: var(--color-accent, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 500; font-size: 14px;">{{ $user->name }}</div>
                                    <div style="font-size: 13px; color: var(--text-secondary, #6b7280); overflow: hidden; text-overflow: ellipsis;">{{ $user->email }}</div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        @if(($role->users_count ?? 0) > count($users ?? []))
                        <div style="padding: 12px 16px; text-align: center; border-top: 1px solid var(--border-color, #e5e7eb);">
                            <a href="{{ route('admin.roles.bulk-assign', $role) }}" class="btn-link" style="font-size: 13px;">
                                View all {{ $role->users_count }} users
                            </a>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Duplicate Modal --}}
<div id="duplicateModal" class="modal" style="display: none; position: fixed; inset: 0; z-index: 100; align-items: center; justify-content: center;">
    <div class="modal-backdrop" style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);" onclick="closeDuplicateModal()"></div>
    <div class="modal-content" style="position: relative; background: var(--bg-surface-1, #fff); border-radius: 12px; width: 100%; max-width: 400px; margin: 20px;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border-color, #e5e7eb);">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Duplicate Role</h3>
            <button type="button" onclick="closeDuplicateModal()" style="background: none; border: none; cursor: pointer; color: var(--text-secondary);">
                @include('backend.partials.icon', ['icon' => 'x'])
            </button>
        </div>
        <form id="duplicateForm" onsubmit="submitDuplicate(event)">
            <div class="modal-body" style="padding: 20px;">
                <div class="form-group">
                    <label for="duplicateName" class="form-label">New Role Name</label>
                    <input type="text" id="duplicateName" name="name" class="form-input" required>
                </div>
                <div class="form-group" style="margin-top: 16px;">
                    <label for="duplicateSlug" class="form-label">Slug (optional)</label>
                    <input type="text" id="duplicateSlug" name="slug" class="form-input" placeholder="auto-generated">
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 12px; padding: 16px 20px; border-top: 1px solid var(--border-color, #e5e7eb);">
                <button type="button" class="btn-secondary" onclick="closeDuplicateModal()">Cancel</button>
                <button type="submit" class="btn-primary">Duplicate</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    // Permission search
    var searchInput = document.getElementById('permissionSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase();
            var items = document.querySelectorAll('.permission-row');

            items.forEach(function(item) {
                var name = (item.dataset.permission || '').toLowerCase();
                var text = item.textContent.toLowerCase();
                item.style.display = (name.includes(query) || text.includes(query)) ? '' : 'none';
            });

            // Auto-expand groups with matches, hide empty groups
            document.querySelectorAll('.permission-group').forEach(function(group) {
                var visibleItems = group.querySelectorAll('.permission-row:not([style*="display: none"])');
                if (query && visibleItems.length > 0) {
                    var content = group.querySelector('.permission-group-content');
                    var icon = group.querySelector('.group-toggle-icon');
                    if (content) content.style.display = 'block';
                    if (icon) icon.textContent = '▼';
                }
                group.style.display = (!query || visibleItems.length > 0) ? '' : 'none';
            });
        });
    }

    // Toggle group expand/collapse
    document.addEventListener('click', function(e) {
        var toggleBtn = e.target.closest('[data-action="toggle-group"]');
        if (toggleBtn) {
            var slug = toggleBtn.dataset.slug;
            var groupEl = document.querySelector('.permission-group[data-group-slug="' + slug + '"]');
            if (groupEl) {
                var content = groupEl.querySelector('.permission-group-content');
                var icon = toggleBtn.querySelector('.group-toggle-icon');
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    if (icon) icon.textContent = '▼';
                } else {
                    content.style.display = 'none';
                    if (icon) icon.textContent = '▶';
                }
            }
        }
    });
})();

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

    var formData = new FormData(document.getElementById('duplicateForm'));

    Vodo.api.post('{{ route('admin.roles.duplicate', $role) }}', {
        name: formData.get('name'),
        slug: formData.get('slug') || null
    }).then(function(response) {
        if (response.success) {
            Vodo.notifications.success(response.message);
            closeDuplicateModal();
            if (response.redirect) {
                window.location.href = response.redirect;
            }
        }
    }).catch(function(error) {
        Vodo.notifications.error(error.message || 'Failed to duplicate role');
    });
}

function deleteRole() {
    if (!confirm('Are you sure you want to delete the role "{{ $role->name }}"? This action cannot be undone.')) {
        return;
    }
    
    Vodo.api.delete('{{ route('admin.roles.destroy', $role) }}').then(function(response) {
        if (response.success) {
            Vodo.notifications.success(response.message);
            window.location.href = '{{ route('admin.roles.index') }}';
        }
    }).catch(function(error) {
        Vodo.notifications.error(error.message || 'Failed to delete role');
    });
}
</script>
@endsection
