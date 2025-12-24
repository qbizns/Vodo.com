@extends('backend.layouts.pjax')

@section('title', 'Subscriptions')
@section('page-id', 'subscriptions/dashboard')
@section('require-css', 'subscriptions')

@section('header', 'Subscriptions Dashboard')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('admin.plugins.subscriptions.plans.create') }}" class="btn-secondary">
        @include('backend.partials.icon', ['icon' => 'plus'])
        <span>New Plan</span>
    </a>
    <a href="{{ route('admin.plugins.subscriptions.subscriptions.create') }}" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'userPlus'])
        <span>New Subscription</span>
    </a>
</div>
@endsection

@section('content')
<div class="subscriptions-dashboard">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon--primary">
                @include('backend.partials.icon', ['icon' => 'repeat'])
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($statistics['active']) }}</div>
                <div class="stat-label">Active Subscriptions</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon--warning">
                @include('backend.partials.icon', ['icon' => 'clock'])
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($statistics['trialing']) }}</div>
                <div class="stat-label">Trialing</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon--success">
                @include('backend.partials.icon', ['icon' => 'dollarSign'])
            </div>
            <div class="stat-content">
                <div class="stat-value">${{ number_format($statistics['mrr'], 2) }}</div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon--info">
                @include('backend.partials.icon', ['icon' => 'trendingUp'])
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($statistics['this_month']) }}</div>
                <div class="stat-label">New This Month</div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
        <a href="{{ route('admin.plugins.subscriptions.plans.index') }}" class="quick-link-card">
            <div class="quick-link-icon">
                @include('backend.partials.icon', ['icon' => 'package'])
            </div>
            <div class="quick-link-content">
                <h3>Manage Plans</h3>
                <p>Create and configure subscription plans</p>
            </div>
            @include('backend.partials.icon', ['icon' => 'arrowRight'])
        </a>

        <a href="{{ route('admin.plugins.subscriptions.subscriptions.index') }}" class="quick-link-card">
            <div class="quick-link-icon">
                @include('backend.partials.icon', ['icon' => 'repeat'])
            </div>
            <div class="quick-link-content">
                <h3>Subscriptions</h3>
                <p>View and manage user subscriptions</p>
            </div>
            @include('backend.partials.icon', ['icon' => 'arrowRight'])
        </a>

        <a href="{{ route('admin.plugins.subscriptions.invoices.index') }}" class="quick-link-card">
            <div class="quick-link-icon">
                @include('backend.partials.icon', ['icon' => 'fileText'])
            </div>
            <div class="quick-link-content">
                <h3>Invoices</h3>
                <p>View and manage billing invoices</p>
            </div>
            @include('backend.partials.icon', ['icon' => 'arrowRight'])
        </a>
    </div>

    <div class="dashboard-grid">
        <!-- Plans Overview -->
        <div class="card">
            <div class="card-header">
                <h3>Plans Overview</h3>
                <a href="{{ route('admin.plugins.subscriptions.plans.index') }}" class="btn-link">View All</a>
            </div>
            <div class="card-body p-0">
                @if($plans->count() > 0)
                <div class="plan-list">
                    @foreach($plans as $plan)
                    <div class="plan-item">
                        <div class="plan-info">
                            <div class="plan-name">{{ $plan->name }}</div>
                            <div class="plan-price">{{ $plan->formatted_price }}/{{ $plan->interval_label }}</div>
                        </div>
                        <div class="plan-subscribers">
                            <span class="subscriber-count">{{ $plan->active_subscriptions_count }}</span>
                            <span class="subscriber-label">subscribers</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state py-4">
                    <p>No plans created yet.</p>
                    <a href="{{ route('admin.plugins.subscriptions.plans.create') }}" class="btn-primary mt-3">Create First Plan</a>
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Subscriptions -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Subscriptions</h3>
                <a href="{{ route('admin.plugins.subscriptions.subscriptions.index') }}" class="btn-link">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentSubscriptions->count() > 0)
                <div class="subscription-list">
                    @foreach($recentSubscriptions as $subscription)
                    <div class="subscription-item">
                        <div class="subscription-user">
                            <div class="user-avatar">
                                {{ strtoupper(substr($subscription->user->name ?? 'U', 0, 2)) }}
                            </div>
                            <div class="user-info">
                                <div class="user-name">{{ $subscription->user->name ?? 'Unknown' }}</div>
                                <div class="user-plan">{{ $subscription->plan->name ?? 'No plan' }}</div>
                            </div>
                        </div>
                        <div class="subscription-meta">
                            <span class="status-badge status-badge--{{ $subscription->status }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                            <div class="subscription-date">{{ $subscription->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state py-4">
                    <p>No subscriptions yet.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.subscriptions-dashboard {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.stat-card {
    background: var(--bg-surface-1, #fff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon svg { width: 24px; height: 24px; }
.stat-icon--primary { background: var(--primary, #6366f1)20; color: var(--primary, #6366f1); }
.stat-icon--success { background: #10b98120; color: #10b981; }
.stat-icon--warning { background: #f59e0b20; color: #f59e0b; }
.stat-icon--info { background: #3b82f620; color: #3b82f6; }

.stat-value { font-size: 1.5rem; font-weight: 700; color: var(--text-primary, #1f2937); }
.stat-label { font-size: 0.875rem; color: var(--text-secondary, #6b7280); }

.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.quick-link-card {
    background: var(--bg-surface-1, #fff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    transition: all 0.2s;
}

.quick-link-card:hover {
    border-color: var(--primary, #6366f1);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}

.quick-link-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--primary, #6366f1)10;
    color: var(--primary, #6366f1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-link-icon svg { width: 24px; height: 24px; }
.quick-link-content { flex: 1; }
.quick-link-content h3 { font-size: 1rem; font-weight: 600; color: var(--text-primary, #1f2937); margin: 0 0 4px; }
.quick-link-content p { font-size: 0.875rem; color: var(--text-secondary, #6b7280); margin: 0; }
.quick-link-card > svg { width: 20px; height: 20px; color: var(--text-tertiary, #9ca3af); }

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.plan-list, .subscription-list { }

.plan-item, .subscription-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.plan-item:last-child, .subscription-item:last-child { border-bottom: none; }

.plan-name { font-weight: 500; color: var(--text-primary, #1f2937); }
.plan-price { font-size: 0.875rem; color: var(--text-secondary, #6b7280); }

.plan-subscribers { text-align: right; }
.subscriber-count { font-weight: 600; color: var(--primary, #6366f1); }
.subscriber-label { font-size: 0.75rem; color: var(--text-tertiary, #9ca3af); margin-left: 4px; }

.subscription-user { display: flex; align-items: center; gap: 12px; }
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary, #6366f1)20;
    color: var(--primary, #6366f1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.user-name { font-weight: 500; color: var(--text-primary, #1f2937); }
.user-plan { font-size: 0.875rem; color: var(--text-secondary, #6b7280); }

.subscription-meta { text-align: right; }
.subscription-date { font-size: 0.75rem; color: var(--text-tertiary, #9ca3af); margin-top: 4px; }

.status-badge { padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }
.status-badge--active { background: #10b98120; color: #10b981; }
.status-badge--trialing { background: #f59e0b20; color: #f59e0b; }
.status-badge--cancelled { background: #ef444420; color: #ef4444; }
.status-badge--expired { background: #6b728020; color: #6b7280; }
</style>
@endsection

