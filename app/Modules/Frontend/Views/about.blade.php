@extends('frontend::layouts.app')

@section('title', 'About Us')

@section('content')
<section class="py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">About Vodo</h1>
            <p class="text-xl text-slate-300 max-w-2xl mx-auto">
                We're building the future of SaaS management
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-6">Our Mission</h2>
                <p class="text-slate-300 mb-4">
                    Vodo is designed to empower businesses of all sizes with powerful tools for managing their operations, teams, and clients in one unified platform.
                </p>
                <p class="text-slate-300 mb-4">
                    We believe that great software should be accessible, intuitive, and powerful. That's why we've built Vodo with a focus on user experience while maintaining enterprise-grade capabilities.
                </p>
                <p class="text-slate-300">
                    Whether you're a startup or an enterprise, Vodo scales with your needs and grows with your business.
                </p>
            </div>
            <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8">
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-violet-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Modular Architecture</h3>
                            <p class="text-slate-400 text-sm">Separate modules for console, owners, admins, and clients</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-fuchsia-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-fuchsia-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Subdomain Routing</h3>
                            <p class="text-slate-400 text-sm">Clean URLs with dedicated subdomains for each area</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-pink-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Separate Auth Systems</h3>
                            <p class="text-slate-400 text-sm">Independent authentication for each user type</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

