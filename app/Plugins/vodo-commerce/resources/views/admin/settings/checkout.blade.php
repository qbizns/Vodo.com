@extends('backend.layouts.pjax')

@section('title', 'Checkout Settings')
@section('page-id', 'commerce/settings/checkout')
@section('header', 'Checkout Settings')

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
            <form action="{{ route('commerce.admin.settings.checkout.update') }}" method="POST" id="checkout-form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_guest_checkout" value="1" 
                               {{ ($settings['enable_guest_checkout'] ?? false) ? 'checked' : '' }}>
                        <span>Enable Guest Checkout</span>
                    </label>
                    <span class="form-hint">Allow customers to checkout without creating an account</span>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="require_phone" value="1" 
                               {{ ($settings['require_phone'] ?? false) ? 'checked' : '' }}>
                        <span>Require Phone Number</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="require_company" value="1" 
                               {{ ($settings['require_company'] ?? false) ? 'checked' : '' }}>
                        <span>Require Company Name</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="terms_url" class="form-label">Terms & Conditions URL</label>
                    <input type="url" id="terms_url" name="terms_url" class="form-input" 
                           value="{{ $settings['terms_url'] ?? '' }}" placeholder="https://...">
                </div>

                <div class="form-group">
                    <label for="privacy_url" class="form-label">Privacy Policy URL</label>
                    <input type="url" id="privacy_url" name="privacy_url" class="form-input" 
                           value="{{ $settings['privacy_url'] ?? '' }}" placeholder="https://...">
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

