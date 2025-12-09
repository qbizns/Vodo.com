@extends('clientarea::layouts.guest')

@section('title', 'Login')

@section('content')
<div class="bg-sky-900 rounded-lg border border-sky-800 p-8">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-sky-400">Vodo Client Area</h1>
        <p class="text-sky-300/70 mt-2">Customer Portal</p>
    </div>

    @if ($errors->any())
    <div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 mb-6">
        <ul class="text-red-400 text-sm">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('clientarea.login.submit') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="block text-sky-300/70 text-sm mb-2">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full px-4 py-3 bg-sky-800 border border-sky-700 rounded-lg text-white placeholder-sky-500 focus:outline-none focus:border-sky-500 transition-colors">
        </div>

        <div class="mb-6">
            <label for="password" class="block text-sky-300/70 text-sm mb-2">Password</label>
            <input type="password" id="password" name="password" required
                class="w-full px-4 py-3 bg-sky-800 border border-sky-700 rounded-lg text-white placeholder-sky-500 focus:outline-none focus:border-sky-500 transition-colors">
        </div>

        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="w-4 h-4 bg-sky-800 border-sky-700 rounded text-sky-500 focus:ring-sky-500">
                <span class="ml-2 text-sky-300/70 text-sm">Remember me</span>
            </label>
        </div>

        <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
            Sign In
        </button>
    </form>
</div>
@endsection

