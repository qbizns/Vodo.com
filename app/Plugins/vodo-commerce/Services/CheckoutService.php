<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use VodoCommerce\Contracts\PaymentGatewayContract;
use VodoCommerce\Contracts\ShippingCarrierContract;
use VodoCommerce\Contracts\TaxProviderContract;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;

class CheckoutService
{
    public function __construct(
        protected Store $store,
        protected PaymentGatewayRegistry $paymentGateways,
        protected ShippingCarrierRegistry $shippingCarriers,
        protected TaxProviderRegistry $taxProviders
    ) {
    }

    public function validateCheckout(Cart $cart): array
    {
        $errors = [];

        // Check cart has items
        if ($cart->isEmpty()) {
            $errors[] = ['field' => 'cart', 'message' => 'Cart is empty'];

            return $errors;
        }

        // Validate items
        $cartService = new CartService($this->store);
        $cartService->getCart($cart->session_id, $cart->customer_id);
        $itemErrors = $cartService->validateItems();

        if (!empty($itemErrors)) {
            foreach ($itemErrors as $error) {
                $errors[] = ['field' => 'items', 'message' => $error['message']];
            }
        }

        // Validate shipping address
        if (empty($cart->shipping_address)) {
            $errors[] = ['field' => 'shipping_address', 'message' => 'Shipping address is required'];
        } else {
            $requiredFields = ['first_name', 'last_name', 'address1', 'city', 'postal_code', 'country'];
            foreach ($requiredFields as $field) {
                if (empty($cart->shipping_address[$field])) {
                    $errors[] = ['field' => "shipping_address.{$field}", 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
                }
            }
        }

        // Validate billing address
        if (empty($cart->billing_address)) {
            $errors[] = ['field' => 'billing_address', 'message' => 'Billing address is required'];
        }

        // Validate shipping method
        if (empty($cart->shipping_method)) {
            $errors[] = ['field' => 'shipping_method', 'message' => 'Shipping method is required'];
        }

        return $errors;
    }

    public function getAvailableShippingRates(Cart $cart): array
    {
        if (empty($cart->shipping_address)) {
            return [];
        }

        $address = new \VodoCommerce\Contracts\ShippingAddress(
            firstName: $cart->shipping_address['first_name'] ?? '',
            lastName: $cart->shipping_address['last_name'] ?? '',
            address1: $cart->shipping_address['address1'] ?? '',
            city: $cart->shipping_address['city'] ?? '',
            postalCode: $cart->shipping_address['postal_code'] ?? '',
            country: $cart->shipping_address['country'] ?? '',
            address2: $cart->shipping_address['address2'] ?? null,
            state: $cart->shipping_address['state'] ?? null,
            phone: $cart->shipping_address['phone'] ?? null,
            company: $cart->shipping_address['company'] ?? null,
        );

        $items = [];
        foreach ($cart->items as $item) {
            $items[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'weight' => $item->product->weight ?? 0,
                'dimensions' => $item->product->dimensions ?? [],
            ];
        }

        $rates = [];

        foreach ($this->shippingCarriers->allEnabled() as $carrier) {
            try {
                $carrierRates = $carrier->getRates($address, $items, $cart->currency);
                foreach ($carrierRates as $rate) {
                    $rates[] = [
                        'carrier' => $carrier->getName(),
                        'carrier_id' => $carrier->getIdentifier(),
                        'service' => $rate->serviceName,
                        'service_code' => $rate->serviceCode,
                        'rate' => $rate->rate,
                        'currency' => $rate->currency,
                        'estimated_days' => $rate->estimatedDays,
                    ];
                }
            } catch (\Exception $e) {
                // Log error but continue with other carriers
                report($e);
            }
        }

        return $rates;
    }

    public function calculateTax(Cart $cart): array
    {
        if (empty($cart->shipping_address)) {
            return ['tax_total' => 0, 'tax_breakdown' => []];
        }

        $address = new \VodoCommerce\Contracts\TaxAddress(
            address1: $cart->shipping_address['address1'] ?? '',
            city: $cart->shipping_address['city'] ?? '',
            postalCode: $cart->shipping_address['postal_code'] ?? '',
            country: $cart->shipping_address['country'] ?? '',
            address2: $cart->shipping_address['address2'] ?? null,
            state: $cart->shipping_address['state'] ?? null,
        );

        $items = [];
        foreach ($cart->items as $item) {
            $items[] = new \VodoCommerce\Contracts\TaxableItem(
                productId: (string) $item->product_id,
                quantity: $item->quantity,
                amount: (float) $item->getLineTotal(),
                taxCode: $item->product->meta['tax_code'] ?? null,
            );
        }

        $defaultProvider = $this->taxProviders->getDefault();
        if (!$defaultProvider) {
            return ['tax_total' => 0, 'tax_breakdown' => []];
        }

        try {
            $calculation = $defaultProvider->calculateTax($items, $address, $cart->currency);

            return [
                'tax_total' => $calculation->totalTax,
                'tax_breakdown' => array_map(fn($rate) => [
                    'name' => $rate->name,
                    'rate' => $rate->rate,
                    'amount' => $rate->amount,
                    'jurisdiction' => $rate->jurisdiction,
                ], $calculation->rates),
            ];
        } catch (\Exception $e) {
            report($e);

            return ['tax_total' => 0, 'tax_breakdown' => []];
        }
    }

    public function getAvailablePaymentMethods(): array
    {
        return array_map(fn(PaymentGatewayContract $gateway) => [
            'id' => $gateway->getIdentifier(),
            'name' => $gateway->getName(),
            'icon' => $gateway->getIcon(),
            'supports' => $gateway->supports(),
        ], $this->paymentGateways->allEnabled());
    }

    public function createOrder(Cart $cart, string $paymentMethod, ?string $customerEmail = null): Order
    {
        $errors = $this->validateCheckout($cart);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Checkout validation failed: ' . json_encode($errors));
        }

        return DB::transaction(function () use ($cart, $paymentMethod, $customerEmail) {
            // Create or find customer
            $customer = $this->resolveCustomer($cart, $customerEmail);

            // Create order
            $order = Order::create([
                'store_id' => $this->store->id,
                'customer_id' => $customer?->id,
                'customer_email' => $customerEmail ?? $customer?->email ?? $cart->billing_address['email'] ?? '',
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_PENDING,
                'fulfillment_status' => Order::FULFILLMENT_UNFULFILLED,
                'currency' => $cart->currency,
                'subtotal' => $cart->subtotal,
                'discount_total' => $cart->discount_total,
                'shipping_total' => $cart->shipping_total,
                'tax_total' => $cart->tax_total,
                'total' => $cart->total,
                'billing_address' => $cart->billing_address,
                'shipping_address' => $cart->shipping_address,
                'shipping_method' => $cart->shipping_method,
                'payment_method' => $paymentMethod,
                'discount_codes' => $cart->discount_codes,
                'notes' => $cart->notes,
                'placed_at' => now(),
            ]);

            // Create order items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'variant_id' => $cartItem->variant_id,
                    'name' => $cartItem->getName(),
                    'sku' => $cartItem->getSku(),
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'total' => $cartItem->getLineTotal(),
                    'options' => $cartItem->options,
                ]);

                // Decrement stock
                if ($cartItem->variant) {
                    $cartItem->variant->decrementStock($cartItem->quantity);
                } else {
                    $cartItem->product->decrementStock($cartItem->quantity);
                }
            }

            // Increment discount usage
            if (!empty($cart->discount_codes)) {
                Discount::whereIn('code', $cart->discount_codes)->each(fn($d) => $d->incrementUsage());
            }

            // Update customer stats
            if ($customer) {
                $customer->incrementOrderStats((float) $order->total);
            }

            // Clear cart
            $cart->clear();

            // Fire event
            do_action('commerce.order.created', $order);

            return $order;
        });
    }

    protected function resolveCustomer(Cart $cart, ?string $email): ?Customer
    {
        if ($cart->customer_id) {
            return Customer::find($cart->customer_id);
        }

        $email = $email ?? $cart->billing_address['email'] ?? null;

        if (!$email) {
            return null;
        }

        // Find or create customer
        return Customer::firstOrCreate(
            ['store_id' => $this->store->id, 'email' => $email],
            [
                'first_name' => $cart->billing_address['first_name'] ?? '',
                'last_name' => $cart->billing_address['last_name'] ?? '',
                'phone' => $cart->billing_address['phone'] ?? null,
            ]
        );
    }

    public function initiatePayment(Order $order): array
    {
        $gateway = $this->paymentGateways->get($order->payment_method);

        if (!$gateway) {
            throw new \InvalidArgumentException("Payment gateway '{$order->payment_method}' not found");
        }

        $items = $order->items->map(fn($item) => [
            'name' => $item->name,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'total' => $item->total,
        ])->toArray();

        $session = $gateway->createCheckoutSession(
            orderId: (string) $order->id,
            amount: (float) $order->total,
            currency: $order->currency,
            items: $items,
            customerEmail: $order->customer_email,
            metadata: [
                'order_number' => $order->order_number,
                'store_id' => $order->store_id,
            ]
        );

        return [
            'session_id' => $session->sessionId,
            'redirect_url' => $session->redirectUrl,
            'client_secret' => $session->clientSecret,
            'expires_at' => $session->expiresAt?->toIso8601String(),
        ];
    }

    public function processPaymentWebhook(string $gatewayId, array $payload, array $headers): array
    {
        $gateway = $this->paymentGateways->get($gatewayId);

        if (!$gateway) {
            return ['success' => false, 'message' => 'Unknown gateway'];
        }

        $result = $gateway->handleWebhook($payload, $headers);

        if (!$result->processed) {
            return ['success' => false, 'message' => $result->message];
        }

        if ($result->orderId) {
            $order = Order::find($result->orderId);

            if ($order && $result->paymentStatus === 'paid') {
                $order->markAsPaid($result->transactionId);
                do_action('commerce.order.paid', $order);
            }
        }

        return ['success' => true, 'message' => $result->message];
    }
}
