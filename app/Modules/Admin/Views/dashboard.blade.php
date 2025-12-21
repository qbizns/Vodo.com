{{-- Handle PJAX requests for SPA navigation --}}
@if(request()->header('X-PJAX'))
    {{-- PJAX Response: Return only content --}}
    <div id="pjax-content" 
         data-page-title="{{ __t('dashboard.title') }}" 
         data-page-header="{{ __t('dashboard.title') }}"
         data-page-id="dashboard"
         data-hide-title-bar="true">
        @include('backend.dashboard.index', [
            'widgets' => $widgets,
            'unusedWidgets' => $unusedWidgets,
            'currentDashboard' => $currentDashboard,
        ])
    </div>
@else
    {{-- Full page response --}}
    @extends('admin::layouts.app', [
        'currentPage' => 'dashboard',
        'currentPageLabel' => __t('dashboard.title'),
        'currentPageIcon' => 'layoutDashboard',
        'hidePageTitle' => true,
    ])

    @section('page-title', __t('dashboard.title'))

    @section('page-content')
    @include('backend.dashboard.index', [
        'widgets' => $widgets,
        'unusedWidgets' => $unusedWidgets,
        'currentDashboard' => $currentDashboard,
    ])
    @endsection
@endif
