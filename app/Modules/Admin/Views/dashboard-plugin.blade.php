{{-- Handle PJAX requests for SPA navigation --}}
@if(request()->header('X-PJAX'))
    {{-- PJAX Response: Return only content --}}
    <div id="pjax-content" 
         data-page-title="{{ $currentPlugin['title'] ?? 'Plugin Dashboard' }}" 
         data-page-header="{{ $currentPlugin['title'] ?? 'Plugin Dashboard' }}"
         data-page-id="dashboard"
         data-hide-title-bar="true">
        @include('backend.dashboard.index', [
            'widgets' => $widgets,
            'unusedWidgets' => $unusedWidgets,
            'currentDashboard' => $currentDashboard,
            'currentPlugin' => $currentPlugin ?? null,
        ])
    </div>
@else
    {{-- Full page response --}}
    @extends('admin::layouts.app', [
        'currentPage' => 'dashboard',
        'currentPageLabel' => ($currentPlugin['title'] ?? 'Plugin Dashboard'),
        'currentPageIcon' => ($currentPlugin['icon'] ?? 'layoutDashboard'),
        'hidePageTitle' => true,
    ])

    @section('page-title', ($currentPlugin['title'] ?? 'Plugin Dashboard'))

    @section('page-content')
    @include('backend.dashboard.index', [
        'widgets' => $widgets,
        'unusedWidgets' => $unusedWidgets,
        'currentDashboard' => $currentDashboard,
        'currentPlugin' => $currentPlugin ?? null,
    ])
    @endsection
@endif
