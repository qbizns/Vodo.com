@extends('clientarea::layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-sky-900 rounded-lg p-6 border border-sky-800">
        <h3 class="text-sky-300/70 text-sm font-medium">Active Services</h3>
        <p class="text-3xl font-bold text-white mt-2">0</p>
    </div>
    <div class="bg-sky-900 rounded-lg p-6 border border-sky-800">
        <h3 class="text-sky-300/70 text-sm font-medium">Open Tickets</h3>
        <p class="text-3xl font-bold text-white mt-2">0</p>
    </div>
    <div class="bg-sky-900 rounded-lg p-6 border border-sky-800">
        <h3 class="text-sky-300/70 text-sm font-medium">Invoices</h3>
        <p class="text-3xl font-bold text-white mt-2">0</p>
    </div>
    <div class="bg-sky-900 rounded-lg p-6 border border-sky-800">
        <h3 class="text-sky-300/70 text-sm font-medium">Account Balance</h3>
        <p class="text-3xl font-bold text-white mt-2">$0</p>
    </div>
</div>

<div class="mt-8 bg-sky-900 rounded-lg border border-sky-800 p-6">
    <h3 class="text-lg font-semibold mb-4">Welcome to Client Area</h3>
    <p class="text-sky-300/70">Manage your services, view invoices, and submit support tickets from this dashboard.</p>
</div>
@endsection

