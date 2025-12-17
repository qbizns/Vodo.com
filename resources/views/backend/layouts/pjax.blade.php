{{--
    PJAX Layout - Returns content fragment for AJAX navigation
    This layout is used when X-PJAX header is present in the request
    It returns only the content with inline styles, not the full page
--}}

@if(request()->header('X-PJAX'))
    {{-- PJAX Response: Only content with inline styles --}}
    <div id="pjax-content" 
         data-page-title="@yield('title', __t('common.page'))" 
         data-page-id="@yield('page-id', '')"
         data-require-css="@yield('require-css', '')">
        
        {{-- Inline styles for this page --}}
        @stack('inline-styles')
        
        {{-- Page content --}}
        @yield('content')
        
        {{-- Inline scripts for this page --}}
        @stack('inline-scripts')
    </div>
@else
    {{-- Full page response - extend the main app layout --}}
    @extends('backend.layouts.app')
    
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
