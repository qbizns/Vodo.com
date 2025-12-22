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
                                    @foreach(array_slice($rule->target_permissions, 0, 3) as $target)
                                        <code class="target-badge">{{ $target }}</code>
                                    @endforeach
                                    @if(count($rule->target_permissions) > 3)
                                        <span class="more-badge">+{{ count($rule->target_permissions) - 3 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="rule-conditions">
                                    @foreach($rule->conditions as $condition)
                                        <span class="condition-badge">
                                            {{ ucfirst($condition['type']) }}:
                                            {{ $condition['value'] ?? '' }}
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
function toggleRule(ruleId, activate) {
    Vodo.api.put(`{{ url('admin/system/permissions/rules') }}/${ruleId}`, {
        is_active: activate
    }).then(response => {
        if (response.success) {
            Vodo.notification.success(response.message || 'Rule updated');
            location.reload();
        }
    }).catch(error => {
        Vodo.notification.error(error.message || 'Failed to update rule');
    });
}

function testRule(ruleId) {
    // Would open a modal to test the rule with sample data
    Vodo.notification.info('Rule testing feature coming soon');
}

function deleteRule(ruleId, ruleName) {
    Vodo.modal.confirm({
        title: 'Delete Access Rule',
        message: `Are you sure you want to delete the rule "${ruleName}"? This action cannot be undone.`,
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        onConfirm: () => {
            Vodo.api.delete(`{{ url('admin/system/permissions/rules') }}/${ruleId}`).then(response => {
                if (response.success) {
                    Vodo.notification.success(response.message || 'Rule deleted');
                    location.reload();
                }
            }).catch(error => {
                Vodo.notification.error(error.message || 'Failed to delete rule');
            });
        }
    });
}
</script>
@endsection
