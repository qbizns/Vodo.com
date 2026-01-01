<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use VodoCommerce\Models\Store;

/**
 * VerifyWebhookSignature - Verifies incoming webhook requests using HMAC SHA256.
 *
 * Follows industry standards (Stripe, Salla, etc.):
 * - Signature in X-Webhook-Signature header
 * - HMAC SHA256 of raw request body
 * - Timing-safe comparison to prevent timing attacks
 * - Replay protection via timestamp validation
 *
 * Header format: t=<timestamp>,v1=<signature>
 */
class VerifyWebhookSignature
{
    /**
     * Signature header name.
     */
    public const SIGNATURE_HEADER = 'X-Webhook-Signature';

    /**
     * Maximum age of a webhook request in seconds (5 minutes).
     */
    public const MAX_TIMESTAMP_AGE = 300;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $secretSource = 'store'): Response
    {
        $signatureHeader = $request->header(self::SIGNATURE_HEADER);

        if (!$signatureHeader) {
            Log::warning('Webhook request missing signature header', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Missing webhook signature',
                'code' => 'MISSING_SIGNATURE',
            ], 401);
        }

        // Parse signature header
        $parsed = $this->parseSignatureHeader($signatureHeader);

        if (!$parsed) {
            Log::warning('Webhook request with invalid signature format', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Invalid signature format',
                'code' => 'INVALID_SIGNATURE_FORMAT',
            ], 401);
        }

        ['timestamp' => $timestamp, 'signature' => $signature] = $parsed;

        // Validate timestamp to prevent replay attacks
        if (!$this->isTimestampValid($timestamp)) {
            Log::warning('Webhook request with expired timestamp', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'timestamp' => $timestamp,
                'age' => time() - $timestamp,
            ]);

            return response()->json([
                'error' => 'Webhook timestamp too old',
                'code' => 'TIMESTAMP_EXPIRED',
            ], 401);
        }

        // Get the secret based on source
        $secret = $this->getSecret($request, $secretSource);

        if (!$secret) {
            Log::warning('Could not determine webhook secret', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'source' => $secretSource,
            ]);

            return response()->json([
                'error' => 'Could not verify webhook',
                'code' => 'SECRET_NOT_FOUND',
            ], 401);
        }

        // Compute expected signature
        $payload = $request->getContent();
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        // Timing-safe comparison
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Webhook signature verification failed', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Invalid webhook signature',
                'code' => 'SIGNATURE_MISMATCH',
            ], 401);
        }

        // Signature verified - add verification metadata to request
        $request->attributes->set('webhook_verified', true);
        $request->attributes->set('webhook_timestamp', $timestamp);

        Log::info('Webhook signature verified', [
            'path' => $request->path(),
            'timestamp' => $timestamp,
        ]);

        return $next($request);
    }

    /**
     * Parse signature header.
     *
     * Format: t=<timestamp>,v1=<signature>
     *
     * @return array{timestamp: int, signature: string}|null
     */
    protected function parseSignatureHeader(string $header): ?array
    {
        $parts = [];

        foreach (explode(',', $header) as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) === 2) {
                $parts[trim($pair[0])] = trim($pair[1]);
            }
        }

        if (!isset($parts['t']) || !isset($parts['v1'])) {
            return null;
        }

        $timestamp = (int) $parts['t'];

        if ($timestamp <= 0) {
            return null;
        }

        return [
            'timestamp' => $timestamp,
            'signature' => $parts['v1'],
        ];
    }

    /**
     * Check if timestamp is within acceptable range.
     */
    protected function isTimestampValid(int $timestamp): bool
    {
        $age = time() - $timestamp;

        // Reject if timestamp is in the future (clock skew tolerance: 60 seconds)
        if ($age < -60) {
            return false;
        }

        // Reject if timestamp is too old
        return $age <= self::MAX_TIMESTAMP_AGE;
    }

    /**
     * Get webhook secret based on the source.
     */
    protected function getSecret(Request $request, string $source): ?string
    {
        return match ($source) {
            'store' => $this->getStoreSecret($request),
            'gateway' => $this->getGatewaySecret($request),
            default => null,
        };
    }

    /**
     * Get secret from store webhook configuration.
     */
    protected function getStoreSecret(Request $request): ?string
    {
        // Try to get store ID from route, header, or payload
        $storeId = $request->route('store')
            ?? $request->header('X-Store-Id')
            ?? $request->input('store_id')
            ?? $request->input('data.object.metadata.store_id');

        if (!$storeId) {
            return null;
        }

        if ($storeId instanceof Store) {
            $store = $storeId;
        } else {
            $store = Store::withoutStoreScope()->find($storeId);
        }

        if (!$store) {
            return null;
        }

        return $store->getSetting('webhook_secret');
    }

    /**
     * Get secret from payment gateway configuration.
     */
    protected function getGatewaySecret(Request $request): ?string
    {
        $gatewayId = $request->route('gatewayId');

        if (!$gatewayId) {
            return null;
        }

        // Get store-specific gateway webhook secret
        $storeId = $request->header('X-Store-Id')
            ?? $request->input('store_id')
            ?? $request->input('data.object.metadata.store_id');

        if (!$storeId) {
            // Fall back to gateway default secret from config
            return config("commerce.gateways.{$gatewayId}.webhook_secret");
        }

        $store = Store::withoutStoreScope()->find($storeId);

        if (!$store) {
            return null;
        }

        // Get store-specific gateway configuration
        $gatewayConfig = $store->getSetting("payment_gateways.{$gatewayId}");

        return $gatewayConfig['webhook_secret'] ?? null;
    }

    /**
     * Generate a signature for outgoing webhooks.
     *
     * @param string $payload The webhook payload (JSON string)
     * @param string $secret The webhook secret
     * @return string The signature header value
     */
    public static function generateSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
