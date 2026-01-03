@extends('backend.layouts.pjax')

@section('title', 'Payment Settings')
@section('page-id', 'commerce/settings/payments')
@section('header', 'Payment Settings')

@section('header-actions')
<a href="{{ route('commerce.admin.settings.general') }}" class="btn-secondary">
    @include('backend.partials.icon', ['icon' => 'arrowLeft'])
    <span>Back to Settings</span>
</a>
@endsection

@section('content')
<div class="max-w-4xl">
    <div class="card">
        <div class="card-body">
            @if($store)
            <form action="{{ route('commerce.admin.settings.payments.update') }}" method="POST">
                @csrf
                @method('PUT')

                <h4 class="mb-4">Available Payment Gateways</h4>
                
                @forelse($gateways as $id => $gateway)
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="gateways[]" value="{{ $id }}" 
                               {{ in_array($id, $enabledGateways) ? 'checked' : '' }}>
                        <span>{{ $gateway['name'] ?? $id }}</span>
                    </label>
                </div>
                @empty
                <p class="text-secondary">No payment gateways configured.</p>
                @endforelse

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Settings</button>
                </div>
            </form>
            @else
            <div class="empty-state">
                <p>Please configure your store first.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

