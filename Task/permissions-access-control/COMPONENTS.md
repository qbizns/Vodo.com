# Permissions & Access Control - UI Components

## Component Structure

```
resources/views/components/permissions/
├── role-card.blade.php
├── role-select.blade.php
├── permission-checkbox.blade.php
├── permission-group.blade.php
├── permission-tree.blade.php
├── permission-matrix.blade.php
├── permission-diff.blade.php
├── access-rule-builder.blade.php
├── condition-row.blade.php
├── user-roles-picker.blade.php
├── override-badge.blade.php
├── audit-entry.blade.php
├── privilege-warning.blade.php
├── dependency-indicator.blade.php
└── bulk-assign-modal.blade.php
```

---

## Core Components

### Permission Tree

```blade
{{-- resources/views/components/permissions/permission-tree.blade.php --}}
@props([
    'groups',
    'selectedPermissions' => [],
    'inheritedPermissions' => [],
    'disabled' => false,
    'showDependencies' => true,
    'userPermissions' => null, // For privilege escalation check
])

<div x-data="permissionTree(@js($selectedPermissions), @js($inheritedPermissions), @js($userPermissions))" 
     class="permission-tree">
    
    <div class="permission-tree__header">
        <input type="text" 
               x-model="search" 
               placeholder="Search permissions..."
               class="permission-tree__search">
        
        <div class="permission-tree__actions">
            <button type="button" @click="expandAll()" class="btn-link">Expand All</button>
            <button type="button" @click="collapseAll()" class="btn-link">Collapse All</button>
            @unless($disabled)
                <button type="button" @click="selectAll()" class="btn-link">Select All</button>
                <button type="button" @click="deselectAll()" class="btn-link">Deselect All</button>
            @endunless
        </div>
    </div>
    
    <div class="permission-tree__stats">
        <span x-text="selectedCount"></span> of <span>{{ collect($groups)->sum(fn($g) => count($g['permissions'])) }}</span> selected
        <template x-if="inheritedCount > 0">
            <span class="text-gray-500">(<span x-text="inheritedCount"></span> inherited)</span>
        </template>
        <template x-if="unauthorizedCount > 0">
            <span class="text-amber-600">
                <x-icon name="alert-triangle" class="w-4 h-4 inline" />
                <span x-text="unauthorizedCount"></span> beyond your permissions
            </span>
        </template>
    </div>
    
    {{-- Privilege escalation warning --}}
    <template x-if="unauthorizedCount > 0">
        <x-permissions.privilege-warning />
    </template>
    
    <div class="permission-tree__groups">
        @foreach($groups as $group)
            <div class="permission-group" 
                 x-data="{ open: false }"
                 data-group="{{ $group['slug'] }}"
                 x-show="groupMatchesSearch('{{ $group['slug'] }}')"
                 :class="{ 'permission-group--open': open }">
                
                <button type="button" 
                        @click="open = !open"
                        class="permission-group__header">
                    <x-icon name="chevron-right" class="permission-group__chevron" />
                    <x-icon :name="$group['icon'] ?? 'folder'" class="permission-group__icon" />
                    <span class="permission-group__name">{{ $group['label'] }}</span>
                    
                    <span class="permission-group__count">
                        <span x-text="getGroupSelectedCount('{{ $group['slug'] }}')"></span>/{{ count($group['permissions']) }}
                    </span>
                    
                    @if($group['plugin'] ?? null)
                        <span class="permission-group__plugin">{{ $group['plugin'] }}</span>
                    @endif
                    
                    @unless($disabled)
                        <button type="button" 
                                @click.stop="toggleGroup('{{ $group['slug'] }}')"
                                class="permission-group__toggle">
                            Toggle All
                        </button>
                    @endunless
                </button>
                
                <div x-show="open" x-collapse class="permission-group__content">
                    @foreach($group['permissions'] as $permission)
                        <label class="permission-item"
                               :class="{ 
                                   'permission-item--inherited': isInherited({{ $permission['id'] }}),
                                   'permission-item--unauthorized': !canGrant({{ $permission['id'] }}),
                                   'permission-item--dangerous': {{ $permission['is_dangerous'] ?? false ? 'true' : 'false' }}
                               }"
                               x-show="permissionMatchesSearch('{{ $permission['name'] }}', '{{ $permission['label'] }}')">
                            
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permission['id'] }}"
                                   x-model="selected"
                                   :disabled="@js($disabled) || isInherited({{ $permission['id'] }}) || !canGrant({{ $permission['id'] }})"
                                   class="permission-item__checkbox">
                            
                            <div class="permission-item__info">
                                <span class="permission-item__name">{{ $permission['label'] }}</span>
                                <span class="permission-item__key">{{ $permission['name'] }}</span>
                                
                                @if($showDependencies && !empty($permission['dependencies']))
                                    <x-permissions.dependency-indicator :dependencies="$permission['dependencies']" />
                                @endif
                            </div>
                            
                            <template x-if="isInherited({{ $permission['id'] }})">
                                <span class="permission-item__inherited">
                                    Inherited from <span x-text="getInheritedFrom({{ $permission['id'] }})"></span>
                                </span>
                            </template>
                            
                            <template x-if="!canGrant({{ $permission['id'] }})">
                                <span class="permission-item__unauthorized" title="You don't have this permission">
                                    <x-icon name="lock" class="w-4 h-4 text-amber-500" />
                                </span>
                            </template>
                            
                            @if($permission['is_dangerous'] ?? false)
                                <span class="permission-item__dangerous" title="This is a dangerous permission">
                                    <x-icon name="alert-triangle" class="w-4 h-4 text-red-500" />
                                </span>
                            @endif
                            
                            @if($permission['description'] ?? null)
                                <span class="permission-item__description" title="{{ $permission['description'] }}">
                                    <x-icon name="info" class="w-4 h-4 text-gray-400" />
                                </span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
function permissionTree(initialSelected, inherited, userPermissions) {
    return {
        selected: initialSelected.map(Number),
        inherited: inherited,
        userPermissions: userPermissions ? userPermissions.map(Number) : null,
        search: '',
        expandedGroups: [],
        
        get selectedCount() {
            return this.selected.length;
        },
        
        get inheritedCount() {
            return Object.keys(this.inherited).length;
        },
        
        get unauthorizedCount() {
            if (!this.userPermissions) return 0;
            return this.selected.filter(id => !this.userPermissions.includes(id) && !this.isInherited(id)).length;
        },
        
        isInherited(permissionId) {
            return this.inherited.hasOwnProperty(permissionId);
        },
        
        canGrant(permissionId) {
            // If no user permissions provided (super admin), allow all
            if (!this.userPermissions) return true;
            return this.userPermissions.includes(permissionId);
        },
        
        getInheritedFrom(permissionId) {
            return this.inherited[permissionId] || 'Parent Role';
        },
        
        getGroupSelectedCount(groupSlug) {
            const group = document.querySelector(`[data-group="${groupSlug}"]`);
            if (!group) return 0;
            const permIds = [...group.querySelectorAll('input[type="checkbox"]')].map(i => parseInt(i.value));
            return permIds.filter(id => this.selected.includes(id) || this.isInherited(id)).length;
        },
        
        toggleGroup(groupSlug) {
            const group = document.querySelector(`[data-group="${groupSlug}"]`);
            const permIds = [...group.querySelectorAll('input:not(:disabled)')].map(i => parseInt(i.value));
            const allSelected = permIds.every(id => this.selected.includes(id));
            
            if (allSelected) {
                this.selected = this.selected.filter(id => !permIds.includes(id));
            } else {
                this.selected = [...new Set([...this.selected, ...permIds])];
            }
        },
        
        selectAll() {
            const all = [...document.querySelectorAll('.permission-tree input:not(:disabled)')].map(i => parseInt(i.value));
            this.selected = [...new Set([...this.selected, ...all])];
        },
        
        deselectAll() {
            const inherited = Object.keys(this.inherited).map(Number);
            this.selected = inherited;
        },
        
        groupMatchesSearch(groupSlug) {
            if (!this.search) return true;
            const group = document.querySelector(`[data-group="${groupSlug}"]`);
            if (!group) return true;
            const term = this.search.toLowerCase();
            const permissions = group.querySelectorAll('.permission-item');
            return [...permissions].some(p => {
                const name = p.querySelector('.permission-item__name')?.textContent.toLowerCase() || '';
                const key = p.querySelector('.permission-item__key')?.textContent.toLowerCase() || '';
                return name.includes(term) || key.includes(term);
            });
        },
        
        permissionMatchesSearch(name, label) {
            if (!this.search) return true;
            const term = this.search.toLowerCase();
            return name.toLowerCase().includes(term) || label.toLowerCase().includes(term);
        },
        
        expandAll() {
            document.querySelectorAll('.permission-group').forEach(g => {
                if (g.__x) g.__x.$data.open = true;
            });
        },
        
        collapseAll() {
            document.querySelectorAll('.permission-group').forEach(g => {
                if (g.__x) g.__x.$data.open = false;
            });
        },
    };
}
</script>
```

### Permission Matrix Component

```blade
{{-- resources/views/components/permissions/permission-matrix.blade.php --}}
@props([
    'roles',
    'permissions',
    'matrix',
])

<div x-data="permissionMatrix(@js($matrix))" class="permission-matrix">
    <div class="permission-matrix__toolbar">
        <input type="text" 
               x-model="search" 
               placeholder="Search permissions..."
               class="permission-matrix__search">
        
        <div class="permission-matrix__filters">
            <select x-model="groupFilter" class="permission-matrix__group-filter">
                <option value="">All Groups</option>
                @foreach($permissions->groupBy('group') as $group => $perms)
                    <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                @endforeach
            </select>
            
            <label class="permission-matrix__show-changes">
                <input type="checkbox" x-model="showChangesOnly">
                Show changes only
            </label>
        </div>
        
        <div class="permission-matrix__actions">
            <button type="button" @click="saveChanges()" :disabled="!hasChanges" class="btn btn-primary">
                Save Changes (<span x-text="changeCount"></span>)
            </button>
            <button type="button" @click="resetChanges()" :disabled="!hasChanges" class="btn btn-secondary">
                Reset
            </button>
        </div>
    </div>
    
    <div class="permission-matrix__container">
        <table class="permission-matrix__table">
            <thead>
                <tr>
                    <th class="permission-matrix__header-permission">Permission</th>
                    @foreach($roles as $role)
                        <th class="permission-matrix__header-role" 
                            style="--role-color: {{ $role->color }}">
                            <div class="permission-matrix__role-name">
                                <x-icon :name="$role->icon ?? 'shield'" class="w-4 h-4" />
                                {{ $role->name }}
                            </div>
                            @if($role->parent)
                                <span class="text-xs text-gray-500">
                                    extends {{ $role->parent->name }}
                                </span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($permissions as $permission)
                    <tr x-show="permissionVisible('{{ $permission->name }}', '{{ $permission->group }}')"
                        :class="{ 'bg-amber-50': hasPermissionChanges({{ $permission->id }}) }">
                        <td class="permission-matrix__permission-cell">
                            <span class="permission-matrix__permission-name">{{ $permission->label }}</span>
                            <span class="permission-matrix__permission-key">{{ $permission->name }}</span>
                            @if($permission->is_dangerous)
                                <x-icon name="alert-triangle" class="w-4 h-4 text-red-500" />
                            @endif
                        </td>
                        @foreach($roles as $role)
                            @php
                                $key = "{$role->id}-{$permission->id}";
                                $isInherited = $role->parent && $role->parent->getAllPermissions()->contains('id', $permission->id);
                            @endphp
                            <td class="permission-matrix__cell"
                                :class="{ 
                                    'changed': isChanged('{{ $key }}'),
                                    'inherited': {{ $isInherited ? 'true' : 'false' }}
                                }">
                                @if($role->is_system)
                                    <x-icon name="check" class="w-5 h-5 text-gray-400" />
                                @elseif($isInherited)
                                    <span class="permission-matrix__inherited" title="Inherited from {{ $role->parent->name }}">
                                        <x-icon name="check" class="w-5 h-5 text-blue-400" />
                                    </span>
                                @else
                                    <button type="button"
                                            @click="toggle('{{ $key }}')"
                                            class="permission-matrix__toggle"
                                            :class="{ 'active': getValue('{{ $key }}') }">
                                        <x-icon x-show="getValue('{{ $key }}')" name="check" class="w-5 h-5 text-green-600" />
                                        <x-icon x-show="!getValue('{{ $key }}')" name="x" class="w-5 h-5 text-gray-300" />
                                    </button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function permissionMatrix(initialMatrix) {
    return {
        original: { ...initialMatrix },
        current: { ...initialMatrix },
        search: '',
        groupFilter: '',
        showChangesOnly: false,
        saving: false,
        
        get hasChanges() {
            return Object.keys(this.current).some(key => this.current[key] !== this.original[key]);
        },
        
        get changeCount() {
            return Object.keys(this.current).filter(key => this.current[key] !== this.original[key]).length;
        },
        
        getValue(key) {
            return this.current[key] ?? false;
        },
        
        toggle(key) {
            this.current[key] = !this.current[key];
        },
        
        isChanged(key) {
            return this.current[key] !== this.original[key];
        },
        
        hasPermissionChanges(permissionId) {
            return Object.keys(this.current)
                .filter(key => key.endsWith(`-${permissionId}`))
                .some(key => this.isChanged(key));
        },
        
        permissionVisible(name, group) {
            if (this.showChangesOnly) {
                const permId = name; // Would need actual ID mapping
                if (!this.hasPermissionChanges(permId)) return false;
            }
            
            if (this.groupFilter && group !== this.groupFilter) return false;
            
            if (this.search) {
                const term = this.search.toLowerCase();
                return name.toLowerCase().includes(term);
            }
            
            return true;
        },
        
        async saveChanges() {
            if (!this.hasChanges || this.saving) return;
            
            this.saving = true;
            
            const changes = {};
            Object.keys(this.current).forEach(key => {
                if (this.current[key] !== this.original[key]) {
                    changes[key] = this.current[key];
                }
            });
            
            try {
                const response = await fetch('{{ route("admin.permissions.matrix.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ changes }),
                });
                
                if (response.ok) {
                    this.original = { ...this.current };
                    // Show success notification
                }
            } catch (error) {
                console.error('Failed to save changes:', error);
            } finally {
                this.saving = false;
            }
        },
        
        resetChanges() {
            this.current = { ...this.original };
        },
    };
}
</script>
```

### Permission Diff Component

```blade
{{-- resources/views/components/permissions/permission-diff.blade.php --}}
@props([
    'roles',
    'comparison',
])

<div class="permission-diff">
    <div class="permission-diff__header">
        <h3>Permission Comparison</h3>
        <p class="text-sm text-gray-500">
            Comparing {{ count($roles) }} roles
        </p>
    </div>
    
    <div class="permission-diff__roles">
        @foreach($roles as $role)
            <div class="permission-diff__role" style="--role-color: {{ $role->color }}">
                <x-icon :name="$role->icon ?? 'shield'" class="w-5 h-5" />
                <span>{{ $role->name }}</span>
                <span class="text-sm text-gray-500">({{ $role->permissions_count }} permissions)</span>
            </div>
        @endforeach
    </div>
    
    <div class="permission-diff__sections">
        @if(!empty($comparison['common']))
            <div class="permission-diff__section permission-diff__section--common">
                <h4 class="permission-diff__section-title">
                    <x-icon name="check-circle" class="w-5 h-5 text-green-500" />
                    Common Permissions ({{ count($comparison['common']) }})
                </h4>
                <div class="permission-diff__list">
                    @foreach($comparison['common'] as $permission)
                        <span class="permission-diff__item permission-diff__item--common">
                            {{ $permission }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
        
        @foreach($roles as $index => $role)
            @php
                $uniqueKey = "only_in_{$role->id}";
                $uniquePermissions = $comparison[$uniqueKey] ?? [];
            @endphp
            
            @if(!empty($uniquePermissions))
                <div class="permission-diff__section permission-diff__section--unique" 
                     style="--role-color: {{ $role->color }}">
                    <h4 class="permission-diff__section-title">
                        <x-icon name="user" class="w-5 h-5" />
                        Only in {{ $role->name }} ({{ count($uniquePermissions) }})
                    </h4>
                    <div class="permission-diff__list">
                        @foreach($uniquePermissions as $permission)
                            <span class="permission-diff__item">
                                {{ $permission }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
```

### Access Rule Builder Component

```blade
{{-- resources/views/components/permissions/access-rule-builder.blade.php --}}
@props([
    'rule' => null,
    'allPermissions' => [],
])

<div x-data="accessRuleBuilder(@js($rule), @js($allPermissions))" class="access-rule-builder">
    <div class="access-rule-builder__section">
        <label class="access-rule-builder__label">Rule Name</label>
        <input type="text" 
               x-model="rule.name" 
               name="name"
               class="access-rule-builder__input"
               placeholder="e.g., Business Hours Only">
    </div>
    
    <div class="access-rule-builder__section">
        <label class="access-rule-builder__label">Description</label>
        <textarea x-model="rule.description" 
                  name="description"
                  class="access-rule-builder__textarea"
                  placeholder="Describe what this rule does..."></textarea>
    </div>
    
    <div class="access-rule-builder__section">
        <label class="access-rule-builder__label">
            Applies to Permissions
            <span class="text-sm text-gray-500">(supports wildcards like invoices.*)</span>
        </label>
        <div class="access-rule-builder__permissions">
            <div class="access-rule-builder__permission-search">
                <input type="text" 
                       x-model="permissionSearch"
                       placeholder="Search or type permission pattern..."
                       class="access-rule-builder__input">
                <button type="button" 
                        @click="addPermissionPattern()"
                        class="btn btn-sm btn-secondary">
                    Add
                </button>
            </div>
            
            <div class="access-rule-builder__selected-permissions">
                <template x-for="(perm, index) in rule.permissions" :key="index">
                    <span class="access-rule-builder__permission-tag">
                        <span x-text="perm"></span>
                        <button type="button" @click="removePermission(index)" class="text-red-500">×</button>
                    </span>
                </template>
            </div>
            
            <div x-show="permissionSearch && filteredPermissions.length > 0" 
                 class="access-rule-builder__permission-dropdown">
                <template x-for="perm in filteredPermissions" :key="perm.id">
                    <button type="button" 
                            @click="addPermission(perm.name)"
                            class="access-rule-builder__permission-option">
                        <span x-text="perm.label"></span>
                        <span class="text-gray-500" x-text="perm.name"></span>
                    </button>
                </template>
            </div>
        </div>
        <input type="hidden" name="permissions" :value="JSON.stringify(rule.permissions)">
    </div>
    
    <div class="access-rule-builder__section">
        <label class="access-rule-builder__label">Conditions</label>
        <p class="text-sm text-gray-500 mb-2">All conditions must be met for the rule to apply (AND logic)</p>
        
        <div class="access-rule-builder__conditions">
            <template x-for="(condition, index) in rule.conditions" :key="index">
                <div class="access-rule-builder__condition">
                    <select x-model="condition.type" 
                            @change="resetConditionValue(index)"
                            class="access-rule-builder__select">
                        <option value="time">Time of Day</option>
                        <option value="day">Day of Week</option>
                        <option value="ip">IP Address</option>
                        <option value="role">User Role</option>
                        <option value="attribute">Custom Attribute</option>
                    </select>
                    
                    <select x-model="condition.operator" class="access-rule-builder__select">
                        <template x-for="op in getOperatorsForType(condition.type)" :key="op.value">
                            <option :value="op.value" x-text="op.label"></option>
                        </template>
                    </select>
                    
                    {{-- Time value --}}
                    <template x-if="condition.type === 'time'">
                        <div class="access-rule-builder__time-inputs">
                            <input type="time" x-model="condition.value.start" class="access-rule-builder__input">
                            <span>to</span>
                            <input type="time" x-model="condition.value.end" class="access-rule-builder__input">
                        </div>
                    </template>
                    
                    {{-- Day value --}}
                    <template x-if="condition.type === 'day'">
                        <div class="access-rule-builder__day-inputs">
                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                <label class="access-rule-builder__day-checkbox">
                                    <input type="checkbox" 
                                           :checked="condition.value.includes('{{ $day }}')"
                                           @change="toggleDay(index, '{{ $day }}')">
                                    {{ ucfirst(substr($day, 0, 3)) }}
                                </label>
                            @endforeach
                        </div>
                    </template>
                    
                    {{-- IP value --}}
                    <template x-if="condition.type === 'ip'">
                        <input type="text" 
                               x-model="condition.value"
                               placeholder="e.g., 192.168.1.0/24"
                               class="access-rule-builder__input">
                    </template>
                    
                    {{-- Role value --}}
                    <template x-if="condition.type === 'role'">
                        <select x-model="condition.value" class="access-rule-builder__select">
                            @foreach(\App\Models\Role::all() as $role)
                                <option value="{{ $role->slug }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </template>
                    
                    {{-- Attribute value --}}
                    <template x-if="condition.type === 'attribute'">
                        <div class="access-rule-builder__attribute-inputs">
                            <input type="text" 
                                   x-model="condition.attribute"
                                   placeholder="user.department"
                                   class="access-rule-builder__input">
                            <input type="text" 
                                   x-model="condition.value"
                                   placeholder="Value"
                                   class="access-rule-builder__input">
                        </div>
                    </template>
                    
                    <button type="button" 
                            @click="removeCondition(index)"
                            class="access-rule-builder__remove-condition">
                        <x-icon name="trash-2" class="w-4 h-4" />
                    </button>
                </div>
            </template>
            
            <button type="button" @click="addCondition()" class="btn btn-secondary btn-sm">
                <x-icon name="plus" class="w-4 h-4" />
                Add Condition
            </button>
        </div>
        <input type="hidden" name="conditions" :value="JSON.stringify(rule.conditions)">
    </div>
    
    <div class="access-rule-builder__section">
        <label class="access-rule-builder__label">When conditions are NOT met</label>
        <div class="access-rule-builder__action-options">
            <label class="access-rule-builder__action-option">
                <input type="radio" x-model="rule.action" value="deny" name="action">
                <span class="access-rule-builder__action-label">
                    <x-icon name="shield-off" class="w-5 h-5 text-red-500" />
                    Deny Access
                </span>
            </label>
            <label class="access-rule-builder__action-option">
                <input type="radio" x-model="rule.action" value="log" name="action">
                <span class="access-rule-builder__action-label">
                    <x-icon name="file-text" class="w-5 h-5 text-amber-500" />
                    Log Only (Allow but record)
                </span>
            </label>
        </div>
    </div>
    
    <div class="access-rule-builder__section">
        <label class="access-rule-builder__label">Priority</label>
        <p class="text-sm text-gray-500 mb-2">Lower numbers = higher priority. Rules are evaluated in order.</p>
        <input type="number" 
               x-model="rule.priority" 
               name="priority"
               min="1" 
               max="1000"
               class="access-rule-builder__input w-24">
    </div>
    
    <div class="access-rule-builder__section">
        <label class="access-rule-builder__checkbox-label">
            <input type="checkbox" x-model="rule.is_active" name="is_active" value="1">
            <span>Rule is active</span>
        </label>
    </div>
</div>

<script>
function accessRuleBuilder(initialRule, allPermissions) {
    return {
        rule: initialRule || {
            name: '',
            description: '',
            permissions: [],
            conditions: [],
            action: 'deny',
            priority: 100,
            is_active: true,
        },
        allPermissions: allPermissions,
        permissionSearch: '',
        
        get filteredPermissions() {
            if (!this.permissionSearch) return this.allPermissions.slice(0, 20);
            const term = this.permissionSearch.toLowerCase();
            return this.allPermissions.filter(p => 
                p.name.toLowerCase().includes(term) || 
                p.label.toLowerCase().includes(term)
            );
        },
        
        getOperatorsForType(type) {
            const operators = {
                time: [
                    { value: 'between', label: 'is between' },
                    { value: 'not_between', label: 'is not between' },
                ],
                day: [
                    { value: 'is_one_of', label: 'is one of' },
                    { value: 'is_not', label: 'is not' },
                ],
                ip: [
                    { value: 'is', label: 'is exactly' },
                    { value: 'is_not', label: 'is not' },
                    { value: 'starts_with', label: 'starts with' },
                    { value: 'in_range', label: 'is in range (CIDR)' },
                ],
                role: [
                    { value: 'is', label: 'is' },
                    { value: 'is_not', label: 'is not' },
                    { value: 'is_one_of', label: 'is one of' },
                ],
                attribute: [
                    { value: 'equals', label: 'equals' },
                    { value: 'not_equals', label: 'does not equal' },
                    { value: 'contains', label: 'contains' },
                    { value: 'greater_than', label: 'is greater than' },
                    { value: 'less_than', label: 'is less than' },
                ],
            };
            return operators[type] || [{ value: 'equals', label: 'equals' }];
        },
        
        addCondition() {
            this.rule.conditions.push({
                type: 'time',
                operator: 'between',
                value: { start: '09:00', end: '17:00' },
            });
        },
        
        removeCondition(index) {
            this.rule.conditions.splice(index, 1);
        },
        
        resetConditionValue(index) {
            const type = this.rule.conditions[index].type;
            const defaults = {
                time: { start: '09:00', end: '17:00' },
                day: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                ip: '',
                role: '',
                attribute: '',
            };
            this.rule.conditions[index].value = defaults[type] || '';
            this.rule.conditions[index].operator = this.getOperatorsForType(type)[0].value;
            
            if (type === 'attribute') {
                this.rule.conditions[index].attribute = '';
            }
        },
        
        toggleDay(index, day) {
            const days = this.rule.conditions[index].value;
            const dayIndex = days.indexOf(day);
            if (dayIndex === -1) {
                days.push(day);
            } else {
                days.splice(dayIndex, 1);
            }
        },
        
        addPermission(name) {
            if (!this.rule.permissions.includes(name)) {
                this.rule.permissions.push(name);
            }
            this.permissionSearch = '';
        },
        
        addPermissionPattern() {
            const pattern = this.permissionSearch.trim();
            if (pattern && !this.rule.permissions.includes(pattern)) {
                this.rule.permissions.push(pattern);
            }
            this.permissionSearch = '';
        },
        
        removePermission(index) {
            this.rule.permissions.splice(index, 1);
        },
    };
}
</script>
```

### Role Select Component

```blade
{{-- resources/views/components/permissions/role-select.blade.php --}}
@props([
    'roles',
    'selected' => [],
    'multiple' => true,
    'name' => 'roles',
    'showCounts' => true,
])

<div x-data="{ 
    selected: @js($selected),
    search: '',
    open: false,
    roles: @js($roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'color' => $r->color, 'permissions_count' => $r->permissions_count ?? 0])),
}" class="role-select">
    
    <div class="role-select__selected" @click="open = true">
        <template x-for="roleId in selected" :key="roleId">
            <span class="role-select__tag" :style="{ backgroundColor: getRoleColor(roleId) + '20', borderColor: getRoleColor(roleId) }">
                <span x-text="getRoleName(roleId)"></span>
                <button type="button" @click.stop="removeRole(roleId)" class="role-select__remove">×</button>
            </span>
        </template>
        
        <input type="text" 
               x-model="search" 
               @focus="open = true"
               placeholder="{{ $multiple ? 'Add role...' : 'Select role...' }}"
               class="role-select__input">
    </div>
    
    <div x-show="open" 
         @click.away="open = false" 
         x-transition
         class="role-select__dropdown">
        @foreach($roles as $role)
            <button type="button"
                    @click="toggleRole({{ $role->id }})"
                    x-show="!search || '{{ strtolower($role->name) }}'.includes(search.toLowerCase())"
                    class="role-select__option"
                    :class="{ 'selected': selected.includes({{ $role->id }}) }">
                <span class="role-select__color" style="background: {{ $role->color }}"></span>
                <span class="flex-1">{{ $role->name }}</span>
                @if($showCounts)
                    <span class="text-xs text-gray-500">{{ $role->permissions_count ?? 0 }} perms</span>
                @endif
                <x-icon x-show="selected.includes({{ $role->id }})" name="check" class="w-4 h-4 text-primary-500" />
            </button>
        @endforeach
        
        <div x-show="!roles.filter(r => !search || r.name.toLowerCase().includes(search.toLowerCase())).length" 
             class="role-select__empty">
            No roles found
        </div>
    </div>
    
    <template x-for="roleId in selected">
        <input type="hidden" :name="'{{ $name }}' + (@js($multiple) ? '[]' : '')" :value="roleId">
    </template>
</div>

<script>
function getRoleName(roleId) {
    const role = this.roles.find(r => r.id === roleId);
    return role ? role.name : '';
}

function getRoleColor(roleId) {
    const role = this.roles.find(r => r.id === roleId);
    return role ? role.color : '#6B7280';
}

function toggleRole(roleId) {
    if (@js($multiple)) {
        if (this.selected.includes(roleId)) {
            this.selected = this.selected.filter(id => id !== roleId);
        } else {
            this.selected.push(roleId);
        }
    } else {
        this.selected = [roleId];
        this.open = false;
    }
    this.search = '';
}

function removeRole(roleId) {
    this.selected = this.selected.filter(id => id !== roleId);
}
</script>
```

### Privilege Warning Component

```blade
{{-- resources/views/components/permissions/privilege-warning.blade.php --}}
@props([
    'message' => null,
])

<div class="privilege-warning">
    <div class="privilege-warning__icon">
        <x-icon name="alert-triangle" class="w-5 h-5 text-amber-500" />
    </div>
    <div class="privilege-warning__content">
        <h4 class="privilege-warning__title">Privilege Escalation Warning</h4>
        <p class="privilege-warning__message">
            {{ $message ?? 'You are attempting to grant permissions that exceed your own access level. These permissions are locked and cannot be assigned by you.' }}
        </p>
    </div>
</div>
```

### Dependency Indicator Component

```blade
{{-- resources/views/components/permissions/dependency-indicator.blade.php --}}
@props([
    'dependencies',
])

<span class="dependency-indicator" 
      x-data="{ showDeps: false }"
      @mouseenter="showDeps = true"
      @mouseleave="showDeps = false">
    <x-icon name="link" class="w-3 h-3 text-blue-400" />
    
    <div x-show="showDeps" 
         x-transition
         class="dependency-indicator__tooltip">
        <span class="dependency-indicator__label">Requires:</span>
        @foreach($dependencies as $dep)
            <span class="dependency-indicator__dep">{{ $dep }}</span>
        @endforeach
    </div>
</span>
```

### Audit Entry Component

```blade
{{-- resources/views/components/permissions/audit-entry.blade.php --}}
@props(['entry'])

<div class="audit-entry" x-data="{ showDetails: false }">
    <div class="audit-entry__icon">
        @switch($entry->action)
            @case('role_created')
                <x-icon name="plus-circle" class="text-green-500" />
                @break
            @case('role_updated')
            @case('permissions_synced')
                <x-icon name="edit" class="text-blue-500" />
                @break
            @case('role_deleted')
                <x-icon name="trash-2" class="text-red-500" />
                @break
            @case('user_role_assigned')
                <x-icon name="user-plus" class="text-purple-500" />
                @break
            @case('user_role_removed')
                <x-icon name="user-minus" class="text-orange-500" />
                @break
            @case('access_rule_triggered')
                <x-icon name="shield-off" class="text-amber-500" />
                @break
            @case('permission_granted')
                <x-icon name="check-circle" class="text-green-500" />
                @break
            @case('permission_denied')
                <x-icon name="x-circle" class="text-red-500" />
                @break
            @default
                <x-icon name="activity" class="text-gray-500" />
        @endswitch
    </div>
    
    <div class="audit-entry__content">
        <div class="audit-entry__header">
            <span class="audit-entry__action">{{ $entry->action_label }}</span>
            <span class="audit-entry__time" title="{{ $entry->created_at->format('Y-m-d H:i:s') }}">
                {{ $entry->created_at->diffForHumans() }}
            </span>
        </div>
        
        <p class="audit-entry__target">
            {{ $entry->target_type }}: <strong>{{ $entry->target_name }}</strong>
        </p>
        
        @if($entry->user)
            <p class="audit-entry__user">
                by {{ $entry->user->name }}
                @if($entry->ip_address)
                    from {{ $entry->ip_address }}
                @endif
            </p>
        @endif
        
        @if($entry->changes)
            <button type="button" 
                    @click="showDetails = !showDetails"
                    class="audit-entry__details-toggle">
                <span x-text="showDetails ? 'Hide Details' : 'View Details'"></span>
                <x-icon name="chevron-down" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showDetails }" />
            </button>
            
            <div x-show="showDetails" x-collapse class="audit-entry__details">
                @if(isset($entry->changes['permissions_added']) && count($entry->changes['permissions_added']))
                    <div class="text-green-600">
                        <strong>Added ({{ count($entry->changes['permissions_added']) }}):</strong>
                        <span class="text-sm">{{ implode(', ', array_slice($entry->changes['permissions_added'], 0, 10)) }}</span>
                        @if(count($entry->changes['permissions_added']) > 10)
                            <span class="text-gray-500">and {{ count($entry->changes['permissions_added']) - 10 }} more</span>
                        @endif
                    </div>
                @endif
                @if(isset($entry->changes['permissions_removed']) && count($entry->changes['permissions_removed']))
                    <div class="text-red-600">
                        <strong>Removed ({{ count($entry->changes['permissions_removed']) }}):</strong>
                        <span class="text-sm">{{ implode(', ', array_slice($entry->changes['permissions_removed'], 0, 10)) }}</span>
                        @if(count($entry->changes['permissions_removed']) > 10)
                            <span class="text-gray-500">and {{ count($entry->changes['permissions_removed']) - 10 }} more</span>
                        @endif
                    </div>
                @endif
                @if(isset($entry->changes['role_name']))
                    <div class="text-gray-700">
                        <strong>Role:</strong> {{ $entry->changes['role_name'] }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
```

### Bulk Assign Modal Component

```blade
{{-- resources/views/components/permissions/bulk-assign-modal.blade.php --}}
@props([
    'role',
    'users' => [],
])

<div x-data="bulkAssignModal(@js($users))" class="bulk-assign-modal">
    <div class="bulk-assign-modal__header">
        <h3>Assign "{{ $role->name }}" Role to Users</h3>
        <p class="text-sm text-gray-500">Select users to assign this role</p>
    </div>
    
    <div class="bulk-assign-modal__search">
        <x-icon name="search" class="w-5 h-5 text-gray-400" />
        <input type="text" 
               x-model="search"
               placeholder="Search users by name or email..."
               class="bulk-assign-modal__search-input">
    </div>
    
    <div class="bulk-assign-modal__actions">
        <button type="button" @click="selectAll()" class="btn btn-sm btn-secondary">
            Select All Visible
        </button>
        <button type="button" @click="deselectAll()" class="btn btn-sm btn-secondary">
            Deselect All
        </button>
        <span class="text-sm text-gray-500">
            <span x-text="selected.length"></span> selected
        </span>
    </div>
    
    <div class="bulk-assign-modal__users">
        <template x-for="user in filteredUsers" :key="user.id">
            <label class="bulk-assign-modal__user" :class="{ 'selected': selected.includes(user.id), 'has-role': user.has_role }">
                <input type="checkbox" 
                       :value="user.id"
                       :checked="selected.includes(user.id)"
                       :disabled="user.has_role"
                       @change="toggleUser(user.id)">
                <div class="bulk-assign-modal__user-info">
                    <span class="bulk-assign-modal__user-name" x-text="user.name"></span>
                    <span class="bulk-assign-modal__user-email" x-text="user.email"></span>
                </div>
                <template x-if="user.has_role">
                    <span class="bulk-assign-modal__already-assigned">Already has role</span>
                </template>
            </label>
        </template>
        
        <div x-show="filteredUsers.length === 0" class="bulk-assign-modal__empty">
            No users found matching your search
        </div>
    </div>
    
    <div class="bulk-assign-modal__footer">
        <button type="button" @click="$dispatch('close')" class="btn btn-secondary">
            Cancel
        </button>
        <button type="button" 
                @click="assignRole()"
                :disabled="selected.length === 0 || assigning"
                class="btn btn-primary">
            <template x-if="assigning">
                <x-icon name="loader" class="w-4 h-4 animate-spin" />
            </template>
            <span x-text="assigning ? 'Assigning...' : `Assign to ${selected.length} User${selected.length === 1 ? '' : 's'}`"></span>
        </button>
    </div>
</div>

<script>
function bulkAssignModal(users) {
    return {
        users: users,
        selected: [],
        search: '',
        assigning: false,
        
        get filteredUsers() {
            if (!this.search) return this.users;
            const term = this.search.toLowerCase();
            return this.users.filter(u => 
                u.name.toLowerCase().includes(term) || 
                u.email.toLowerCase().includes(term)
            );
        },
        
        toggleUser(userId) {
            const index = this.selected.indexOf(userId);
            if (index === -1) {
                this.selected.push(userId);
            } else {
                this.selected.splice(index, 1);
            }
        },
        
        selectAll() {
            const visibleIds = this.filteredUsers
                .filter(u => !u.has_role)
                .map(u => u.id);
            this.selected = [...new Set([...this.selected, ...visibleIds])];
        },
        
        deselectAll() {
            this.selected = [];
        },
        
        async assignRole() {
            if (this.selected.length === 0 || this.assigning) return;
            
            this.assigning = true;
            
            try {
                const response = await fetch(this.$el.closest('form').action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ user_ids: this.selected }),
                });
                
                if (response.ok) {
                    this.$dispatch('assigned', { count: this.selected.length });
                    this.$dispatch('close');
                }
            } catch (error) {
                console.error('Failed to assign roles:', error);
            } finally {
                this.assigning = false;
            }
        },
    };
}
</script>
```

---

## Blade Directives

```php
// AppServiceProvider.php
public function boot(): void
{
    Blade::if('role', function ($roles) {
        return auth()->check() && auth()->user()->hasAnyRole((array) $roles);
    });

    Blade::if('permission', function ($permission) {
        return auth()->check() && auth()->user()->hasPermission($permission);
    });
    
    Blade::if('ability', function ($ability, $model = null) {
        return auth()->check() && auth()->user()->hasAbility($ability, $model ? [$model] : []);
    });

    // Usage:
    // @role('admin')
    //     <a href="/admin">Admin Panel</a>
    // @endrole

    // @permission('invoices.create')
    //     <button>Create Invoice</button>
    // @endpermission
    
    // @ability('invoices.edit', $invoice)
    //     <button>Edit Invoice</button>
    // @endability
}
```

---

## CSS Classes (Tailwind)

```css
/* Permission Tree */
.permission-tree { @apply space-y-4; }
.permission-tree__header { @apply flex items-center justify-between gap-4 mb-4; }
.permission-tree__search { @apply flex-1 rounded-lg border border-gray-300 px-4 py-2; }
.permission-tree__actions { @apply flex items-center gap-2; }
.permission-tree__stats { @apply text-sm text-gray-600 mb-4; }

.permission-group { @apply border border-gray-200 rounded-lg overflow-hidden; }
.permission-group--open .permission-group__chevron { @apply rotate-90; }
.permission-group__header { @apply w-full flex items-center gap-3 px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors; }
.permission-group__chevron { @apply w-4 h-4 text-gray-400 transition-transform; }
.permission-group__icon { @apply w-5 h-5 text-gray-500; }
.permission-group__name { @apply font-medium flex-1 text-left; }
.permission-group__count { @apply text-sm text-gray-500; }
.permission-group__plugin { @apply text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded; }
.permission-group__toggle { @apply text-xs text-primary-600 hover:text-primary-700; }
.permission-group__content { @apply p-2 space-y-1; }

.permission-item { @apply flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-50 cursor-pointer; }
.permission-item--inherited { @apply bg-blue-50; }
.permission-item--unauthorized { @apply bg-amber-50 opacity-75; }
.permission-item--dangerous { @apply border-l-2 border-red-400; }
.permission-item__checkbox { @apply w-4 h-4 rounded border-gray-300; }
.permission-item__info { @apply flex-1; }
.permission-item__name { @apply block text-sm font-medium text-gray-900; }
.permission-item__key { @apply block text-xs text-gray-500 font-mono; }
.permission-item__inherited { @apply text-xs text-blue-600; }
.permission-item__unauthorized { @apply text-xs text-amber-600; }

/* Privilege Warning */
.privilege-warning { @apply flex gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg mb-4; }
.privilege-warning__icon { @apply flex-shrink-0; }
.privilege-warning__title { @apply font-medium text-amber-800; }
.privilege-warning__message { @apply text-sm text-amber-700; }

/* Dependency Indicator */
.dependency-indicator { @apply relative inline-flex items-center ml-1 cursor-help; }
.dependency-indicator__tooltip { @apply absolute bottom-full left-0 mb-1 p-2 bg-gray-900 text-white text-xs rounded shadow-lg whitespace-nowrap z-10; }
.dependency-indicator__label { @apply text-gray-400; }
.dependency-indicator__dep { @apply block text-blue-300; }

/* Role Select */
.role-select { @apply relative; }
.role-select__selected { @apply flex flex-wrap gap-2 p-2 border border-gray-300 rounded-lg min-h-[42px]; }
.role-select__tag { @apply inline-flex items-center gap-1 px-2 py-1 rounded text-sm border; }
.role-select__remove { @apply text-gray-400 hover:text-gray-600; }
.role-select__input { @apply flex-1 min-w-[100px] outline-none bg-transparent; }
.role-select__dropdown { @apply absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto z-20; }
.role-select__option { @apply w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-50 text-left; }
.role-select__option.selected { @apply bg-primary-50; }
.role-select__color { @apply w-3 h-3 rounded-full; }
.role-select__empty { @apply px-4 py-3 text-sm text-gray-500 text-center; }

/* Audit Entry */
.audit-entry { @apply flex gap-4 p-4 border-b border-gray-100; }
.audit-entry__icon { @apply flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center; }
.audit-entry__content { @apply flex-1 min-w-0; }
.audit-entry__header { @apply flex items-center justify-between gap-2; }
.audit-entry__action { @apply font-medium text-gray-900; }
.audit-entry__time { @apply text-sm text-gray-500; }
.audit-entry__target { @apply text-sm text-gray-700 mt-1; }
.audit-entry__user { @apply text-sm text-gray-500; }
.audit-entry__details-toggle { @apply inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 mt-2; }
.audit-entry__details { @apply mt-2 p-3 bg-gray-50 rounded text-sm space-y-1; }

/* Bulk Assign Modal */
.bulk-assign-modal { @apply flex flex-col h-full; }
.bulk-assign-modal__header { @apply p-4 border-b border-gray-200; }
.bulk-assign-modal__search { @apply flex items-center gap-2 px-4 py-3 border-b border-gray-200; }
.bulk-assign-modal__search-input { @apply flex-1 outline-none; }
.bulk-assign-modal__actions { @apply flex items-center gap-2 px-4 py-2 bg-gray-50 border-b border-gray-200; }
.bulk-assign-modal__users { @apply flex-1 overflow-auto p-2; }
.bulk-assign-modal__user { @apply flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer; }
.bulk-assign-modal__user.selected { @apply bg-primary-50; }
.bulk-assign-modal__user.has-role { @apply opacity-50 cursor-not-allowed; }
.bulk-assign-modal__user-info { @apply flex-1; }
.bulk-assign-modal__user-name { @apply block font-medium text-gray-900; }
.bulk-assign-modal__user-email { @apply block text-sm text-gray-500; }
.bulk-assign-modal__already-assigned { @apply text-xs text-gray-500; }
.bulk-assign-modal__empty { @apply text-center py-8 text-gray-500; }
.bulk-assign-modal__footer { @apply flex justify-end gap-2 p-4 border-t border-gray-200; }
```
