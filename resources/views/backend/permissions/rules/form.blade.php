{{-- Access Rule Form - Matching SCREENS.md Wireframe --}}
{{-- Uses vanilla JS, no Alpine --}}

@extends('backend.layouts.pjax')

@section('title', isset($rule) ? 'Edit Access Rule' : 'Create Access Rule')
@section('page-id', 'system/permissions/rules/form')
@section('require-css', 'permissions')

@section('header', isset($rule) ? 'Edit Access Rule: ' . ($rule->name ?? '') : 'Create Access Rule')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.permissions.rules') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Rules</span>
    </a>
</div>
@endsection

@section('content')
@php
$formData = [
    'name' => old('name', $rule->name ?? ''),
    'description' => old('description', $rule->description ?? ''),
    'priority' => old('priority', $rule->priority ?? 1),
    'is_active' => old('is_active', $rule->is_active ?? true),
    'target_permissions' => old('target_permissions', $rule->permissions ?? []),
    'conditions' => old('conditions', $rule->conditions ?? []),
    'action' => old('action', $rule->action ?? 'deny')
];

// Ensure conditions is an array
if (!is_array($formData['conditions'])) {
    $formData['conditions'] = [];
}
if (!is_array($formData['target_permissions'])) {
    $formData['target_permissions'] = [];
}

// Define condition types with their operators
$conditionTypesList = [
    ['key' => 'time', 'label' => 'Time of Day', 'icon' => 'clock'],
    ['key' => 'day', 'label' => 'Day of Week', 'icon' => 'calendar'],
    ['key' => 'ip', 'label' => 'IP Address', 'icon' => 'globe'],
    ['key' => 'role', 'label' => 'User Role', 'icon' => 'shield'],
    ['key' => 'custom', 'label' => 'Custom Attribute', 'icon' => 'tag'],
];

$operatorsByType = [
    'time' => [
        ['key' => 'between', 'label' => 'between'],
        ['key' => 'not_between', 'label' => 'not between'],
        ['key' => 'before', 'label' => 'before'],
        ['key' => 'after', 'label' => 'after'],
    ],
    'day' => [
        ['key' => 'is_one_of', 'label' => 'is one of'],
        ['key' => 'is_not', 'label' => 'is not'],
    ],
    'ip' => [
        ['key' => 'is', 'label' => 'equals'],
        ['key' => 'is_not', 'label' => 'not equals'],
        ['key' => 'starts_with', 'label' => 'starts with'],
        ['key' => 'in_range', 'label' => 'in range'],
    ],
    'role' => [
        ['key' => 'is', 'label' => 'is'],
        ['key' => 'is_not', 'label' => 'is not'],
        ['key' => 'is_one_of', 'label' => 'is one of'],
    ],
    'custom' => [
        ['key' => 'equals', 'label' => 'equals'],
        ['key' => 'not_equals', 'label' => 'not equals'],
        ['key' => 'contains', 'label' => 'contains'],
        ['key' => 'greater_than', 'label' => 'greater than'],
        ['key' => 'less_than', 'label' => 'less than'],
    ],
];

$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
@endphp

<div class="access-rule-form-page" 
     data-component="rule-form"
     data-operators="{{ json_encode($operatorsByType) }}"
     data-roles="{{ json_encode($roles ?? []) }}"
     data-redirect-url="{{ route('admin.permissions.rules') }}">
    
    <form action="{{ isset($rule) ? route('admin.permissions.rules.update', $rule) : route('admin.permissions.rules.store') }}"
          method="POST"
          id="ruleForm"
          data-method="{{ isset($rule) ? 'put' : 'post' }}">
        @csrf
        @if(isset($rule))
            @method('PUT')
        @endif

        {{-- Rule Details --}}
        <div class="form-section mb-6">
            <div class="form-section-header">
                <h3>Rule Details</h3>
            </div>
            <div class="form-section-body">
                <div class="form-group">
                    <label for="name" class="form-label required">Rule Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-input"
                           value="{{ $formData['name'] }}"
                           placeholder="e.g., Business Hours Only"
                           required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description"
                              name="description"
                              class="form-textarea"
                              rows="2"
                              placeholder="Restrict sensitive operations to business hours">{{ $formData['description'] }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="priority" class="form-label">Priority</label>
                        <input type="number"
                               id="priority"
                               name="priority"
                               class="form-input"
                               value="{{ $formData['priority'] }}"
                               min="1"
                               max="100"
                               style="width: 100px;">
                        <span class="form-hint">(lower = evaluated first)</span>
                    </div>

                    <div class="form-group form-group-checkbox" style="padding-top: 28px;">
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1"
                                   {{ $formData['is_active'] ? 'checked' : '' }}>
                            <span>Enabled</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Target Permissions --}}
        <div class="form-section mb-6">
            <div class="form-section-header">
                <h3>Target Permissions</h3>
                <p>supports wildcards like invoices.*</p>
            </div>
            <div class="form-section-body">
                {{-- Selected Targets Display --}}
                <div id="selectedTargets" class="selected-permissions mb-4" style="{{ empty($formData['target_permissions']) ? 'display: none;' : '' }}">
                    <div class="permission-tags" id="permissionTags">
                        @foreach($formData['target_permissions'] as $target)
                            <span class="permission-tag" data-target="{{ $target }}">
                                <code>{{ $target }}</code>
                                <input type="hidden" name="permissions[]" value="{{ $target }}">
                                <button type="button" class="tag-remove" data-action="remove-target">×</button>
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Add Custom Pattern --}}
                <div class="form-group mb-4">
                    <div style="display: flex; gap: 8px;">
                        <input type="text"
                               class="form-input"
                               id="customPattern"
                               placeholder="e.g., invoices.* or users.delete"
                               style="flex: 1;">
                        <button type="button" class="btn-secondary" data-action="add-pattern">
                            @include('backend.partials.icon', ['icon' => 'plus'])
                            Add
                        </button>
                    </div>
                </div>

                {{-- Permission Checklist --}}
                <div style="border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; max-height: 300px; overflow-y: auto;">
                    <div style="padding: 12px; border-bottom: 1px solid var(--border-color, #e5e7eb); position: sticky; top: 0; background: var(--bg-surface-1, #fff); z-index: 1;">
                        <div class="search-input-wrapper" style="width: 100%;">
                            @include('backend.partials.icon', ['icon' => 'search'])
                            <input type="text"
                                   class="search-input"
                                   placeholder="Search permissions..."
                                   id="permissionSearch"
                                   style="width: 100%;">
                        </div>
                    </div>
                    <div class="permission-checklist" id="permissionChecklist" style="padding: 8px;">
                        @foreach($groupedPermissions ?? [] as $group)
                        @php
                            $groupSlug = $group['slug'] ?? '';
                            $groupName = $group['label'] ?? $group['name'] ?? $groupSlug;
                        @endphp
                        <div class="permission-check-group" data-group="{{ $groupSlug }}" style="margin-bottom: 8px;">
                            <div style="font-weight: 600; font-size: 13px; color: var(--text-secondary, #6b7280); padding: 8px 12px; background: var(--bg-surface-2, #f9fafb); border-radius: 6px;">
                                {{ $groupName }}
                            </div>
                            <div style="padding: 4px 0 4px 16px;">
                                @foreach($group['permissions'] ?? [] as $perm)
                                @php $permSlug = $perm['slug'] ?? ''; @endphp
                                <label class="permission-check-item" data-perm="{{ $permSlug }}" style="display: flex; align-items: center; gap: 8px; padding: 6px 8px; cursor: pointer; border-radius: 4px;">
                                    <input type="checkbox"
                                           data-slug="{{ $permSlug }}"
                                           {{ in_array($permSlug, $formData['target_permissions']) ? 'checked' : '' }}
                                           style="width: 16px; height: 16px;">
                                    <span style="font-size: 13px;">{{ $perm['label'] ?? $perm['name'] ?? $permSlug }}</span>
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
        <div class="form-section mb-6">
            <div class="form-section-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3>Conditions</h3>
                    <p>all must match</p>
                </div>
                <button type="button" class="btn-secondary btn-sm" data-action="add-condition">
                    @include('backend.partials.icon', ['icon' => 'plus'])
                    Add Condition
                </button>
            </div>
            <div class="form-section-body">
                <div id="conditionsList">
                    @forelse($formData['conditions'] as $index => $condition)
                    @php
                        $condType = $condition['type'] ?? 'time';
                        $condOp = $condition['operator'] ?? 'between';
                        $condValue = $condition['value'] ?? '';
                        if (is_array($condValue)) {
                            $condValue = json_encode($condValue);
                        }
                    @endphp
                    <div class="condition-row" data-index="{{ $index }}" style="display: flex; gap: 12px; align-items: flex-start; padding: 16px; background: var(--bg-surface-2, #f9fafb); border-radius: 8px; margin-bottom: 12px;">
                        {{-- Type --}}
                        <select class="form-select condition-type" name="conditions[{{ $index }}][type]" data-index="{{ $index }}" style="width: 140px;">
                            @foreach($conditionTypesList as $type)
                                <option value="{{ $type['key'] }}" {{ $condType === $type['key'] ? 'selected' : '' }}>{{ $type['label'] }}</option>
                            @endforeach
                        </select>

                        {{-- Operator --}}
                        <select class="form-select condition-operator" name="conditions[{{ $index }}][operator]" data-index="{{ $index }}" style="width: 120px;">
                            @foreach($operatorsByType[$condType] ?? [] as $op)
                                <option value="{{ $op['key'] }}" {{ $condOp === $op['key'] ? 'selected' : '' }}>{{ $op['label'] }}</option>
                            @endforeach
                        </select>

                        {{-- Value (dynamic based on type) --}}
                        <div class="condition-value" data-index="{{ $index }}" style="flex: 1;">
                            @if($condType === 'time')
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="time" class="form-input" name="conditions[{{ $index }}][value_from]" value="{{ is_array($condition['value'] ?? null) ? ($condition['value'][0] ?? '09:00') : '09:00' }}" style="width: 120px;">
                                    <span>and</span>
                                    <input type="time" class="form-input" name="conditions[{{ $index }}][value_to]" value="{{ is_array($condition['value'] ?? null) ? ($condition['value'][1] ?? '17:00') : '17:00' }}" style="width: 120px;">
                                </div>
                            @elseif($condType === 'day')
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    @php $selectedDays = is_array($condition['value'] ?? null) ? $condition['value'] : []; @endphp
                                    @foreach($days as $day)
                                        <label style="display: inline-flex; align-items: center; padding: 6px 12px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 6px; cursor: pointer; font-size: 13px; {{ in_array($day, $selectedDays) ? 'background: var(--color-accent, #6366f1); color: white; border-color: var(--color-accent, #6366f1);' : '' }}">
                                            <input type="checkbox" name="conditions[{{ $index }}][days][]" value="{{ $day }}" {{ in_array($day, $selectedDays) ? 'checked' : '' }} style="display: none;">
                                            {{ $day }}
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <input type="text" class="form-input" name="conditions[{{ $index }}][value]" value="{{ $condValue }}" placeholder="Value">
                            @endif
                        </div>

                        {{-- Remove --}}
                        <button type="button" class="btn-link danger" data-action="remove-condition" style="padding: 8px;">
                            @include('backend.partials.icon', ['icon' => 'x'])
                        </button>
                    </div>
                    @empty
                    <div class="empty-conditions" style="text-align: center; padding: 32px; color: var(--text-secondary, #6b7280);">
                        <p>No conditions added yet. Click "Add Condition" to create one.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Action --}}
        <div class="form-section mb-6">
            <div class="form-section-header">
                <h3>Action</h3>
                <p>When conditions do NOT match</p>
            </div>
            <div class="form-section-body">
                <div style="display: flex; gap: 24px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 16px 20px; border: 2px solid {{ $formData['action'] === 'deny' ? 'var(--color-accent, #6366f1)' : 'var(--border-color, #e5e7eb)' }}; border-radius: 8px; cursor: pointer; flex: 1;">
                        <input type="radio" name="action" value="deny" {{ $formData['action'] === 'deny' ? 'checked' : '' }} style="margin-top: 2px;">
                        <div>
                            <strong style="display: block;">Deny Access</strong>
                            <span style="font-size: 13px; color: var(--text-secondary, #6b7280);">Block the permission entirely</span>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 16px 20px; border: 2px solid {{ $formData['action'] === 'log' ? 'var(--color-accent, #6366f1)' : 'var(--border-color, #e5e7eb)' }}; border-radius: 8px; cursor: pointer; flex: 1;">
                        <input type="radio" name="action" value="log" {{ $formData['action'] === 'log' ? 'checked' : '' }} style="margin-top: 2px;">
                        <div>
                            <strong style="display: block;">Allow (Log Only)</strong>
                            <span style="font-size: 13px; color: var(--text-secondary, #6b7280);">Allow but log the access attempt</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="form-actions">
            <a href="{{ route('admin.permissions.rules') }}" class="btn-secondary">Cancel</a>

            @if(isset($rule))
                <button type="button" class="btn-danger" data-action="delete">
                    @include('backend.partials.icon', ['icon' => 'trash'])
                    Delete Rule
                </button>
            @endif

            <button type="button" class="btn-secondary" data-action="test">
                @include('backend.partials.icon', ['icon' => 'play'])
                Test Rule
            </button>

            <button type="submit" class="btn-primary" id="submitBtn">
                @include('backend.partials.icon', ['icon' => 'check'])
                <span class="btn-text">{{ isset($rule) ? 'Save' : 'Create Rule' }}</span>
                <span class="btn-loading" style="display: none;">Saving...</span>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    'use strict';
    
    var operatorsByType = @json($operatorsByType);
    var days = @json($days);
    var conditionIndex = {{ count($formData['conditions']) }};
    var targetPermissions = @json($formData['target_permissions']);
    
    function initRuleForm() {
        var container = document.querySelector('.access-rule-form-page[data-component="rule-form"]');
        if (!container || container.dataset.initialized) return;
        
        // Add pattern button
        var addPatternBtn = container.querySelector('[data-action="add-pattern"]');
        var customPatternInput = container.querySelector('#customPattern');
        
        if (addPatternBtn && customPatternInput) {
            addPatternBtn.addEventListener('click', function() {
                var pattern = customPatternInput.value.trim();
                if (pattern && !targetPermissions.includes(pattern)) {
                    addTargetTag(pattern);
                    customPatternInput.value = '';
                }
            });
            
            customPatternInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addPatternBtn.click();
                }
            });
        }
        
        // Permission checkboxes
        container.addEventListener('change', function(e) {
            if (e.target.matches('.permission-check-item input[type="checkbox"]')) {
                var slug = e.target.dataset.slug;
                if (e.target.checked) {
                    if (!targetPermissions.includes(slug)) {
                        addTargetTag(slug);
                    }
                } else {
                    removeTargetTag(slug);
                }
            }
            
            // Action radio styling
            if (e.target.matches('[name="action"]')) {
                container.querySelectorAll('[name="action"]').forEach(function(radio) {
                    var label = radio.closest('label');
                    if (label) {
                        label.style.borderColor = radio.checked ? 'var(--color-accent, #6366f1)' : 'var(--border-color, #e5e7eb)';
                    }
                });
            }
            
            // Condition type change - update operators
            if (e.target.matches('.condition-type')) {
                var idx = e.target.dataset.index;
                var type = e.target.value;
                updateConditionOperators(idx, type);
                updateConditionValueInput(idx, type);
            }
            
            // Day checkbox styling
            if (e.target.matches('[name^="conditions"][name$="[days][]"]')) {
                var label = e.target.closest('label');
                if (label) {
                    if (e.target.checked) {
                        label.style.background = 'var(--color-accent, #6366f1)';
                        label.style.color = 'white';
                        label.style.borderColor = 'var(--color-accent, #6366f1)';
                    } else {
                        label.style.background = '';
                        label.style.color = '';
                        label.style.borderColor = 'var(--border-color, #e5e7eb)';
                    }
                }
            }
        });
        
        // Click handlers
        container.addEventListener('click', function(e) {
            // Remove target
            if (e.target.closest('[data-action="remove-target"]')) {
                var tag = e.target.closest('.permission-tag');
                if (tag) {
                    var target = tag.dataset.target;
                    removeTargetTag(target);
                    // Uncheck checkbox
                    var cb = container.querySelector('.permission-check-item input[data-slug="' + target + '"]');
                    if (cb) cb.checked = false;
                }
            }
            
            // Add condition
            if (e.target.closest('[data-action="add-condition"]')) {
                addCondition();
            }
            
            // Remove condition
            if (e.target.closest('[data-action="remove-condition"]')) {
                var row = e.target.closest('.condition-row');
                if (row) row.remove();
                // Show empty message if no conditions
                var conditionsList = container.querySelector('#conditionsList');
                if (conditionsList && conditionsList.querySelectorAll('.condition-row').length === 0) {
                    conditionsList.innerHTML = '<div class="empty-conditions" style="text-align: center; padding: 32px; color: var(--text-secondary, #6b7280);"><p>No conditions added yet. Click "Add Condition" to create one.</p></div>';
                }
            }
            
            // Delete rule
            if (e.target.closest('[data-action="delete"]')) {
                if (!confirm('Are you sure you want to delete this rule?')) return;
                var form = container.querySelector('#ruleForm');
                Vodo.ajax.delete(form.action).then(function(response) {
                    if (response.success) {
                        Vodo.notifications.success(response.message || 'Rule deleted');
                        window.location.href = container.dataset.redirectUrl;
                    }
                }).catch(function(error) {
                    Vodo.notifications.error(error.message || 'Failed to delete rule');
                });
            }
            
            // Test rule
            if (e.target.closest('[data-action="test"]')) {
                openTestModal();
            }
        });
        
        // Permission search
        var searchInput = container.querySelector('#permissionSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var query = searchInput.value.toLowerCase();
                container.querySelectorAll('.permission-check-item').forEach(function(item) {
                    var perm = (item.dataset.perm || '').toLowerCase();
                    item.style.display = !query || perm.includes(query) ? '' : 'none';
                });
                // Hide empty groups
                container.querySelectorAll('.permission-check-group').forEach(function(group) {
                    var visible = group.querySelectorAll('.permission-check-item:not([style*="display: none"])');
                    group.style.display = visible.length > 0 ? '' : 'none';
                });
            });
        }
        
        // Form submission
        var form = container.querySelector('#ruleForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                var submitBtn = container.querySelector('#submitBtn');
                var btnText = submitBtn.querySelector('.btn-text');
                var btnLoading = submitBtn.querySelector('.btn-loading');
                
                submitBtn.disabled = true;
                if (btnText) btnText.style.display = 'none';
                if (btnLoading) btnLoading.style.display = '';
                
                var data = {
                    name: form.querySelector('[name="name"]').value,
                    description: form.querySelector('[name="description"]').value,
                    priority: parseInt(form.querySelector('[name="priority"]').value) || 1,
                    is_active: form.querySelector('[name="is_active"]')?.checked || false,
                    permissions: targetPermissions,
                    conditions: collectConditions(),
                    action: form.querySelector('[name="action"]:checked')?.value || 'deny'
                };
                
                var method = form.dataset.method || 'post';
                Vodo.ajax[method](form.action, data).then(function(response) {
                    if (response.success) {
                        Vodo.notifications.success(response.message || 'Rule saved');
                        window.location.href = container.dataset.redirectUrl;
                    }
                }).catch(function(error) {
                    Vodo.notifications.error(error.message || 'Failed to save rule');
                    submitBtn.disabled = false;
                    if (btnText) btnText.style.display = '';
                    if (btnLoading) btnLoading.style.display = 'none';
                });
            });
        }
        
        container.dataset.initialized = 'true';
    }
    
    function addTargetTag(target) {
        if (targetPermissions.includes(target)) return;
        targetPermissions.push(target);
        
        var tagsContainer = document.querySelector('#permissionTags');
        var selectedTargets = document.querySelector('#selectedTargets');
        
        if (tagsContainer) {
            var tag = document.createElement('span');
            tag.className = 'permission-tag';
            tag.dataset.target = target;
            tag.innerHTML = '<code>' + target + '</code>' +
                '<input type="hidden" name="permissions[]" value="' + target + '">' +
                '<button type="button" class="tag-remove" data-action="remove-target">×</button>';
            tagsContainer.appendChild(tag);
        }
        
        if (selectedTargets) {
            selectedTargets.style.display = '';
        }
    }
    
    function removeTargetTag(target) {
        targetPermissions = targetPermissions.filter(function(t) { return t !== target; });
        var tag = document.querySelector('.permission-tag[data-target="' + target + '"]');
        if (tag) tag.remove();
        
        var selectedTargets = document.querySelector('#selectedTargets');
        if (selectedTargets && targetPermissions.length === 0) {
            selectedTargets.style.display = 'none';
        }
    }
    
    function addCondition() {
        var conditionsList = document.querySelector('#conditionsList');
        if (!conditionsList) return;
        
        // Remove empty message if present
        var emptyMsg = conditionsList.querySelector('.empty-conditions');
        if (emptyMsg) emptyMsg.remove();
        
        var html = '<div class="condition-row" data-index="' + conditionIndex + '" style="display: flex; gap: 12px; align-items: flex-start; padding: 16px; background: var(--bg-surface-2, #f9fafb); border-radius: 8px; margin-bottom: 12px;">' +
            '<select class="form-select condition-type" name="conditions[' + conditionIndex + '][type]" data-index="' + conditionIndex + '" style="width: 140px;">' +
            '<option value="time">Time of Day</option>' +
            '<option value="day">Day of Week</option>' +
            '<option value="ip">IP Address</option>' +
            '<option value="role">User Role</option>' +
            '<option value="custom">Custom Attribute</option>' +
            '</select>' +
            '<select class="form-select condition-operator" name="conditions[' + conditionIndex + '][operator]" data-index="' + conditionIndex + '" style="width: 120px;">' +
            '<option value="between">between</option>' +
            '<option value="not_between">not between</option>' +
            '<option value="before">before</option>' +
            '<option value="after">after</option>' +
            '</select>' +
            '<div class="condition-value" data-index="' + conditionIndex + '" style="flex: 1;">' +
            '<div style="display: flex; align-items: center; gap: 8px;">' +
            '<input type="time" class="form-input" name="conditions[' + conditionIndex + '][value_from]" value="09:00" style="width: 120px;">' +
            '<span>and</span>' +
            '<input type="time" class="form-input" name="conditions[' + conditionIndex + '][value_to]" value="17:00" style="width: 120px;">' +
            '</div>' +
            '</div>' +
            '<button type="button" class="btn-link danger" data-action="remove-condition" style="padding: 8px;">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
            '</button>' +
            '</div>';
        
        conditionsList.insertAdjacentHTML('beforeend', html);
        conditionIndex++;
    }
    
    function updateConditionOperators(idx, type) {
        var select = document.querySelector('.condition-operator[data-index="' + idx + '"]');
        if (!select) return;
        
        var ops = operatorsByType[type] || [];
        select.innerHTML = '';
        ops.forEach(function(op) {
            var option = document.createElement('option');
            option.value = op.key;
            option.textContent = op.label;
            select.appendChild(option);
        });
    }
    
    function updateConditionValueInput(idx, type) {
        var valueDiv = document.querySelector('.condition-value[data-index="' + idx + '"]');
        if (!valueDiv) return;
        
        var html = '';
        
        if (type === 'time') {
            html = '<div style="display: flex; align-items: center; gap: 8px;">' +
                '<input type="time" class="form-input" name="conditions[' + idx + '][value_from]" value="09:00" style="width: 120px;">' +
                '<span>and</span>' +
                '<input type="time" class="form-input" name="conditions[' + idx + '][value_to]" value="17:00" style="width: 120px;">' +
                '</div>';
        } else if (type === 'day') {
            html = '<div style="display: flex; gap: 6px; flex-wrap: wrap;">';
            days.forEach(function(day) {
                html += '<label style="display: inline-flex; align-items: center; padding: 6px 12px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 6px; cursor: pointer; font-size: 13px;">' +
                    '<input type="checkbox" name="conditions[' + idx + '][days][]" value="' + day + '" style="display: none;">' +
                    day +
                    '</label>';
            });
            html += '</div>';
        } else {
            html = '<input type="text" class="form-input" name="conditions[' + idx + '][value]" placeholder="Value">';
        }
        
        valueDiv.innerHTML = html;
    }
    
    function collectConditions() {
        var conditions = [];
        document.querySelectorAll('.condition-row').forEach(function(row) {
            var idx = row.dataset.index;
            var type = row.querySelector('.condition-type')?.value || 'time';
            var operator = row.querySelector('.condition-operator')?.value || 'between';
            var value;
            
            if (type === 'time') {
                var from = row.querySelector('[name="conditions[' + idx + '][value_from]"]')?.value || '09:00';
                var to = row.querySelector('[name="conditions[' + idx + '][value_to]"]')?.value || '17:00';
                value = [from, to];
            } else if (type === 'day') {
                var checkboxes = row.querySelectorAll('[name="conditions[' + idx + '][days][]"]:checked');
                value = Array.from(checkboxes).map(function(cb) { return cb.value; });
                // Ensure at least an empty array
                if (!value) value = [];
            } else {
                value = row.querySelector('[name="conditions[' + idx + '][value]"]')?.value || '';
            }
            
            conditions.push({ type: type, operator: operator, value: value });
        });
        return conditions;
    }
    
    // Test Rule Modal
    function openTestModal() {
        @if(isset($rule))
        var ruleId = {{ $rule->id ?? 'null' }};
        var ruleName = '{{ addslashes($rule->name ?? "New Rule") }}';
        var permissions = targetPermissions.length > 0 ? targetPermissions : @json($rule->permissions ?? []);
        @else
        var ruleId = null;
        var ruleName = document.getElementById('name')?.value || 'New Rule';
        var permissions = targetPermissions;
        @endif
        
        if (!ruleId) {
            Vodo.notifications.warning('Please save the rule first before testing');
            return;
        }
        
        // Get current time and day
        var now = new Date();
        var currentTime = now.toTimeString().slice(0, 5);
        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        var currentDay = days[now.getDay()];
        
        // Build permission options
        var permOptions = permissions.map(function(p) { return '<option value="' + p + '">' + p + '</option>'; }).join('');
        if (permOptions === '') {
            permOptions = '<option value="">No permissions defined</option>';
        }
        
        var modalContent = `
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                Simulate conditions to test if this rule would trigger.
            </p>
            
            <div class="test-params-section">
                <h4 style="margin-bottom: 16px; font-size: 14px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Test Parameters</h4>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Current Time</label>
                        <input type="time" id="testTime" class="form-input" value="${currentTime}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Day</label>
                        <select id="testDay" class="form-select">
                            <option value="Monday" ${currentDay === 'Monday' ? 'selected' : ''}>Monday</option>
                            <option value="Tuesday" ${currentDay === 'Tuesday' ? 'selected' : ''}>Tuesday</option>
                            <option value="Wednesday" ${currentDay === 'Wednesday' ? 'selected' : ''}>Wednesday</option>
                            <option value="Thursday" ${currentDay === 'Thursday' ? 'selected' : ''}>Thursday</option>
                            <option value="Friday" ${currentDay === 'Friday' ? 'selected' : ''}>Friday</option>
                            <option value="Saturday" ${currentDay === 'Saturday' ? 'selected' : ''}>Saturday</option>
                            <option value="Sunday" ${currentDay === 'Sunday' ? 'selected' : ''}>Sunday</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">IP Address</label>
                        <input type="text" id="testIp" class="form-input" value="192.168.1.100" placeholder="e.g., 192.168.1.100">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">User Role</label>
                        <input type="text" id="testRole" class="form-input" value="Manager" placeholder="e.g., Manager, Admin">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Custom Attribute</label>
                        <input type="text" id="testCustom" class="form-input" placeholder="e.g., department=finance">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Permission to Check</label>
                    <select id="testPermission" class="form-select">
                        ${permOptions}
                    </select>
                    <input type="text" id="testPermissionCustom" class="form-input" style="margin-top: 8px;" placeholder="Or enter custom permission...">
                </div>
            </div>
            
            <div id="testResults" style="margin-top: 24px; display: none;">
                <h4 style="margin-bottom: 16px; font-size: 14px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Test Results</h4>
                <div id="testResultsContent"></div>
            </div>
        `;
        
        Vodo.modals.open({
            id: 'test-rule-modal',
            title: 'Test Rule: ' + ruleName,
            size: 'lg',
            content: modalContent,
            footer: `
                <button class="btn-secondary" data-modal-close>Close</button>
                <button class="btn-primary" id="runTestBtn">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    Run Test
                </button>
            `,
            onOpen: function(id, $modal) {
                document.getElementById('runTestBtn').addEventListener('click', function() {
                    runTest(ruleId);
                });
            }
        });
    }
    
    function runTest(ruleId) {
        var permission = document.getElementById('testPermissionCustom').value.trim() || 
                         document.getElementById('testPermission').value;
        
        if (!permission) {
            Vodo.notifications.error('Please select or enter a permission to test');
            return;
        }
        
        var testData = {
            time: document.getElementById('testTime').value,
            day: document.getElementById('testDay').value,
            ip: document.getElementById('testIp').value,
            role: document.getElementById('testRole').value,
            custom: document.getElementById('testCustom').value,
            permission: permission
        };
        
        // Show loading state
        var resultsDiv = document.getElementById('testResults');
        var resultsContent = document.getElementById('testResultsContent');
        resultsDiv.style.display = 'block';
        resultsContent.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-secondary);"><span>Testing...</span></div>';
        
        Vodo.ajax.post('{{ url("system/permissions/rules") }}/' + ruleId + '/test', testData)
            .then(function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    resultsContent.innerHTML = '<div class="test-result-error">Test failed: ' + (response.message || 'Unknown error') + '</div>';
                }
            })
            .catch(function(error) {
                resultsContent.innerHTML = '<div class="test-result-error">Test failed: ' + (error.message || 'Unknown error') + '</div>';
            });
    }
    
    function displayResults(data) {
        var resultsContent = document.getElementById('testResultsContent');
        
        // Summary box
        var summaryClass = 'test-result-neutral';
        if (data.matches && data.action === 'deny') {
            summaryClass = 'test-result-deny';
        } else if (data.matches && data.action === 'log') {
            summaryClass = 'test-result-log';
        } else if (!data.matches) {
            summaryClass = 'test-result-allow';
        }
        
        var html = `
            <div class="test-summary ${summaryClass}">
                <span class="test-summary-icon">${data.matches ? '⚠' : '✓'}</span>
                <span class="test-summary-text">${data.summary}</span>
            </div>
        `;
        
        // Condition breakdown
        if (data.condition_results && data.condition_results.length > 0) {
            html += '<div class="test-conditions"><h5>Condition Breakdown:</h5><ul>';
            data.condition_results.forEach(function(cond) {
                var icon = cond.passes ? '✓' : '✗';
                var condClass = cond.passes ? 'condition-pass' : 'condition-fail';
                html += '<li class="' + condClass + '"><span class="cond-icon">' + icon + '</span> ' + cond.explanation + '</li>';
            });
            html += '</ul></div>';
        } else if (data.permission_matches === false) {
            html += '<div class="test-conditions"><p style="color: var(--text-secondary);">Permission does not match any target in this rule.</p></div>';
        } else {
            html += '<div class="test-conditions"><p style="color: var(--text-secondary);">No conditions defined - rule applies to all matching permissions.</p></div>';
        }
        
        resultsContent.innerHTML = html;
    }
    
    // Init
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initRuleForm, 0);
    } else {
        document.addEventListener('DOMContentLoaded', initRuleForm);
    }
    document.addEventListener('pjax:complete', initRuleForm);
})();
</script>

<style>
/* Permission tag styling */
.permission-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.permission-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 6px;
}

.permission-tag code {
    font-size: 13px;
    color: #6366f1;
}

.permission-tag .tag-remove {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-tertiary, #9ca3af);
    font-size: 16px;
    line-height: 1;
    padding: 0 2px;
}

.permission-tag .tag-remove:hover {
    color: #ef4444;
}

/* Permission checklist hover */
.permission-check-item:hover {
    background: var(--bg-surface-2, #f9fafb);
}
</style>
@endsection
