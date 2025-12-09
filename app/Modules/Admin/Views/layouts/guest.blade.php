@extends('backend.layouts.guest', [
    'brandName' => 'Vodo Admin',
])

@section('login-title')
    @yield('page-login-title', 'Sign in')
@endsection

@section('login-subtitle')
    @yield('page-login-subtitle', 'Welcome back to Vodo Admin')
@endsection

@section('content')
    @yield('page-content')
@endsection
