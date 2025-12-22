{{-- Access Rule Form (Screen 5 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', isset($rule) ? 'Edit Access Rule' : 'Create Access Rule')
@section('page-id', 'system/permissions/rules/form')
@section('require-css', 'permissions')

@section('header', isset($rule) ? 'Edit Access Rule: ' . $rule->name : 'Create Access Rule')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.permissions.rules') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Rules</span>
    </a>
</div>
@endsection

@section('content')
<div class="access-rule-form-page" x-data="accessRuleForm(@json($rule ?? null), @json($permissions), @json($conditionTypes))">
    <form action="{{ isset($rule) ? route('admin.permissions.rules.update', $rule) : route('admin.permissions.rules.store') }}"
          method="POST"
          id="ruleForm"
          @submit.prevent="submitForm">
        @csrf
        @if(isset($rule))
            @method('PUT')
        @endif

        {{-- Basic Info --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3>Rule Details</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="name" class="form-label required">Rule Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-input"
                           x-model="form.name"
                           placeholder="e.g., Business Hours Only"
                           required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description"
                              name="description"
                              class="form-textarea"
                              x-model="form.description"
                              rows="2"
                              placeholder="Describe what this rule does"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="priority" class="form-label">Priority</label>
                        <input type="number"
                               id="priority"
                               name="priority"
                               class="form-input"
                               x-model="form.priority"
                               min="1"
                               max="100">
                        <span class="form-hint">Lower number = evaluated first (1-100)</span>
                    </div>

                    <div class="form-group form-group-checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" x-model="form.is_active">
                            <span>Rule is active</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Target Permissions --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3>Target Permissions</h3>
                <span class="text-muted">Select which permissions this rule affects (supports wildcards like invoices.*)</span>
            </div>
            <div class="card-body">
                <div class="form-group mb-4">
                    <label class="form-label">Add Custom Pattern</label>
                    <div class="flex gap-2">
                        <input type="text"
                               class="form-input flex-1"
                               placeholder="e.g., invoices.* or users.delete"
                               x-model="customPattern">
                        <button type="button" class="btn-secondary" @click="addCustomPattern">
                            @include('backend.partials.icon', ['icon' => 'plus'])
                            Add
                        </button>
                    </div>
                </div>

                <div class="selected-permissions mb-4" x-show="form.target_permissions.length > 0">
                    <label class="form-label">Selected Targets:</label>
                    <div class="permission-tags">
                        <template x-for="(target, index) in form.target_permissions" :key="index">
                            <span class="permission-tag">
                                <code x-text="target"></code>
                                <button type="button" @click="removeTarget(index)">
                                    @include('backend.partials.icon', ['icon' => 'x'])
                                </button>
                            </span>
                        </template>
                    </div>
                </div>

                <div class="permission-selector">
                    <div class="search-input-wrapper mb-3">
                        @include('backend.partials.icon', ['icon' => 'search'])
                        <input type="text"
                               class="search-input"
                               placeholder="Search permissions..."
                               x-model="permissionSearch">
                    </div>

                    <div class="permission-checklist">
                        @foreach($groupedPermissions as $groupSlug => $group)
                            <div class="permission-check-group"
                                 x-show="isGroupVisible('{{ $groupSlug }}')">
                                <div class="group-header">
                                    <label class="checkbox-label">
                                        <input type="checkbox"
                                               @change="toggleGroupPermissions('{{ $groupSlug }}')"
                                               :checked="isGroupSelected('{{ $groupSlug }}')">
                                        <strong>{{ $group['name'] }}</strong>
                                    </label>
                                </div>
                                <div class="group-permissions">
                                    @foreach($group['permissions'] as $perm)
                                        <label class="checkbox-label permission-check-item"
                                               x-show="isPermissionSearchVisible('{{ $perm['slug'] }}')">
                                            <input type="checkbox"
                                                   :checked="form.target_permissions.includes('{{ $perm['slug'] }}')"
                                                   @change="togglePermission('{{ $perm['slug'] }}')">
                                            <span>{{ $perm['label'] ?? $perm['slug'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Conditions --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3>Conditions</h3>
                <span class="text-muted">All conditions must match for the rule to apply</span>
            </div>
            <div class="card-body">
                <div class="conditions-list">
                    <template x-for="(condition, index) in form.conditions" :key="index">
                        <div class="condition-row">
                            <select class="form-select condition-type"
                                    x-model="condition.type"
                                    @change="updateConditionOperators(index)">
                                <template x-for="type in conditionTypes" :key="type.key">
                                    <option :value="type.key" x-text="type.label"></option>
                                </template>
                            </select>

                            <select class="form-select condition-operator"
                                    x-model="condition.operator">
                                <template x-for="op in getOperatorsForType(condition.type)" :key="op.value">
                                    <option :value="op.value" x-text="op.label"></option>
                                </template>
                            </select>

                            <div class="condition-value">
                                {{-- Time input --}}
                                <template x-if="getValueType(condition.type) === 'time_range'">
                                    <div class="time-range-input">
                                        <input type="time" class="form-input" x-model="condition.value.start">
                                        <span>to</span>
                                        <input type="time" class="form-input" x-model="condition.value.end">
                                    </div>
                                </template>

                                {{-- Day select --}}
                                <template x-if="getValueType(condition.type) === 'day_select'">
                                    <div class="day-select-input">
                                        @foreach(['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $day => $label)
                                            <label class="day-option">
                                                <input type="checkbox"
                                                       value="{{ $day }}"
                                                       :checked="condition.value?.includes('{{ $day }}')"
                                                       @change="toggleDay(index, '{{ $day }}')">
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </template>

                                {{-- IP input --}}
                                <template x-if="getValueType(condition.type) === 'ip_input'">
                                    <input type="text"
                                           class="form-input"
                                           x-model="condition.value"
                                           placeholder="e.g., 10.0.0.* or 192.168.1.0/24">
                                </template>

                                {{-- Role select --}}
                                <template x-if="getValueType(condition.type) === 'role_select'">
                                    <select class="form-select" x-model="condition.value">
                                        @foreach($roles as $role)
                                            <option value="{{ $role->slug }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </template>

                                {{-- Custom input --}}
                                <template x-if="getValueType(condition.type) === 'custom_input'">
                                    <div class="custom-condition-input">
                                        <input type="text"
                                               class="form-input"
                                               x-model="condition.field"
                                               placeholder="field name">
                                        <input type="text"
                                               class="form-input"
                                               x-model="condition.value"
                                               placeholder="value">
                                    </div>
                                </template>
                            </div>

                            <button type="button"
                                    class="btn-link danger"
                                    @click="removeCondition(index)">
                                @include('backend.partials.icon', ['icon' => 'trash'])
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" class="btn-secondary mt-4" @click="addCondition">
                    @include('backend.partials.icon', ['icon' => 'plus'])
                    Add Condition
                </button>
            </div>
        </div>

        {{-- Action --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3>Action</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">When conditions do NOT match:</label>
                    <div class="flex gap-4">
                        <label class="radio-label">
                            <input type="radio" x-model="form.action" value="deny">
                            <span>
                                <strong>Deny Access</strong>
                                <span class="text-muted">- Block the permission entirely</span>
                            </span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" x-model="form.action" value="log">
                            <span>
                                <strong>Allow (Log Only)</strong>
                                <span class="text-muted">- Allow but log the access attempt</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="form-actions">
            <a href="{{ route('admin.permissions.rules') }}" class="btn-secondary">Cancel</a>

            @if(isset($rule))
                <button type="button" class="btn-danger" @click="deleteRule">
                    @include('backend.partials.icon', ['icon' => 'trash'])
                    Delete Rule
                </button>
            @endif

            <button type="button" class="btn-secondary" @click="testRule">
                @include('backend.partials.icon', ['icon' => 'play'])
                Test Rule
            </button>

            <button type="submit" class="btn-primary" :disabled="isSubmitting">
                @include('backend.partials.icon', ['icon' => 'check'])
                <span x-text="isSubmitting ? 'Saving...' : '{{ isset($rule) ? 'Save Changes' : 'Create Rule' }}'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function accessRuleForm(rule, permissions, conditionTypes) {
    return {
        form: {
            name: rule?.name || '',
            description: rule?.description || '',
            priority: rule?.priority || 10,
            is_active: rule?.is_active ?? true,
            target_permissions: rule?.target_permissions || [],
            conditions: rule?.conditions || [],
            action: rule?.action || 'deny'
        },

        permissions: permissions,
        conditionTypes: conditionTypes,
        customPattern: '',
        permissionSearch: '',
        isSubmitting: false,

        // Permission selection
        addCustomPattern() {
            if (!this.customPattern) return;
            if (!this.form.target_permissions.includes(this.customPattern)) {
                this.form.target_permissions.push(this.customPattern);
            }
            this.customPattern = '';
        },

        removeTarget(index) {
            this.form.target_permissions.splice(index, 1);
        },

        togglePermission(slug) {
            const index = this.form.target_permissions.indexOf(slug);
            if (index > -1) {
                this.form.target_permissions.splice(index, 1);
            } else {
                this.form.target_permissions.push(slug);
            }
        },

        toggleGroupPermissions(groupSlug) {
            // Find all permissions in group and toggle them
        },

        isGroupSelected(groupSlug) {
            // Check if all permissions in group are selected
            return false;
        },

        isGroupVisible(groupSlug) {
            return true;
        },

        isPermissionSearchVisible(slug) {
            if (!this.permissionSearch) return true;
            return slug.toLowerCase().includes(this.permissionSearch.toLowerCase());
        },

        // Conditions
        addCondition() {
            this.form.conditions.push({
                type: 'time',
                operator: 'between',
                value: { start: '09:00', end: '17:00' }
            });
        },

        removeCondition(index) {
            this.form.conditions.splice(index, 1);
        },

        updateConditionOperators(index) {
            // Reset operator and value when type changes
            const condition = this.form.conditions[index];
            const operators = this.getOperatorsForType(condition.type);
            if (operators.length > 0) {
                condition.operator = operators[0].value;
            }
            condition.value = this.getDefaultValue(condition.type);
        },

        getOperatorsForType(type) {
            const typeConfig = this.conditionTypes.find(t => t.key === type);
            return typeConfig?.operators || [];
        },

        getValueType(type) {
            const typeConfig = this.conditionTypes.find(t => t.key === type);
            return typeConfig?.value_type || 'text';
        },

        getDefaultValue(type) {
            switch (this.getValueType(type)) {
                case 'time_range':
                    return { start: '09:00', end: '17:00' };
                case 'day_select':
                    return ['mon', 'tue', 'wed', 'thu', 'fri'];
                default:
                    return '';
            }
        },

        toggleDay(conditionIndex, day) {
            const condition = this.form.conditions[conditionIndex];
            if (!Array.isArray(condition.value)) {
                condition.value = [];
            }
            const index = condition.value.indexOf(day);
            if (index > -1) {
                condition.value.splice(index, 1);
            } else {
                condition.value.push(day);
            }
        },

        // Form submission
        async submitForm() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                const url = '{{ isset($rule) ? route('admin.permissions.rules.update', $rule) : route('admin.permissions.rules.store') }}';
                const method = '{{ isset($rule) ? 'put' : 'post' }}';

                const response = await Vodo.api[method](url, this.form);

                if (response.success) {
                    Vodo.notification.success(response.message || 'Rule saved');
                    Vodo.pjax.load('{{ route('admin.permissions.rules') }}');
                }
            } catch (error) {
                Vodo.notification.error(error.message || 'Failed to save rule');
            } finally {
                this.isSubmitting = false;
            }
        },

        testRule() {
            Vodo.notification.info('Rule testing feature coming soon');
        },

        deleteRule() {
            @if(isset($rule))
            Vodo.modal.confirm({
                title: 'Delete Rule',
                message: 'Are you sure you want to delete this rule?',
                confirmText: 'Delete',
                confirmClass: 'btn-danger',
                onConfirm: async () => {
                    try {
                        const response = await Vodo.api.delete('{{ route('admin.permissions.rules.destroy', $rule) }}');
                        if (response.success) {
                            Vodo.notification.success(response.message || 'Rule deleted');
                            Vodo.pjax.load('{{ route('admin.permissions.rules') }}');
                        }
                    } catch (error) {
                        Vodo.notification.error(error.message || 'Failed to delete rule');
                    }
                }
            });
            @endif
        }
    };
}
</script>
@endsection
