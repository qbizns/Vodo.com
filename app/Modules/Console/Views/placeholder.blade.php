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
