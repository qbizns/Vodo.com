<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Webhook;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;
use VodoCommerce\Services\CheckoutService;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $gatewayId): JsonResponse
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        // Get store from metadata or headers (gateway-specific)
        $storeId = $this->extractStoreId($payload, $headers);

        if (!$storeId) {
            return response()->json(['error' => 'Store not identified'], 400);
        }

        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $checkoutService = new CheckoutService(
            $store,
            app(PaymentGatewayRegistry::class),
            app(ShippingCarrierRegistry::class),
            app(TaxProviderRegistry::class)
        );

        $result = $checkoutService->processPaymentWebhook($gatewayId, $payload, $headers);

        if ($result['success']) {
            return response()->json(['status' => 'ok', 'message' => $result['message']]);
        }

        return response()->json(['error' => $result['message']], 400);
    }

    protected function extractStoreId(array $payload, array $headers): ?int
    {
        // Check metadata first (Stripe-style)
        if (isset($payload['data']['object']['metadata']['store_id'])) {
            return (int) $payload['data']['object']['metadata']['store_id'];
        }

        // Check custom header
        if (isset($headers['x-store-id'][0])) {
            return (int) $headers['x-store-id'][0];
        }

        // Check payload directly (generic)
        if (isset($payload['store_id'])) {
            return (int) $payload['store_id'];
        }

        return null;
    }
}
