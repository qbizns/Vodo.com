{{--
    PJAX Layout - Returns content fragment for AJAX navigation
    This layout is used when X-PJAX header is present in the request
    It returns only the content with inline styles, not the full page

    Supports three response formats:
    1. X-Fragment-Only header: Returns JSON with content, title, etc.
    2. X-PJAX header: Returns HTML fragment wrapped in #pjax-content
    3. Normal request: Renders full page using the app layout
--}}

@php
    $isPjax = request()->header('X-PJAX');
    $isFragmentOnly = request()->header('X-Fragment-Only');
@endphp

@if($isFragmentOnly)
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
@elseif($isPjax)
{{-- PJAX Response: Only content with inline styles --}}
<div id="pjax-content" 
     data-page-title="@yield('title', __t('common.page'))" 
     data-page-id="@yield('page-id', '')"
     data-require-css="@yield('require-css', '')">
    
    {{-- Header content for PJAX to update (can contain HTML) --}}
    <div id="pjax-header" style="display:none;">
        @yield('header')
    </div>
    
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
{{-- Full page response - Render complete HTML page --}}
@php
    // Capture the yielded content for the full page
    $pageTitle = trim($__env->yieldContent('title', __t('common.page')));
    $pageHeader = trim($__env->yieldContent('header', ''));
    $headerActions = trim($__env->yieldContent('header-actions', ''));
    $pageContent = $__env->yieldContent('content');
    $pageId = trim($__env->yieldContent('page-id', 'dashboard'));
    $requireCss = trim($__env->yieldContent('require-css', ''));
    $commandBar = $__env->yieldContent('command-bar');
@endphp
@include('backend.layouts.app-full', [
    'pageTitle' => $pageTitle,
    'pageHeader' => $pageHeader,
    'headerActions' => $headerActions,
    'pageContent' => $pageContent,
    'currentPage' => $pageId,
    'requireCss' => $requireCss,
    'commandBar' => $commandBar,
])
@endif
