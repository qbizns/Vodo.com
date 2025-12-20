{{-- Handle PJAX requests for SPA navigation --}}
@if(request()->header('X-PJAX'))
    {{-- PJAX Response: Return only content with CSS requirement --}}
    <div id="pjax-content" 
         data-page-title="{{ __t('plugins.installed') }}" 
         data-page-id="system/plugins"
         data-require-css="plugins">
         
        {{-- Header actions for PJAX to update --}}
        <div id="pjax-header-actions" style="display:none;">
            @include('backend.plugins.partials.header-actions')
        </div>

        @include('backend.plugins.index-content', [
            'plugins' => $plugins,
            'stats' => $stats,
            'categories' => $categories,
        ])
    </div>
@else
    {{-- Full page response --}}
    @extends('admin::layouts.app', [
        'currentPage' => 'system/plugins',
        'currentPageLabel' => __t('plugins.installed'),
        'currentPageIcon' => 'plug',
    ])

    @section('page-title', __t('plugins.installed'))
    @section('page-header', __t('plugins.installed'))

    @section('header-actions')
        @include('backend.plugins.partials.header-actions')
    @endsection

    @section('page-content')
        @include('backend.plugins.index-content', [
            'plugins' => $plugins,
            'stats' => $stats,
            'categories' => $categories,
        ])
    @endsection

    @include('backend.plugins.scripts')
@endif
