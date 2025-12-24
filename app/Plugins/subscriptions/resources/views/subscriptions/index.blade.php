@extends('backend.layouts.pjax')

@section('title', 'Subscriptions')
@section('page-id', 'subscriptions/subscriptions')
@section('require-css', 'subscriptions')

@section('header', 'Subscriptions')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.plugins.subscriptions.subscriptions.create') }}" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>New Subscription</span>
    </a>
</div>
@endsection

@section('content')
<div class="subscriptions-page">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="filters-form">
                <div class="filters-row">
                    <div class="filter-group">
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                               placeholder="Search by user..." class="form-input">
                    </div>
                    <div class="filter-group">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="plan_id" class="form-select">
                            <option value="">All Plans</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ ($filters['plan_id'] ?? '') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary">
                        @include('backend.partials.icon', ['icon' => 'search'])
                        <span>Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="card">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Expires</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar">
                                    {{ strtoupper(substr($subscription->user->name ?? 'U', 0, 2)) }}
                                </div>
                                <div class="user-info">
                                    <div class="user-name">{{ $subscription->user->name ?? 'Unknown' }}</div>
                                    <div class="user-email">{{ $subscription->user->email ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="plan-name">{{ $subscription->plan->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="status-badge status-badge--{{ $subscription->status }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="price">{{ $subscription->formatted_price }}</span>
                        </td>
                        <td>
                            @if($subscription->ends_at)
                                <span class="text-secondary">{{ $subscription->ends_at->format('M d, Y') }}</span>
                                @if($subscription->days_remaining !== null && $subscription->days_remaining <= 7)
                                    <span class="badge badge--warning">{{ $subscription->days_remaining }} days</span>
                                @endif
                            @else
                                <span class="text-tertiary">Never</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="actions-dropdown">
                                <button type="button" class="btn-icon" data-dropdown-trigger>
                                    @include('backend.partials.icon', ['icon' => 'moreVertical'])
                                </button>
                                <div class="dropdown-menu">
                                    <a href="{{ route('admin.plugins.subscriptions.subscriptions.show', $subscription) }}" class="dropdown-item">
                                        @include('backend.partials.icon', ['icon' => 'eye'])
                                        <span>View</span>
                                    </a>
                                    @if($subscription->status !== 'cancelled')
                                    <button type="button" class="dropdown-item" onclick="renewSubscription({{ $subscription->id }})">
                                        @include('backend.partials.icon', ['icon' => 'refresh'])
                                        <span>Renew</span>
                                    </button>
                                    <hr class="dropdown-divider">
                                    <button type="button" class="dropdown-item dropdown-item--danger" onclick="cancelSubscription({{ $subscription->id }})">
                                        @include('backend.partials.icon', ['icon' => 'x'])
                                        <span>Cancel</span>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    @include('backend.partials.icon', ['icon' => 'repeat'])
                                </div>
                                <h3>No Subscriptions Found</h3>
                                <p>No subscriptions match your criteria.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
        <div class="card-footer">
            {{ $subscriptions->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function renewSubscription(subscriptionId) {
    Vodo.modal.confirm({
        title: 'Renew Subscription',
        message: 'Are you sure you want to renew this subscription?',
        confirmText: 'Renew',
        onConfirm: () => {
            Vodo.api.post(`/admin/subscriptions/subscriptions/${subscriptionId}/renew`)
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

function cancelSubscription(subscriptionId) {
    Vodo.modal.confirm({
        title: 'Cancel Subscription',
        message: 'Are you sure you want to cancel this subscription?',
        confirmText: 'Cancel Subscription',
        confirmClass: 'btn-danger',
        onConfirm: () => {
            Vodo.api.post(`/admin/subscriptions/subscriptions/${subscriptionId}/cancel`)
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

