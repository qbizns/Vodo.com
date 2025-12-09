@extends('backend.layouts.guest', [
    'brandName' => 'Vodo Owner',
])

@section('login-title')
    @yield('page-login-title', 'Sign in')
@endsection

@section('login-subtitle')
    @yield('page-login-subtitle', 'Welcome back to Vodo Owner')
@endsection

@section('content')
    @yield('page-content')
@endsection
