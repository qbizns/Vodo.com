@php
    // Get cart count for header
    $cartCount = 0;
    try {
        $cartSessionId = session()->get('cart_session_id');
        if ($cartSessionId) {
            $cart = \VodoCommerce\Models\Cart::where('store_id', $store->id)
                ->where('session_id', $cartSessionId)
                ->first();
            if ($cart) {
                $cartCount = $cart->items()->sum('quantity') ?? 0;
            }
        }
    } catch (\Exception $e) {
        $cartCount = 0;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $store->getSetting('rtl') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $store->name)</title>
    <meta name="description" content="@yield('description', $store->description)">

    @if($store->logo)
        <link rel="icon" href="{{ $store->logo }}" type="image/png">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <style>
        :root {
            --primary-color: {{ $store->getSetting('primary_color', '#3B82F6') }};
            --secondary-color: {{ $store->getSetting('secondary_color', '#10B981') }};
            --font-family: {{ $store->getSetting('font_family', 'Inter') }}, system-ui, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            line-height: 1.6;
            color: #1f2937;
            background-color: #f9fafb;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .nav {
            display: flex;
            gap: 1.5rem;
        }

        .nav a {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav a:hover {
            color: var(--primary-color);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cart-icon {
            position: relative;
            padding: 0.5rem;
            color: #4b5563;
        }

        .cart-count {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--primary-color);
            color: white;
            font-size: 0.75rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Main content */
        .main {
            min-height: calc(100vh - 200px);
            padding: 2rem 0;
        }

        /* Footer */
        .footer {
            background: #1f2937;
            color: #9ca3af;
            padding: 3rem 0 1.5rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h4 {
            color: white;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .footer-section a {
            color: #9ca3af;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.2s;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            padding-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Utilities */
        .text-center { text-align: center; }
        .mt-4 { margin-top: 1rem; }
        .mt-8 { margin-top: 2rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-8 { margin-bottom: 2rem; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-4 { gap: 1rem; }

        /* Responsive */
        @media (max-width: 768px) {
            .nav {
                display: none;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container header-content">
            <a href="{{ route('storefront.vodo-commerce.home', $store->slug) }}" class="logo">
                @if($store->logo)
                    <img src="{{ $store->logo }}" alt="{{ $store->name }}">
                @else
                    {{ $store->name }}
                @endif
            </a>

            <nav class="nav">
                <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}">Products</a>
                @foreach($categories ?? [] as $category)
                    <a href="{{ route('storefront.vodo-commerce.category', [$store->slug, $category->slug]) }}">{{ $category->name }}</a>
                @endforeach
            </nav>

            <div class="header-actions">
                <a href="{{ route('storefront.vodo-commerce.products.search', $store->slug) }}" title="Search">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </a>
                <a href="{{ route('storefront.vodo-commerce.cart.show', $store->slug) }}" class="cart-icon" title="Cart">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="cart-count" id="cart-count">{{ $cartCount }}</span>
                </a>
                @auth
                    <a href="{{ route('storefront.vodo-commerce.account.dashboard', $store->slug) }}" title="Account">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        @if(session('success'))
            <div class="container">
                <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="container">
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>{{ $store->name }}</h4>
                    <p>{{ $store->description }}</p>
                </div>
                <div class="footer-section">
                    <h4>Shop</h4>
                    <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}">All Products</a>
                    @foreach(collect($categories ?? [])->take(4) as $category)
                        <a href="{{ route('storefront.vodo-commerce.category', [$store->slug, $category->slug]) }}">{{ $category->name }}</a>
                    @endforeach
                </div>
                <div class="footer-section">
                    <h4>Account</h4>
                    @auth
                        <a href="{{ route('storefront.vodo-commerce.account.dashboard', $store->slug) }}">My Account</a>
                        <a href="{{ route('storefront.vodo-commerce.account.orders', $store->slug) }}">My Orders</a>
                    @else
                        <a href="{{ url('/login') }}">Login</a>
                        <a href="{{ url('/register') }}">Register</a>
                    @endauth
                    <a href="{{ route('storefront.vodo-commerce.cart.show', $store->slug) }}">Cart</a>
                </div>
                <div class="footer-section">
                    <h4>Help</h4>
                    <a href="#">Contact Us</a>
                    <a href="#">Shipping Info</a>
                    <a href="#">Returns</a>
                    <a href="#">FAQ</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ $store->name }}. All rights reserved.</p>
                <p style="margin-top: 0.5rem; font-size: 0.75rem;">Powered by Vodo Commerce</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
