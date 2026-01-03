@extends('backend.layouts.pjax')

@section('title', 'Tax Settings')
@section('page-id', 'commerce/settings/taxes')
@section('header', 'Tax Settings')

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
            <form action="{{ route('commerce.admin.settings.taxes.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="prices_include_tax" value="1" 
                               {{ ($taxSettings['prices_include_tax'] ?? false) ? 'checked' : '' }}>
                        <span>Prices Include Tax</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="calculate_tax_based_on" class="form-label">Calculate Tax Based On</label>
                    <select id="calculate_tax_based_on" name="calculate_tax_based_on" class="form-input">
                        <option value="shipping" {{ ($taxSettings['calculate_tax_based_on'] ?? 'shipping') === 'shipping' ? 'selected' : '' }}>Shipping Address</option>
                        <option value="billing" {{ ($taxSettings['calculate_tax_based_on'] ?? '') === 'billing' ? 'selected' : '' }}>Billing Address</option>
                    </select>
                </div>

                <h4 class="mt-6 mb-4">Tax Providers</h4>
                @forelse($providers as $id => $provider)
                <div class="form-group">
                    <label class="radio-label">
                        <input type="radio" name="tax_provider" value="{{ $id }}" 
                               {{ ($taxSettings['tax_provider'] ?? '') === $id ? 'checked' : '' }}>
                        <span>{{ $provider['name'] ?? $id }}</span>
                    </label>
                </div>
                @empty
                <p class="text-secondary">No tax providers configured.</p>
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

