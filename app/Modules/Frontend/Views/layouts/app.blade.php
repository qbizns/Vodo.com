<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome') - Vodo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-violet-950 via-slate-900 to-slate-950 text-white min-h-screen">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-slate-900/80 backdrop-blur-lg border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('frontend.home') }}" class="text-2xl font-bold bg-gradient-to-r from-violet-400 to-fuchsia-400 bg-clip-text text-transparent">
                    Vodo
                </a>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('frontend.home') }}" class="text-slate-300 hover:text-white transition-colors">Home</a>
                    <a href="{{ route('frontend.about') }}" class="text-slate-300 hover:text-white transition-colors">About</a>
                    <a href="{{ route('frontend.contact') }}" class="text-slate-300 hover:text-white transition-colors">Contact</a>
                    <a href="//client-area.{{ config('modules.domain') }}/login" class="bg-violet-600 hover:bg-violet-700 px-4 py-2 rounded-lg text-white font-medium transition-colors">
                        Client Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-12 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold bg-gradient-to-r from-violet-400 to-fuchsia-400 bg-clip-text text-transparent mb-4">Vodo</h3>
                    <p class="text-slate-400">Your complete SaaS solution for modern businesses.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-slate-400">
                        <li><a href="{{ route('frontend.home') }}" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="{{ route('frontend.about') }}" class="hover:text-white transition-colors">About</a></li>
                        <li><a href="{{ route('frontend.contact') }}" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Portals</h4>
                    <ul class="space-y-2 text-slate-400">
                        <li><a href="//client-area.{{ config('modules.domain') }}" class="hover:text-white transition-colors">Client Area</a></li>
                        <li><a href="//owner.{{ config('modules.domain') }}" class="hover:text-white transition-colors">Owner Portal</a></li>
                        <li><a href="//admin.{{ config('modules.domain') }}" class="hover:text-white transition-colors">Admin Portal</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-slate-400">
                        <li>info@vodo.com</li>
                        <li>+1 (555) 123-4567</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800 mt-8 pt-8 text-center text-slate-400">
                <p>&copy; {{ date('Y') }} Vodo. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>

