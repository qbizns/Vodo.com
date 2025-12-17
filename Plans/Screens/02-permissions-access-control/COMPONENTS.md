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
├── access-rule-builder.blade.php
├── condition-row.blade.php
├── user-roles-picker.blade.php
├── override-badge.blade.php
└── audit-entry.blade.php
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
])

<div x-data="permissionTree(@js($selectedPermissions), @js($inheritedPermissions))" 
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
    </div>
    
    <div class="permission-tree__groups">
        @foreach($groups as $group)
            <div class="permission-group" 
                 x-data="{ open: false }"
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
                               :class="{ 'permission-item--inherited': isInherited({{ $permission['id'] }}) }"
                               x-show="permissionMatchesSearch('{{ $permission['name'] }}', '{{ $permission['label'] }}')">
                            
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permission['id'] }}"
                                   x-model="selected"
                                   :disabled="@js($disabled) || isInherited({{ $permission['id'] }})"
                                   class="permission-item__checkbox">
                            
                            <div class="permission-item__info">
                                <span class="permission-item__name">{{ $permission['label'] }}</span>
                                <span class="permission-item__key">{{ $permission['name'] }}</span>
                            </div>
                            
                            <template x-if="isInherited({{ $permission['id'] }})">
                                <span class="permission-item__inherited">
                                    Inherited from <span x-text="getInheritedFrom({{ $permission['id'] }})"></span>
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
function permissionTree(initialSelected, inherited) {
    return {
        selected: initialSelected,
        inherited: inherited,
        search: '',
        expandedGroups: [],
        
        get selectedCount() {
            return this.selected.length;
        },
        
        get inheritedCount() {
            return Object.keys(this.inherited).length;
        },
        
        isInherited(permissionId) {
            return this.inherited.hasOwnProperty(permissionId);
        },
        
        getInheritedFrom(permissionId) {
            return this.inherited[permissionId] || 'Parent Role';
        },
        
        getGroupSelectedCount(groupSlug) {
            const group = this.$root.querySelector(`[data-group="${groupSlug}"]`);
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
            // Check if any permission in group matches
            return true; // Simplified
        },
        
        permissionMatchesSearch(name, label) {
            if (!this.search) return true;
            const term = this.search.toLowerCase();
            return name.toLowerCase().includes(term) || label.toLowerCase().includes(term);
        },
        
        expandAll() {
            document.querySelectorAll('.permission-group').forEach(g => g.__x.$data.open = true);
        },
        
        collapseAll() {
            document.querySelectorAll('.permission-group').forEach(g => g.__x.$data.open = false);
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
        <input type="text" x-model="search" placeholder="Search..." class="input">
        <select x-model="groupFilter" class="select">
            <option value="">All Groups</option>
            @foreach($permissions->groupBy('group.slug') as $slug => $perms)
                <option value="{{ $slug }}">{{ $perms->first()->group->name ?? $slug }}</option>
            @endforeach
        </select>
    </div>
    
    <div class="permission-matrix__container">
        <table class="permission-matrix__table">
            <thead>
                <tr>
                    <th class="permission-matrix__corner">Permission</th>
                    @foreach($roles as $role)
                        <th class="permission-matrix__role-header">
                            <div class="flex flex-col items-center">
                                <span class="font-medium">{{ $role->name }}</span>
                                @if($role->is_system)
                                    <span class="text-xs text-gray-400">System</span>
                                @endif
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($permissions->groupBy('group_id') as $groupId => $groupPermissions)
                    <tr class="permission-matrix__group-row">
                        <td colspan="{{ count($roles) + 1 }}" class="permission-matrix__group-name">
                            <x-icon :name="$groupPermissions->first()->group->icon ?? 'folder'" class="w-4 h-4" />
                            {{ $groupPermissions->first()->group->name ?? 'Ungrouped' }}
                        </td>
                    </tr>
                    @foreach($groupPermissions as $permission)
                        <tr class="permission-matrix__row" 
                            x-show="matchesFilter('{{ $permission->name }}', '{{ $permission->group->slug ?? '' }}')">
                            <td class="permission-matrix__permission-name">
                                <span title="{{ $permission->description }}">{{ $permission->label }}</span>
                                <code class="text-xs text-gray-400">{{ $permission->name }}</code>
                            </td>
                            @foreach($roles as $role)
                                <td class="permission-matrix__cell">
                                    @if($role->is_system && $role->slug === 'super-admin')
                                        <span class="text-purple-500" title="All permissions">✓</span>
                                    @else
                                        <button type="button"
                                                @click="toggle({{ $role->id }}, {{ $permission->id }})"
                                                :class="getCellClass({{ $role->id }}, {{ $permission->id }})"
                                                class="permission-matrix__toggle">
                                            <template x-if="isGranted({{ $role->id }}, {{ $permission->id }})">
                                                <x-icon name="check" class="w-4 h-4" />
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
    
    <div class="permission-matrix__footer">
        <div class="permission-matrix__legend">
            <span><span class="legend-granted"></span> Granted</span>
            <span><span class="legend-inherited"></span> Inherited</span>
            <span><span class="legend-changed"></span> Changed</span>
        </div>
        
        <div class="permission-matrix__actions">
            <button type="button" @click="resetChanges()" x-show="hasChanges" class="btn btn-secondary">
                Reset Changes
            </button>
            <button type="button" @click="saveChanges()" x-show="hasChanges" class="btn btn-primary">
                Save Changes (<span x-text="changeCount"></span>)
            </button>
        </div>
    </div>
</div>
```

### Access Rule Builder

```blade
{{-- resources/views/components/permissions/access-rule-builder.blade.php --}}
@props(['rule' => null])

<div x-data="accessRuleBuilder(@js($rule))" class="access-rule-builder">
    <div class="form-group">
        <label>Rule Name</label>
        <input type="text" x-model="rule.name" name="name" required class="input">
    </div>
    
    <div class="form-group">
        <label>Description</label>
        <textarea x-model="rule.description" name="description" class="textarea"></textarea>
    </div>
    
    <div class="form-group">
        <label>Target Permissions</label>
        <div class="permission-selector">
            <input type="text" x-model="permissionSearch" placeholder="Search permissions..." class="input mb-2">
            <div class="permission-selector__list">
                <template x-for="perm in filteredPermissions" :key="perm.id">
                    <label class="permission-selector__item">
                        <input type="checkbox" 
                               :value="perm.name" 
                               x-model="rule.permissions"
                               class="checkbox">
                        <span x-text="perm.label"></span>
                        <code class="text-xs" x-text="perm.name"></code>
                    </label>
                </template>
            </div>
            <p class="text-sm text-gray-500 mt-2">
                Tip: Use wildcards like <code>invoices.*</code> to match all invoice permissions
            </p>
        </div>
    </div>
    
    <div class="form-group">
        <label>Conditions</label>
        <p class="text-sm text-gray-500 mb-2">All conditions must match for the rule to apply</p>
        
        <div class="conditions-list">
            <template x-for="(condition, index) in rule.conditions" :key="index">
                <div class="condition-row">
                    <select x-model="condition.type" @change="resetConditionValue(index)" class="select">
                        <option value="time">Time of Day</option>
                        <option value="day">Day of Week</option>
                        <option value="date">Date</option>
                        <option value="ip">IP Address</option>
                        <option value="role">User Role</option>
                        <option value="team">Team/Department</option>
                        <option value="custom">Custom Field</option>
                    </select>
                    
                    <select x-model="condition.operator" class="select">
                        <template x-for="op in getOperatorsForType(condition.type)">
                            <option :value="op.value" x-text="op.label"></option>
                        </template>
                    </select>
                    
                    {{-- Dynamic value input based on type --}}
                    <template x-if="condition.type === 'time'">
                        <div class="flex items-center gap-2">
                            <input type="time" x-model="condition.value.start" class="input">
                            <span>to</span>
                            <input type="time" x-model="condition.value.end" class="input">
                        </div>
                    </template>
                    
                    <template x-if="condition.type === 'day'">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="day in days">
                                <label class="day-checkbox">
                                    <input type="checkbox" :value="day" x-model="condition.value">
                                    <span x-text="day.slice(0,3)"></span>
                                </label>
                            </template>
                        </div>
                    </template>
                    
                    <template x-if="condition.type === 'ip'">
                        <input type="text" x-model="condition.value" placeholder="10.0.0.*" class="input">
                    </template>
                    
                    <button type="button" @click="removeCondition(index)" class="btn-icon text-red-500">
                        <x-icon name="trash-2" class="w-4 h-4" />
                    </button>
                </div>
            </template>
        </div>
        
        <button type="button" @click="addCondition()" class="btn btn-secondary btn-sm mt-2">
            <x-icon name="plus" class="w-4 h-4" /> Add Condition
        </button>
    </div>
    
    <div class="form-group">
        <label>Action when conditions DO NOT match</label>
        <div class="radio-group">
            <label>
                <input type="radio" x-model="rule.action" value="deny" name="action">
                <span>Deny Access</span>
            </label>
            <label>
                <input type="radio" x-model="rule.action" value="log" name="action">
                <span>Allow but Log (Monitoring)</span>
            </label>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label>Priority</label>
            <input type="number" x-model="rule.priority" min="1" max="1000" class="input w-24">
            <p class="text-xs text-gray-500">Lower = evaluated first</p>
        </div>
        
        <div class="form-group">
            <label class="flex items-center gap-2">
                <input type="checkbox" x-model="rule.is_active" class="checkbox">
                <span>Rule is active</span>
            </label>
        </div>
    </div>
    
    <input type="hidden" name="permissions" :value="JSON.stringify(rule.permissions)">
    <input type="hidden" name="conditions" :value="JSON.stringify(rule.conditions)">
</div>

<script>
function accessRuleBuilder(existingRule) {
    return {
        rule: existingRule || {
            name: '',
            description: '',
            permissions: [],
            conditions: [],
            action: 'deny',
            priority: 100,
            is_active: true,
        },
        permissionSearch: '',
        allPermissions: [],
        days: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        
        async init() {
            const response = await fetch('/api/v1/admin/permissions?grouped=false');
            const data = await response.json();
            this.allPermissions = data.data;
        },
        
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
                    { value: 'in_range', label: 'is in range' },
                ],
                role: [
                    { value: 'is', label: 'is' },
                    { value: 'is_not', label: 'is not' },
                    { value: 'is_one_of', label: 'is one of' },
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
                day: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                ip: '',
                role: '',
            };
            this.rule.conditions[index].value = defaults[type] || '';
            this.rule.conditions[index].operator = this.getOperatorsForType(type)[0].value;
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
])

<div x-data="{ 
    selected: @js($selected),
    search: '',
    open: false,
}" class="role-select">
    
    <div class="role-select__selected">
        <template x-for="roleId in selected" :key="roleId">
            <span class="role-select__tag">
                <span x-text="getRoleName(roleId)"></span>
                <button type="button" @click="removeRole(roleId)" class="role-select__remove">×</button>
            </span>
        </template>
        
        <input type="text" 
               x-model="search" 
               @focus="open = true"
               placeholder="{{ $multiple ? 'Add role...' : 'Select role...' }}"
               class="role-select__input">
    </div>
    
    <div x-show="open" @click.away="open = false" class="role-select__dropdown">
        @foreach($roles as $role)
            <button type="button"
                    @click="toggleRole({{ $role->id }})"
                    x-show="!search || '{{ strtolower($role->name) }}'.includes(search.toLowerCase())"
                    class="role-select__option"
                    :class="{ 'selected': selected.includes({{ $role->id }}) }">
                <span class="role-select__color" style="background: {{ $role->color }}"></span>
                <span>{{ $role->name }}</span>
                <span class="text-xs text-gray-500">{{ $role->permissions_count }} perms</span>
                <x-icon x-show="selected.includes({{ $role->id }})" name="check" class="w-4 h-4 text-primary-500" />
            </button>
        @endforeach
    </div>
    
    <template x-for="roleId in selected">
        <input type="hidden" :name="'{{ $name }}[]'" :value="roleId">
    </template>
</div>
```

### Audit Entry Component

```blade
{{-- resources/views/components/permissions/audit-entry.blade.php --}}
@props(['entry'])

<div class="audit-entry">
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
            @case('access_rule_triggered')
                <x-icon name="shield-off" class="text-amber-500" />
                @break
            @default
                <x-icon name="activity" class="text-gray-500" />
        @endswitch
    </div>
    
    <div class="audit-entry__content">
        <div class="audit-entry__header">
            <span class="audit-entry__action">{{ $entry->action_label }}</span>
            <span class="audit-entry__time">{{ $entry->created_at->diffForHumans() }}</span>
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
                View Details
            </button>
            
            <div x-show="showDetails" class="audit-entry__details">
                @if(isset($entry->changes['permissions_added']))
                    <div class="text-green-600">
                        <strong>Added:</strong>
                        {{ implode(', ', $entry->changes['permissions_added']) }}
                    </div>
                @endif
                @if(isset($entry->changes['permissions_removed']))
                    <div class="text-red-600">
                        <strong>Removed:</strong>
                        {{ implode(', ', $entry->changes['permissions_removed']) }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
```

---

## Blade Directives

```php
// AppServiceProvider.php
Blade::if('role', function ($roles) {
    return auth()->check() && auth()->user()->hasAnyRole((array) $roles);
});

Blade::if('permission', function ($permission) {
    return auth()->check() && auth()->user()->can($permission);
});

// Usage:
@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole

@permission('invoices.create')
    <button>Create Invoice</button>
@endpermission
```
