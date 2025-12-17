@extends('frontend::layouts.app')

@section('title', __t('frontend.contact'))

@section('content')
<section class="py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ __t('frontend.contact_title') }}</h1>
            <p class="text-xl text-slate-300 max-w-2xl mx-auto">
                {{ __t('frontend.contact_subtitle') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8">
                <h2 class="text-2xl font-bold mb-6">{{ __t('frontend.send_message') }}</h2>
                <form class="space-y-6">
                    <div>
                        <label class="block text-slate-300 text-sm mb-2">{{ __t('frontend.your_name') }}</label>
                        <input type="text" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-violet-500 transition-colors" placeholder="John Doe">
                    </div>
                    <div>
                        <label class="block text-slate-300 text-sm mb-2">{{ __t('frontend.email_address') }}</label>
                        <input type="email" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-violet-500 transition-colors" placeholder="john@example.com">
                    </div>
                    <div>
                        <label class="block text-slate-300 text-sm mb-2">{{ __t('frontend.message') }}</label>
                        <textarea rows="5" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-violet-500 transition-colors resize-none" placeholder="{{ __t('frontend.message_placeholder') }}"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 px-6 py-3 rounded-lg text-white font-semibold transition-colors">
                        {{ __t('frontend.send') }}
                    </button>
                </form>
            </div>

            <div class="space-y-8">
                <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8">
                    <h3 class="text-xl font-semibold mb-4">{{ __t('frontend.get_in_touch') }}</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-violet-600/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-slate-400 text-sm">{{ __t('frontend.email') }}</p>
                                <p class="text-white">info@vodo.com</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-fuchsia-600/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-fuchsia-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-slate-400 text-sm">{{ __t('frontend.phone') }}</p>
                                <p class="text-white">+1 (555) 123-4567</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8">
                    <h3 class="text-xl font-semibold mb-4">{{ __t('frontend.access_portals') }}</h3>
                    <div class="space-y-3">
                        <a href="//client-area.{{ config('modules.domain') }}" class="block w-full text-center py-3 border border-slate-600 hover:border-violet-500 rounded-lg text-slate-300 hover:text-white transition-colors">
                            {{ __t('frontend.client_area') }}
                        </a>
                        <a href="//owner.{{ config('modules.domain') }}" class="block w-full text-center py-3 border border-slate-600 hover:border-violet-500 rounded-lg text-slate-300 hover:text-white transition-colors">
                            {{ __t('frontend.owner_portal') }}
                        </a>
                        <a href="//admin.{{ config('modules.domain') }}" class="block w-full text-center py-3 border border-slate-600 hover:border-violet-500 rounded-lg text-slate-300 hover:text-white transition-colors">
                            {{ __t('frontend.admin_portal') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
