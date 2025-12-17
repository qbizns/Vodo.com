{{--
    Console Module Layout
    navGroups is injected by NavigationComposer with plugin items included
--}}
@extends('backend.layouts.app', [
    'guard' => 'console',
    'modulePrefix' => 'console',
    'brandName' => 'Vodo Console',
    'version' => 'v.1.0.0',
    'baseUrl' => '',
    'currentPage' => $currentPage ?? 'dashboard',
    'currentPageLabel' => $currentPageLabel ?? 'Dashboard',
    'currentPageIcon' => $currentPageIcon ?? 'layoutDashboard',
    'splashTitle' => 'VODO',
    'splashSubtitle' => 'Console',
    'splashVersion' => 'Version 1.0.0',
    'splashCopyright' => '© ' . date('Y') . ' VODO Systems',
    'profileUrl' => '/profile',
    'settingsUrl' => '/settings',
    'logoutUrl' => route('console.logout'),
    'navBoardUrl' => route('console.navigation-board'),
])

@section('title')
    @yield('page-title', 'Dashboard')
@endsection

@section('header')
    @yield('page-header', 'Dashboard')
@endsection

@section('content')
    @yield('page-content')
@endsection

@section('command-bar')
    @yield('page-command-bar')
@endsection

@section('header-actions')
    @yield('page-header-actions')
@endsection
