<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use VodoCommerce\Models\WebhookDelivery;
use VodoCommerce\Models\WebhookEvent;
use VodoCommerce\Models\WebhookLog;
use VodoCommerce\Models\WebhookSubscription;

class WebhookDeliveryService
{
    /**
     * Deliver a webhook event.
     */
    public function deliver(WebhookEvent $event): bool
    {
        if (!$event->subscription || !$event->subscription->isActive()) {
            WebhookLog::warning(
                $event->store_id,
                'Cannot deliver event: subscription inactive or not found',
                ['event_id' => $event->event_id],
                $event->subscription_id,
                $event->id
            );

            $event->cancel();

            return false;
        }

        $subscription = $event->subscription;

        // Mark event as processing
        $event->markAsProcessing(gethostname());

        // Create delivery record
        $delivery = $this->createDelivery($event, $subscription);

        // Attempt delivery
        try {
            $result = $this->sendRequest($event, $subscription, $delivery);

            if ($result['success']) {
                $this->handleSuccessfulDelivery($event, $subscription, $delivery, $result);

                return true;
            } else {
                $this->handleFailedDelivery($event, $subscription, $delivery, $result);

                return false;
            }
        } catch (\Exception $e) {
            $this->handleDeliveryException($event, $subscription, $delivery, $e);

            return false;
        }
    }

    /**
     * Create a delivery record.
     */
    protected function createDelivery(WebhookEvent $event, WebhookSubscription $subscription): WebhookDelivery
    {
        return WebhookDelivery::create([
            'event_id' => $event->id,
            'subscription_id' => $subscription->id,
            'url' => $subscription->url,
            'payload' => $event->payload,
            'headers' => $this->buildHeaders($event, $subscription),
            'attempt_number' => $event->retry_count + 1,
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);
    }

    /**
     * Build HTTP headers for the webhook request.
     */
    protected function buildHeaders(WebhookEvent $event, WebhookSubscription $subscription): array
    {
        $payload = json_encode($event->payload);
        $signature = hash_hmac('sha256', $payload, $subscription->secret);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Vodo-Commerce-Webhook/1.0',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Event-Id' => $event->event_id,
            'X-Webhook-Event-Type' => $event->event_type,
            'X-Webhook-Delivery-Id' => (string) $event->id,
            'X-Webhook-Timestamp' => now()->timestamp,
        ];

        // Merge custom headers
        if ($subscription->custom_headers) {
            $headers = array_merge($headers, $subscription->custom_headers);
        }

        return $headers;
    }

    /**
     * Send the HTTP request to the webhook endpoint.
     */
    protected function sendRequest(
        WebhookEvent $event,
        WebhookSubscription $subscription,
        WebhookDelivery $delivery
    ): array {
        $delivery->markAsSent();
        $startTime = microtime(true);

        try {
            $response = Http::timeout($subscription->timeout_seconds)
                ->withHeaders($delivery->headers)
                ->post($subscription->url, $event->payload);

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $success = $response->successful();

            return [
                'success' => $success,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'response_headers' => json_encode($response->headers()),
                'duration_ms' => $duration,
                'error' => $success ? null : "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'success' => false,
                'status_code' => 0,
                'response_body' => null,
                'response_headers' => null,
                'duration_ms' => $duration,
                'error' => "Connection error: {$e->getMessage()}",
                'is_timeout' => str_contains($e->getMessage(), 'timeout'),
            ];
        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'success' => false,
                'status_code' => 0,
                'response_body' => null,
                'response_headers' => null,
                'duration_ms' => $duration,
                'error' => "Exception: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Handle successful delivery.
     */
    protected function handleSuccessfulDelivery(
        WebhookEvent $event,
        WebhookSubscription $subscription,
        WebhookDelivery $delivery,
        array $result
    ): void {
        DB::transaction(function () use ($event, $subscription, $delivery, $result) {
            // Update delivery record
            $delivery->markAsSuccess(
                $result['status_code'],
                $result['response_body'],
                $result['response_headers'],
                $result['duration_ms']
            );

            // Update event
            $event->markAsDelivered();

            // Update subscription statistics
            $subscription->updateDeliveryStats(true);

            // Log success
            WebhookLog::info(
                $event->store_id,
                "Webhook delivered successfully: {$event->event_type}",
                [
                    'event_id' => $event->event_id,
                    'status_code' => $result['status_code'],
                    'duration_ms' => $result['duration_ms'],
                ],
                $subscription->id,
                $event->id,
                $delivery->id
            );
        });
    }

    /**
     * Handle failed delivery.
     */
    protected function handleFailedDelivery(
        WebhookEvent $event,
        WebhookSubscription $subscription,
        WebhookDelivery $delivery,
        array $result
    ): void {
        DB::transaction(function () use ($event, $subscription, $delivery, $result) {
            // Update delivery record
            if ($result['is_timeout'] ?? false) {
                $delivery->markAsTimeout($result['duration_ms']);
            } else {
                $delivery->markAsFailed(
                    $result['status_code'],
                    $result['error'],
                    $result['response_body'],
                    $result['duration_ms']
                );
            }

            // Update event
            $event->markAsFailed($result['error'], $subscription->retry_delay_seconds);

            // Update subscription statistics
            $subscription->updateDeliveryStats(false);

            // Log failure
            WebhookLog::error(
                $event->store_id,
                "Webhook delivery failed: {$event->event_type}",
                [
                    'event_id' => $event->event_id,
                    'error' => $result['error'],
                    'status_code' => $result['status_code'],
                    'retry_count' => $event->retry_count,
                    'will_retry' => $event->canRetry(),
                ],
                $subscription->id,
                $event->id,
                $delivery->id
            );
        });
    }

    /**
     * Handle delivery exception.
     */
    protected function handleDeliveryException(
        WebhookEvent $event,
        WebhookSubscription $subscription,
        WebhookDelivery $delivery,
        \Exception $exception
    ): void {
        DB::transaction(function () use ($event, $subscription, $delivery, $exception) {
            // Update delivery record
            $delivery->markAsFailed(0, $exception->getMessage());

            // Update event
            $event->markAsFailed($exception->getMessage(), $subscription->retry_delay_seconds);

            // Update subscription statistics
            $subscription->updateDeliveryStats(false);

            // Log critical error
            WebhookLog::critical(
                $event->store_id,
                "Webhook delivery exception: {$event->event_type}",
                [
                    'event_id' => $event->event_id,
                    'exception' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ],
                $subscription->id,
                $event->id,
                $delivery->id
            );
        });
    }

    /**
     * Process pending webhook events.
     */
    public function processPendingEvents(int $limit = 10): int
    {
        $events = WebhookEvent::readyForRetry()
            ->with('subscription')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($events as $event) {
            $this->deliver($event);
            $processed++;
        }

        return $processed;
    }
}
