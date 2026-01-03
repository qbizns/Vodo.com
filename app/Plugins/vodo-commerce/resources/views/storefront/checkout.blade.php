@extends('vodo-commerce::default.layouts.main')

@section('title', 'Checkout - ' . $store->name)

@section('content')
<div class="container">
    <h1 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Checkout</h1>

    <form id="checkout-form" onsubmit="submitCheckout(event)">
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
            <!-- Checkout Details -->
            <div>
                <!-- Contact Information -->
                <div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Contact Information</h2>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required placeholder="your@email.com">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone (optional)</label>
                        <input type="tel" name="phone" class="form-input">
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Shipping Address</h2>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="shipping_address[address1]" class="form-input" required placeholder="Street address">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apartment, suite, etc. (optional)</label>
                        <input type="text" name="shipping_address[address2]" class="form-input">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="shipping_address[city]" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="shipping_address[postal_code]" class="form-input" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">State/Province</label>
                            <input type="text" name="shipping_address[state]" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <select name="shipping_address[country]" class="form-input" required>
                                <option value="">Select country</option>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="AU">Australia</option>
                                <option value="DE">Germany</option>
                                <option value="FR">France</option>
                                <option value="EG">Egypt</option>
                                <option value="AE">UAE</option>
                                <option value="SA">Saudi Arabia</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Shipping Method -->
                <div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Shipping Method</h2>
                    <div id="shipping-methods" style="color: #6b7280;">
                        Enter your shipping address to see available shipping methods
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Payment Method</h2>
                    @if(!empty($paymentMethods))
                        @foreach($paymentMethods as $method)
                            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
                                <input type="radio" name="payment_method" value="{{ $method['id'] }}" {{ $loop->first ? 'checked' : '' }}>
                                <div>
                                    <span style="font-weight: 500;">{{ $method['name'] }}</span>
                                    @if(!empty($method['description']))
                                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">{{ $method['description'] }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    @else
                        <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer;">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <span style="font-weight: 500;">Cash on Delivery</span>
                        </label>
                    @endif
                </div>

                <!-- Order Notes -->
                <div class="card" style="padding: 1.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Order Notes (optional)</h2>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Any special instructions for your order"></textarea>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div class="card" style="padding: 1.5rem; position: sticky; top: 1rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Order Summary</h2>

                    <!-- Cart Items -->
                    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                        @foreach($cart['items'] as $item)
                            <div style="display: flex; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                                <div style="position: relative;">
                                    @if($item['image'])
                                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 0.5rem;">
                                    @else
                                        <div style="width: 60px; height: 60px; background: #f3f4f6; border-radius: 0.5rem;"></div>
                                    @endif
                                    <span style="position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; background: #4b5563; color: white; border-radius: 50%; font-size: 0.75rem; display: flex; align-items: center; justify-content: center;">
                                        {{ $item['quantity'] }}
                                    </span>
                                </div>
                                <div style="flex: 1;">
                                    <p style="font-weight: 500; margin: 0;">{{ $item['name'] }}</p>
                                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">{{ $store->currency }} {{ number_format($item['line_total'], 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6b7280;">Subtotal</span>
                            <span>{{ $store->currency }} {{ number_format($cart['subtotal'], 2) }}</span>
                        </div>
                        @if(($cart['discount_total'] ?? 0) > 0)
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #059669;">
                                <span>Discount</span>
                                <span>-{{ $store->currency }} {{ number_format($cart['discount_total'], 2) }}</span>
                            </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6b7280;">Shipping</span>
                            <span id="checkout-shipping">{{ $store->currency }} 0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6b7280;">Tax</span>
                            <span id="checkout-tax">{{ $store->currency }} 0.00</span>
                        </div>
                    </div>

                    <hr style="margin: 1rem 0; border: none; border-top: 1px solid #e5e7eb;">

                    <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">
                        <span>Total</span>
                        <span id="checkout-total">{{ $store->currency }} {{ number_format($cart['total'], 2) }}</span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;" id="place-order-btn">
                        Place Order
                    </button>

                    <a href="{{ route('storefront.vodo-commerce.cart.show', $store->slug) }}" style="display: block; text-align: center; margin-top: 1rem; color: #6b7280; text-decoration: none;">
                        Return to Cart
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
let shippingCost = 0;
let selectedShippingMethod = null;

// When shipping address changes, get shipping rates
document.querySelectorAll('input[name^="shipping_address"], select[name^="shipping_address"]').forEach(input => {
    input.addEventListener('change', debounce(getShippingRates, 500));
});

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function getShippingRates() {
    const form = document.getElementById('checkout-form');
    const formData = new FormData(form);

    const shippingAddress = {
        first_name: formData.get('first_name') || 'Test',
        last_name: formData.get('last_name') || 'User',
        address1: formData.get('shipping_address[address1]'),
        address2: formData.get('shipping_address[address2]'),
        city: formData.get('shipping_address[city]'),
        postal_code: formData.get('shipping_address[postal_code]'),
        state: formData.get('shipping_address[state]'),
        country: formData.get('shipping_address[country]')
    };

    if (!shippingAddress.address1 || !shippingAddress.city || !shippingAddress.postal_code || !shippingAddress.country) {
        return;
    }

    document.getElementById('shipping-methods').innerHTML = 'Loading shipping options...';

    fetch('{{ route('storefront.vodo-commerce.checkout.shipping-rates', $store->slug) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ shipping_address: shippingAddress })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Shipping rates response:', data);
        if (!data.success) {
            throw new Error(data.message || 'Failed to load shipping rates');
        }
        if (data.rates && data.rates.length > 0) {
            let html = '';
            data.rates.forEach((rate, index) => {
                html += `
                    <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
                        <input type="radio" name="shipping_method" value="${rate.id}" data-cost="${rate.cost}" ${index === 0 ? 'checked' : ''} onchange="selectShipping(this)">
                        <div style="flex: 1;">
                            <span style="font-weight: 500;">${rate.name}</span>
                            ${rate.estimated_days ? `<p style="font-size: 0.875rem; color: #6b7280; margin: 0;">${rate.estimated_days} business days</p>` : ''}
                        </div>
                        <span style="font-weight: 600;">{{ $store->currency }} ${parseFloat(rate.cost).toFixed(2)}</span>
                    </label>
                `;
            });
            document.getElementById('shipping-methods').innerHTML = html;

            // Select first shipping method
            const firstRadio = document.querySelector('input[name="shipping_method"]');
            if (firstRadio) {
                selectShipping(firstRadio);
            }
        } else {
            document.getElementById('shipping-methods').innerHTML = '<p style="color: #dc2626;">No shipping methods available for this address</p>';
        }
    })
    .catch(error => {
        console.error('Shipping rates error:', error);
        document.getElementById('shipping-methods').innerHTML = '<p style="color: #dc2626;">Error loading shipping options: ' + (error.message || 'Unknown error') + '</p>';
    });
}

function selectShipping(input) {
    shippingCost = parseFloat(input.dataset.cost);
    selectedShippingMethod = input.value;
    
    // Save shipping method to cart
    fetch('{{ route('storefront.vodo-commerce.checkout.shipping-method', $store->slug) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ 
            shipping_method: selectedShippingMethod,
            shipping_cost: shippingCost
        })
    });
    
    updateTotals();
}

function updateTotals() {
    const subtotal = {{ $cart['subtotal'] }};
    const discount = {{ $cart['discount_total'] ?? 0 }};
    const total = subtotal - discount + shippingCost;

    document.getElementById('checkout-shipping').textContent = '{{ $store->currency }} ' + shippingCost.toFixed(2);
    document.getElementById('checkout-total').textContent = '{{ $store->currency }} ' + total.toFixed(2);
}

async function submitCheckout(e) {
    e.preventDefault();

    const form = document.getElementById('checkout-form');
    const formData = new FormData(form);
    const btn = document.getElementById('place-order-btn');

    // Validate shipping method
    if (!selectedShippingMethod) {
        alert('Please select a shipping method');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Processing...';

    const shippingAddress = {
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        address1: formData.get('shipping_address[address1]'),
        address2: formData.get('shipping_address[address2]'),
        city: formData.get('shipping_address[city]'),
        postal_code: formData.get('shipping_address[postal_code]'),
        state: formData.get('shipping_address[state]'),
        country: formData.get('shipping_address[country]')
    };

    const billingAddress = {
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        email: formData.get('email'),
        address1: formData.get('shipping_address[address1]'),
        address2: formData.get('shipping_address[address2]'),
        city: formData.get('shipping_address[city]'),
        postal_code: formData.get('shipping_address[postal_code]'),
        state: formData.get('shipping_address[state]'),
        country: formData.get('shipping_address[country]')
    };

    try {
        // Step 1: Save addresses to cart
        const addressResponse = await fetch('{{ route('storefront.vodo-commerce.checkout.addresses', $store->slug) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                shipping_address: shippingAddress,
                billing_address: billingAddress
            })
        });
        
        const addressData = await addressResponse.json();
        if (!addressData.success) {
            throw new Error(addressData.message || 'Failed to save addresses');
        }

        // Step 2: Place the order
        const orderResponse = await fetch('{{ route('storefront.vodo-commerce.checkout.place-order', $store->slug) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                payment_method: formData.get('payment_method'),
                notes: formData.get('notes'),
                billing_address: billingAddress
            })
        });
        
        const orderData = await orderResponse.json();
        
        if (orderData.success) {
            const orderNumber = orderData.order?.order_number || orderData.order_number;
            window.location.href = '{{ route('storefront.vodo-commerce.checkout.success', [$store->slug, '__ORDER__']) }}'.replace('__ORDER__', orderNumber);
        } else {
            let errorMsg = orderData.message || 'Failed to place order';
            if (orderData.errors && orderData.errors.length > 0) {
                errorMsg = orderData.errors.map(e => e.message).join('\n');
            }
            alert(errorMsg);
            btn.disabled = false;
            btn.textContent = 'Place Order';
        }
    } catch (error) {
        alert(error.message || 'An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Place Order';
    }
}
</script>
@endpush
@endsection

