{{-- Permission Matrix (Screen 3 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Permission Matrix')
@section('page-id', 'system/permissions/matrix')
@section('require-css', 'permissions')

@section('header', 'Permission Matrix')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.index') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Roles</span>
    </a>
    <button type="button"
            class="btn-primary flex items-center gap-2"
            id="saveMatrixBtn"
            style="display: none;">
        @include('backend.partials.icon', ['icon' => 'save'])
        <span>Save Changes</span>
    </button>
</div>
@endsection

@section('content')
<div class="permission-matrix-page" x-data="permissionMatrix(@json($matrixData))">
    {{-- Filters --}}
    <div class="matrix-toolbar mb-4">
        <div class="search-filter-group">
            <div class="search-input-wrapper">
                @include('backend.partials.icon', ['icon' => 'search'])
                <input type="text"
                       class="search-input"
                       placeholder="Search permissions..."
                       x-model="searchQuery"
                       @input.debounce.300ms="filterMatrix">
            </div>

            <select class="filter-select" x-model="groupFilter" @change="filterMatrix">
                <option value="">All Groups</option>
                @foreach($groups as $group)
                    <option value="{{ $group->slug }}">{{ $group->name }}</option>
                @endforeach
            </select>

            <select class="filter-select" x-model="pluginFilter" @change="filterMatrix">
                <option value="">All Plugins</option>
                <option value="core">Core</option>
                @foreach($plugins as $plugin)
                    <option value="{{ $plugin->slug }}">{{ $plugin->name }}</option>
                @endforeach
            </select>

            <label class="checkbox-label">
                <input type="checkbox" x-model="showChangesOnly" @change="filterMatrix">
                <span>Show changes only</span>
            </label>
        </div>

        <div class="matrix-actions">
            <button type="button" class="btn-secondary btn-sm" @click="collapseAll">
                @include('backend.partials.icon', ['icon' => 'minimize2'])
                Collapse All
            </button>
            <button type="button" class="btn-secondary btn-sm" @click="expandAll">
                @include('backend.partials.icon', ['icon' => 'maximize2'])
                Expand All
            </button>
        </div>
    </div>

    {{-- Change Counter --}}
    <div class="changes-bar" x-show="changeCount > 0">
        <span class="changes-count">
            <span x-text="changeCount"></span> unsaved changes
        </span>
        <div class="changes-actions">
            <button type="button" class="btn-secondary btn-sm" @click="resetChanges">
                Reset Changes
            </button>
            <button type="button" class="btn-primary btn-sm" @click="saveChanges">
                Save Changes
            </button>
        </div>
    </div>

    {{-- Matrix Table --}}
    <div class="matrix-container">
        <div class="matrix-scroll">
            <table class="permission-matrix">
                <thead>
                    <tr>
                        <th class="permission-col sticky-col">Permission</th>
                        @foreach($roles as $role)
                            <th class="role-col"
                                :class="{ 'super-admin': {{ $role->is_system && $role->slug === 'super-admin' ? 'true' : 'false' }} }">
                                <div class="role-header">
                                    <span class="role-color" style="background-color: {{ $role->color }};"></span>
                                    <span class="role-name">{{ $role->name }}</span>
                                    @if($role->is_system)
                                        <span class="role-badge">System</span>
                                    @endif
                                </div>
                                <button type="button"
                                        class="btn-link btn-xs"
                                        @click="toggleColumn({{ $role->id }})"
                                        :disabled="{{ $role->slug === 'super-admin' ? 'true' : 'false' }}">
                                    Toggle All
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedPermissions as $groupSlug => $group)
                        {{-- Group Header --}}
                        <tr class="group-row"
                            x-show="isGroupVisible('{{ $groupSlug }}')"
                            @click="toggleGroup('{{ $groupSlug }}')">
                            <td class="group-cell sticky-col" colspan="{{ count($roles) + 1 }}">
                                <span class="group-icon">
                                    @include('backend.partials.icon', ['icon' => 'folder'])
                                </span>
                                <span class="group-name">{{ $group['name'] }}</span>
                                <span class="group-count">({{ count($group['permissions']) }})</span>
                                @if($group['plugin'] ?? null)
                                    <span class="group-plugin">{{ $group['plugin'] }}</span>
                                @endif
                                <span class="group-chevron" :class="{ 'expanded': expandedGroups['{{ $groupSlug }}'] }">
                                    @include('backend.partials.icon', ['icon' => 'chevronDown'])
                                </span>
                            </td>
                        </tr>

                        {{-- Permission Rows --}}
                        @foreach($group['permissions'] as $permission)
                            <tr class="permission-row"
                                x-show="isPermissionVisible('{{ $groupSlug }}', '{{ $permission['slug'] }}')"
                                :class="{
                                    'dangerous': {{ $permission['is_dangerous'] ? 'true' : 'false' }},
                                    'has-changes': hasPermissionChanges({{ $permission['id'] }})
                                }">
                                <td class="permission-cell sticky-col">
                                    <span class="permission-name">
                                        {{ $permission['label'] ?? $permission['slug'] }}
                                    </span>
                                    @if($permission['is_dangerous'])
                                        <span class="badge badge-danger" title="Dangerous permission">!</span>
                                    @endif
                                    @if(!empty($permission['dependencies']))
                                        <span class="deps-indicator"
                                              title="Requires: {{ implode(', ', $permission['dependencies']) }}">
                                            @include('backend.partials.icon', ['icon' => 'link'])
                                        </span>
                                    @endif
                                </td>

                                @foreach($roles as $role)
                                    @php
                                        $isSuperAdmin = $role->slug === 'super-admin';
                                        $isGranted = isset($matrix[$role->id][$permission['id']]);
                                        $isInherited = isset($inheritedMatrix[$role->id][$permission['id']]);
                                    @endphp
                                    <td class="matrix-cell"
                                        :class="{
                                            'super-admin': {{ $isSuperAdmin ? 'true' : 'false' }},
                                            'inherited': isInherited({{ $role->id }}, {{ $permission['id'] }}),
                                            'changed': isChanged({{ $role->id }}, {{ $permission['id'] }})
                                        }">
                                        @if($isSuperAdmin)
                                            <span class="matrix-check all" title="All permissions">
                                                @include('backend.partials.icon', ['icon' => 'checkCircle'])
                                            </span>
                                        @else
                                            <button type="button"
                                                    class="matrix-toggle"
                                                    :class="{
                                                        'granted': isGranted({{ $role->id }}, {{ $permission['id'] }}),
                                                        'inherited': isInherited({{ $role->id }}, {{ $permission['id'] }})
                                                    }"
                                                    @click="toggle({{ $role->id }}, {{ $permission['id'] }})"
                                                    :disabled="isInherited({{ $role->id }}, {{ $permission['id'] }}) || !canGrant({{ $permission['id'] }})">
                                                <template x-if="isGranted({{ $role->id }}, {{ $permission['id'] }})">
                                                    <span class="toggle-check">
                                                        @include('backend.partials.icon', ['icon' => 'check'])
                                                    </span>
                                                </template>
                                                <template x-if="isInherited({{ $role->id }}, {{ $permission['id'] }}) && !isGranted({{ $role->id }}, {{ $permission['id'] }})">
                                                    <span class="toggle-inherited">
                                                        @include('backend.partials.icon', ['icon' => 'cornerDownRight'])
                                                    </span>
                                                </template>
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Legend --}}
    <div class="matrix-legend mt-4">
        <div class="legend-item">
            <span class="legend-icon all">@include('backend.partials.icon', ['icon' => 'checkCircle'])</span>
            <span>All (Super Admin)</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon granted">@include('backend.partials.icon', ['icon' => 'check'])</span>
            <span>Granted</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon inherited">@include('backend.partials.icon', ['icon' => 'cornerDownRight'])</span>
            <span>Inherited</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon denied"></span>
            <span>Denied</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon dangerous">!</span>
            <span>Dangerous</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon deps">@include('backend.partials.icon', ['icon' => 'link'])</span>
            <span>Has Dependencies</span>
        </div>
    </div>
</div>

<script>
function permissionMatrix(data) {
    return {
        // Initial state
        permissions: data.permissions || {},
        inherited: data.inherited || {},
        grantable: new Set(data.grantable || []),
        groups: data.groups || [],

        // UI state
        changes: {},
        expandedGroups: {},
        searchQuery: '',
        groupFilter: '',
        pluginFilter: '',
        showChangesOnly: false,

        init() {
            // Expand all groups by default
            this.groups.forEach(g => {
                this.expandedGroups[g] = true;
            });
        },

        // Permission state
        isGranted(roleId, permissionId) {
            const key = `${roleId}-${permissionId}`;
            if (this.changes.hasOwnProperty(key)) {
                return this.changes[key];
            }
            return !!this.permissions[key];
        },

        isInherited(roleId, permissionId) {
            return !!this.inherited[`${roleId}-${permissionId}`];
        },

        canGrant(permissionId) {
            return this.grantable.has(permissionId);
        },

        isChanged(roleId, permissionId) {
            return this.changes.hasOwnProperty(`${roleId}-${permissionId}`);
        },

        hasPermissionChanges(permissionId) {
            return Object.keys(this.changes).some(k => k.endsWith(`-${permissionId}`));
        },

        // Actions
        toggle(roleId, permissionId) {
            if (this.isInherited(roleId, permissionId) || !this.canGrant(permissionId)) return;

            const key = `${roleId}-${permissionId}`;
            const current = this.isGranted(roleId, permissionId);
            const original = !!this.permissions[key];

            if (current === original) {
                // First change
                this.changes[key] = !current;
            } else if (!current === original) {
                // Reverting to original
                delete this.changes[key];
            } else {
                this.changes[key] = !current;
            }

            this.updateSaveButton();
        },

        toggleColumn(roleId) {
            // Get all visible permissions
            const visiblePermissions = document.querySelectorAll('.permission-row:not([style*="display: none"]) .matrix-toggle');
            const roleToggles = Array.from(visiblePermissions).filter(el => {
                const cell = el.closest('td');
                const row = el.closest('tr');
                const cells = Array.from(row.querySelectorAll('td'));
                const roleIndex = cells.indexOf(cell);
                // Match by role position (role index in the matrix)
                return true; // Simplified - in real implementation, match by role
            });

            // Check if all are granted
            const allGranted = roleToggles.every(el => el.classList.contains('granted'));

            // Toggle all
            roleToggles.forEach(el => {
                // Extract role and permission IDs and toggle
            });
        },

        toggleRow(permissionId) {
            // Similar logic for row toggle
        },

        // Group management
        toggleGroup(groupSlug) {
            this.expandedGroups[groupSlug] = !this.expandedGroups[groupSlug];
        },

        expandAll() {
            this.groups.forEach(g => {
                this.expandedGroups[g] = true;
            });
        },

        collapseAll() {
            this.groups.forEach(g => {
                this.expandedGroups[g] = false;
            });
        },

        // Filtering
        isGroupVisible(groupSlug) {
            if (this.groupFilter && this.groupFilter !== groupSlug) return false;
            // Add plugin filter logic
            return true;
        },

        isPermissionVisible(groupSlug, permissionSlug) {
            if (!this.expandedGroups[groupSlug]) return false;

            if (this.showChangesOnly) {
                // Check if this permission has any changes
                const hasChanges = Object.keys(this.changes).some(k => k.includes(permissionSlug));
                if (!hasChanges) return false;
            }

            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                return permissionSlug.toLowerCase().includes(query);
            }

            return true;
        },

        filterMatrix() {
            // Handled by x-show directives
        },

        // Changes management
        get changeCount() {
            return Object.keys(this.changes).length;
        },

        resetChanges() {
            this.changes = {};
            this.updateSaveButton();
        },

        updateSaveButton() {
            const btn = document.getElementById('saveMatrixBtn');
            if (btn) {
                btn.style.display = this.changeCount > 0 ? '' : 'none';
            }
        },

        async saveChanges() {
            if (this.changeCount === 0) return;

            try {
                const response = await Vodo.api.post('{{ route('admin.permissions.matrix.update') }}', {
                    changes: this.changes
                });

                if (response.success) {
                    // Update local state
                    Object.entries(this.changes).forEach(([key, value]) => {
                        if (value) {
                            this.permissions[key] = true;
                        } else {
                            delete this.permissions[key];
                        }
                    });

                    this.changes = {};
                    this.updateSaveButton();
                    Vodo.notification.success(response.message || 'Matrix updated successfully');
                }
            } catch (error) {
                Vodo.notification.error(error.message || 'Failed to save changes');
            }
        }
    };
}
</script>
@endsection
