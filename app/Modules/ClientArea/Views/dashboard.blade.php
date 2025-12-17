@extends('clientarea::layouts.app')

@section('title', __t('dashboard.title'))
@section('header', __t('dashboard.title'))

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-sky-900 rounded-lg p-6 border border-sky-800">
        <h3 class="text-sky-300/70 text-sm font-medium">{{ __t('frontend.active_services') }}</h3>
        <p class="text-3xl font-bold text-white mt-2">0</p>
    </div>
    <div class="bg-sky-900 rounded-lg p-6 border border-sky-800">
        <h3 class="text-sky-300/70 text-sm font-medium">{{ __t('frontend.open_tickets') }}</h3>
        <p class="text-3xl font-bold text-white mt-2">0</p>
    </div>
    <div class="bg-sky-900 rounded-lg p-6 border border-sky-800">
        <h3 class="text-sky-300/70 text-sm font-medium">{{ __t('frontend.invoices') }}</h3>
        <p class="text-3xl font-bold text-white mt-2">0</p>
    </div>
    <div class="bg-sky-900 rounded-lg p-6 border border-sky-800">
        <h3 class="text-sky-300/70 text-sm font-medium">{{ __t('frontend.account_balance') }}</h3>
        <p class="text-3xl font-bold text-white mt-2">$0</p>
    </div>
</div>

<div class="mt-8 bg-sky-900 rounded-lg border border-sky-800 p-6">
    <h3 class="text-lg font-semibold mb-4">{{ __t('frontend.welcome_to_client_area') }}</h3>
    <p class="text-sky-300/70">{{ __t('frontend.client_area_desc') }}</p>
</div>
@endsection
