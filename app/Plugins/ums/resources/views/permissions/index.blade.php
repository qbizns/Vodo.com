@extends('backend.layouts.pjax')

@section('title', 'Permissions')
@section('page-id', 'ums/permissions')
@section('require-css', 'ums')

@section('header', 'Permissions')

@section('header-actions')
<div class="flex items-center gap-3">
    <span class="text-secondary">{{ $totalPermissions }} total permissions</span>
</div>
@endsection

@section('content')
<div class="permissions-page">
    @foreach($permissionGroups as $group)
    <div class="card mb-4">
        <div class="card-header">
            <div class="group-header">
                <div class="group-icon">
                    @include('backend.partials.icon', ['icon' => $group->icon ?? 'folder'])
                </div>
                <div class="group-info">
                    <h3>{{ $group->name }}</h3>
                    @if($group->description)
                        <p>{{ $group->description }}</p>
                    @endif
                </div>
                <span class="group-count">{{ $group->permissions_count }} permissions</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($group->permissions->count() > 0)
            <div class="permissions-list">
                @foreach($group->permissions as $permission)
                <div class="permission-item">
                    <div class="permission-info">
                        <div class="permission-name">{{ $permission->label ?? $permission->name }}</div>
                        <div class="permission-slug">{{ $permission->slug }}</div>
                    </div>
                    @if($permission->description)
                    <div class="permission-description">{{ $permission->description }}</div>
                    @endif
                    @if($permission->is_dangerous)
                    <span class="badge badge--danger">Dangerous</span>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state py-4">
                <p>No permissions in this group.</p>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

<style>
.group-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.group-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--primary, #6366f1)10;
    color: var(--primary, #6366f1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.group-icon svg {
    width: 20px;
    height: 20px;
}

.group-info {
    flex: 1;
}

.group-info h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.group-info p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
}

.group-count {
    font-size: 0.875rem;
    color: var(--text-tertiary, #9ca3af);
}

.permissions-list {
    divide-y divide-gray-100;
}

.permission-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.permission-item:last-child {
    border-bottom: none;
}

.permission-info {
    min-width: 200px;
}

.permission-name {
    font-weight: 500;
    color: var(--text-primary, #1f2937);
}

.permission-slug {
    font-size: 0.75rem;
    color: var(--text-tertiary, #9ca3af);
    font-family: monospace;
}

.permission-description {
    flex: 1;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
}

.badge--danger {
    background: #ef444420;
    color: #ef4444;
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 4px;
}
</style>
@endsection

