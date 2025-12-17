{{-- Handle PJAX requests for SPA navigation --}}
@if(request()->header('X-PJAX'))
    {{-- PJAX Response: Return only content with CSS requirement --}}
    <div id="pjax-content" 
         data-page-title="{{ __t('plugins.management') }}" 
         data-page-id="system/plugins"
         data-require-css="plugins">
        @include('backend.plugins.content', [
            'plugins' => $plugins,
            'routePrefix' => 'owner',
        ])
    </div>
@else
    {{-- Full page response --}}
    @extends('owner::layouts.app', [
        'currentPage' => 'system/plugins',
        'currentPageLabel' => __t('plugins.management'),
        'currentPageIcon' => 'plug',
    ])

    @section('page-title', __t('plugins.management'))
    @section('page-header', __t('plugins.management'))

    @section('page-content')
        @include('backend.plugins.content', [
            'plugins' => $plugins,
            'routePrefix' => 'owner',
        ])
    @endsection

    @include('backend.plugins.scripts')
@endif
