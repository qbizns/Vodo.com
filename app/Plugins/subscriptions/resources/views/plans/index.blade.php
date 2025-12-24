@extends('backend.layouts.pjax')

@section('title', 'Plans')
@section('page-id', 'subscriptions/plans')
@section('require-css', 'subscriptions')

@section('header', 'Subscription Plans')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.plugins.subscriptions.plans.create') }}" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>Add Plan</span>
    </a>
</div>
@endsection

@section('content')
<div class="plans-page">
    <div class="card">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Interval</th>
                        <th>Subscribers</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                    <tr>
                        <td>
                            <div class="plan-cell">
                                <div class="plan-name">{{ $plan->name }}</div>
                                <div class="plan-slug">{{ $plan->slug }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="price">{{ $plan->formatted_price }}</span>
                        </td>
                        <td>
                            <span class="interval">{{ $plan->interval_label }}</span>
                        </td>
                        <td>
                            <span class="text-secondary">{{ $plan->active_subscriptions_count }}</span>
                        </td>
                        <td>
                            @if($plan->is_active)
                                <span class="status-badge status-badge--success">Active</span>
                            @else
                                <span class="status-badge status-badge--danger">Inactive</span>
                            @endif
                            @if($plan->is_popular)
                                <span class="badge badge--info">Popular</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="actions-dropdown">
                                <button type="button" class="btn-icon" data-dropdown-trigger>
                                    @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                </button>
                                <div class="dropdown-menu">
                                    <a href="{{ route('admin.plugins.subscriptions.plans.edit', $plan) }}" class="dropdown-item">
                                        @include('backend.partials.icon', ['icon' => 'edit'])
                                        <span>Edit</span>
                                    </a>
                                    <button type="button" class="dropdown-item" onclick="togglePlanStatus({{ $plan->id }})">
                                        @include('backend.partials.icon', ['icon' => $plan->is_active ? 'eyeOff' : 'eye'])
                                        <span>{{ $plan->is_active ? 'Deactivate' : 'Activate' }}</span>
                                    </button>
                                    <hr class="dropdown-divider">
                                    <button type="button" class="dropdown-item dropdown-item--danger" onclick="deletePlan({{ $plan->id }})">
                                        @include('backend.partials.icon', ['icon' => 'trash'])
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    @include('backend.partials.icon', ['icon' => 'package'])
                                </div>
                                <h3>No Plans Found</h3>
                                <p>Create your first subscription plan.</p>
                                <a href="{{ route('admin.plugins.subscriptions.plans.create') }}" class="btn-primary mt-4">Create Plan</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($plans->hasPages())
        <div class="card-footer">
            {{ $plans->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function togglePlanStatus(planId) {
    Vodo.api.post(`/admin/subscriptions/plans/${planId}/toggle-status`)
        .then(response => {
            if (response.success) {
                Vodo.notification.success(response.message);
                Vodo.pjax.reload();
            }
        })
        .catch(error => {
            Vodo.notification.error(error.message);
        });
}

function deletePlan(planId) {
    Vodo.modal.confirm({
        title: 'Delete Plan',
        message: 'Are you sure you want to delete this plan?',
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        onConfirm: () => {
            Vodo.api.delete(`/admin/subscriptions/plans/${planId}`)
                .then(response => {
                    if (response.success) {
                        Vodo.notification.success(response.message);
                        Vodo.pjax.reload();
                    }
                })
                .catch(error => {
                    Vodo.notification.error(error.message);
                });
        }
    });
}
</script>
@endsection

