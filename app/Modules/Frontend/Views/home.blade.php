@extends('frontend::layouts.app')

@section('title', 'Welcome')

@section('content')
<!-- Hero Section -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute inset-0">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-violet-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-fuchsia-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl md:text-7xl font-bold mb-6">
            <span class="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-pink-400 bg-clip-text text-transparent">
                Build Something
            </span>
            <br>
            <span class="text-white">Amazing</span>
        </h1>
        <p class="text-xl text-slate-300 max-w-2xl mx-auto mb-10">
            The ultimate SaaS platform for managing your business, clients, and teams. Powerful, flexible, and beautifully designed.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('frontend.contact') }}" class="bg-violet-600 hover:bg-violet-700 px-8 py-4 rounded-xl text-white font-semibold text-lg transition-all hover:scale-105">
                Get Started
            </a>
            <a href="{{ route('frontend.about') }}" class="border border-slate-600 hover:border-slate-500 px-8 py-4 rounded-xl text-white font-semibold text-lg transition-all hover:bg-slate-800">
                Learn More
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Powerful Features</h2>
            <p class="text-slate-400 max-w-2xl mx-auto">Everything you need to run your business efficiently</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8 hover:border-violet-500/50 transition-colors">
                <div class="w-14 h-14 bg-violet-600/20 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3">Multi-Tenant</h3>
                <p class="text-slate-400">Support for multiple businesses with isolated data and customizable branding.</p>
            </div>

            <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8 hover:border-fuchsia-500/50 transition-colors">
                <div class="w-14 h-14 bg-fuchsia-600/20 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-fuchsia-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3">Secure</h3>
                <p class="text-slate-400">Enterprise-grade security with separate authentication for each user type.</p>
            </div>

            <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8 hover:border-pink-500/50 transition-colors">
                <div class="w-14 h-14 bg-pink-600/20 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3">Fast & Scalable</h3>
                <p class="text-slate-400">Built with modern technologies for lightning-fast performance at any scale.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-violet-600 to-fuchsia-600 rounded-3xl p-12 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Get Started?</h2>
            <p class="text-violet-100 mb-8 max-w-xl mx-auto">Join thousands of businesses already using Vodo to streamline their operations.</p>
            <a href="{{ route('frontend.contact') }}" class="inline-block bg-white text-violet-600 hover:bg-violet-50 px-8 py-4 rounded-xl font-semibold text-lg transition-colors">
                Contact Us Today
            </a>
        </div>
    </div>
</section>
@endsection

