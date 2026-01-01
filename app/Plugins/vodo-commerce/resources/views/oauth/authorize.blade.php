<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Authorize {{ $application['name'] }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            max-width: 28rem;
            width: 100%;
            overflow: hidden;
        }
        .header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }
        .app-logo {
            width: 4rem;
            height: 4rem;
            border-radius: 0.75rem;
            margin: 0 auto 1rem;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6b7280;
        }
        .app-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0.75rem;
        }
        .app-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .app-description {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .content {
            padding: 1.5rem;
        }
        .permission-heading {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 1rem;
        }
        .scopes-list {
            list-style: none;
            margin-bottom: 1.5rem;
        }
        .scope-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .scope-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: #10b981;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }
        .scope-text {
            font-size: 0.875rem;
            color: #374151;
        }
        .scope-category {
            font-size: 0.75rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: #92400e;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
        }
        .btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #d1d5db;
        }
        .footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .redirect-info {
            font-size: 0.75rem;
            color: #6b7280;
            text-align: center;
        }
        .redirect-uri {
            font-family: monospace;
            color: #374151;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="app-logo">
                @if($application['logo_url'])
                    <img src="{{ $application['logo_url'] }}" alt="{{ $application['name'] }}">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                @endif
            </div>
            <h1 class="app-name">{{ $application['name'] }}</h1>
            @if($application['website'])
                <p class="app-description">{{ parse_url($application['website'], PHP_URL_HOST) }}</p>
            @endif
        </div>

        <div class="content">
            <p class="permission-heading">
                <strong>{{ $application['name'] }}</strong> is requesting access to your store:
            </p>

            <ul class="scopes-list">
                @foreach($scopes as $scope)
                    <li class="scope-item">
                        <svg class="scope-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <div class="scope-text">{{ $scope['description'] }}</div>
                            <div class="scope-category">{{ $scope['category'] }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="warning">
                <strong>Review carefully:</strong> This app will have access to the permissions listed above.
                You can revoke access at any time from your store settings.
            </div>

            <form method="POST" action="{{ route('api.plugins.vodo-commerce.oauth.confirm') }}">
                @csrf
                <div class="actions">
                    <button type="submit" name="decision" value="deny" class="btn btn-secondary">
                        Deny
                    </button>
                    <button type="submit" name="decision" value="approve" class="btn btn-primary">
                        Authorize
                    </button>
                </div>
            </form>
        </div>

        <div class="footer">
            <p class="redirect-info">
                After authorization, you will be redirected to:<br>
                <span class="redirect-uri">{{ $redirectUri }}</span>
            </p>
        </div>
    </div>
</body>
</html>
