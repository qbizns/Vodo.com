@extends('owner::layouts.app', [
    'currentPage' => 'dashboard',
    'currentPageLabel' => __t('dashboard.title'),
    'currentPageIcon' => 'layoutDashboard',
    'hidePageTitle' => true,
])

@section('page-title', __t('dashboard.title'))

@section('page-content')
@include('backend.dashboard.index', [
    'widgets' => $widgets,
    'unusedWidgets' => $unusedWidgets,
    'currentDashboard' => $currentDashboard,
])
@endsection
