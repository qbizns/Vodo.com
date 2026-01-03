@extends('backend.layouts.pjax')

@section('title', 'Store Settings')
@section('page-id', 'commerce/settings/general')
@section('header', 'Store Settings')

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ route('commerce.admin.dashboard') }}" class="btn-secondary">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to Dashboard</span>
    </a>
</div>
@endsection

@section('content')
<div class="max-w-4xl">
    <div class="card">
        <div class="card-header">
            <h3>General Settings</h3>
        </div>
        <div class="card-body">
            @if($store)
            <form action="{{ route('commerce.admin.settings.general.update') }}" method="POST" id="settings-form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name" class="form-label required">Store Name</label>
                    <input type="text" id="name" name="name" class="form-input" 
                           value="{{ old('name', $store->name) }}" required>
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-input" rows="3">{{ old('description', $store->description) }}</textarea>
                    @error('description')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="currency" class="form-label required">Currency</label>
                        <select id="currency" name="currency" class="form-input" required>
                            <option value="USD" {{ $store->currency === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="EUR" {{ $store->currency === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ $store->currency === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            <option value="EGP" {{ $store->currency === 'EGP' ? 'selected' : '' }}>EGP - Egyptian Pound</option>
                        </select>
                        @error('currency')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="timezone" class="form-label required">Timezone</label>
                        <select id="timezone" name="timezone" class="form-input" required>
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ $store->timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        @include('backend.partials.icon', ['icon' => 'save'])
                        <span>Save Settings</span>
                    </button>
                </div>
            </form>
            @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    @include('backend.partials.icon', ['icon' => 'store'])
                </div>
                <h3>No Store Configured</h3>
                <p>You haven't set up a store yet. Please contact support to configure your commerce store.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Settings Navigation --}}
    <div class="card mt-6">
        <div class="card-header">
            <h3>Other Settings</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <a href="{{ route('commerce.admin.settings.checkout') }}" class="settings-card">
                    @include('backend.partials.icon', ['icon' => 'shoppingCart'])
                    <span>Checkout</span>
                </a>
                <a href="{{ route('commerce.admin.settings.payments') }}" class="settings-card">
                    @include('backend.partials.icon', ['icon' => 'creditCard'])
                    <span>Payments</span>
                </a>
                <a href="{{ route('commerce.admin.settings.shipping') }}" class="settings-card">
                    @include('backend.partials.icon', ['icon' => 'truck'])
                    <span>Shipping</span>
                </a>
                <a href="{{ route('commerce.admin.settings.taxes') }}" class="settings-card">
                    @include('backend.partials.icon', ['icon' => 'receipt'])
                    <span>Taxes</span>
                </a>
                <a href="{{ route('commerce.admin.settings.notifications') }}" class="settings-card">
                    @include('backend.partials.icon', ['icon' => 'bell'])
                    <span>Notifications</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.settings-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s;
}
.settings-card:hover {
    border-color: var(--primary);
    background: var(--bg-surface-2);
}
.settings-card svg {
    width: 24px;
    height: 24px;
    color: var(--text-secondary);
}
</style>

<script>
document.getElementById('settings-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    Vodo.api.put(this.action, data)
        .then(response => {
            if (response.success) {
                Vodo.notification.success(response.message || 'Settings saved');
            }
        })
        .catch(error => {
            Vodo.notification.error(error.message || 'Failed to save settings');
        });
});
</script>
@endsection

