@extends('backend.layouts.pjax')

@section('title', 'Shipping Settings')
@section('page-id', 'commerce/settings/shipping')
@section('header', 'Shipping Settings')

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
            <form action="{{ route('commerce.admin.settings.shipping.update') }}" method="POST">
                @csrf
                @method('PUT')

                <h4 class="mb-4">Shipping Carriers</h4>
                
                @forelse($carriers as $id => $carrier)
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="carriers[]" value="{{ $id }}" 
                               {{ in_array($id, $enabledCarriers) ? 'checked' : '' }}>
                        <span>{{ $carrier['name'] ?? $id }}</span>
                    </label>
                </div>
                @empty
                <p class="text-secondary">No shipping carriers configured.</p>
                @endforelse

                <div class="form-group mt-6">
                    <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold</label>
                    <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" 
                           class="form-input" step="0.01" min="0"
                           value="{{ $store->settings['free_shipping_threshold'] ?? '' }}"
                           placeholder="Leave empty to disable">
                    <span class="form-hint">Orders above this amount get free shipping</span>
                </div>

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

