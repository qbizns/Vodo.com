@extends('backend.layouts.pjax')

@section('title', 'User Management')
@section('page-id', 'ums/dashboard')
@section('require-css', 'ums')

@section('header', 'User Management Dashboard')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('plugins.ums.users.create') }}" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'userPlus'])
        <span>Add User</span>
    </a>
</div>
@endsection

@section('content')
<div class="ums-dashboard">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon--primary">
                @include('backend.partials.icon', ['icon' => 'users'])
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon--success">
                @include('backend.partials.icon', ['icon' => 'userCheck'])
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($statistics['active']) }}</div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon--warning">
                @include('backend.partials.icon', ['icon' => 'userX'])
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($statistics['inactive']) }}</div>
                <div class="stat-label">Inactive Users</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon--info">
                @include('backend.partials.icon', ['icon' => 'userPlus'])
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($statistics['today']) }}</div>
                <div class="stat-label">New Today</div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
        <a href="{{ route('plugins.ums.users.index') }}" class="quick-link-card">
            <div class="quick-link-icon">
                @include('backend.partials.icon', ['icon' => 'users'])
            </div>
            <div class="quick-link-content">
                <h3>Manage Users</h3>
                <p>View, create, edit and delete users</p>
            </div>
            @include('backend.partials.icon', ['icon' => 'arrowRight'])
        </a>

        <a href="{{ route('plugins.ums.roles.index') }}" class="quick-link-card">
            <div class="quick-link-icon">
                @include('backend.partials.icon', ['icon' => 'shield'])
            </div>
            <div class="quick-link-content">
                <h3>Manage Roles</h3>
                <p>Configure roles and their permissions</p>
            </div>
            @include('backend.partials.icon', ['icon' => 'arrowRight'])
        </a>

        <a href="{{ route('plugins.ums.permissions.index') }}" class="quick-link-card">
            <div class="quick-link-icon">
                @include('backend.partials.icon', ['icon' => 'key'])
            </div>
            <div class="quick-link-content">
                <h3>View Permissions</h3>
                <p>Browse all available permissions</p>
            </div>
            @include('backend.partials.icon', ['icon' => 'arrowRight'])
        </a>
    </div>

    <!-- Recent Users -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Users</h3>
            <a href="{{ route('plugins.ums.users.index') }}" class="btn-link">
                View All
            </a>
        </div>
        <div class="card-body p-0">
            @if($recentUsers->count() > 0)
            <div class="user-list">
                @foreach($recentUsers as $user)
                <div class="user-list-item">
                    <div class="user-avatar">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}">
                        @else
                            <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ $user->name }}</div>
                        <div class="user-email">{{ $user->email }}</div>
                    </div>
                    <div class="user-meta">
                        <div class="user-roles">
                            @foreach($user->roles->take(2) as $role)
                                <span class="badge" style="background: {{ $role->color }}20; color: {{ $role->color }}">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                        <div class="user-date">{{ $user->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <p>No users yet.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.ums-dashboard {
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

.stat-icon svg {
    width: 24px;
    height: 24px;
}

.stat-icon--primary {
    background: var(--primary, #6366f1)20;
    color: var(--primary, #6366f1);
}

.stat-icon--success {
    background: #10b98120;
    color: #10b981;
}

.stat-icon--warning {
    background: #f59e0b20;
    color: #f59e0b;
}

.stat-icon--info {
    background: #3b82f620;
    color: #3b82f6;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #1f2937);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
}

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

.quick-link-icon svg {
    width: 24px;
    height: 24px;
}

.quick-link-content {
    flex: 1;
}

.quick-link-content h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary, #1f2937);
    margin: 0 0 4px;
}

.quick-link-content p {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
    margin: 0;
}

.quick-link-card > svg {
    width: 20px;
    height: 20px;
    color: var(--text-tertiary, #9ca3af);
}

.user-list {
    divide-y divide-gray-100;
}

.user-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.user-list-item:last-child {
    border-bottom: none;
}

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
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info {
    flex: 1;
}

.user-name {
    font-weight: 500;
    color: var(--text-primary, #1f2937);
}

.user-email {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
}

.user-meta {
    text-align: right;
}

.user-roles {
    display: flex;
    gap: 4px;
    justify-content: flex-end;
    margin-bottom: 4px;
}

.user-date {
    font-size: 0.75rem;
    color: var(--text-tertiary, #9ca3af);
}

.badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}
</style>
@endsection

