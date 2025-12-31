<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Contracts\PayoutGatewayInterface;
use App\Services\Payment\DTO\ChargeResult;
use App\Services\Payment\DTO\RefundResult;
use App\Services\Payment\DTO\CustomerResult;
use App\Services\Payment\DTO\PaymentMethodResult;
use App\Services\Payment\DTO\PayoutResult;
use App\Services\Payment\DTO\ConnectedAccountResult;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Stripe Payment Gateway
 *
 * Implementation of payment and payout operations using Stripe.
 */
class StripeGateway implements PaymentGatewayInterface, PayoutGatewayInterface
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    public function getIdentifier(): string
    {
        return 'stripe';
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->getSupportedCurrencies());
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'SAR', 'AED', 'KWD', 'BHD', 'OMR', 'QAR'];
    }

    public function createCustomer(array $data): CustomerResult
    {
        try {
            $customer = $this->client->customers->create([
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            return CustomerResult::success($customer->id, [
                'email' => $customer->email,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'metadata' => $customer->metadata?->toArray() ?? [],
                'raw_response' => $customer->toArray(),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe createCustomer failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateCustomer(string $customerId, array $data): CustomerResult
    {
        try {
            $customer = $this->client->customers->update($customerId, [
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            return CustomerResult::success($customer->id, [
                'email' => $customer->email,
                'name' => $customer->name,
                'raw_response' => $customer->toArray(),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe updateCustomer failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteCustomer(string $customerId): bool
    {
        try {
            $this->client->customers->delete($customerId);
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe deleteCustomer failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function attachPaymentMethod(string $customerId, string $paymentMethodId): PaymentMethodResult
    {
        try {
            $paymentMethod = $this->client->paymentMethods->attach($paymentMethodId, [
                'customer' => $customerId,
            ]);

            return $this->mapPaymentMethod($paymentMethod);
        } catch (ApiErrorException $e) {
            Log::error('Stripe attachPaymentMethod failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function detachPaymentMethod(string $paymentMethodId): bool
    {
        try {
            $this->client->paymentMethods->detach($paymentMethodId);
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe detachPaymentMethod failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getPaymentMethod(string $paymentMethodId): ?PaymentMethodResult
    {
        try {
            $paymentMethod = $this->client->paymentMethods->retrieve($paymentMethodId);
            return $this->mapPaymentMethod($paymentMethod);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    public function listPaymentMethods(string $customerId): array
    {
        try {
            $methods = $this->client->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card',
            ]);

            return array_map(
                fn($pm) => $this->mapPaymentMethod($pm),
                $methods->data
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe listPaymentMethods failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function charge(array $data): ChargeResult
    {
        try {
            $paymentIntent = $this->client->paymentIntents->create([
                'amount' => $data['amount'],
                'currency' => strtolower($data['currency']),
                'customer' => $data['customer_id'] ?? null,
                'payment_method' => $data['payment_method_id'],
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'metadata' => $data['metadata'] ?? [],
                'description' => $data['description'] ?? null,
            ]);

            $status = match ($paymentIntent->status) {
                'succeeded' => PaymentStatus::Succeeded,
                'processing' => PaymentStatus::Processing,
                'requires_action', 'requires_confirmation' => PaymentStatus::Pending,
                default => PaymentStatus::Failed,
            };

            if ($status === PaymentStatus::Succeeded) {
                return ChargeResult::success(
                    $paymentIntent->id,
                    $paymentIntent->amount,
                    strtoupper($paymentIntent->currency),
                    [
                        'payment_method_id' => $paymentIntent->payment_method,
                        'customer_id' => $paymentIntent->customer,
                        'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null,
                        'raw_response' => $paymentIntent->toArray(),
                    ]
                );
            }

            if ($status === PaymentStatus::Pending) {
                return ChargeResult::pending(
                    $paymentIntent->id,
                    $paymentIntent->amount,
                    strtoupper($paymentIntent->currency),
                    [
                        'payment_method_id' => $paymentIntent->payment_method,
                        'customer_id' => $paymentIntent->customer,
                        'raw_response' => $paymentIntent->toArray(),
                    ]
                );
            }

            return ChargeResult::failed(
                $paymentIntent->id,
                $paymentIntent->amount,
                strtoupper($paymentIntent->currency),
                $paymentIntent->last_payment_error?->code ?? 'unknown',
                $paymentIntent->last_payment_error?->message ?? 'Payment failed',
                ['raw_response' => $paymentIntent->toArray()]
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe charge failed', ['error' => $e->getMessage()]);
            return ChargeResult::failed(
                '',
                $data['amount'],
                $data['currency'],
                $e->getStripeCode() ?? 'api_error',
                $e->getMessage()
            );
        }
    }

    public function capture(string $chargeId, ?int $amount = null): ChargeResult
    {
        try {
            $params = [];
            if ($amount !== null) {
                $params['amount_to_capture'] = $amount;
            }

            $paymentIntent = $this->client->paymentIntents->capture($chargeId, $params);

            return ChargeResult::success(
                $paymentIntent->id,
                $paymentIntent->amount_received,
                strtoupper($paymentIntent->currency),
                ['raw_response' => $paymentIntent->toArray()]
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe capture failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function refund(string $chargeId, ?int $amount = null, ?string $reason = null): RefundResult
    {
        try {
            $params = ['payment_intent' => $chargeId];
            if ($amount !== null) {
                $params['amount'] = $amount;
            }
            if ($reason !== null) {
                $params['reason'] = $reason;
            }

            $refund = $this->client->refunds->create($params);

            return RefundResult::success(
                $refund->id,
                $chargeId,
                $refund->amount,
                strtoupper($refund->currency),
                [
                    'reason' => $refund->reason,
                    'raw_response' => $refund->toArray(),
                ]
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', ['error' => $e->getMessage()]);
            return RefundResult::failed(
                '',
                $chargeId,
                $amount ?? 0,
                '',
                $e->getStripeCode() ?? 'api_error',
                $e->getMessage()
            );
        }
    }

    public function getCharge(string $chargeId): ?ChargeResult
    {
        try {
            $paymentIntent = $this->client->paymentIntents->retrieve($chargeId);

            $status = match ($paymentIntent->status) {
                'succeeded' => PaymentStatus::Succeeded,
                'processing' => PaymentStatus::Processing,
                'canceled' => PaymentStatus::Cancelled,
                default => PaymentStatus::Pending,
            };

            return new ChargeResult(
                success: $status === PaymentStatus::Succeeded,
                chargeId: $paymentIntent->id,
                status: $status,
                amount: $paymentIntent->amount,
                currency: strtoupper($paymentIntent->currency),
                paymentMethodId: $paymentIntent->payment_method,
                customerId: $paymentIntent->customer,
                rawResponse: $paymentIntent->toArray(),
            );
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    public function createPaymentIntent(array $data): array
    {
        try {
            $paymentIntent = $this->client->paymentIntents->create([
                'amount' => $data['amount'],
                'currency' => strtolower($data['currency']),
                'customer' => $data['customer_id'] ?? null,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => $data['metadata'] ?? [],
            ]);

            return [
                'id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe createPaymentIntent failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function confirmPaymentIntent(string $intentId, array $data = []): array
    {
        try {
            $paymentIntent = $this->client->paymentIntents->confirm($intentId, $data);

            return [
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe confirmPaymentIntent failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function parseWebhookEvent(string $payload): array
    {
        $event = json_decode($payload, true);
        return [
            'type' => $event['type'] ?? '',
            'data' => $event['data']['object'] ?? [],
            'id' => $event['id'] ?? '',
        ];
    }

    // PayoutGatewayInterface implementation

    public function createConnectedAccount(array $data): ConnectedAccountResult
    {
        try {
            $account = $this->client->accounts->create([
                'type' => 'express',
                'country' => $data['country'] ?? 'SA',
                'email' => $data['email'] ?? null,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => $data['business_type'] ?? 'individual',
                'metadata' => $data['metadata'] ?? [],
            ]);

            return ConnectedAccountResult::success(
                $account->id,
                'pending',
                $account->charges_enabled,
                $account->payouts_enabled,
                [
                    'email' => $account->email,
                    'country' => $account->country,
                    'default_currency' => $account->default_currency,
                    'requirements' => $account->requirements?->toArray() ?? [],
                    'raw_response' => $account->toArray(),
                ]
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe createConnectedAccount failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateConnectedAccount(string $accountId, array $data): ConnectedAccountResult
    {
        try {
            $account = $this->client->accounts->update($accountId, $data);

            return ConnectedAccountResult::success(
                $account->id,
                $account->charges_enabled && $account->payouts_enabled ? 'verified' : 'pending',
                $account->charges_enabled,
                $account->payouts_enabled,
                [
                    'email' => $account->email,
                    'country' => $account->country,
                    'raw_response' => $account->toArray(),
                ]
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe updateConnectedAccount failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getConnectedAccount(string $accountId): ?ConnectedAccountResult
    {
        try {
            $account = $this->client->accounts->retrieve($accountId);

            return ConnectedAccountResult::success(
                $account->id,
                $account->charges_enabled && $account->payouts_enabled ? 'verified' : 'pending',
                $account->charges_enabled,
                $account->payouts_enabled,
                [
                    'email' => $account->email,
                    'country' => $account->country,
                    'default_currency' => $account->default_currency,
                    'requirements' => $account->requirements?->toArray() ?? [],
                    'raw_response' => $account->toArray(),
                ]
            );
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    public function getOnboardingLink(string $accountId, string $returnUrl, string $refreshUrl): string
    {
        try {
            $link = $this->client->accountLinks->create([
                'account' => $accountId,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
            ]);

            return $link->url;
        } catch (ApiErrorException $e) {
            Log::error('Stripe getOnboardingLink failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function isAccountReady(string $accountId): bool
    {
        $account = $this->getConnectedAccount($accountId);
        return $account?->isReady() ?? false;
    }

    public function createPayout(string $accountId, int $amount, string $currency, array $metadata = []): PayoutResult
    {
        try {
            $transfer = $this->client->transfers->create([
                'amount' => $amount,
                'currency' => strtolower($currency),
                'destination' => $accountId,
                'metadata' => $metadata,
            ]);

            return PayoutResult::success(
                $transfer->id,
                $transfer->amount,
                strtoupper($transfer->currency),
                [
                    'account_id' => $accountId,
                    'raw_response' => $transfer->toArray(),
                ]
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe createPayout failed', ['error' => $e->getMessage()]);
            return PayoutResult::failed(
                '',
                $amount,
                $currency,
                $e->getStripeCode() ?? 'api_error',
                $e->getMessage()
            );
        }
    }

    public function getPayout(string $payoutId): ?PayoutResult
    {
        try {
            $transfer = $this->client->transfers->retrieve($payoutId);

            return new PayoutResult(
                success: true,
                payoutId: $transfer->id,
                status: PayoutStatus::Completed,
                amount: $transfer->amount,
                currency: strtoupper($transfer->currency),
                accountId: $transfer->destination,
                rawResponse: $transfer->toArray(),
            );
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    public function cancelPayout(string $payoutId): bool
    {
        // Stripe transfers cannot be cancelled once created
        return false;
    }

    public function getAccountBalance(string $accountId): array
    {
        try {
            $balance = $this->client->balance->retrieve([], [
                'stripe_account' => $accountId,
            ]);

            return [
                'available' => collect($balance->available)->map(fn($b) => [
                    'amount' => $b->amount,
                    'currency' => strtoupper($b->currency),
                ])->all(),
                'pending' => collect($balance->pending)->map(fn($b) => [
                    'amount' => $b->amount,
                    'currency' => strtoupper($b->currency),
                ])->all(),
            ];
        } catch (ApiErrorException $e) {
            return ['available' => [], 'pending' => []];
        }
    }

    public function listPayouts(string $accountId, array $filters = []): array
    {
        try {
            $transfers = $this->client->transfers->all([
                'destination' => $accountId,
                'limit' => $filters['limit'] ?? 100,
            ]);

            return array_map(fn($t) => [
                'id' => $t->id,
                'amount' => $t->amount,
                'currency' => strtoupper($t->currency),
                'created' => $t->created,
            ], $transfers->data);
        } catch (ApiErrorException $e) {
            return [];
        }
    }

    protected function mapPaymentMethod($pm): PaymentMethodResult
    {
        $card = $pm->card;

        return PaymentMethodResult::fromCard(
            $pm->id,
            $card->brand,
            $card->last4,
            str_pad((string) $card->exp_month, 2, '0', STR_PAD_LEFT),
            (string) $card->exp_year,
            [
                'customer_id' => $pm->customer,
                'raw_response' => $pm->toArray(),
            ]
        );
    }
}
