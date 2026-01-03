<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #fff; font-size: 24px; font-weight: 600;">Order Confirmed!</h1>
            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">Thank you for your purchase</p>
        </div>

        <!-- Content -->
        <div style="padding: 30px;">
            <p style="margin: 0 0 20px; color: #374151;">
                Hi {{ $data['customer_name'] ?? 'Valued Customer' }},
            </p>
            <p style="margin: 0 0 30px; color: #6b7280; line-height: 1.6;">
                Your order <strong style="color: #374151;">#{{ $data['order_number'] }}</strong> has been confirmed and is being processed. 
                You'll receive another email when your order ships.
            </p>

            <!-- Order Items -->
            <div style="margin-bottom: 25px;">
                <h2 style="font-size: 16px; color: #374151; margin: 0 0 15px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;">Order Items</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    @foreach($data['items'] as $item)
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 12px 0;">
                            <div style="display: flex; align-items: center;">
                                <div>
                                    <p style="margin: 0; font-weight: 500; color: #374151;">{{ $item['name'] ?? 'Product' }}</p>
                                    <p style="margin: 5px 0 0; font-size: 13px; color: #9ca3af;">
                                        Qty: {{ $item['quantity'] ?? 1 }}
                                        @if(!empty($item['sku']))
                                            · SKU: {{ $item['sku'] }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 500; color: #374151;">
                            {{ $data['currency'] }} {{ number_format($item['line_total'] ?? 0, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </table>
            </div>

            <!-- Order Summary -->
            <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                <h2 style="font-size: 16px; color: #374151; margin: 0 0 15px;">Order Summary</h2>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #6b7280;">Subtotal</span>
                    <span style="color: #374151;">{{ $data['currency'] }} {{ number_format($data['subtotal'], 2) }}</span>
                </div>

                @if($data['discount_total'] > 0)
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #059669;">
                        Discount
                        @if(!empty($data['discount_codes']))
                            <span style="font-size: 12px; background: #d1fae5; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">
                                {{ implode(', ', $data['discount_codes']) }}
                            </span>
                        @endif
                    </span>
                    <span style="color: #059669; font-weight: 500;">-{{ $data['currency'] }} {{ number_format($data['discount_total'], 2) }}</span>
                </div>
                @endif

                @if($data['shipping_total'] > 0)
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #6b7280;">
                        Shipping
                        @if(!empty($data['shipping_method']))
                            <span style="font-size: 12px; color: #9ca3af;">({{ $data['shipping_method'] }})</span>
                        @endif
                    </span>
                    <span style="color: #374151;">{{ $data['currency'] }} {{ number_format($data['shipping_total'], 2) }}</span>
                </div>
                @endif

                @if($data['tax_total'] > 0)
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #6b7280;">Tax</span>
                    <span style="color: #374151;">{{ $data['currency'] }} {{ number_format($data['tax_total'], 2) }}</span>
                </div>
                @endif

                <div style="border-top: 2px solid #e5e7eb; margin-top: 12px; padding-top: 12px; display: flex; justify-content: space-between;">
                    <span style="font-weight: 600; color: #374151; font-size: 18px;">Total</span>
                    <span style="font-weight: 700; color: #6366f1; font-size: 18px;">{{ $data['currency'] }} {{ number_format($data['total'], 2) }}</span>
                </div>

                @if($data['discount_total'] > 0)
                <div style="background: #d1fae5; border-radius: 6px; padding: 10px 15px; margin-top: 15px; text-align: center;">
                    <span style="color: #065f46; font-weight: 500;">🎉 You saved {{ $data['currency'] }} {{ number_format($data['discount_total'], 2) }} on this order!</span>
                </div>
                @endif
            </div>

            <!-- Addresses -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                @if(!empty($data['shipping_address']))
                <div>
                    <h3 style="font-size: 14px; color: #374151; margin: 0 0 10px; font-weight: 600;">Shipping Address</h3>
                    <div style="font-size: 14px; color: #6b7280; line-height: 1.6;">
                        @php $addr = $data['shipping_address']; @endphp
                        <p style="margin: 0;">{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</p>
                        <p style="margin: 0;">{{ $addr['address1'] ?? '' }}</p>
                        @if(!empty($addr['address2']))
                        <p style="margin: 0;">{{ $addr['address2'] }}</p>
                        @endif
                        <p style="margin: 0;">{{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['postal_code'] ?? '' }}</p>
                        <p style="margin: 0;">{{ $addr['country'] ?? '' }}</p>
                    </div>
                </div>
                @endif

                @if(!empty($data['billing_address']))
                <div>
                    <h3 style="font-size: 14px; color: #374151; margin: 0 0 10px; font-weight: 600;">Billing Address</h3>
                    <div style="font-size: 14px; color: #6b7280; line-height: 1.6;">
                        @php $addr = $data['billing_address']; @endphp
                        <p style="margin: 0;">{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</p>
                        <p style="margin: 0;">{{ $addr['address1'] ?? '' }}</p>
                        @if(!empty($addr['address2']))
                        <p style="margin: 0;">{{ $addr['address2'] }}</p>
                        @endif
                        <p style="margin: 0;">{{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['postal_code'] ?? '' }}</p>
                        <p style="margin: 0;">{{ $addr['country'] ?? '' }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Payment Method -->
            @if(!empty($data['payment_method']))
            <div style="margin-bottom: 25px;">
                <h3 style="font-size: 14px; color: #374151; margin: 0 0 10px; font-weight: 600;">Payment Method</h3>
                <p style="margin: 0; font-size: 14px; color: #6b7280;">
                    {{ ucfirst(str_replace('_', ' ', $data['payment_method'])) }}
                </p>
            </div>
            @endif

            <!-- Order Notes -->
            @if(!empty($data['notes']))
            <div style="background: #fef3c7; border-radius: 8px; padding: 15px; margin-bottom: 25px;">
                <h3 style="font-size: 14px; color: #92400e; margin: 0 0 8px; font-weight: 600;">📝 Order Notes</h3>
                <p style="margin: 0; font-size: 14px; color: #78350f;">{{ $data['notes'] }}</p>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div style="background: #f9fafb; padding: 25px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0 0 10px; color: #6b7280; font-size: 14px;">
                Questions about your order? Contact our support team.
            </p>
            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                @if($data['placed_at'])
                    Order placed on {{ \Carbon\Carbon::parse($data['placed_at'])->format('F j, Y \a\t g:i A') }}
                @endif
            </p>
        </div>
    </div>
</body>
</html>
