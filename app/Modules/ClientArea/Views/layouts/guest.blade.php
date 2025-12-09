<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - Vodo Client Area</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-sky-950 text-white min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        @yield('content')
    </div>
</body>
</html>

