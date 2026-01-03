@extends('vodo-commerce::default.layouts.main')

@section('title', 'Order Confirmed - ' . $store->name)

@section('content')
<div class="container" style="max-width: 600px;">
    <div class="card" style="padding: 3rem; text-align: center;">
        <div style="width: 80px; height: 80px; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <svg width="40" height="40" fill="none" stroke="#059669" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">Thank you for your order!</h1>
        <p style="color: #6b7280; margin-bottom: 2rem;">Your order has been received and is being processed.</p>

        <div style="background: #f9fafb; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; text-align: left;">
            <h2 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Order Details</h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <span style="font-size: 0.875rem; color: #6b7280;">Order Number</span>
                    <p style="font-weight: 600;">{{ $order->order_number }}</p>
                </div>
                <div>
                    <span style="font-size: 0.875rem; color: #6b7280;">Date</span>
                    <p style="font-weight: 600;">{{ $order->placed_at->format('M d, Y') }}</p>
                </div>
                <div>
                    <span style="font-size: 0.875rem; color: #6b7280;">Email</span>
                    <p style="font-weight: 600;">{{ $order->customer_email }}</p>
                </div>
                <div>
                    <span style="font-size: 0.875rem; color: #6b7280;">Total</span>
                    <p style="font-weight: 600;">{{ $order->currency }} {{ number_format($order->total, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 2rem;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb;">
                        <th style="text-align: left; padding: 0.75rem; font-size: 0.875rem;">Item</th>
                        <th style="text-align: center; padding: 0.75rem; font-size: 0.875rem;">Qty</th>
                        <th style="text-align: right; padding: 0.75rem; font-size: 0.875rem;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr style="border-top: 1px solid #e5e7eb;">
                        <td style="padding: 0.75rem;">
                            <span style="font-weight: 500;">{{ $item->name }}</span>
                            @if($item->sku)
                                <br><span style="font-size: 0.75rem; color: #6b7280;">{{ $item->sku }}</span>
                            @endif
                        </td>
                        <td style="text-align: center; padding: 0.75rem;">{{ $item->quantity }}</td>
                        <td style="text-align: right; padding: 0.75rem;">{{ $order->currency }} {{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="border-top: 1px solid #e5e7eb;">
                        <td colspan="2" style="text-align: right; padding: 0.75rem; color: #6b7280;">Subtotal</td>
                        <td style="text-align: right; padding: 0.75rem;">{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    @if($order->discount_total > 0)
                    <tr>
                        <td colspan="2" style="text-align: right; padding: 0.75rem; color: #059669;">Discount</td>
                        <td style="text-align: right; padding: 0.75rem; color: #059669;">-{{ $order->currency }} {{ number_format($order->discount_total, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="2" style="text-align: right; padding: 0.75rem; color: #6b7280;">Shipping</td>
                        <td style="text-align: right; padding: 0.75rem;">{{ $order->currency }} {{ number_format($order->shipping_total, 2) }}</td>
                    </tr>
                    @if($order->tax_total > 0)
                    <tr>
                        <td colspan="2" style="text-align: right; padding: 0.75rem; color: #6b7280;">Tax</td>
                        <td style="text-align: right; padding: 0.75rem;">{{ $order->currency }} {{ number_format($order->tax_total, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="border-top: 2px solid #e5e7eb;">
                        <td colspan="2" style="text-align: right; padding: 0.75rem; font-weight: 700;">Total</td>
                        <td style="text-align: right; padding: 0.75rem; font-weight: 700;">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1.5rem;">
            We've sent a confirmation email to <strong>{{ $order->customer_email }}</strong>
        </p>

        <div style="display: flex; gap: 1rem; justify-content: center;">
            @auth
                <a href="{{ route('storefront.vodo-commerce.account.orders.show', [$store->slug, $order->order_number]) }}" class="btn btn-primary">
                    View Order
                </a>
            @endauth
            <a href="{{ route('storefront.vodo-commerce.products.index', $store->slug) }}" class="btn btn-secondary">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
@endsection
