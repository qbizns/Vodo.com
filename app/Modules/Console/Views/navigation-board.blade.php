{{-- Handle PJAX requests for SPA navigation --}}
@if(request()->header('X-PJAX'))
    {{-- PJAX Response: Return only content --}}
    <div id="pjax-content" 
         data-page-title="Navigation Board" 
         data-page-id="navigation-board"
         data-page-header="Navigation Board">
        
        @include('backend.pages.navigation-board', [
            'allNavGroups' => $allNavGroups,
            'userFavMenus' => $userFavMenus,
        ])
    </div>
@else
    {{-- Full page response --}}
    @extends('console::layouts.app', [
        'currentPage' => 'navigation-board',
        'currentPageLabel' => 'Navigation Board',
        'currentPageIcon' => 'layoutDashboard',
    ])

    @section('page-title', 'Navigation Board')
    @section('page-header', 'Navigation Board')

    @section('page-content')
        @include('backend.pages.navigation-board', [
            'allNavGroups' => $allNavGroups,
            'userFavMenus' => $userFavMenus,
        ])
    @endsection
@endif
