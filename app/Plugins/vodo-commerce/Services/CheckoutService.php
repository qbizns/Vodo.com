<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use VodoCommerce\Contracts\PaymentGatewayContract;
use VodoCommerce\Contracts\ShippingCarrierContract;
use VodoCommerce\Contracts\TaxProviderContract;
use VodoCommerce\Events\CommerceEvents;
use VodoCommerce\Models\Address;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;
use VodoCommerce\Traits\CircuitOpenException;
use VodoCommerce\Traits\WithCircuitBreaker;

class CheckoutService
{
    use WithCircuitBreaker;

    protected InventoryReservationService $reservationService;

    public function __construct(
        protected Store $store,
        protected PaymentGatewayRegistry $paymentGateways,
        protected ShippingCarrierRegistry $shippingCarriers,
        protected TaxProviderRegistry $taxProviders
    ) {
        $this->reservationService = new InventoryReservationService($store);
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
            return $this->getDefaultShippingRates($cart);
        }

        try {
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
        } catch (\Exception $e) {
            Log::warning('Failed to create shipping address', ['error' => $e->getMessage()]);
            return $this->getDefaultShippingRates($cart);
        }

        $items = [];
        foreach ($cart->items as $item) {
            $items[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'weight' => $item->product->weight ?? 0,
                'dimensions' => is_array($item->product->dimensions) ? $item->product->dimensions : [],
            ];
        }

        $rates = [];

        try {
            $enabledCarriers = $this->shippingCarriers->allEnabled();
        } catch (\Exception $e) {
            Log::warning('Failed to get enabled shipping carriers', ['error' => $e->getMessage()]);
            return $this->getDefaultShippingRates($cart);
        }

        foreach ($enabledCarriers as $carrier) {
            $circuitKey = $this->getCircuitKey('shipping', $carrier->getIdentifier());

            try {
                // Wrap external carrier call with circuit breaker
                $carrierRates = $this->withCircuitBreaker(
                    $circuitKey,
                    fn() => $carrier->getRates($address, $items, $cart->currency),
                    [] // Return empty on circuit open
                );

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
                Log::warning('Shipping carrier rate fetch failed', [
                    'carrier' => $carrier->getIdentifier(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // If no shipping carriers configured, provide default options
        if (empty($rates)) {
            return $this->getDefaultShippingRates($cart);
        }

        return $rates;
    }

    /**
     * Get default shipping rates when no carriers are configured.
     */
    protected function getDefaultShippingRates(Cart $cart): array
    {
        $currency = $cart->currency ?? $this->store->currency ?? 'USD';
        
        return [
            [
                'id' => 'standard',
                'name' => 'Standard Shipping',
                'cost' => 10.00,
                'currency' => $currency,
                'estimated_days' => '5-7',
            ],
            [
                'id' => 'express',
                'name' => 'Express Shipping',
                'cost' => 25.00,
                'currency' => $currency,
                'estimated_days' => '2-3',
            ],
            [
                'id' => 'free',
                'name' => 'Free Shipping',
                'cost' => 0.00,
                'currency' => $currency,
                'estimated_days' => '7-14',
            ],
        ];
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

        $circuitKey = $this->getCircuitKey('tax', $defaultProvider->getIdentifier());

        try {
            // Wrap external tax provider call with circuit breaker
            $calculation = $this->withCircuitBreaker(
                $circuitKey,
                fn() => $defaultProvider->calculateTax($items, $address, $cart->currency)
            );

            if (!$calculation) {
                return ['tax_total' => 0, 'tax_breakdown' => []];
            }

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
            Log::warning('Tax calculation failed', [
                'provider' => $defaultProvider->getIdentifier(),
                'error' => $e->getMessage(),
            ]);

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

            // Decrement stock FIRST with atomic check to prevent overselling
            // This must happen before creating order items to fail fast
            foreach ($cart->items as $cartItem) {
                $stockDecremented = false;

                if ($cartItem->variant) {
                    $stockDecremented = $cartItem->variant->decrementStock($cartItem->quantity);
                } else {
                    $stockDecremented = $cartItem->product->decrementStock($cartItem->quantity);
                }

                if (!$stockDecremented) {
                    // Rollback will happen automatically due to DB::transaction
                    throw new \RuntimeException(
                        "Insufficient stock for '{$cartItem->getName()}'. " .
                        "Only {$cartItem->getAvailableQuantity()} available."
                    );
                }
            }

            // Create order items (stock already decremented successfully)
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
            }

            // Increment discount usage
            if (!empty($cart->discount_codes)) {
                Discount::whereIn('code', $cart->discount_codes)->each(fn($d) => $d->incrementUsage());
            }

            // Update customer stats
            if ($customer) {
                $customer->incrementOrderStats((float) $order->total);
            }

            // Convert reservations to order (releases them since stock is now committed)
            $this->reservationService->convertToOrder($cart);

            // Clear cart
            $cart->clear();

            // Fire events through HookManager
            do_action(CommerceEvents::ORDER_CREATED, $order, $this->store);
            do_action(CommerceEvents::PAYMENT_INITIATED, $order, $paymentMethod);

            Log::info('Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'store_id' => $this->store->id,
                'total' => $order->total,
            ]);

            return $order;
        });
    }

    protected function resolveCustomer(Cart $cart, ?string $email): ?Customer
    {
        if ($cart->customer_id) {
            $customer = Customer::find($cart->customer_id);
            if ($customer) {
                // Still save/update addresses for existing customer
                $this->saveCustomerAddresses($customer, $cart);
                return $customer;
            }
        }

        $email = $email ?? $cart->billing_address['email'] ?? null;

        if (!$email) {
            return null;
        }

        // Find or create customer
        $customer = Customer::firstOrCreate(
            ['store_id' => $this->store->id, 'email' => $email],
            [
                'first_name' => $cart->billing_address['first_name'] ?? '',
                'last_name' => $cart->billing_address['last_name'] ?? '',
                'phone' => $cart->billing_address['phone'] ?? null,
            ]
        );

        // Save addresses for the customer
        $this->saveCustomerAddresses($customer, $cart);

        return $customer;
    }

    /**
     * Save customer addresses from cart data.
     */
    protected function saveCustomerAddresses(Customer $customer, Cart $cart): void
    {
        // Save shipping address
        if (!empty($cart->shipping_address)) {
            $shippingAddress = $this->findOrCreateAddress(
                $customer,
                'shipping',
                $cart->shipping_address
            );
            
            // Set as default if customer doesn't have a default address
            if (!$customer->default_address_id) {
                $customer->update(['default_address_id' => $shippingAddress->id]);
            }
        }

        // Save billing address
        if (!empty($cart->billing_address)) {
            $this->findOrCreateAddress(
                $customer,
                'billing',
                $cart->billing_address
            );
        }
    }

    /**
     * Find existing address or create new one.
     */
    protected function findOrCreateAddress(Customer $customer, string $type, array $addressData): Address
    {
        // Try to find existing matching address
        $existingAddress = Address::where('customer_id', $customer->id)
            ->where('type', $type)
            ->where('address1', $addressData['address1'] ?? '')
            ->where('city', $addressData['city'] ?? '')
            ->where('postal_code', $addressData['postal_code'] ?? '')
            ->where('country', $addressData['country'] ?? '')
            ->first();

        if ($existingAddress) {
            // Update existing address with any new data
            $existingAddress->update([
                'first_name' => $addressData['first_name'] ?? $existingAddress->first_name,
                'last_name' => $addressData['last_name'] ?? $existingAddress->last_name,
                'company' => $addressData['company'] ?? $existingAddress->company,
                'address2' => $addressData['address2'] ?? $existingAddress->address2,
                'state' => $addressData['state'] ?? $existingAddress->state,
                'phone' => $addressData['phone'] ?? $existingAddress->phone,
            ]);
            return $existingAddress;
        }

        // Create new address
        $address = Address::create([
            'customer_id' => $customer->id,
            'type' => $type,
            'first_name' => $addressData['first_name'] ?? '',
            'last_name' => $addressData['last_name'] ?? '',
            'company' => $addressData['company'] ?? null,
            'address1' => $addressData['address1'] ?? '',
            'address2' => $addressData['address2'] ?? null,
            'city' => $addressData['city'] ?? '',
            'state' => $addressData['state'] ?? null,
            'postal_code' => $addressData['postal_code'] ?? '',
            'country' => $addressData['country'] ?? '',
            'phone' => $addressData['phone'] ?? null,
            'is_default' => !$customer->addresses()->where('type', $type)->exists(),
        ]);

        return $address;
    }

    public function initiatePayment(Order $order): array
    {
        $gateway = $this->paymentGateways->get($order->payment_method);

        if (!$gateway) {
            throw new \InvalidArgumentException("Payment gateway '{$order->payment_method}' not found");
        }

        // Check if this is an offline payment method (like COD)
        $supports = method_exists($gateway, 'supports') ? $gateway->supports() : [];
        $isOffline = in_array('offline', $supports['features'] ?? []);

        if ($isOffline) {
            // For offline payments, move order to processing (awaiting fulfillment)
            $order->update([
                'status' => Order::STATUS_PROCESSING,
                'payment_status' => Order::PAYMENT_PENDING, // Will be collected on delivery
            ]);

            Log::info('Offline payment order confirmed', [
                'order_id' => $order->id,
                'gateway' => $gateway->getIdentifier(),
            ]);

            return [
                'session_id' => null,
                'redirect_url' => route('storefront.vodo-commerce.checkout.success', [
                    'store' => $this->store->slug,
                    'order' => $order->order_number,
                ]),
                'client_secret' => null,
                'expires_at' => null,
                'offline' => true,
                'message' => 'Order confirmed. Payment will be collected on delivery.',
            ];
        }

        $session = $gateway->createCheckoutSession($order, [
            'return_url' => route('storefront.vodo-commerce.checkout.success', [
                'store' => $this->store->slug,
                'order' => $order->order_number,
            ]),
            'cancel_url' => route('storefront.vodo-commerce.checkout.show', [
                'store' => $this->store->slug,
            ]),
        ]);

        Log::info('Payment session initiated', [
            'order_id' => $order->id,
            'gateway' => $gateway->getIdentifier(),
            'session_id' => $session->id,
        ]);

        return [
            'session_id' => $session->id,
            'redirect_url' => $session->url,
            'client_secret' => $session->metadata['client_secret'] ?? null,
            'expires_at' => $session->expiresAt ? date('c', $session->expiresAt) : null,
        ];
    }

    public function processPaymentWebhook(string $gatewayId, array $payload, array $headers): array
    {
        $gateway = $this->paymentGateways->get($gatewayId);

        if (!$gateway) {
            return ['success' => false, 'message' => 'Unknown gateway'];
        }

        $circuitKey = $this->getCircuitKey('payment', $gatewayId);

        // Wrap webhook processing with circuit breaker
        // Webhooks need to process, so throw on circuit open (provider will retry)
        $result = $this->withCircuitBreaker(
            $circuitKey,
            fn() => $gateway->handleWebhook($payload, $headers),
            null,
            true // Throw on open - let payment provider retry
        );

        if (!$result->processed) {
            return ['success' => false, 'message' => $result->message];
        }

        if ($result->orderId) {
            $order = Order::withoutStoreScope()->find($result->orderId);

            if ($order) {
                $previousStatus = $order->payment_status;

                if ($result->paymentStatus === 'paid') {
                    $order->markAsPaid($result->transactionId);

                    // Fire payment events
                    do_action(CommerceEvents::PAYMENT_PAID, $order, $result->transactionId);
                    do_action(CommerceEvents::ORDER_STATUS_CHANGED, $order, $previousStatus, $order->status);

                    Log::info('Order payment completed', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'gateway' => $gatewayId,
                        'transaction_id' => $result->transactionId,
                    ]);
                } elseif ($result->paymentStatus === 'failed') {
                    do_action(CommerceEvents::PAYMENT_FAILED, $order, $result->message);

                    Log::warning('Order payment failed', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'gateway' => $gatewayId,
                        'reason' => $result->message,
                    ]);
                }
            }
        }

        // Fire webhook received event for other plugins to react
        do_action(CommerceEvents::WEBHOOK_PAYMENT_RECEIVED, $gatewayId, $payload, $result);

        return ['success' => true, 'message' => $result->message];
    }
}
