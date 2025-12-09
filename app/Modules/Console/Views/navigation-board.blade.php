@extends('console::layouts.app', [
    'currentPage' => 'navigation-board',
    'currentPageLabel' => 'Navigation Board',
    'currentPageIcon' => 'layoutDashboard',
])

@section('page-title', 'Navigation Board')
@section('page-header', 'Navigation Board')

@section('page-content')
    @include('backend.pages.navigation-board', [
        'allNavGroups' => $allNavGroups,
        'visibleItems' => $visibleItems,
    ])
@endsection
