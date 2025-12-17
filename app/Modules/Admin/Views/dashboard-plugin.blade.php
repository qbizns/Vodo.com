@extends('admin::layouts.app', [
    'currentPage' => 'dashboard',
    'currentPageLabel' => ($currentPlugin['title'] ?? 'Plugin Dashboard'),
    'currentPageIcon' => ($currentPlugin['icon'] ?? 'layoutDashboard'),
    'hidePageTitle' => true,
])

@section('page-title', ($currentPlugin['title'] ?? 'Plugin Dashboard'))

@section('page-content')
@include('backend.dashboard.index', [
    'widgets' => $widgets,
    'unusedWidgets' => $unusedWidgets,
    'currentDashboard' => $currentDashboard,
    'currentPlugin' => $currentPlugin ?? null,
])
@endsection
