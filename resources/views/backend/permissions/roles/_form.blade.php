{{-- Role Form Partial --}}
{{-- Used by both create and edit views --}}

<div class="role-form" x-data="roleForm({
    role: @json($role ?? null),
    permissions: @json($permissions ?? []),
    selectedPermissions: @json($selectedPermissions ?? []),
    inheritedPermissions: @json($inheritedPermissions ?? []),
    lockedPermissions: @json($lockedPermissions ?? []),
    roles: @json($parentRoles ?? [])
})">
    {{-- Role Details Section --}}
    <div class="form-section">
        <div class="form-section-header">
            <h3>Role Details</h3>
            <p>Basic information about the role</p>
        </div>
        <div class="form-section-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label required">Role Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-input"
                           value="{{ old('name', $role->name ?? '') }}"
                           placeholder="e.g., Manager, Editor, Viewer"
                           required
                           @input="generateSlug">
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="slug" class="form-label">Role Slug</label>
                    <input type="text"
                           id="slug"
                           name="slug"
                           class="form-input"
                           value="{{ old('slug', $role->slug ?? '') }}"
                           placeholder="auto-generated"
                           x-model="slug"
                           {{ isset($role) && $role->is_system ? 'readonly' : '' }}>
                    @error('slug')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description"
                          name="description"
                          class="form-textarea"
                          rows="2"
                          placeholder="Brief description of this role's purpose">{{ old('description', $role->description ?? '') }}</textarea>
                @error('description')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="parent_id" class="form-label">Inherits From</label>
                    <select id="parent_id"
                            name="parent_id"
                            class="form-select"
                            @change="loadInheritedPermissions">
                        <option value="">-- No inheritance --</option>
                        @foreach($parentRoles ?? [] as $parentRole)
                            @if(!isset($role) || $parentRole->id !== $role->id)
                                <option value="{{ $parentRole->id }}"
                                        {{ old('parent_id', $role->parent_id ?? '') == $parentRole->id ? 'selected' : '' }}>
                                    {{ $parentRole->name }}
                                    ({{ $parentRole->permissions_count ?? 0 }} permissions)
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <span class="form-hint">Inherited permissions cannot be removed from this role</span>
                    @error('parent_id')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="level" class="form-label">Access Level</label>
                    <input type="number"
                           id="level"
                           name="level"
                           class="form-input"
                           value="{{ old('level', $role->level ?? 1) }}"
                           min="0"
                           max="100">
                    <span class="form-hint">Higher levels have more access (0-100)</span>
                    @error('level')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Role Color</label>
                    <div class="color-picker">
                        @php
                            $colors = [
                                '#6366f1' => 'Indigo',
                                '#8b5cf6' => 'Violet',
                                '#ec4899' => 'Pink',
                                '#ef4444' => 'Red',
                                '#f97316' => 'Orange',
                                '#eab308' => 'Yellow',
                                '#22c55e' => 'Green',
                                '#14b8a6' => 'Teal',
                                '#0ea5e9' => 'Sky',
                                '#6b7280' => 'Gray',
                            ];
                            $selectedColor = old('color', $role->color ?? '#6366f1');
                        @endphp
                        @foreach($colors as $hex => $name)
                            <label class="color-option" title="{{ $name }}">
                                <input type="radio"
                                       name="color"
                                       value="{{ $hex }}"
                                       {{ $selectedColor === $hex ? 'checked' : '' }}>
                                <span class="color-swatch" style="background-color: {{ $hex }};"></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Role Icon</label>
                    <div class="icon-picker">
                        @php
                            $icons = ['shield', 'crown', 'user', 'users', 'briefcase', 'star', 'settings', 'key'];
                            $selectedIcon = old('icon', $role->icon ?? 'shield');
                        @endphp
                        @foreach($icons as $icon)
                            <label class="icon-option">
                                <input type="radio"
                                       name="icon"
                                       value="{{ $icon }}"
                                       {{ $selectedIcon === $icon ? 'checked' : '' }}>
                                <span class="icon-display">
                                    @include('backend.partials.icon', ['icon' => $icon])
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group-checkbox">
                    <label class="checkbox-label">
                        <input type="checkbox"
                               name="is_default"
                               value="1"
                               {{ old('is_default', $role->is_default ?? false) ? 'checked' : '' }}>
                        <span class="checkbox-text">Set as default role for new users</span>
                    </label>
                </div>

                <div class="form-group form-group-checkbox">
                    <label class="checkbox-label">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $role->is_active ?? true) ? 'checked' : '' }}>
                        <span class="checkbox-text">Role is active</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Privilege Escalation Warning --}}
    <div class="alert alert-warning" x-show="lockedCount > 0" x-cloak>
        <div class="alert-icon">
            @include('backend.partials.icon', ['icon' => 'alertTriangle'])
        </div>
        <div class="alert-content">
            <h4>Privilege Escalation Protection</h4>
            <p>
                <span x-text="lockedCount"></span> permission(s) are locked because they exceed your access level.
                You cannot grant permissions you don't have. Locked permissions are marked with a lock icon.
            </p>
        </div>
    </div>

    {{-- Permissions Section --}}
    <div class="form-section">
        <div class="form-section-header">
            <h3>Permissions</h3>
            <p>Select which permissions this role grants</p>
        </div>
        <div class="form-section-body">
            {{-- Permission Stats --}}
            <div class="permission-stats">
                <div class="stat-item">
                    <span class="stat-value" x-text="selectedCount"></span>
                    <span class="stat-label">Selected</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" x-text="directCount"></span>
                    <span class="stat-label">Direct</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" x-text="inheritedCount"></span>
                    <span class="stat-label">Inherited</span>
                </div>
                <div class="stat-item stat-warning" x-show="lockedCount > 0">
                    <span class="stat-value" x-text="lockedCount"></span>
                    <span class="stat-label">Locked</span>
                </div>
            </div>

            {{-- Permission Filters --}}
            <div class="permission-filters">
                <div class="search-input-wrapper">
                    @include('backend.partials.icon', ['icon' => 'search'])
                    <input type="text"
                           class="search-input"
                           placeholder="Search permissions..."
                           x-model="searchQuery"
                           @input.debounce.300ms="filterPermissions">
                </div>

                <select class="filter-select" x-model="groupFilter" @change="filterPermissions">
                    <option value="">All Groups</option>
                    <template x-for="group in groups" :key="group.slug">
                        <option :value="group.slug" x-text="group.name"></option>
                    </template>
                </select>

                <select class="filter-select" x-model="pluginFilter" @change="filterPermissions">
                    <option value="">All Plugins</option>
                    <option value="core">Core</option>
                    @foreach($plugins ?? [] as $plugin)
                        <option value="{{ $plugin->slug }}">{{ $plugin->name }}</option>
                    @endforeach
                </select>

                <button type="button" class="btn-secondary btn-sm" @click="selectAll">
                    Select All Visible
                </button>
                <button type="button" class="btn-secondary btn-sm" @click="deselectAll">
                    Deselect All
                </button>
            </div>

            {{-- Permission Groups --}}
            <div class="permission-groups">
                <template x-for="group in filteredGroups" :key="group.slug">
                    <div class="permission-group" :class="{ 'expanded': group.expanded }">
                        <button type="button"
                                class="permission-group-header"
                                @click="toggleGroup(group.slug)">
                            <span class="group-icon">
                                @include('backend.partials.icon', ['icon' => 'folder'])
                            </span>
                            <span class="group-name" x-text="group.name"></span>
                            <span class="group-count">
                                <span x-text="getGroupSelectedCount(group)"></span>
                                / <span x-text="group.permissions.length"></span>
                            </span>
                            <template x-if="group.plugin">
                                <span class="group-plugin" x-text="group.plugin"></span>
                            </template>
                            <button type="button"
                                    class="btn-link btn-sm"
                                    @click.stop="selectGroup(group.slug)">
                                Select All
                            </button>
                            <span class="group-chevron">
                                @include('backend.partials.icon', ['icon' => 'chevronDown'])
                            </span>
                        </button>

                        <div class="permission-group-content" x-show="group.expanded" x-collapse>
                            <template x-for="permission in group.permissions" :key="permission.id">
                                <label class="permission-item"
                                       :class="{
                                           'inherited': isInherited(permission.id),
                                           'locked': isLocked(permission.id),
                                           'dangerous': permission.is_dangerous
                                       }">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           :value="permission.id"
                                           :checked="isSelected(permission.id)"
                                           :disabled="isInherited(permission.id) || isLocked(permission.id)"
                                           @change="togglePermission(permission.id)">

                                    <span class="permission-info">
                                        <span class="permission-name">
                                            <span x-text="permission.label || permission.slug"></span>
                                            <template x-if="permission.is_dangerous">
                                                <span class="badge badge-danger" title="Dangerous permission">!</span>
                                            </template>
                                        </span>
                                        <template x-if="permission.dependencies && permission.dependencies.length > 0">
                                            <span class="permission-deps" :title="'Requires: ' + permission.dependencies.join(', ')">
                                                @include('backend.partials.icon', ['icon' => 'link'])
                                            </span>
                                        </template>
                                    </span>

                                    <span class="permission-status">
                                        <template x-if="isInherited(permission.id)">
                                            <span class="status-inherited">Inherited</span>
                                        </template>
                                        <template x-if="isLocked(permission.id)">
                                            <span class="status-locked">
                                                @include('backend.partials.icon', ['icon' => 'lock'])
                                                Locked
                                            </span>
                                        </template>
                                        <template x-if="!isInherited(permission.id) && !isLocked(permission.id) && isSelected(permission.id)">
                                            <span class="status-direct">Direct</span>
                                        </template>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="filteredGroups.length === 0">
                    <div class="empty-state">
                        <p>No permissions match your search criteria.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function roleForm(config) {
    return {
        role: config.role,
        permissions: config.permissions,
        selectedPermissions: new Set(config.selectedPermissions),
        inheritedPermissions: new Set(config.inheritedPermissions),
        lockedPermissions: new Set(config.lockedPermissions),
        roles: config.roles,
        groups: [],
        filteredGroups: [],
        searchQuery: '',
        groupFilter: '',
        pluginFilter: '',
        slug: config.role?.slug || '',

        init() {
            // Build groups from permissions
            this.buildGroups();
            this.filteredGroups = this.groups;
        },

        buildGroups() {
            const groupMap = {};

            this.permissions.forEach(permission => {
                const groupSlug = permission.group?.slug || 'general';
                const groupName = permission.group?.name || 'General';
                const plugin = permission.plugin || null;

                if (!groupMap[groupSlug]) {
                    groupMap[groupSlug] = {
                        slug: groupSlug,
                        name: groupName,
                        plugin: plugin,
                        permissions: [],
                        expanded: false
                    };
                }

                groupMap[groupSlug].permissions.push(permission);
            });

            this.groups = Object.values(groupMap).sort((a, b) => {
                if (a.plugin && !b.plugin) return 1;
                if (!a.plugin && b.plugin) return -1;
                return a.name.localeCompare(b.name);
            });
        },

        get selectedCount() {
            return this.selectedPermissions.size + this.inheritedPermissions.size;
        },

        get directCount() {
            return this.selectedPermissions.size;
        },

        get inheritedCount() {
            return this.inheritedPermissions.size;
        },

        get lockedCount() {
            return this.lockedPermissions.size;
        },

        isSelected(permissionId) {
            return this.selectedPermissions.has(permissionId) || this.inheritedPermissions.has(permissionId);
        },

        isInherited(permissionId) {
            return this.inheritedPermissions.has(permissionId);
        },

        isLocked(permissionId) {
            return this.lockedPermissions.has(permissionId);
        },

        togglePermission(permissionId) {
            if (this.isInherited(permissionId) || this.isLocked(permissionId)) return;

            if (this.selectedPermissions.has(permissionId)) {
                this.selectedPermissions.delete(permissionId);
            } else {
                this.selectedPermissions.add(permissionId);
            }
        },

        toggleGroup(groupSlug) {
            const group = this.groups.find(g => g.slug === groupSlug);
            if (group) {
                group.expanded = !group.expanded;
            }
        },

        selectGroup(groupSlug) {
            const group = this.groups.find(g => g.slug === groupSlug);
            if (!group) return;

            group.permissions.forEach(p => {
                if (!this.isInherited(p.id) && !this.isLocked(p.id)) {
                    this.selectedPermissions.add(p.id);
                }
            });
        },

        getGroupSelectedCount(group) {
            return group.permissions.filter(p => this.isSelected(p.id)).length;
        },

        selectAll() {
            this.filteredGroups.forEach(group => {
                group.permissions.forEach(p => {
                    if (!this.isInherited(p.id) && !this.isLocked(p.id)) {
                        this.selectedPermissions.add(p.id);
                    }
                });
            });
        },

        deselectAll() {
            this.filteredGroups.forEach(group => {
                group.permissions.forEach(p => {
                    if (!this.isInherited(p.id)) {
                        this.selectedPermissions.delete(p.id);
                    }
                });
            });
        },

        filterPermissions() {
            const query = this.searchQuery.toLowerCase();

            this.filteredGroups = this.groups
                .map(group => {
                    // Filter by group
                    if (this.groupFilter && group.slug !== this.groupFilter) {
                        return null;
                    }

                    // Filter by plugin
                    if (this.pluginFilter) {
                        if (this.pluginFilter === 'core' && group.plugin) return null;
                        if (this.pluginFilter !== 'core' && group.plugin !== this.pluginFilter) return null;
                    }

                    // Filter permissions by search query
                    if (query) {
                        const filteredPermissions = group.permissions.filter(p =>
                            p.slug.toLowerCase().includes(query) ||
                            (p.label && p.label.toLowerCase().includes(query))
                        );

                        if (filteredPermissions.length === 0) return null;

                        return {
                            ...group,
                            permissions: filteredPermissions,
                            expanded: true // Auto-expand when searching
                        };
                    }

                    return group;
                })
                .filter(Boolean);
        },

        generateSlug() {
            if (this.role && this.role.is_system) return;

            const name = document.getElementById('name').value;
            this.slug = name.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
        },

        async loadInheritedPermissions() {
            const parentId = document.getElementById('parent_id').value;

            if (!parentId) {
                this.inheritedPermissions = new Set();
                return;
            }

            try {
                const response = await Vodo.api.get(`{{ url('admin/system/permissions/api/roles') }}/${parentId}/permissions`);
                if (response.success && response.permissions) {
                    this.inheritedPermissions = new Set(response.permissions);
                }
            } catch (error) {
                console.error('Failed to load inherited permissions:', error);
            }
        }
    };
}
</script>
