{{-- Role Form Partial - Matching SCREENS.md Wireframe --}}
{{-- Uses Vodo.permissions.RoleForm (vanilla JS, no Alpine) --}}

@php
$configData = [
    'role' => $role ?? null,
    'permissions' => $permissions ?? [],
    'selectedPermissions' => $selectedPermissions ?? [],
    'inheritedPermissions' => $inheritedPermissions ?? [],
    'lockedPermissions' => $lockedPermissions ?? [],
    'roles' => $parentRoles ?? []
];
$selectedCount = count($selectedPermissions ?? []);
$inheritedCount = count($inheritedPermissions ?? []);
$lockedCount = count($lockedPermissions ?? []);
$totalSelected = $selectedCount + $inheritedCount;
$parentRoleName = null;
if (isset($role) && $role->parent_id && isset($parentRoles)) {
    $parent = collect($parentRoles)->firstWhere('id', $role->parent_id);
    $parentRoleName = $parent->name ?? null;
}
@endphp

<div class="role-form" 
     data-component="role-form"
     data-config="{{ json_encode($configData) }}"
     data-api-url="{{ url('admin/system/permissions/api/roles') }}">
    
    {{-- Role Details Section --}}
    <div class="form-section">
        <div class="form-section-header">
            <h3>Role Details</h3>
        </div>
        <div class="form-section-body">
            {{-- Name and Slug Row --}}
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label required">Role Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-input"
                           value="{{ old('name', $role->name ?? '') }}"
                           placeholder="Manager"
                           required>
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
                           placeholder="manager"
                           {{ isset($role) && $role->is_system ? 'readonly' : '' }}>
                    @error('slug')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Description --}}
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description"
                          name="description"
                          class="form-textarea"
                          rows="2"
                          placeholder="Department managers with elevated access to reports and team mgmt">{{ old('description', $role->description ?? '') }}</textarea>
                @error('description')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Inherits From and Color Row --}}
            <div class="form-row">
                <div class="form-group">
                    <label for="parent_id" class="form-label">Inherits From</label>
                    <select id="parent_id"
                            name="parent_id"
                            class="form-select">
                        <option value="">-- No inheritance --</option>
                        @foreach($parentRoles ?? [] as $parentRole)
                            @if(!isset($role) || $parentRole->id !== $role->id)
                                <option value="{{ $parentRole->id }}"
                                        {{ old('parent_id', $role->parent_id ?? '') == $parentRole->id ? 'selected' : '' }}>
                                    {{ $parentRole->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('parent_id')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Color</label>
                    <div class="color-picker">
                        @php
                            $colors = [
                                '#10b981' => 'Green',
                                '#6366f1' => 'Indigo',
                                '#8b5cf6' => 'Violet',
                                '#ec4899' => 'Pink',
                                '#ef4444' => 'Red',
                                '#f97316' => 'Orange',
                                '#eab308' => 'Yellow',
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
            </div>

            {{-- Default Role Checkbox --}}
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox"
                           name="is_default"
                           value="1"
                           {{ old('is_default', $role->is_default ?? false) ? 'checked' : '' }}>
                    <span class="checkbox-text">Set as default role for new users</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Privilege Escalation Warning --}}
    @if($lockedCount > 0)
    <div class="alert alert-warning" data-role="locked-warning">
        <div class="alert-icon">
            @include('backend.partials.icon', ['icon' => 'alertTriangle'])
        </div>
        <div class="alert-content">
            <h4>⚠️ Privilege Escalation Warning</h4>
            <p>
                You cannot grant permissions you don't have. Locked permissions are marked with 🔒 and cannot be assigned.
            </p>
        </div>
    </div>
    @endif

    {{-- Permissions Section --}}
    <div class="form-section">
        <div class="form-section-header">
            <h3>Permissions</h3>
        </div>
        <div class="form-section-body">
            {{-- Search and Filters Toolbar --}}
            <div class="permission-filters">
                <div class="search-input-wrapper" style="flex: 1; max-width: 300px;">
                    @include('backend.partials.icon', ['icon' => 'search'])
                    <input type="text"
                           class="search-input"
                           placeholder="Search permissions..."
                           data-role="permission-search"
                           style="width: 100%;">
                </div>

                <select class="filter-select" data-role="group-filter">
                    <option value="">Group ▼</option>
                    {{-- Populated by JS --}}
                </select>

                <select class="filter-select" data-role="plugin-filter">
                    <option value="">Plugin ▼</option>
                    <option value="core">Core</option>
                    @foreach($plugins ?? [] as $plugin)
                        <option value="{{ $plugin->slug }}">{{ $plugin->name }}</option>
                    @endforeach
                </select>

                <button type="button" class="btn-secondary btn-sm" data-action="select-all">
                    Select All
                </button>
            </div>

            {{-- Permission Stats Summary --}}
            <div class="permission-summary-bar" style="padding: 12px 16px; background: var(--bg-surface-2, #f9fafb); border-radius: 8px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <strong data-stat="selected">{{ $totalSelected }}</strong> permissions selected
                        (<span data-stat="direct">{{ $selectedCount }}</span> direct<span id="inheritedStat">{{ $inheritedCount > 0 ? ", {$inheritedCount} inherited" . ($parentRoleName ? " from {$parentRoleName}" : '') : '' }}</span>)
                    </div>
                    @if($lockedCount > 0)
                    <div style="color: #f59e0b;">
                        ⚠ <span data-stat="locked">{{ $lockedCount }}</span> permissions locked (beyond your access level)
                    </div>
                    @endif
                </div>
            </div>

            {{-- Permission Groups --}}
            <div class="permission-groups">
                @foreach($permissions as $group)
                @php
                    $groupPermissions = $group['permissions'] ?? [];
                    $groupSelectedCount = 0;
                    foreach ($groupPermissions as $perm) {
                        if (in_array($perm['id'], $selectedPermissions ?? []) || in_array($perm['id'], $inheritedPermissions ?? [])) {
                            $groupSelectedCount++;
                        }
                    }
                @endphp
                <div class="permission-group" data-group-slug="{{ $group['slug'] }}">
                    {{-- Group Header --}}
                    <button type="button" class="permission-group-header" data-action="toggle-group" data-slug="{{ $group['slug'] }}">
                        <span class="group-toggle-icon" style="transition: transform 0.2s;">▶</span>
                        <span class="group-icon">
                            @include('backend.partials.icon', ['icon' => 'folder'])
                        </span>
                        <span class="group-name">{{ $group['label'] ?? $group['name'] ?? $group['slug'] }}</span>
                        <span class="group-count">({{ count($groupPermissions) }} permissions)</span>
                        @if(isset($group['plugin']) && $group['plugin'])
                            <span class="group-plugin">{{ $group['plugin'] }}</span>
                        @endif
                        <button type="button" class="btn-link btn-sm" data-action="select-group" data-slug="{{ $group['slug'] }}" style="margin-left: auto;">
                            [Select All]
                        </button>
                    </button>

                    {{-- Group Content (Permissions List) --}}
                    <div class="permission-group-content" style="display: none;">
                        <div style="border: 1px solid var(--border-color, #e5e7eb); border-radius: 6px; overflow: hidden;">
                            @foreach($groupPermissions as $perm)
                            @php
                                $permId = $perm['id'];
                                $isSelected = in_array($permId, $selectedPermissions ?? []);
                                $isInherited = in_array($permId, $inheritedPermissions ?? []);
                                $isLocked = in_array($permId, $lockedPermissions ?? []);
                                $isDangerous = $perm['is_dangerous'] ?? false;
                                $hasDeps = !empty($perm['dependencies']);
                                $permSlug = $perm['slug'] ?? '';
                                $permLabel = $perm['label'] ?? $perm['name'] ?? $perm['slug'];
                            @endphp
                            <div class="permission-row" style="display: flex; align-items: center; padding: 10px 16px; border-bottom: 1px solid var(--border-color, #e5e7eb); {{ $isDangerous ? 'background: rgba(239, 68, 68, 0.03);' : '' }}">
                                {{-- Checkbox --}}
                                <div style="width: 30px;">
                                    @if($isLocked)
                                        <span title="Locked - You cannot grant this permission">🔒</span>
                                    @else
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permId }}"
                                               {{ ($isSelected || $isInherited) ? 'checked' : '' }}
                                               {{ $isInherited ? 'disabled' : '' }}
                                               data-action="toggle-permission"
                                               data-id="{{ $permId }}"
                                               style="width: 18px; height: 18px; accent-color: var(--color-accent, #6366f1);">
                                    @endif
                                </div>
                                
                                {{-- Permission Slug --}}
                                <div style="width: 200px; font-family: monospace; font-size: 13px; color: var(--text-primary);">
                                    {{ $permSlug }}
                                </div>
                                
                                {{-- Permission Label/Description --}}
                                <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
                                    <span>{{ $permLabel }}</span>
                                    @if($hasDeps)
                                        <span title="Requires: {{ implode(', ', $perm['dependencies']) }}" style="color: var(--color-accent, #6366f1);">
                                            🔗→{{ implode(',', array_map(fn($d) => explode('.', $d)[1] ?? $d, $perm['dependencies'])) }}
                                        </span>
                                    @endif
                                </div>
                                
                                {{-- Status --}}
                                <div style="width: 120px; text-align: right; font-size: 12px;">
                                    @if($isInherited)
                                        <span style="color: #8b5cf6;">Inherited</span>
                                    @elseif($isLocked)
                                        <span style="color: #ef4444;">(locked)</span>
                                    @elseif($isSelected)
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

                @if(empty($permissions))
                <div class="empty-state" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                    <p>No permissions available.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
// Role Form - Vanilla JS Implementation
(function() {
    function initRoleForm() {
        var container = document.querySelector('.role-form[data-component="role-form"]');
        if (!container || container.dataset.initialized) return;
        
        var config;
        try {
            config = JSON.parse(container.dataset.config);
        } catch(e) {
            console.error('Failed to parse role form config', e);
            return;
        }
        
        var selectedPermissions = new Set(config.selectedPermissions || []);
        var inheritedPermissions = new Set(config.inheritedPermissions || []);
        var lockedPermissions = new Set(config.lockedPermissions || []);
        var expandedGroups = new Set();
        
        // Toggle group expand/collapse
        container.addEventListener('click', function(e) {
            var toggleBtn = e.target.closest('[data-action="toggle-group"]');
            if (toggleBtn) {
                e.preventDefault();
                var slug = toggleBtn.dataset.slug;
                var groupEl = container.querySelector('.permission-group[data-group-slug="' + slug + '"]');
                if (groupEl) {
                    var content = groupEl.querySelector('.permission-group-content');
                    var icon = toggleBtn.querySelector('.group-toggle-icon');
                    if (content.style.display === 'none') {
                        content.style.display = 'block';
                        expandedGroups.add(slug);
                        if (icon) icon.textContent = '▼';
                        groupEl.classList.add('expanded');
                    } else {
                        content.style.display = 'none';
                        expandedGroups.delete(slug);
                        if (icon) icon.textContent = '▶';
                        groupEl.classList.remove('expanded');
                    }
                }
                return;
            }
            
            // Select group all
            var selectGroupBtn = e.target.closest('[data-action="select-group"]');
            if (selectGroupBtn) {
                e.preventDefault();
                e.stopPropagation();
                var slug = selectGroupBtn.dataset.slug;
                var groupEl = container.querySelector('.permission-group[data-group-slug="' + slug + '"]');
                if (groupEl) {
                    groupEl.querySelectorAll('input[type="checkbox"][data-action="toggle-permission"]:not(:disabled)').forEach(function(cb) {
                        cb.checked = true;
                        selectedPermissions.add(parseInt(cb.dataset.id));
                    });
                    updateStats();
                }
                return;
            }
            
            // Select all
            var selectAllBtn = e.target.closest('[data-action="select-all"]');
            if (selectAllBtn) {
                container.querySelectorAll('input[type="checkbox"][data-action="toggle-permission"]:not(:disabled)').forEach(function(cb) {
                    cb.checked = true;
                    selectedPermissions.add(parseInt(cb.dataset.id));
                });
                updateStats();
                return;
            }
        });
        
        // Permission checkbox changes
        container.addEventListener('change', function(e) {
            if (e.target.matches('[data-action="toggle-permission"]')) {
                var id = parseInt(e.target.dataset.id);
                if (e.target.checked) {
                    selectedPermissions.add(id);
                } else {
                    selectedPermissions.delete(id);
                }
                updateStats();
            }
        });
        
        // Search filter
        var searchInput = container.querySelector('[data-role="permission-search"]');
        if (searchInput) {
            var debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    var query = searchInput.value.toLowerCase();
                    container.querySelectorAll('.permission-row').forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        row.style.display = !query || text.includes(query) ? '' : 'none';
                    });
                    // Auto-expand groups with matches
                    if (query) {
                        container.querySelectorAll('.permission-group').forEach(function(group) {
                            var visibleRows = group.querySelectorAll('.permission-row:not([style*="display: none"])');
                            if (visibleRows.length > 0) {
                                var content = group.querySelector('.permission-group-content');
                                var icon = group.querySelector('.group-toggle-icon');
                                if (content) content.style.display = 'block';
                                if (icon) icon.textContent = '▼';
                                group.classList.add('expanded');
                            }
                        });
                    }
                }, 200);
            });
        }
        
        // Group filter
        var groupFilter = container.querySelector('[data-role="group-filter"]');
        if (groupFilter) {
            // Populate options
            container.querySelectorAll('.permission-group').forEach(function(group) {
                var slug = group.dataset.groupSlug;
                var name = group.querySelector('.group-name')?.textContent || slug;
                if (!groupFilter.querySelector('option[value="' + slug + '"]')) {
                    var opt = document.createElement('option');
                    opt.value = slug;
                    opt.textContent = name;
                    groupFilter.appendChild(opt);
                }
            });
            
            groupFilter.addEventListener('change', function() {
                var value = groupFilter.value;
                container.querySelectorAll('.permission-group').forEach(function(group) {
                    group.style.display = !value || group.dataset.groupSlug === value ? '' : 'none';
                });
            });
        }
        
        // Slug auto-generation
        var nameInput = container.querySelector('#name');
        var slugInput = container.querySelector('#slug');
        if (nameInput && slugInput && !config.role?.is_system) {
            nameInput.addEventListener('input', function() {
                if (!slugInput.value || slugInput.dataset.autoGenerated) {
                    slugInput.value = nameInput.value.toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/(^-|-$)/g, '');
                    slugInput.dataset.autoGenerated = 'true';
                }
            });
            slugInput.addEventListener('input', function() {
                delete slugInput.dataset.autoGenerated;
            });
        }
        
        function updateStats() {
            var totalSelected = selectedPermissions.size + inheritedPermissions.size;
            var directCount = selectedPermissions.size;
            
            var selectedEl = container.querySelector('[data-stat="selected"]');
            var directEl = container.querySelector('[data-stat="direct"]');
            
            if (selectedEl) selectedEl.textContent = totalSelected;
            if (directEl) directEl.textContent = directCount;
        }
        
        container.dataset.initialized = 'true';
    }

    // Init on DOM ready
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initRoleForm, 0);
    } else {
        document.addEventListener('DOMContentLoaded', initRoleForm);
    }
    // Re-init on PJAX navigation
    document.addEventListener('pjax:complete', initRoleForm);
})();
</script>
