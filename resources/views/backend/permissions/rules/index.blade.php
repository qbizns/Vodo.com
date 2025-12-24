{{-- Access Rules (Screen 5 - Permissions & Access Control) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', 'Access Rules')
@section('page-id', 'system/permissions/rules')
@section('require-css', 'permissions')

@section('header', 'Access Rules')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.roles.index') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Roles</span>
    </a>
    <a href="{{ route('admin.permissions.rules.create') }}" class="btn-primary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>Create Rule</span>
    </a>
</div>
@endsection

@section('content')
<div class="access-rules-page">
    {{-- Info Banner --}}
    <div class="alert alert-info mb-6">
        <div class="alert-icon">
            @include('backend.partials.icon', ['icon' => 'info'])
        </div>
        <div class="alert-content">
            <p>
                Access rules add conditional restrictions to permissions. Rules are evaluated in priority order;
                first matching rule wins. <strong>Rules can only restrict, not grant, permissions.</strong>
            </p>
        </div>
    </div>

    {{-- Rules Table --}}
    @if($rules->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                @include('backend.partials.icon', ['icon' => 'shieldOff'])
            </div>
            <h3>No Access Rules</h3>
            <p>Create access rules to add conditional restrictions to permissions.</p>
            <a href="{{ route('admin.permissions.rules.create') }}" class="btn-primary mt-4">
                Create Your First Rule
            </a>
        </div>
    @else
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Name</th>
                        <th>Target Permissions</th>
                        <th>Conditions</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 80px;" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $rule)
                        <tr class="rule-row" data-rule-id="{{ $rule->id }}">
                            <td>
                                <span class="rule-priority">{{ $rule->priority }}</span>
                            </td>
                            <td>
                                <div class="rule-info">
                                    <span class="rule-name">{{ $rule->name }}</span>
                                    @if($rule->description)
                                        <p class="rule-description">{{ Str::limit($rule->description, 60) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="rule-targets">
                                    @php $permissions = $rule->permissions ?? []; @endphp
                                    @foreach(array_slice($permissions, 0, 3) as $target)
                                        <code class="target-badge">{{ $target }}</code>
                                    @endforeach
                                    @if(count($permissions) > 3)
                                        <span class="more-badge">+{{ count($permissions) - 3 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="rule-conditions">
                                    @foreach($rule->conditions ?? [] as $condition)
                                        @php
                                            $type = $condition['type'] ?? 'unknown';
                                            $value = $condition['value'] ?? '';
                                            if (is_array($value)) {
                                                $value = implode('-', $value);
                                            }
                                        @endphp
                                        <span class="condition-badge">
                                            {{ ucfirst($type) }}: {{ $value }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($rule->is_active)
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Paused</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="actions-dropdown">
                                    <button type="button" class="action-menu-btn">
                                        @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                    </button>
                                    <div class="action-menu">
                                        <a href="{{ route('admin.permissions.rules.edit', $rule) }}" class="action-item">
                                            @include('backend.partials.icon', ['icon' => 'edit'])
                                            Edit
                                        </a>
                                        <button type="button"
                                                class="action-item"
                                                onclick="toggleRule({{ $rule->id }}, {{ $rule->is_active ? 'false' : 'true' }})">
                                            @include('backend.partials.icon', ['icon' => $rule->is_active ? 'pause' : 'play'])
                                            {{ $rule->is_active ? 'Pause' : 'Activate' }}
                                        </button>
                                        <button type="button"
                                                class="action-item"
                                                onclick="testRule({{ $rule->id }})">
                                            @include('backend.partials.icon', ['icon' => 'play'])
                                            Test Rule
                                        </button>
                                        <div class="action-divider"></div>
                                        <button type="button"
                                                class="action-item danger"
                                                onclick="deleteRule({{ $rule->id }}, '{{ $rule->name }}')">
                                            @include('backend.partials.icon', ['icon' => 'trash'])
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($rules->hasPages())
            <div class="pagination-wrapper">
                {{ $rules->links() }}
            </div>
        @endif
    @endif
</div>

<script>
// Store rules data for testing
var rulesData = @json($rules->items());
var testBaseUrl = '{{ url("system/permissions/rules") }}';
var currentTestRuleId = null;

function toggleRule(ruleId, activate) {
    Vodo.ajax.put(`${testBaseUrl}/${ruleId}`, {
        is_active: activate
    }).then(response => {
        if (response.success) {
            Vodo.notifications.success(response.message || 'Rule updated');
            location.reload();
        }
    }).catch(error => {
        Vodo.notifications.error(error.message || 'Failed to update rule');
    });
}

function testRule(ruleId) {
    currentTestRuleId = ruleId;
    var rule = rulesData.find(r => r.id === ruleId);
    var ruleName = rule ? rule.name : 'Unknown Rule';
    var permissions = rule ? (rule.permissions || []) : [];
    
    // Get current time and day
    var now = new Date();
    var currentTime = now.toTimeString().slice(0, 5);
    var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var currentDay = days[now.getDay()];
    
    // Build permission options
    var permOptions = permissions.map(p => `<option value="${p}">${p}</option>`).join('');
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
            <button class="btn-primary" onclick="runRuleTest()">
                <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                Run Test
            </button>
        `
    });
}

function runRuleTest() {
    if (!currentTestRuleId) return;
    
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
    
    Vodo.ajax.post(`${testBaseUrl}/${currentTestRuleId}/test`, testData)
        .then(response => {
            if (response.success) {
                displayTestResults(response.data);
            } else {
                resultsContent.innerHTML = '<div class="test-result-error">Test failed: ' + (response.message || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            resultsContent.innerHTML = '<div class="test-result-error">Test failed: ' + (error.message || 'Unknown error') + '</div>';
        });
}

function displayTestResults(data) {
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
            html += `<li class="${condClass}"><span class="cond-icon">${icon}</span> ${cond.explanation}</li>`;
        });
        html += '</ul></div>';
    } else if (data.permission_matches === false) {
        html += '<div class="test-conditions"><p style="color: var(--text-secondary);">Permission does not match any target in this rule.</p></div>';
    } else {
        html += '<div class="test-conditions"><p style="color: var(--text-secondary);">No conditions defined - rule applies to all matching permissions.</p></div>';
    }
    
    resultsContent.innerHTML = html;
}

function deleteRule(ruleId, ruleName) {
    if (!confirm(`Are you sure you want to delete the rule "${ruleName}"? This action cannot be undone.`)) {
        return;
    }
    
    Vodo.ajax.delete(`${testBaseUrl}/${ruleId}`).then(response => {
        if (response.success) {
            Vodo.notifications.success(response.message || 'Rule deleted');
            location.reload();
        }
    }).catch(error => {
        Vodo.notifications.error(error.message || 'Failed to delete rule');
    });
}
</script>
@endsection
