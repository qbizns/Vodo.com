@extends('console::layouts.app', [
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
                    <span class="widget-title">Total Owners</span>
                </div>
            </div>
            <p style="font-size: 28px; font-weight: var(--font-weight-semibold); color: var(--text-primary);">0</p>
        </div>
        <div class="widget" style="cursor: default;">
            <div class="widget-header">
                <div class="widget-title-group">
                    <span class="widget-title">Active Subscriptions</span>
                </div>
            </div>
            <p style="font-size: 28px; font-weight: var(--font-weight-semibold); color: var(--text-primary);">0</p>
        </div>
        <div class="widget" style="cursor: default;">
            <div class="widget-header">
                <div class="widget-title-group">
                    <span class="widget-title">Total Revenue</span>
                </div>
            </div>
            <p style="font-size: 28px; font-weight: var(--font-weight-semibold); color: var(--text-primary);">$0</p>
        </div>
        <div class="widget" style="cursor: default;">
            <div class="widget-header">
                <div class="widget-title-group">
                    <span class="widget-title">Total Clients</span>
                </div>
            </div>
            <p style="font-size: 28px; font-weight: var(--font-weight-semibold); color: var(--text-primary);">0</p>
        </div>
    </div>

    {{-- Welcome Section --}}
    <div class="widget" style="cursor: default;">
        <div class="widget-header">
            <div class="widget-title-group">
                <span class="widget-title">Welcome to Vodo Console</span>
            </div>
        </div>
        <p style="color: var(--text-secondary); line-height: 1.6;">
            This is the SaaS super admin panel. From here you can manage all owners, subscriptions, and system settings.
        </p>
    </div>
</div>
@endsection
