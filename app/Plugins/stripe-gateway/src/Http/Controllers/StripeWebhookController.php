<?php

declare(strict_types=1);

namespace StripeGateway\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use StripeGateway\Services\StripePaymentGateway;
use VodoCommerce\Models\Order;
use VodoCommerce\Events\CommerceEvents;

/**
 * Stripe Webhook Controller
 *
 * Handles incoming webhooks from Stripe and processes payment events.
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripePaymentGateway $gateway
    ) {
    }

    /**
     * Handle incoming Stripe webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        // Flatten headers
        $flatHeaders = [];
        foreach ($headers as $key => $values) {
            $flatHeaders[$key] = is_array($values) ? $values[0] : $values;
        }

        try {
            $result = $this->gateway->handleWebhook($payload, $flatHeaders);

            if (!$result->processed) {
                Log::warning('Stripe webhook not processed', [
                    'message' => $result->message,
                    'event_type' => $payload['type'] ?? 'unknown',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result->message,
                ], 400);
            }

            // Process the order update if we have an order ID
            if (isset($result->orderId)) {
                $this->processOrderUpdate($result);
            }

            return response()->json([
                'success' => true,
                'message' => $result->message,
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'event_type' => $payload['type'] ?? 'unknown',
            ]);

            // Return 500 so Stripe will retry
            return response()->json([
                'success' => false,
                'message' => 'Internal error processing webhook',
            ], 500);
        }
    }

    /**
     * Process order update based on webhook result.
     */
    protected function processOrderUpdate(object $result): void
    {
        $order = Order::withoutStoreScope()->find($result->orderId);

        if (!$order) {
            Log::warning('Order not found for Stripe webhook', [
                'order_id' => $result->orderId,
            ]);
            return;
        }

        $previousStatus = $order->payment_status;

        if ($result->paymentStatus === 'paid') {
            $order->markAsPaid($result->transactionId ?? null);

            // Fire payment success events
            do_action(CommerceEvents::PAYMENT_PAID, $order, $result->transactionId ?? null);
            do_action(CommerceEvents::ORDER_STATUS_CHANGED, $order, $previousStatus, $order->status);

            Log::info('Order marked as paid via Stripe webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'transaction_id' => $result->transactionId ?? null,
            ]);
        } elseif ($result->paymentStatus === 'failed') {
            // Fire payment failed event
            do_action(CommerceEvents::PAYMENT_FAILED, $order, $result->message ?? 'Payment failed');

            Log::warning('Order payment failed via Stripe webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => $result->message ?? null,
            ]);
        } elseif (in_array($result->paymentStatus, ['refunded', 'partially_refunded'])) {
            // Fire refund event
            do_action(CommerceEvents::PAYMENT_REFUNDED, $order, $result->refundAmount ?? 0);

            Log::info('Order refunded via Stripe webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refund_amount' => $result->refundAmount ?? 0,
            ]);
        }
    }
}
