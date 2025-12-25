<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\Integration\Trigger\TriggerEngine;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Webhook Controller
 *
 * Receives webhooks from external services.
 */
class WebhookController extends Controller
{
    public function __construct(
        protected TriggerEngine $triggerEngine
    ) {}

    /**
     * Handle incoming webhook.
     */
    public function handle(Request $request, string $subscriptionId)
    {
        try {
            $payload = $request->all();
            $headers = $request->headers->all();

            // Flatten headers (they come as arrays)
            $flatHeaders = [];
            foreach ($headers as $key => $value) {
                $flatHeaders[$key] = is_array($value) ? ($value[0] ?? '') : $value;
            }

            $this->triggerEngine->handleWebhook($subscriptionId, $payload, $flatHeaders);

            return response()->json(['success' => true], 200);

        } catch (\App\Exceptions\Integration\WebhookVerificationException $e) {
            return response()->json(['error' => 'Verification failed'], 401);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Subscription not found'], 404);

        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
