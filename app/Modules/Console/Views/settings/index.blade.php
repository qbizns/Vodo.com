{{-- Handle PJAX requests for SPA navigation --}}
@if(request()->header('X-PJAX'))
    {{-- PJAX Response: Return only content with CSS requirement --}}
    <div id="pjax-content" 
         data-page-title="Settings" 
         data-page-id="system/settings"
         data-require-css="settings">
        @include('backend.settings.index', [
            'activeSection' => $activeSection,
            'pluginsWithSettings' => $pluginsWithSettings,
            'sectionContent' => $sectionContent,
        ])
    </div>
@else
    {{-- Full page response --}}
    @extends('console::layouts.app', [
        'currentPage' => 'system/settings',
        'currentPageLabel' => 'Settings',
        'currentPageIcon' => 'settings',
    ])

    @section('page-title', 'Settings')
    @section('page-header', 'Settings')

    @section('page-content')
        @include('backend.settings.index', [
            'activeSection' => $activeSection,
            'pluginsWithSettings' => $pluginsWithSettings,
            'sectionContent' => $sectionContent,
        ])
    @endsection
@endif
