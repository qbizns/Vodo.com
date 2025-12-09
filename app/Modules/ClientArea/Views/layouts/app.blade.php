<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Client Area') - Vodo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-sky-950 text-white min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-sky-900 border-r border-sky-800">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-sky-400">Vodo Client</h1>
                <p class="text-sky-300/70 text-sm">Customer Portal</p>
            </div>
            <nav class="mt-6">
                <a href="{{ route('clientarea.dashboard') }}" class="flex items-center px-6 py-3 text-sky-200 hover:bg-sky-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Bar -->
            <header class="bg-sky-900 border-b border-sky-800 px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold">@yield('header', 'Dashboard')</h2>
                <div class="flex items-center space-x-4">
                    <span class="text-sky-300">{{ auth('client')->user()->name ?? 'Guest' }}</span>
                    <form action="{{ route('clientarea.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sky-300 hover:text-white transition-colors">Logout</button>
                    </form>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

