<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') - {{ $brandName ?? 'VODO' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('backend/css/style.css') }}">
    <style>
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            width: 100vw;
            padding: var(--spacing-4);
            background-color: var(--bg-window);
            font-family: var(--font-family-base);
            box-sizing: border-box;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            padding: var(--spacing-8);
            background-color: var(--bg-surface-1);
            border-radius: var(--border-radius);
            border: 1px solid var(--divider);
            box-shadow: var(--elevation-2);
        }

        .login-logo {
            margin: 0 auto var(--spacing-4);
            width: 64px;
            height: 64px;
            border-radius: var(--border-radius);
            background-color: var(--color-accent);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-logo svg {
            width: 32px;
            height: 32px;
            color: var(--text-inverse);
        }

        .login-title {
            font-size: var(--text-title-large);
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
            margin-bottom: var(--spacing-2);
        }

        .login-subtitle {
            font-size: var(--text-body);
            color: var(--text-secondary);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-2);
        }

        .form-label {
            font-size: var(--text-body);
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            height: var(--button-height-medium);
            padding: 0 var(--spacing-3);
            border-radius: var(--border-radius);
            border: 1px solid var(--divider);
            background-color: var(--bg-surface-1);
            color: var(--text-primary);
            font-size: var(--text-body);
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.15s ease;
        }

        .form-input:focus {
            border-color: var(--color-accent);
        }

        .form-input.is-invalid {
            border-color: var(--color-error);
        }

        .invalid-feedback {
            font-size: var(--text-caption);
            color: var(--color-error);
            margin-top: var(--spacing-1);
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .remember-checkbox {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
        }

        .remember-checkbox input {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--color-accent);
        }

        .remember-checkbox label {
            font-size: var(--text-body);
            color: var(--text-primary);
            cursor: pointer;
        }

        .forgot-link {
            font-size: var(--text-body);
            color: var(--color-link);
            text-decoration: none;
            transition: text-decoration 0.15s ease;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .error-message {
            padding: var(--spacing-3);
            background-color: #FEE;
            color: var(--color-error);
            border: 1px solid var(--color-error);
            border-radius: var(--border-radius);
            font-size: var(--text-body);
            text-align: center;
        }

        .dark .error-message {
            background-color: rgba(241, 112, 123, 0.1);
        }

        .submit-btn {
            width: 100%;
            height: var(--button-height-medium);
            padding: 0 var(--spacing-6);
            background-color: var(--color-accent);
            color: var(--text-inverse);
            border: none;
            border-radius: var(--border-radius);
            font-size: var(--text-body);
            font-weight: var(--font-weight-semibold);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-2);
            transition: background-color 0.15s ease;
        }

        .submit-btn:hover:not(:disabled) {
            background-color: var(--accent-hover);
        }

        .submit-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .spinner {
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .signup-link {
            margin-top: var(--spacing-6);
            text-align: center;
            font-size: var(--text-body);
            color: var(--text-secondary);
        }

        .signup-link a {
            color: var(--color-link);
            font-weight: var(--font-weight-semibold);
            text-decoration: none;
            transition: text-decoration 0.15s ease;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Splash Screen --}}
    @include('backend.partials.splash')

    <div class="login-container">
        <div class="login-card">
            {{-- Logo and Title --}}
            <div style="margin-bottom: var(--spacing-8); text-align: center;">
                <div class="login-logo">
                    @hasSection('login-icon')
                        @yield('login-icon')
                    @else
                        {{-- Default LogIn Icon SVG --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                    @endif
                </div>
                <h2 class="login-title">@yield('login-title', 'Sign in')</h2>
                <p class="login-subtitle">@yield('login-subtitle', 'Welcome back to ' . ($brandName ?? 'VODO'))</p>
            </div>

            @yield('content')
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Apply dark mode if enabled
            const darkMode = localStorage.getItem('darkMode') === 'true';
            if (darkMode) {
                $('html').addClass('dark');
            }

            // Initialize splash screen
            const $splash = $('#splashScreen');
            if ($splash.length) {
                const minDisplayTime = 3000; // 3 seconds minimum
                const startTime = Date.now();

                let progress = 0;
                const progressBar = $('#splashProgressBar');
                
                // Animate progress bar over 3 seconds
                const progressInterval = setInterval(function() {
                    if (progress >= 100) {
                        clearInterval(progressInterval);
                        return;
                    }
                    progress += (100 / 30); // 30 steps over 3 seconds (100ms intervals)
                    progressBar.css('width', Math.min(progress, 100) + '%');
                }, 100);

                // Hide splash after minimum time AND page load
                function hideSplash() {
                    const elapsed = Date.now() - startTime;
                    const remaining = Math.max(0, minDisplayTime - elapsed);

                    setTimeout(function() {
                        clearInterval(progressInterval);
                        progressBar.css('width', '100%');
                        
                        setTimeout(function() {
                            $splash.addClass('splash-hidden');
                            setTimeout(function() {
                                $splash.remove();
                            }, 500);
                        }, 200);
                    }, remaining);
                }

                // Wait for page load
                if (document.readyState === 'complete') {
                    hideSplash();
                } else {
                    $(window).on('load', hideSplash);
                }
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
