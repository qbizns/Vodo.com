<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTO\ChargeResult;
use App\Services\Payment\DTO\RefundResult;
use App\Services\Payment\DTO\CustomerResult;
use App\Services\Payment\DTO\PaymentMethodResult;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Moyasar Payment Gateway
 *
 * Implementation for Saudi Arabia's Moyasar payment gateway.
 * Supports mada, Visa, Mastercard, Apple Pay, and STC Pay.
 */
class MoyasarGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.moyasar.base_url', 'https://api.moyasar.com/v1');
        $this->apiKey = config('services.moyasar.secret_key');
    }

    public function getIdentifier(): string
    {
        return 'moyasar';
    }

    public function getName(): string
    {
        return 'Moyasar';
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->getSupportedCurrencies());
    }

    public function getSupportedCurrencies(): array
    {
        return ['SAR', 'USD', 'EUR', 'GBP', 'AED', 'KWD', 'BHD', 'OMR', 'QAR'];
    }

    public function createCustomer(array $data): CustomerResult
    {
        // Moyasar doesn't have explicit customer management
        // We create a virtual customer ID
        $customerId = 'moy_cust_' . uniqid();

        return CustomerResult::success($customerId, [
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    public function updateCustomer(string $customerId, array $data): CustomerResult
    {
        return CustomerResult::success($customerId, [
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
        ]);
    }

    public function deleteCustomer(string $customerId): bool
    {
        return true;
    }

    public function attachPaymentMethod(string $customerId, string $paymentMethodId): PaymentMethodResult
    {
        // Moyasar handles payment methods at charge time
        return new PaymentMethodResult(
            success: true,
            paymentMethodId: $paymentMethodId,
            type: 'card',
            customerId: $customerId,
        );
    }

    public function detachPaymentMethod(string $paymentMethodId): bool
    {
        return true;
    }

    public function getPaymentMethod(string $paymentMethodId): ?PaymentMethodResult
    {
        return null;
    }

    public function listPaymentMethods(string $customerId): array
    {
        return [];
    }

    public function charge(array $data): ChargeResult
    {
        try {
            $payload = [
                'amount' => $data['amount'],
                'currency' => strtoupper($data['currency']),
                'description' => $data['description'] ?? 'Marketplace purchase',
                'callback_url' => $data['callback_url'] ?? config('app.url') . '/webhooks/moyasar',
                'source' => $this->buildSource($data),
                'metadata' => $data['metadata'] ?? [],
            ];

            $response = Http::withBasicAuth($this->apiKey, '')
                ->post("{$this->baseUrl}/payments", $payload);

            if (!$response->successful()) {
                return ChargeResult::failed(
                    '',
                    $data['amount'],
                    $data['currency'],
                    'api_error',
                    $response->json('message', 'Payment failed'),
                    ['raw_response' => $response->json()]
                );
            }

            $payment = $response->json();
            $status = $this->mapStatus($payment['status'] ?? 'failed');

            if ($status === PaymentStatus::Succeeded) {
                return ChargeResult::success(
                    $payment['id'],
                    $payment['amount'],
                    strtoupper($payment['currency']),
                    [
                        'fee' => $payment['fee'] ?? null,
                        'raw_response' => $payment,
                    ]
                );
            }

            if ($status === PaymentStatus::Pending) {
                return ChargeResult::pending(
                    $payment['id'],
                    $payment['amount'],
                    strtoupper($payment['currency']),
                    [
                        'metadata' => ['transaction_url' => $payment['source']['transaction_url'] ?? null],
                        'raw_response' => $payment,
                    ]
                );
            }

            return ChargeResult::failed(
                $payment['id'] ?? '',
                $data['amount'],
                $data['currency'],
                $payment['source']['message'] ?? 'unknown',
                $payment['source']['message'] ?? 'Payment failed',
                ['raw_response' => $payment]
            );
        } catch (\Exception $e) {
            Log::error('Moyasar charge failed', ['error' => $e->getMessage()]);
            return ChargeResult::failed(
                '',
                $data['amount'],
                $data['currency'],
                'exception',
                $e->getMessage()
            );
        }
    }

    public function capture(string $chargeId, ?int $amount = null): ChargeResult
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->post("{$this->baseUrl}/payments/{$chargeId}/capture");

            if (!$response->successful()) {
                throw new \Exception($response->json('message', 'Capture failed'));
            }

            $payment = $response->json();

            return ChargeResult::success(
                $payment['id'],
                $payment['amount'],
                strtoupper($payment['currency']),
                ['raw_response' => $payment]
            );
        } catch (\Exception $e) {
            Log::error('Moyasar capture failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function refund(string $chargeId, ?int $amount = null, ?string $reason = null): RefundResult
    {
        try {
            $payload = [];
            if ($amount !== null) {
                $payload['amount'] = $amount;
            }

            $response = Http::withBasicAuth($this->apiKey, '')
                ->post("{$this->baseUrl}/payments/{$chargeId}/refund", $payload);

            if (!$response->successful()) {
                return RefundResult::failed(
                    '',
                    $chargeId,
                    $amount ?? 0,
                    '',
                    'api_error',
                    $response->json('message', 'Refund failed')
                );
            }

            $payment = $response->json();

            return RefundResult::success(
                $payment['id'] . '_refund',
                $chargeId,
                $payment['refunded'] ?? $amount,
                strtoupper($payment['currency']),
                [
                    'reason' => $reason,
                    'raw_response' => $payment,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Moyasar refund failed', ['error' => $e->getMessage()]);
            return RefundResult::failed(
                '',
                $chargeId,
                $amount ?? 0,
                '',
                'exception',
                $e->getMessage()
            );
        }
    }

    public function getCharge(string $chargeId): ?ChargeResult
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->get("{$this->baseUrl}/payments/{$chargeId}");

            if (!$response->successful()) {
                return null;
            }

            $payment = $response->json();
            $status = $this->mapStatus($payment['status'] ?? 'failed');

            return new ChargeResult(
                success: $status === PaymentStatus::Succeeded,
                chargeId: $payment['id'],
                status: $status,
                amount: $payment['amount'],
                currency: strtoupper($payment['currency']),
                fee: $payment['fee'] ?? null,
                rawResponse: $payment,
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createPaymentIntent(array $data): array
    {
        // Moyasar uses a different flow - create a payment form
        return [
            'publishable_key' => config('services.moyasar.publishable_key'),
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency']),
            'description' => $data['description'] ?? '',
            'callback_url' => $data['callback_url'] ?? config('app.url') . '/webhooks/moyasar',
            'metadata' => $data['metadata'] ?? [],
        ];
    }

    public function confirmPaymentIntent(string $intentId, array $data = []): array
    {
        $charge = $this->getCharge($intentId);

        return [
            'id' => $intentId,
            'status' => $charge?->status->value ?? 'unknown',
            'amount' => $charge?->amount ?? 0,
            'currency' => $charge?->currency ?? '',
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.moyasar.webhook_secret');
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function parseWebhookEvent(string $payload): array
    {
        $data = json_decode($payload, true);

        return [
            'type' => $data['type'] ?? '',
            'data' => $data['data'] ?? [],
            'id' => $data['id'] ?? '',
        ];
    }

    protected function buildSource(array $data): array
    {
        $sourceType = $data['source_type'] ?? 'creditcard';

        return match ($sourceType) {
            'creditcard' => [
                'type' => 'creditcard',
                'name' => $data['card_name'] ?? '',
                'number' => $data['card_number'] ?? '',
                'cvc' => $data['card_cvc'] ?? '',
                'month' => $data['card_month'] ?? '',
                'year' => $data['card_year'] ?? '',
            ],
            'mada' => [
                'type' => 'creditcard',
                'name' => $data['card_name'] ?? '',
                'number' => $data['card_number'] ?? '',
                'cvc' => $data['card_cvc'] ?? '',
                'month' => $data['card_month'] ?? '',
                'year' => $data['card_year'] ?? '',
            ],
            'applepay' => [
                'type' => 'applepay',
                'token' => $data['apple_token'] ?? '',
            ],
            'stcpay' => [
                'type' => 'stcpay',
                'mobile' => $data['mobile'] ?? '',
            ],
            'token' => [
                'type' => 'token',
                'token' => $data['token'] ?? '',
            ],
            default => throw new \InvalidArgumentException("Unsupported source type: {$sourceType}"),
        };
    }

    protected function mapStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'paid' => PaymentStatus::Succeeded,
            'initiated', 'pending', 'authorized' => PaymentStatus::Pending,
            'failed', 'expired', 'voided' => PaymentStatus::Failed,
            'refunded' => PaymentStatus::Refunded,
            default => PaymentStatus::Failed,
        };
    }
}
