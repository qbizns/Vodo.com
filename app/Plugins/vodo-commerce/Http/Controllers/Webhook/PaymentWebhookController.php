<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Webhook;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;
use VodoCommerce\Services\CheckoutService;

/**
 * PaymentWebhookController - Handles incoming payment gateway webhooks.
 *
 * Security: This controller is protected by VerifyWebhookSignature middleware.
 * All incoming requests must have valid HMAC SHA256 signatures.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayRegistry $paymentGateways,
        protected ShippingCarrierRegistry $shippingCarriers,
        protected TaxProviderRegistry $taxProviders
    ) {}

    public function handle(Request $request, string $gatewayId): JsonResponse
    {
        // Signature is already verified by middleware
        $webhookVerified = $request->attributes->get('webhook_verified', false);

        if (!$webhookVerified) {
            Log::warning('Webhook handler called without signature verification', [
                'gateway' => $gatewayId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Webhook not verified',
                'code' => 'VERIFICATION_REQUIRED',
            ], 401);
        }

        $payload = $request->all();
        $headers = $request->headers->all();

        // Get store from metadata or headers (gateway-specific)
        $storeId = $this->extractStoreId($payload, $headers);

        if (!$storeId) {
            Log::warning('Webhook received without store identification', [
                'gateway' => $gatewayId,
            ]);

            return response()->json([
                'error' => 'Store not identified',
                'code' => 'STORE_NOT_IDENTIFIED',
            ], 400);
        }

        $store = Store::withoutTenantScope()->find($storeId);

        if (!$store) {
            Log::warning('Webhook received for non-existent store', [
                'gateway' => $gatewayId,
                'store_id' => $storeId,
            ]);

            return response()->json([
                'error' => 'Store not found',
                'code' => 'STORE_NOT_FOUND',
            ], 404);
        }

        Log::info('Processing payment webhook', [
            'gateway' => $gatewayId,
            'store_id' => $storeId,
            'store' => $store->name,
        ]);

        $checkoutService = new CheckoutService(
            $store,
            $this->paymentGateways,
            $this->shippingCarriers,
            $this->taxProviders
        );

        $result = $checkoutService->processPaymentWebhook($gatewayId, $payload, $headers);

        if ($result['success']) {
            Log::info('Payment webhook processed successfully', [
                'gateway' => $gatewayId,
                'store_id' => $storeId,
                'message' => $result['message'],
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => $result['message'],
            ]);
        }

        Log::warning('Payment webhook processing failed', [
            'gateway' => $gatewayId,
            'store_id' => $storeId,
            'error' => $result['message'],
        ]);

        return response()->json([
            'error' => $result['message'],
            'code' => 'PROCESSING_FAILED',
        ], 400);
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
