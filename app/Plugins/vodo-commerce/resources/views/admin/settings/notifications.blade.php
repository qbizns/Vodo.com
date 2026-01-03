@extends('backend.layouts.pjax')

@section('title', 'Notification Settings')
@section('page-id', 'commerce/settings/notifications')
@section('header', 'Notification Settings')

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
            <form action="{{ route('commerce.admin.settings.notifications.update') }}" method="POST">
                @csrf
                @method('PUT')

                <h4 class="mb-4">Email Notifications</h4>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="order_confirmation" value="1" 
                               {{ ($settings['order_confirmation'] ?? true) ? 'checked' : '' }}>
                        <span>Order Confirmation</span>
                    </label>
                    <span class="form-hint">Send email when order is placed</span>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="order_shipped" value="1" 
                               {{ ($settings['order_shipped'] ?? true) ? 'checked' : '' }}>
                        <span>Order Shipped</span>
                    </label>
                    <span class="form-hint">Send email when order is shipped</span>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="order_cancelled" value="1" 
                               {{ ($settings['order_cancelled'] ?? true) ? 'checked' : '' }}>
                        <span>Order Cancelled</span>
                    </label>
                </div>

                <h4 class="mt-6 mb-4">Admin Alerts</h4>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="low_stock_alert" value="1" 
                               {{ ($settings['low_stock_alert'] ?? false) ? 'checked' : '' }}>
                        <span>Low Stock Alert</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" 
                           class="form-input" min="1" 
                           value="{{ $settings['low_stock_threshold'] ?? 5 }}">
                </div>

                <div class="form-group">
                    <label for="admin_email" class="form-label">Admin Email</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-input" 
                           value="{{ $settings['admin_email'] ?? '' }}"
                           placeholder="admin@example.com">
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

