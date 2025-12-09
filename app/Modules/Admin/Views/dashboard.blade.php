@extends('admin::layouts.app', [
    'currentPage' => 'dashboard',
    'currentPageLabel' => 'Dashboard',
    'currentPageIcon' => 'layoutDashboard',
])

@section('page-title', 'Dashboard')
@section('page-header', 'Dashboard')

@section('page-content')
<div class="dashboard-container">
    {{-- Stats Grid --}}
    <div class="quick-actions-grid">
        <div class="widget" style="cursor: default;">
            <div class="widget-header">
                <div class="widget-title-group">
                    <span class="widget-title">Total Clients</span>
                </div>
            </div>
            <p style="font-size: 28px; font-weight: var(--font-weight-semibold); color: var(--text-primary);">50</p>
        </div>
        <div class="widget" style="cursor: default;">
            <div class="widget-header">
                <div class="widget-title-group">
                    <span class="widget-title">Active Tasks</span>
                </div>
            </div>
            <p style="font-size: 28px; font-weight: var(--font-weight-semibold); color: var(--text-primary);">0</p>
        </div>
        <div class="widget" style="cursor: default;">
            <div class="widget-header">
                <div class="widget-title-group">
                    <span class="widget-title">Pending Requests</span>
                </div>
            </div>
            <p style="font-size: 28px; font-weight: var(--font-weight-semibold); color: var(--text-primary);">0</p>
        </div>
        <div class="widget" style="cursor: default;">
            <div class="widget-header">
                <div class="widget-title-group">
                    <span class="widget-title">Completed Tasks</span>
                </div>
            </div>
            <p style="font-size: 28px; font-weight: var(--font-weight-semibold); color: var(--text-primary);">0</p>
        </div>
    </div>

    {{-- Welcome Section --}}
    <div class="widget" style="cursor: default;">
        <div class="widget-header">
            <div class="widget-title-group">
                <span class="widget-title">Welcome to Admin Panel</span>
            </div>
        </div>
        <p style="color: var(--text-secondary); line-height: 1.6;">
            Manage clients, tasks, and system operations from this dashboard.
        </p>
    </div>
</div>
@endsection
