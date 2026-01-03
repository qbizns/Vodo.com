@extends('vodo-commerce::default.layouts.main')

@section('title', 'Cart - ' . $store->name)

@section('content')
<div class="container">
    <h1 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Shopping Cart</h1>

    @if(empty($cart['items']))
        <div class="card" style="padding: 3rem; text-align: center;">
            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 1rem; color: #9ca3af;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">Your cart is empty</p>
            <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}" class="btn btn-primary">
                Continue Shopping
            </a>
        </div>
    @else
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Cart Items -->
            <div>
                <div class="card">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <th style="text-align: left; padding: 1rem;">Product</th>
                                <th style="text-align: center; padding: 1rem;">Quantity</th>
                                <th style="text-align: right; padding: 1rem;">Total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                            @foreach($cart['items'] as $item)
                            <tr style="border-bottom: 1px solid #e5e7eb;" data-item-id="{{ $item['id'] }}">
                                <td style="padding: 1rem;">
                                    <div style="display: flex; gap: 1rem; align-items: center;">
                                        @if($item['image'])
                                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 0.5rem;">
                                        @else
                                            <div style="width: 80px; height: 80px; background: #f3f4f6; border-radius: 0.5rem;"></div>
                                        @endif
                                        <div>
                                            <h3 style="font-weight: 600;">{{ $item['name'] }}</h3>
                                            @if($item['sku'])
                                                <span style="font-size: 0.875rem; color: #6b7280;">SKU: {{ $item['sku'] }}</span>
                                            @endif
                                            <p style="font-size: 0.875rem; color: #6b7280;">
                                                {{ $store->currency }} {{ number_format($item['unit_price'], 2) }} each
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; padding: 1rem;">
                                    <div style="display: inline-flex; border: 1px solid #d1d5db; border-radius: 0.5rem; overflow: hidden;">
                                        <button onclick="updateQuantity({{ $item['id'] }}, {{ $item['quantity'] - 1 }})" style="padding: 0.5rem 0.75rem; border: none; background: #f3f4f6; cursor: pointer;">-</button>
                                        <input type="text" value="{{ $item['quantity'] }}" style="width: 40px; text-align: center; border: none; border-left: 1px solid #d1d5db; border-right: 1px solid #d1d5db;" readonly>
                                        <button onclick="updateQuantity({{ $item['id'] }}, {{ $item['quantity'] + 1 }})" style="padding: 0.5rem 0.75rem; border: none; background: #f3f4f6; cursor: pointer;">+</button>
                                    </div>
                                </td>
                                <td style="text-align: right; padding: 1rem; font-weight: 600;">
                                    {{ $store->currency }} {{ number_format($item['line_total'], 2) }}
                                </td>
                                <td style="padding: 1rem;">
                                    <button onclick="removeItem({{ $item['id'] }})" style="color: #dc2626; border: none; background: none; cursor: pointer;">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div class="card" style="padding: 1.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Order Summary</h2>

                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6b7280;">Subtotal</span>
                            <span id="summary-subtotal">{{ $store->currency }} {{ number_format($cart['subtotal'], 2) }}</span>
                        </div>
                        @if($cart['discount_total'] > 0)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #059669;">
                            <span>Discount</span>
                            <span id="summary-discount">-{{ $store->currency }} {{ number_format($cart['discount_total'], 2) }}</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6b7280;">Shipping</span>
                            <span id="summary-shipping">Calculated at checkout</span>
                        </div>
                    </div>

                    <hr style="margin: 1rem 0; border: none; border-top: 1px solid #e5e7eb;">

                    <div style="display: flex; justify-content: space-between; font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem;">
                        <span>Total</span>
                        <span id="summary-total">{{ $store->currency }} {{ number_format($cart['total'], 2) }}</span>
                    </div>

                    <!-- Discount Code -->
                    <div style="margin-bottom: 1.5rem;">
                        <form onsubmit="applyDiscount(event)">
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" id="discount-code" class="form-input" placeholder="Discount code" style="flex: 1;">
                                <button type="submit" class="btn btn-secondary">Apply</button>
                            </div>
                        </form>
                        @if(!empty($cart['discount_codes']))
                            <div style="margin-top: 0.5rem;">
                                @foreach($cart['discount_codes'] as $code)
                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                        {{ $code }}
                                        <button onclick="removeDiscount('{{ $code }}')" style="border: none; background: none; cursor: pointer; color: #065f46;">&times;</button>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <a href="{{ route('storefront.vodo-commerce.checkout.show', $store->slug) }}" class="btn btn-primary" style="width: 100%;">
                        Proceed to Checkout
                    </a>

                    <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}" style="display: block; text-align: center; margin-top: 1rem; color: #6b7280; text-decoration: none;">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function updateQuantity(itemId, quantity) {
    if (quantity < 0) return;

    fetch('{{ url()->current() }}/items/' + itemId, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update cart');
        }
    });
}

function removeItem(itemId) {
    if (!confirm('Remove this item from cart?')) return;

    fetch('{{ url()->current() }}/items/' + itemId, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to remove item');
        }
    });
}

function applyDiscount(e) {
    e.preventDefault();
    const code = document.getElementById('discount-code').value;
    if (!code) return;

    fetch('{{ route('storefront.vodo-commerce.cart.discount.apply', $store->slug) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ code: code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Invalid discount code');
        }
    });
}

function removeDiscount(code) {
    fetch('{{ url()->current() }}/discount/' + code, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>
@endpush
@endsection
