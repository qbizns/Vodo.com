{{-- Handle PJAX requests for SPA navigation --}}
@if(request()->header('X-PJAX'))
    {{-- PJAX Response: Return only content --}}
    <div id="pjax-content" 
         data-page-title="{{ $pageTitle ?? 'Page' }}" 
         data-page-header="{{ $pageTitle ?? 'Page' }}"
         data-page-id="{{ $pageSlug ?? 'page' }}">
        @include('backend.pages.placeholder', [
            'pageTitle' => $pageTitle,
        ])
    </div>
@else
    {{-- Full page response --}}
    @extends('console::layouts.app', [
        'currentPage' => $pageSlug ?? 'page',
        'currentPageLabel' => $pageTitle ?? 'Page',
        'currentPageIcon' => 'settings',
    ])

    @section('page-title', $pageTitle ?? 'Page')
    @section('page-header', $pageTitle ?? 'Page')

    @section('page-content')
        @include('backend.pages.placeholder', [
            'pageTitle' => $pageTitle,
        ])
    @endsection
@endif
