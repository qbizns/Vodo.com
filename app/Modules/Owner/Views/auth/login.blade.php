@extends('owner::layouts.guest')

@section('title', 'Login')

@section('page-login-title', 'Sign in')
@section('page-login-subtitle', 'Business Owner Portal')

@section('page-content')
    {{-- Login Form --}}
    <form method="POST" action="{{ route('owner.login.submit') }}" style="display: flex; flex-direction: column; gap: var(--spacing-5);">
        @csrf
        
        {{-- Email Field --}}
        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-input @error('email') is-invalid @enderror"
                placeholder="m@example.com"
                value="{{ old('email') }}"
                required
                autofocus
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password Field --}}
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-input @error('password') is-invalid @enderror"
                required
            >
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember Me & Forgot Password --}}
        <div class="remember-row">
            <div class="remember-checkbox">
                <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Remember me</label>
            </div>
            <a href="#" class="forgot-link">Forgot password?</a>
        </div>

        {{-- Error Message --}}
        @if ($errors->any())
            <div class="error-message">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        {{-- Submit Button --}}
        <button type="submit" class="submit-btn">
            <span>Sign in</span>
        </button>
    </form>
@endsection
