/**
 * Vodo Permissions Module
 * Vanilla JavaScript components for the permission system
 * Replaces Alpine.js functionality with PJAX-compatible code
 */

(function(window) {
    'use strict';

    // Create namespace
    window.Vodo = window.Vodo || {};
    window.Vodo.permissions = {};

    /**
     * Role Form Component
     * Handles role create/edit form interactions
     */
    Vodo.permissions.RoleForm = class {
        constructor(container, config) {
            this.container = container;
            this.config = config;
            this.role = config.role || null;
            this.permissions = config.permissions || [];
            this.selectedPermissions = new Set(config.selectedPermissions || []);
            this.inheritedPermissions = new Set(config.inheritedPermissions || []);
            this.lockedPermissions = new Set(config.lockedPermissions || []);
            this.groups = [];
            this.filteredGroups = [];
            this.searchQuery = '';
            this.groupFilter = '';
            this.pluginFilter = '';
            this.slug = config.role?.slug || '';

            this.init();
        }

        init() {
            this.buildGroups();
            this.filteredGroups = [...this.groups];
            this.render();
            this.bindEvents();
            this.updateStats();
        }

        buildGroups() {
            // Handle grouped format from getGroupedForUI
            if (Array.isArray(this.permissions) && this.permissions.length > 0 && this.permissions[0].permissions) {
                this.groups = this.permissions.map(group => ({
                    slug: group.slug,
                    name: group.label || group.name,
                    icon: group.icon || 'folder',
                    plugin: group.plugin || null,
                    permissions: group.permissions || [],
                    expanded: false
                }));
            } else {
                // Flat format - build groups
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
            }
        }

        render() {
            const groupsContainer = this.container.querySelector('.permission-groups');
            if (!groupsContainer) return;

            groupsContainer.innerHTML = this.filteredGroups.map(group => this.renderGroup(group)).join('');
            
            if (this.filteredGroups.length === 0) {
                groupsContainer.innerHTML = '<div class="empty-state"><p>No permissions match your search criteria.</p></div>';
            }
        }

        renderGroup(group) {
            const selectedCount = this.getGroupSelectedCount(group);
            const isExpanded = group.expanded;
            
            return `
                <div class="permission-group ${isExpanded ? 'expanded' : ''}" data-group-slug="${group.slug}">
                    <button type="button" class="permission-group-header" data-action="toggle-group" data-slug="${group.slug}">
                        <span class="group-icon">
                            <svg class="icon"><use href="#icon-folder"></use></svg>
                        </span>
                        <span class="group-name">${this.escapeHtml(group.name)}</span>
                        <span class="group-count">${selectedCount} / ${group.permissions.length}</span>
                        ${group.plugin ? `<span class="group-plugin">${this.escapeHtml(group.plugin)}</span>` : ''}
                        <button type="button" class="btn-link btn-sm" data-action="select-group" data-slug="${group.slug}">
                            Select All
                        </button>
                        <span class="group-chevron">
                            <svg class="icon"><use href="#icon-chevronDown"></use></svg>
                        </span>
                    </button>
                    <div class="permission-group-content" style="${isExpanded ? '' : 'display: none;'}">
                        ${group.permissions.map(p => this.renderPermission(p)).join('')}
                    </div>
                </div>
            `;
        }

        renderPermission(permission) {
            const isSelected = this.isSelected(permission.id);
            const isInherited = this.isInherited(permission.id);
            const isLocked = this.isLocked(permission.id);
            const isDangerous = permission.is_dangerous;

            const classes = [
                'permission-item',
                isInherited ? 'inherited' : '',
                isLocked ? 'locked' : '',
                isDangerous ? 'dangerous' : ''
            ].filter(Boolean).join(' ');

            let statusHtml = '';
            if (isInherited) {
                statusHtml = '<span class="status-inherited">Inherited</span>';
            } else if (isLocked) {
                statusHtml = '<span class="status-locked"><svg class="icon"><use href="#icon-lock"></use></svg> Locked</span>';
            } else if (isSelected) {
                statusHtml = '<span class="status-direct">Direct</span>';
            }

            return `
                <label class="${classes}">
                    <input type="checkbox"
                           name="permissions[]"
                           value="${permission.id}"
                           ${isSelected ? 'checked' : ''}
                           ${(isInherited || isLocked) ? 'disabled' : ''}
                           data-action="toggle-permission"
                           data-id="${permission.id}">
                    <span class="permission-info">
                        <span class="permission-name">
                            ${this.escapeHtml(permission.label || permission.name || permission.slug)}
                            ${isDangerous ? '<span class="badge badge-danger" title="Dangerous permission">!</span>' : ''}
                        </span>
                    </span>
                    <span class="permission-status">${statusHtml}</span>
                </label>
            `;
        }

        bindEvents() {
            // Delegate events to container
            this.container.addEventListener('click', (e) => {
                const action = e.target.closest('[data-action]');
                if (!action) return;

                const actionName = action.dataset.action;
                
                switch (actionName) {
                    case 'toggle-group':
                        e.preventDefault();
                        this.toggleGroup(action.dataset.slug);
                        break;
                    case 'select-group':
                        e.preventDefault();
                        e.stopPropagation();
                        this.selectGroup(action.dataset.slug);
                        break;
                }
            });

            this.container.addEventListener('change', (e) => {
                const action = e.target.dataset.action;
                if (action === 'toggle-permission') {
                    this.togglePermission(parseInt(e.target.dataset.id));
                }
            });

            // Search input
            const searchInput = this.container.querySelector('[data-role="permission-search"]');
            if (searchInput) {
                let debounceTimer;
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        this.searchQuery = e.target.value;
                        this.filterPermissions();
                    }, 300);
                });
            }

            // Group filter
            const groupFilter = this.container.querySelector('[data-role="group-filter"]');
            if (groupFilter) {
                groupFilter.addEventListener('change', (e) => {
                    this.groupFilter = e.target.value;
                    this.filterPermissions();
                });
            }

            // Plugin filter
            const pluginFilter = this.container.querySelector('[data-role="plugin-filter"]');
            if (pluginFilter) {
                pluginFilter.addEventListener('change', (e) => {
                    this.pluginFilter = e.target.value;
                    this.filterPermissions();
                });
            }

            // Select all button
            const selectAllBtn = this.container.querySelector('[data-action="select-all"]');
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', () => this.selectAll());
            }

            // Deselect all button
            const deselectAllBtn = this.container.querySelector('[data-action="deselect-all"]');
            if (deselectAllBtn) {
                deselectAllBtn.addEventListener('click', () => this.deselectAll());
            }

            // Name input for slug generation
            const nameInput = this.container.querySelector('#name');
            if (nameInput && !(this.role && this.role.is_system)) {
                nameInput.addEventListener('input', () => this.generateSlug());
            }

            // Parent select for loading inherited permissions
            const parentSelect = this.container.querySelector('#parent_id');
            if (parentSelect) {
                parentSelect.addEventListener('change', () => this.loadInheritedPermissions());
            }
        }

        // Stats
        get selectedCount() {
            return this.selectedPermissions.size + this.inheritedPermissions.size;
        }

        get directCount() {
            return this.selectedPermissions.size;
        }

        get inheritedCount() {
            return this.inheritedPermissions.size;
        }

        get lockedCount() {
            return this.lockedPermissions.size;
        }

        updateStats() {
            const statElements = {
                selected: this.container.querySelector('[data-stat="selected"]'),
                direct: this.container.querySelector('[data-stat="direct"]'),
                inherited: this.container.querySelector('[data-stat="inherited"]'),
                locked: this.container.querySelector('[data-stat="locked"]')
            };

            if (statElements.selected) statElements.selected.textContent = this.selectedCount;
            if (statElements.direct) statElements.direct.textContent = this.directCount;
            if (statElements.inherited) statElements.inherited.textContent = this.inheritedCount;
            if (statElements.locked) {
                statElements.locked.textContent = this.lockedCount;
                const lockedContainer = statElements.locked.closest('.stat-item');
                if (lockedContainer) {
                    lockedContainer.style.display = this.lockedCount > 0 ? '' : 'none';
                }
            }

            // Update warning
            const warningAlert = this.container.querySelector('[data-role="locked-warning"]');
            if (warningAlert) {
                warningAlert.style.display = this.lockedCount > 0 ? '' : 'none';
                const countEl = warningAlert.querySelector('[data-count="locked"]');
                if (countEl) countEl.textContent = this.lockedCount;
            }
        }

        // Permission state methods
        isSelected(permissionId) {
            return this.selectedPermissions.has(permissionId) || this.inheritedPermissions.has(permissionId);
        }

        isInherited(permissionId) {
            return this.inheritedPermissions.has(permissionId);
        }

        isLocked(permissionId) {
            return this.lockedPermissions.has(permissionId);
        }

        togglePermission(permissionId) {
            if (this.isInherited(permissionId) || this.isLocked(permissionId)) return;

            if (this.selectedPermissions.has(permissionId)) {
                this.selectedPermissions.delete(permissionId);
            } else {
                this.selectedPermissions.add(permissionId);
            }
            
            this.render();
            this.updateStats();
        }

        toggleGroup(groupSlug) {
            const group = this.groups.find(g => g.slug === groupSlug);
            if (group) {
                group.expanded = !group.expanded;
                // Also update filtered groups
                const filteredGroup = this.filteredGroups.find(g => g.slug === groupSlug);
                if (filteredGroup) filteredGroup.expanded = group.expanded;
            }
            this.render();
        }

        selectGroup(groupSlug) {
            const group = this.groups.find(g => g.slug === groupSlug);
            if (!group) return;

            group.permissions.forEach(p => {
                if (!this.isInherited(p.id) && !this.isLocked(p.id)) {
                    this.selectedPermissions.add(p.id);
                }
            });
            
            this.render();
            this.updateStats();
        }

        getGroupSelectedCount(group) {
            return group.permissions.filter(p => this.isSelected(p.id)).length;
        }

        selectAll() {
            this.filteredGroups.forEach(group => {
                group.permissions.forEach(p => {
                    if (!this.isInherited(p.id) && !this.isLocked(p.id)) {
                        this.selectedPermissions.add(p.id);
                    }
                });
            });
            this.render();
            this.updateStats();
        }

        deselectAll() {
            this.filteredGroups.forEach(group => {
                group.permissions.forEach(p => {
                    if (!this.isInherited(p.id)) {
                        this.selectedPermissions.delete(p.id);
                    }
                });
            });
            this.render();
            this.updateStats();
        }

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
                            (p.label && p.label.toLowerCase().includes(query)) ||
                            (p.name && p.name.toLowerCase().includes(query))
                        );

                        if (filteredPermissions.length === 0) return null;

                        return {
                            ...group,
                            permissions: filteredPermissions,
                            expanded: true
                        };
                    }

                    return { ...group };
                })
                .filter(Boolean);

            // Update group filter dropdown options
            const groupFilterEl = this.container.querySelector('[data-role="group-filter"]');
            if (groupFilterEl && groupFilterEl.options.length <= 1) {
                this.groups.forEach(group => {
                    const option = document.createElement('option');
                    option.value = group.slug;
                    option.textContent = group.name;
                    groupFilterEl.appendChild(option);
                });
            }

            this.render();
        }

        generateSlug() {
            if (this.role && this.role.is_system) return;

            const nameInput = this.container.querySelector('#name');
            const slugInput = this.container.querySelector('#slug');
            
            if (nameInput && slugInput) {
                this.slug = nameInput.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
                slugInput.value = this.slug;
            }
        }

        async loadInheritedPermissions() {
            const parentSelect = this.container.querySelector('#parent_id');
            const parentId = parentSelect?.value;

            if (!parentId) {
                this.inheritedPermissions = new Set();
                this.render();
                this.updateStats();
                return;
            }

            try {
                const baseUrl = this.container.dataset.apiUrl || '/admin/system/permissions/api/roles';
                const response = await Vodo.api.get(`${baseUrl}/${parentId}/permissions`);
                if (response.success && response.permissions) {
                    this.inheritedPermissions = new Set(response.permissions);
                    this.render();
                    this.updateStats();
                }
            } catch (error) {
                console.error('Failed to load inherited permissions:', error);
            }
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Permission Matrix Component
     */
    Vodo.permissions.PermissionMatrix = class {
        constructor(container, config) {
            this.container = container;
            this.permissions = config.permissions || {};
            this.inherited = config.inherited || {};
            // grantable is an object like {"1-1": true}, convert keys to Set
            this.grantable = new Set(
                config.grantable 
                    ? (Array.isArray(config.grantable) ? config.grantable : Object.keys(config.grantable))
                    : []
            );
            this.groups = config.groups || [];
            this.changes = {};
            this.expandedGroups = {};
            this.searchQuery = '';
            this.groupFilter = '';
            this.pluginFilter = '';
            this.showChangesOnly = false;

            this.init();
        }

        init() {
            // Expand all groups by default
            this.groups.forEach(g => {
                this.expandedGroups[g] = true;
            });
            this.bindEvents();
            this.updateUI();
        }

        bindEvents() {
            // Matrix toggle buttons
            this.container.addEventListener('click', (e) => {
                const toggle = e.target.closest('.matrix-toggle');
                if (toggle) {
                    const roleId = parseInt(toggle.dataset.roleId);
                    const permId = parseInt(toggle.dataset.permId);
                    this.toggle(roleId, permId);
                }

                const groupRow = e.target.closest('.group-row');
                if (groupRow) {
                    this.toggleGroup(groupRow.dataset.group);
                }

                // Toggle column (Toggle All for a role)
                const toggleColBtn = e.target.closest('[data-action="toggle-column"]');
                if (toggleColBtn) {
                    e.preventDefault();
                    const roleId = parseInt(toggleColBtn.dataset.roleId);
                    this.toggleColumn(roleId);
                }

                // Collapse all groups
                const collapseAllBtn = e.target.closest('[data-action="collapse-all"]');
                if (collapseAllBtn) {
                    this.collapseAllGroups();
                }

                // Expand all groups
                const expandAllBtn = e.target.closest('[data-action="expand-all"]');
                if (expandAllBtn) {
                    this.expandAllGroups();
                }
            });

            // Search and filters
            const searchInput = this.container.querySelector('[data-role="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    this.searchQuery = e.target.value;
                    this.updateVisibility();
                });
            }

            // Group filter
            const groupFilter = this.container.querySelector('[data-role="group-filter"]');
            if (groupFilter) {
                groupFilter.addEventListener('change', (e) => {
                    this.groupFilter = e.target.value;
                    this.updateVisibility();
                });
            }

            // Plugin filter
            const pluginFilter = this.container.querySelector('[data-role="plugin-filter"]');
            if (pluginFilter) {
                pluginFilter.addEventListener('change', (e) => {
                    this.pluginFilter = e.target.value;
                    this.updateVisibility();
                });
            }

            // Show changes only checkbox
            const changesOnlyCheckbox = this.container.querySelector('[data-role="changes-only"]');
            if (changesOnlyCheckbox) {
                changesOnlyCheckbox.addEventListener('change', (e) => {
                    this.showChangesOnly = e.target.checked;
                    this.updateVisibility();
                });
            }

            // Save and reset buttons
            const saveBtn = this.container.querySelector('[data-action="save"]');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => this.saveChanges());
            }

            const resetBtn = this.container.querySelector('[data-action="reset"]');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => this.resetChanges());
            }
        }

        toggleColumn(roleId) {
            // Get all permission toggles for this role
            const toggles = this.container.querySelectorAll(`.matrix-toggle[data-role-id="${roleId}"]`);
            
            // Check if all are currently granted
            let allGranted = true;
            let permIds = [];
            
            toggles.forEach(toggle => {
                const permId = parseInt(toggle.dataset.permId);
                permIds.push(permId);
                if (!this.isGranted(roleId, permId) && !this.isInherited(roleId, permId)) {
                    allGranted = false;
                }
            });

            // Toggle all to the opposite state
            permIds.forEach(permId => {
                if (!this.isInherited(roleId, permId)) {
                    const key = `${roleId}-${permId}`;
                    const newValue = !allGranted;
                    const original = !!this.permissions[key];
                    
                    if (newValue !== original) {
                        this.changes[key] = newValue;
                    } else {
                        delete this.changes[key];
                    }
                }
            });

            this.updateUI();
            Vodo.notifications.success(allGranted ? 'All permissions revoked for this role' : 'All permissions granted for this role');
        }

        collapseAllGroups() {
            this.groups.forEach(g => {
                this.expandedGroups[g] = false;
            });
            this.updateVisibility();
        }

        expandAllGroups() {
            this.groups.forEach(g => {
                this.expandedGroups[g] = true;
            });
            this.updateVisibility();
        }

        isGranted(roleId, permissionId) {
            const key = `${roleId}-${permissionId}`;
            if (this.changes.hasOwnProperty(key)) {
                return this.changes[key];
            }
            return !!this.permissions[key];
        }

        isInherited(roleId, permissionId) {
            return !!this.inherited[`${roleId}-${permissionId}`];
        }

        canGrant(roleId, permissionId) {
            // Grantable uses keys like "roleId-permId"
            const key = `${roleId}-${permissionId}`;
            return this.grantable.has(key) || this.grantable.size === 0;
        }

        toggle(roleId, permissionId) {
            if (this.isInherited(roleId, permissionId) || !this.canGrant(roleId, permissionId)) return;

            const key = `${roleId}-${permissionId}`;
            const current = this.isGranted(roleId, permissionId);
            const original = !!this.permissions[key];

            if (current === original) {
                this.changes[key] = !current;
            } else if (!current === original) {
                delete this.changes[key];
            } else {
                this.changes[key] = !current;
            }

            this.updateUI();
        }

        toggleGroup(groupSlug) {
            this.expandedGroups[groupSlug] = !this.expandedGroups[groupSlug];
            this.updateVisibility();
        }

        get changeCount() {
            return Object.keys(this.changes).length;
        }

        resetChanges() {
            this.changes = {};
            this.updateUI();
        }

        updateUI() {
            // Update change counter
            const changeBar = this.container.querySelector('.changes-bar');
            const changeCount = this.container.querySelector('[data-count="changes"]');
            const saveBtn = document.getElementById('saveMatrixBtn');
            
            if (changeBar) {
                changeBar.style.display = this.changeCount > 0 ? '' : 'none';
            }
            if (changeCount) {
                changeCount.textContent = this.changeCount;
            }
            if (saveBtn) {
                saveBtn.style.display = this.changeCount > 0 ? '' : 'none';
            }

            // Update toggle states
            this.container.querySelectorAll('.matrix-toggle').forEach(toggle => {
                const roleId = parseInt(toggle.dataset.roleId);
                const permId = parseInt(toggle.dataset.permId);
                const isGranted = this.isGranted(roleId, permId);
                const isInherited = this.isInherited(roleId, permId);
                const isChanged = this.changes.hasOwnProperty(`${roleId}-${permId}`);

                toggle.classList.toggle('granted', isGranted);
                toggle.classList.toggle('inherited', isInherited);
                toggle.closest('td')?.classList.toggle('changed', isChanged);
            });
        }

        updateVisibility() {
            const query = this.searchQuery.toLowerCase();
            
            // Update group row chevrons
            this.container.querySelectorAll('.group-row').forEach(row => {
                const group = row.dataset.group || '';
                const chevron = row.querySelector('.group-chevron');
                const isExpanded = this.expandedGroups[group];
                
                if (chevron) {
                    chevron.classList.toggle('expanded', isExpanded);
                    chevron.classList.toggle('collapsed', !isExpanded);
                }
                row.classList.toggle('collapsed', !isExpanded);
            });
            
            // Update permission row visibility
            this.container.querySelectorAll('.permission-row').forEach(row => {
                const slug = row.dataset.permSlug || '';
                const group = row.dataset.group || '';
                const permName = row.querySelector('.permission-name')?.textContent?.toLowerCase() || '';
                
                let visible = true;
                
                // Search filter
                if (query && !slug.toLowerCase().includes(query) && !permName.includes(query)) {
                    visible = false;
                }
                
                // Group filter
                if (this.groupFilter && group !== this.groupFilter) {
                    visible = false;
                }
                
                // Expanded state
                if (!this.expandedGroups[group]) {
                    visible = false;
                }
                
                // Show changes only
                if (this.showChangesOnly) {
                    const hasChange = Array.from(row.querySelectorAll('.matrix-toggle')).some(toggle => {
                        const roleId = toggle.dataset.roleId;
                        const permId = toggle.dataset.permId;
                        return this.changes.hasOwnProperty(`${roleId}-${permId}`);
                    });
                    if (!hasChange) {
                        visible = false;
                    }
                }
                
                row.style.display = visible ? '' : 'none';
            });
            
            // Update group row visibility based on filter
            if (this.groupFilter) {
                this.container.querySelectorAll('.group-row').forEach(row => {
                    const group = row.dataset.group || '';
                    row.style.display = group === this.groupFilter ? '' : 'none';
                });
            } else {
                this.container.querySelectorAll('.group-row').forEach(row => {
                    row.style.display = '';
                });
            }
        }

        async saveChanges() {
            if (this.changeCount === 0) return;

            try {
                const url = this.container.dataset.saveUrl;
                const response = await Vodo.api.post(url, { changes: this.changes });

                if (response.success) {
                    Object.entries(this.changes).forEach(([key, value]) => {
                        if (value) {
                            this.permissions[key] = true;
                        } else {
                            delete this.permissions[key];
                        }
                    });

                    this.changes = {};
                    this.updateUI();
                    Vodo.notifications.success(response.message || 'Matrix updated successfully');
                }
            } catch (error) {
                Vodo.notifications.error(error.message || 'Failed to save changes');
            }
        }
    };

    /**
     * Bulk Assign Component
     */
    Vodo.permissions.BulkAssign = class {
        constructor(container, config) {
            this.container = container;
            this.existingUserIds = new Set(config.existingUserIds || []);
            this.selectedUsers = new Set();
            this.expiresType = 'never';
            this.expiresAt = '';
            this.notifyUsers = false;
            this.isSubmitting = false;

            this.init();
        }

        init() {
            this.bindEvents();
            this.updateUI();
        }

        bindEvents() {
            // User checkboxes
            this.container.addEventListener('change', (e) => {
                if (e.target.matches('.user-selection-item input[type="checkbox"]')) {
                    const userId = parseInt(e.target.value);
                    this.toggleUser(userId);
                }

                if (e.target.matches('[name="expires_type"]')) {
                    this.expiresType = e.target.value;
                    this.updateExpirationUI();
                }

                if (e.target.matches('[name="expires_at"]')) {
                    this.expiresAt = e.target.value;
                    this.updateUI();
                }

                if (e.target.matches('[name="notify_users"]')) {
                    this.notifyUsers = e.target.checked;
                    this.updateUI();
                }
            });

            // Select/deselect buttons
            const selectAllBtn = this.container.querySelector('[data-action="select-all-visible"]');
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', () => this.selectAllVisible());
            }

            const deselectAllBtn = this.container.querySelector('[data-action="deselect-all"]');
            if (deselectAllBtn) {
                deselectAllBtn.addEventListener('click', () => this.deselectAll());
            }

            // Form submission
            const form = this.container.querySelector('#bulkAssignForm');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.submitForm();
                });
            }
        }

        toggleUser(userId) {
            if (this.existingUserIds.has(userId)) return;

            if (this.selectedUsers.has(userId)) {
                this.selectedUsers.delete(userId);
            } else {
                this.selectedUsers.add(userId);
            }
            this.updateUI();
        }

        selectAllVisible() {
            const checkboxes = this.container.querySelectorAll('.user-selection-item input[type="checkbox"]:not(:disabled)');
            checkboxes.forEach(cb => {
                const userId = parseInt(cb.value);
                this.selectedUsers.add(userId);
                cb.checked = true;
            });
            this.updateUI();
        }

        deselectAll() {
            this.selectedUsers.clear();
            const checkboxes = this.container.querySelectorAll('.user-selection-item input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);
            this.updateUI();
        }

        updateUI() {
            // Update selected count
            const countEl = this.container.querySelector('[data-count="selected"]');
            if (countEl) {
                countEl.textContent = this.selectedUsers.size;
            }

            // Update summary
            const summarySection = this.container.querySelector('[data-role="summary"]');
            if (summarySection) {
                summarySection.style.display = this.selectedUsers.size > 0 ? '' : 'none';
            }

            // Update submit button
            const submitBtn = this.container.querySelector('[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = this.selectedUsers.size === 0 || this.isSubmitting;
            }
        }

        updateExpirationUI() {
            const dateInput = this.container.querySelector('[name="expires_at"]');
            if (dateInput) {
                const wrapper = dateInput.closest('.mt-2');
                if (wrapper) {
                    wrapper.style.display = this.expiresType === 'date' ? '' : 'none';
                }
                dateInput.required = this.expiresType === 'date';
            }
        }

        async submitForm() {
            if (this.selectedUsers.size === 0 || this.isSubmitting) return;

            this.isSubmitting = true;
            this.updateUI();

            try {
                const url = this.container.querySelector('#bulkAssignForm').dataset.submitUrl;
                const response = await Vodo.api.post(url, {
                    users: Array.from(this.selectedUsers),
                    expires_at: this.expiresType === 'date' ? this.expiresAt : null,
                    notify_users: this.notifyUsers
                });

                if (response.success) {
                    Vodo.notifications.success(response.message || 'Users assigned successfully');
                    const redirectUrl = this.container.dataset.redirectUrl;
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                }
            } catch (error) {
                Vodo.notifications.error(error.message || 'Failed to assign users');
            } finally {
                this.isSubmitting = false;
                this.updateUI();
            }
        }
    };

    /**
     * Role Compare Component
     */
    Vodo.permissions.RoleCompare = class {
        constructor(container, config) {
            this.container = container;
            this.comparison = config.comparison || null;
            this.roles = config.roles || [];
            this.selectedRoles = config.roles ? config.roles.map(r => r.id) : [];
            this.viewFilter = 'all';
            this.searchQuery = '';
            this.sections = { common: true };

            this.init();
        }

        init() {
            if (this.comparison) {
                this.comparison.roles.forEach(role => {
                    this.sections['unique-' + role.id] = true;
                });
            }
            this.bindEvents();
            this.updateUI();
        }

        bindEvents() {
            // Role selection checkboxes
            this.container.addEventListener('change', (e) => {
                if (e.target.matches('.role-selector-item input[type="checkbox"]')) {
                    const roleId = parseInt(e.target.value);
                    this.toggleRole(roleId);
                }
            });

            // Compare button
            const compareBtn = this.container.querySelector('[data-action="compare"]');
            if (compareBtn) {
                compareBtn.addEventListener('click', () => this.compare());
            }

            // Section toggles
            this.container.addEventListener('click', (e) => {
                const sectionHeader = e.target.closest('.section-header');
                if (sectionHeader) {
                    const section = sectionHeader.dataset.section;
                    this.toggleSection(section);
                }
            });

            // Export button
            const exportBtn = this.container.querySelector('[data-action="export"]');
            if (exportBtn) {
                exportBtn.addEventListener('click', () => this.exportComparison());
            }
        }

        toggleRole(roleId) {
            const index = this.selectedRoles.indexOf(roleId);
            if (index > -1) {
                this.selectedRoles.splice(index, 1);
            } else if (this.selectedRoles.length < 5) {
                this.selectedRoles.push(roleId);
            }
            this.updateUI();
        }

        compare() {
            if (this.selectedRoles.length < 2) return;

            const params = new URLSearchParams();
            this.selectedRoles.forEach(id => params.append('roles[]', id));

            const baseUrl = this.container.dataset.compareUrl;
            window.location.href = `${baseUrl}?${params.toString()}`;
        }

        toggleSection(key) {
            this.sections[key] = !this.sections[key];
            this.updateUI();
        }

        updateUI() {
            // Update selected count
            const countEl = this.container.querySelector('[data-count="selected"]');
            if (countEl) {
                countEl.textContent = this.selectedRoles.length + ' roles selected';
            }

            // Update compare button
            const compareBtn = this.container.querySelector('[data-action="compare"]');
            if (compareBtn) {
                compareBtn.disabled = this.selectedRoles.length < 2;
            }

            // Update section visibility
            Object.entries(this.sections).forEach(([key, expanded]) => {
                const section = this.container.querySelector(`[data-section="${key}"]`);
                if (section) {
                    const content = section.querySelector('.section-content');
                    const chevron = section.querySelector('.section-chevron');
                    if (content) content.style.display = expanded ? '' : 'none';
                    if (chevron) chevron.classList.toggle('expanded', expanded);
                }
            });
        }

        exportComparison() {
            if (!this.comparison) return;

            const data = {
                roles: this.comparison.roles.map(r => r.name),
                common: this.comparison.common,
                unique: {}
            };

            this.comparison.roles.forEach(role => {
                data.unique[role.name] = this.comparison.unique[role.id];
            });

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'role-comparison.json';
            a.click();
            URL.revokeObjectURL(url);
        }
    };

    /**
     * User Permissions Component
     */
    Vodo.permissions.UserPermissions = class {
        constructor(container, config) {
            this.container = container;
            this.effectiveCount = config.effectiveCount || 0;
            this.fromRolesCount = config.fromRolesCount || 0;
            this.grantedOverrides = config.grantedOverrides || 0;
            this.deniedOverrides = config.deniedOverrides || 0;
            this.expandedGroups = {};
            this.expandAllGroups = false;
            this.searchQuery = '';

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Group toggles
            this.container.addEventListener('click', (e) => {
                const groupHeader = e.target.closest('.permission-group-header');
                if (groupHeader) {
                    const slug = groupHeader.dataset.group;
                    this.toggleGroup(slug);
                }

                // Expand/collapse all
                const expandBtn = e.target.closest('[data-action="expand-all"]');
                if (expandBtn) {
                    this.expandAllGroups = !this.expandAllGroups;
                    this.updateUI();
                }
            });

            // Search
            const searchInput = this.container.querySelector('[data-role="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    this.searchQuery = e.target.value;
                    this.updateVisibility();
                });
            }

            // Modal handling
            this.setupModals();
        }

        setupModals() {
            // Add role modal
            const addRoleBtn = this.container.querySelector('[data-action="show-add-role"]');
            const addRoleModal = this.container.querySelector('#addRoleModal');
            if (addRoleBtn && addRoleModal) {
                addRoleBtn.addEventListener('click', () => {
                    addRoleModal.style.display = 'flex';
                });
            }

            // Add override modal
            const addOverrideBtn = this.container.querySelector('[data-action="show-add-override"]');
            const addOverrideModal = this.container.querySelector('#addOverrideModal');
            if (addOverrideBtn && addOverrideModal) {
                addOverrideBtn.addEventListener('click', () => {
                    addOverrideModal.style.display = 'flex';
                });
            }

            // Close modal buttons
            this.container.querySelectorAll('.modal-close, .modal-backdrop').forEach(el => {
                el.addEventListener('click', () => {
                    this.container.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
                });
            });
        }

        toggleGroup(slug) {
            this.expandedGroups[slug] = !this.expandedGroups[slug];
            this.updateUI();
        }

        updateUI() {
            // Update groups expansion
            this.container.querySelectorAll('.permission-group-view').forEach(group => {
                const slug = group.dataset.group;
                const isExpanded = this.expandAllGroups || this.expandedGroups[slug];
                const content = group.querySelector('.permission-group-items');
                const chevron = group.querySelector('.group-chevron');
                
                if (content) content.style.display = isExpanded ? '' : 'none';
                if (chevron) chevron.classList.toggle('expanded', isExpanded);
            });
        }

        updateVisibility() {
            const query = this.searchQuery.toLowerCase();
            
            this.container.querySelectorAll('.effective-permission-item').forEach(item => {
                const slug = item.dataset.slug || '';
                const visible = !query || slug.toLowerCase().includes(query);
                item.style.display = visible ? '' : 'none';
            });
        }
    };

    /**
     * Access Rule Form Component
     */
    Vodo.permissions.AccessRuleForm = class {
        constructor(container, config) {
            this.container = container;
            this.form = {
                name: config.rule?.name || '',
                description: config.rule?.description || '',
                priority: config.rule?.priority || 10,
                is_active: config.rule?.is_active ?? true,
                target_permissions: config.rule?.target_permissions || [],
                conditions: config.rule?.conditions || [],
                action: config.rule?.action || 'deny'
            };
            this.permissions = config.permissions || [];
            this.conditionTypes = config.conditionTypes || [];
            this.isSubmitting = false;

            this.init();
        }

        init() {
            this.bindEvents();
            this.renderConditions();
        }

        bindEvents() {
            // Add condition button
            const addConditionBtn = this.container.querySelector('[data-action="add-condition"]');
            if (addConditionBtn) {
                addConditionBtn.addEventListener('click', () => this.addCondition());
            }

            // Form submission
            const form = this.container.querySelector('#ruleForm');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.submitForm();
                });
            }

            // Permission toggles
            this.container.addEventListener('change', (e) => {
                if (e.target.matches('.permission-check-item input[type="checkbox"]')) {
                    const slug = e.target.dataset.slug;
                    this.togglePermission(slug);
                }
            });
        }

        addCondition() {
            this.form.conditions.push({
                type: 'time',
                operator: 'between',
                value: { start: '09:00', end: '17:00' }
            });
            this.renderConditions();
        }

        removeCondition(index) {
            this.form.conditions.splice(index, 1);
            this.renderConditions();
        }

        togglePermission(slug) {
            const index = this.form.target_permissions.indexOf(slug);
            if (index > -1) {
                this.form.target_permissions.splice(index, 1);
            } else {
                this.form.target_permissions.push(slug);
            }
            this.updateSelectedPermissions();
        }

        renderConditions() {
            const conditionsList = this.container.querySelector('.conditions-list');
            if (!conditionsList) return;

            conditionsList.innerHTML = this.form.conditions.map((condition, index) => `
                <div class="condition-row" data-index="${index}">
                    <select class="form-select condition-type" data-field="type">
                        ${this.conditionTypes.map(type => `
                            <option value="${type.key}" ${condition.type === type.key ? 'selected' : ''}>
                                ${type.label}
                            </option>
                        `).join('')}
                    </select>
                    <button type="button" class="btn-link danger" data-action="remove-condition" data-index="${index}">
                        Remove
                    </button>
                </div>
            `).join('');

            // Bind remove buttons
            conditionsList.querySelectorAll('[data-action="remove-condition"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    this.removeCondition(parseInt(btn.dataset.index));
                });
            });
        }

        updateSelectedPermissions() {
            const container = this.container.querySelector('.selected-permissions .permission-tags');
            if (!container) return;

            container.innerHTML = this.form.target_permissions.map((target, index) => `
                <span class="permission-tag">
                    <code>${target}</code>
                    <button type="button" data-action="remove-target" data-index="${index}">×</button>
                </span>
            `).join('');
        }

        async submitForm() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                const url = this.container.querySelector('#ruleForm').action;
                const method = this.container.querySelector('#ruleForm').dataset.method || 'post';

                const response = await Vodo.api[method](url, this.form);

                if (response.success) {
                    Vodo.notifications.success(response.message || 'Rule saved');
                    const redirectUrl = this.container.dataset.redirectUrl;
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                }
            } catch (error) {
                Vodo.notifications.error(error.message || 'Failed to save rule');
            } finally {
                this.isSubmitting = false;
            }
        }
    };

    /**
     * Initialize components on page load and PJAX navigation
     */
    Vodo.permissions.init = function() {
        // Role form
        const roleForm = document.querySelector('.role-form[data-component="role-form"]');
        if (roleForm && roleForm.dataset.config) {
            try {
                const config = JSON.parse(roleForm.dataset.config);
                new Vodo.permissions.RoleForm(roleForm, config);
            } catch (e) {
                console.error('Failed to initialize RoleForm:', e);
            }
        }

        // Permission matrix
        const matrix = document.querySelector('.permission-matrix-page[data-component="matrix"]');
        if (matrix && matrix.dataset.config) {
            try {
                const config = JSON.parse(matrix.dataset.config);
                new Vodo.permissions.PermissionMatrix(matrix, config);
            } catch (e) {
                console.error('Failed to initialize PermissionMatrix:', e);
            }
        }

        // Bulk assign
        const bulkAssign = document.querySelector('.bulk-assign-page[data-component="bulk-assign"]');
        if (bulkAssign && bulkAssign.dataset.config) {
            try {
                const config = JSON.parse(bulkAssign.dataset.config);
                new Vodo.permissions.BulkAssign(bulkAssign, config);
            } catch (e) {
                console.error('Failed to initialize BulkAssign:', e);
            }
        }

        // Role compare
        const roleCompare = document.querySelector('.role-compare-page[data-component="role-compare"]');
        if (roleCompare && roleCompare.dataset.config) {
            try {
                const config = JSON.parse(roleCompare.dataset.config);
                new Vodo.permissions.RoleCompare(roleCompare, config);
            } catch (e) {
                console.error('Failed to initialize RoleCompare:', e);
            }
        }

        // User permissions
        const userPerms = document.querySelector('.user-permissions-page[data-component="user-permissions"]');
        if (userPerms && userPerms.dataset.config) {
            try {
                const config = JSON.parse(userPerms.dataset.config);
                new Vodo.permissions.UserPermissions(userPerms, config);
            } catch (e) {
                console.error('Failed to initialize UserPermissions:', e);
            }
        }

        // Access rule form
        const ruleForm = document.querySelector('.access-rule-form-page[data-component="rule-form"]');
        if (ruleForm && ruleForm.dataset.config) {
            try {
                const config = JSON.parse(ruleForm.dataset.config);
                new Vodo.permissions.AccessRuleForm(ruleForm, config);
            } catch (e) {
                console.error('Failed to initialize AccessRuleForm:', e);
            }
        }
    };

    // Auto-initialize on DOM ready and PJAX
    document.addEventListener('DOMContentLoaded', Vodo.permissions.init);
    document.addEventListener('pjax:complete', Vodo.permissions.init);

})(window);

