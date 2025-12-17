@extends('console::layouts.guest')

@section('title', __t('auth.login'))

@section('page-login-title', __t('auth.sign_in'))
@section('page-login-subtitle', __t('auth.login_subtitle'))

@section('page-content')
    {{-- Login Form --}}
    <form method="POST" action="{{ route('console.login.submit') }}" style="display: flex; flex-direction: column; gap: var(--spacing-5);">
        @csrf
        
        {{-- Email Field --}}
        <div class="form-group">
            <label for="email" class="form-label">{{ __t('auth.email') }}</label>
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
            <label for="password" class="form-label">{{ __t('auth.password') }}</label>
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
                <label for="remember">{{ __t('auth.remember_me') }}</label>
            </div>
            <a href="#" class="forgot-link">{{ __t('auth.forgot_password') }}</a>
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
            <span>{{ __t('auth.sign_in') }}</span>
        </button>
    </form>
@endsection
