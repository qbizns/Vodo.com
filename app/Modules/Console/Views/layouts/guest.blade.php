@extends('backend.layouts.guest', [
    'brandName' => 'Vodo Console',
])

@section('login-title')
    @yield('page-login-title', 'Sign in')
@endsection

@section('login-subtitle')
    @yield('page-login-subtitle', 'Welcome back to Vodo Console')
@endsection

@section('content')
    @yield('page-content')
@endsection
