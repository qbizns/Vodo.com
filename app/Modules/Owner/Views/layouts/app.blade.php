{{--
    Owner Module Layout
    navGroups is injected by NavigationComposer with plugin items included
--}}
@extends('backend.layouts.app', [
    'guard' => 'owner',
    'modulePrefix' => 'owner',
    'brandName' => 'Vodo Owner',
    'version' => 'v.1.0.0',
    'baseUrl' => '',
    'currentPage' => $currentPage ?? 'dashboard',
    'currentPageLabel' => $currentPageLabel ?? 'Dashboard',
    'currentPageIcon' => $currentPageIcon ?? 'layoutDashboard',
    'splashTitle' => 'VODO',
    'splashSubtitle' => 'Owner',
    'splashVersion' => 'Version 1.0.0',
    'splashCopyright' => '© ' . date('Y') . ' VODO Systems',
    'profileUrl' => '/profile',
    'settingsUrl' => '/settings',
    'logoutUrl' => route('owner.logout'),
    'navBoardUrl' => route('owner.navigation-board'),
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
