<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTO\PayoutResult;
use App\Services\Payment\DTO\ConnectedAccountResult;

/**
 * Payout Gateway Interface
 *
 * Defines the contract for payout operations to developers.
 */
interface PayoutGatewayInterface
{
    /**
     * Get the gateway identifier.
     */
    public function getIdentifier(): string;

    /**
     * Create a connected account for a developer.
     */
    public function createConnectedAccount(array $data): ConnectedAccountResult;

    /**
     * Update a connected account.
     */
    public function updateConnectedAccount(string $accountId, array $data): ConnectedAccountResult;

    /**
     * Get connected account details.
     */
    public function getConnectedAccount(string $accountId): ?ConnectedAccountResult;

    /**
     * Get the onboarding link for account verification.
     */
    public function getOnboardingLink(string $accountId, string $returnUrl, string $refreshUrl): string;

    /**
     * Check if an account is ready for payouts.
     */
    public function isAccountReady(string $accountId): bool;

    /**
     * Create a payout to a connected account.
     */
    public function createPayout(string $accountId, int $amount, string $currency, array $metadata = []): PayoutResult;

    /**
     * Get payout details.
     */
    public function getPayout(string $payoutId): ?PayoutResult;

    /**
     * Cancel a pending payout.
     */
    public function cancelPayout(string $payoutId): bool;

    /**
     * Get account balance.
     */
    public function getAccountBalance(string $accountId): array;

    /**
     * List payouts for an account.
     */
    public function listPayouts(string $accountId, array $filters = []): array;
}
