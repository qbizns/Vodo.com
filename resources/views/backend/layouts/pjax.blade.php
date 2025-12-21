{{--
    PJAX Layout - Returns content fragment for AJAX navigation
    This layout is used when X-PJAX header is present in the request
    It returns only the content with inline styles, not the full page

    Supports two response formats:
    1. X-Fragment-Only header: Returns JSON with content, title, etc.
    2. X-PJAX header: Returns HTML fragment wrapped in #pjax-content
--}}

@if(request()->header('X-Fragment-Only'))
    {{-- JSON Fragment Response (new format for Vodo.ajax.fragment) --}}
    @php
        $fragmentData = [
            'content' => $__env->yieldContent('content'),
            'title' => trim($__env->yieldContent('title', __t('common.page'))),
            'header' => trim($__env->yieldContent('header', '')),
            'headerActions' => trim($__env->yieldContent('header-actions', '')),
            'css' => trim($__env->yieldContent('require-css', '')),
            'pageId' => trim($__env->yieldContent('page-id', '')),
        ];

        // Include inline scripts if present
        $inlineScripts = $__env->yieldPushContent('inline-scripts');
        if (!empty(trim($inlineScripts))) {
            $fragmentData['scripts'] = $inlineScripts;
        }

        // Include inline styles if present
        $inlineStyles = $__env->yieldPushContent('inline-styles');
        if (!empty(trim($inlineStyles))) {
            $fragmentData['styles'] = $inlineStyles;
        }
    @endphp
    {!! json_encode($fragmentData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
@elseif(request()->header('X-PJAX'))
    {{-- PJAX Response: Only content with inline styles --}}
    <div id="pjax-content" 
         data-page-title="@yield('title', __t('common.page'))" 
         data-page-id="@yield('page-id', '')"
         data-page-header="@yield('header', '')"
         data-require-css="@yield('require-css', '')">
        
        {{-- Header actions for PJAX to update --}}
        {{-- Header actions for PJAX to update --}}
        <div id="pjax-header-actions" style="display:none;">
            @yield('header-actions')
        </div>

        
        {{-- Inline styles for this page --}}
        @stack('inline-styles')
        
        {{-- Page content --}}
        @yield('content')
        
        {{-- Inline scripts for this page --}}
        @stack('inline-scripts')
    </div>
@else
    {{-- Full page response - extend the main app layout --}}
    {{-- Pass currentPage to enable proper CSS loading on full page refresh --}}
    @extends('backend.layouts.app', [
        'currentPage' => trim($__env->yieldContent('page-id', 'dashboard')),
        'currentPageLabel' => trim($__env->yieldContent('title', __t('common.page'))),
    ])
    
    @section('title')
        @yield('title', __t('common.page'))
    @endsection
    
    @section('header')
        @yield('header', '')
    @endsection
    
    @section('content')
        @yield('content')
    @endsection
    
    @hasSection('header-actions')
        @section('header-actions')
            @yield('header-actions')
        @endsection
    @endif
    
    @hasSection('command-bar')
        @section('command-bar')
            @yield('command-bar')
        @endsection
    @endif
@endif
