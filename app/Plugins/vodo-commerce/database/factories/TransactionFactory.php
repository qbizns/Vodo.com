<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Transaction;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10, 1000);
        $feeAmount = round($amount * 0.029 + 0.30, 2); // 2.9% + $0.30
        $netAmount = round($amount - $feeAmount, 2);

        return [
            'store_id' => Store::factory(),
            'order_id' => Order::factory(),
            'customer_id' => Customer::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'external_id' => fake()->uuid(),
            'type' => Transaction::TYPE_PAYMENT,
            'status' => Transaction::STATUS_COMPLETED,
            'payment_status' => null,
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'SAR']),
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'fees' => [
                'fixed' => 0.30,
                'percentage' => 2.9,
                'calculated' => $feeAmount,
            ],
            'payment_method_type' => fake()->randomElement(['card', 'bank_transfer', 'wallet']),
            'card_brand' => fake()->randomElement(['visa', 'mastercard', 'amex', null]),
            'card_last4' => fake()->optional()->numerify('####'),
            'bank_name' => null,
            'wallet_provider' => null,
            'gateway_response' => [
                'id' => fake()->uuid(),
                'status' => 'succeeded',
                'message' => 'Payment successful',
            ],
            'failure_reason' => null,
            'failure_code' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'is_test' => fake()->boolean(30),
            'authorized_at' => null,
            'captured_at' => null,
            'settled_at' => null,
            'failed_at' => null,
            'refunded_at' => null,
            'processed_at' => now(),
            'parent_transaction_id' => null,
            'refunded_amount' => 0,
            'refund_reason' => null,
            'metadata' => null,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the transaction is a payment
     */
    public function payment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_PAYMENT,
        ]);
    }

    /**
     * Indicate that the transaction is a refund
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_REFUND,
            'parent_transaction_id' => Transaction::factory()->completed(),
            'refund_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the transaction is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_PENDING,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is processing
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_PROCESSING,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the transaction has failed
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_FAILED,
            'failure_reason' => fake()->randomElement([
                'Insufficient funds',
                'Card declined',
                'Invalid card number',
                'Expired card',
            ]),
            'failure_code' => fake()->randomElement(['insufficient_funds', 'card_declined', 'invalid_card', 'expired_card']),
            'failed_at' => now(),
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_CANCELLED,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is refunded
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_REFUNDED,
            'refunded_amount' => $attributes['amount'],
            'refunded_at' => now(),
        ]);
    }

    /**
     * Indicate that the transaction is authorized
     */
    public function authorized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_PROCESSING,
            'payment_status' => Transaction::PAYMENT_STATUS_AUTHORIZED,
            'authorized_at' => now(),
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is captured
     */
    public function captured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_COMPLETED,
            'payment_status' => Transaction::PAYMENT_STATUS_CAPTURED,
            'authorized_at' => now()->subHours(2),
            'captured_at' => now(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the transaction is settled
     */
    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_COMPLETED,
            'payment_status' => Transaction::PAYMENT_STATUS_SETTLED,
            'authorized_at' => now()->subDays(3),
            'captured_at' => now()->subDays(2),
            'settled_at' => now(),
            'processed_at' => now()->subDays(2),
        ]);
    }

    /**
     * Indicate that the transaction is a card payment
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_type' => 'card',
            'card_brand' => fake()->randomElement(['visa', 'mastercard', 'amex', 'discover']),
            'card_last4' => fake()->numerify('####'),
        ]);
    }

    /**
     * Indicate that the transaction is a bank transfer
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_type' => 'bank_transfer',
            'bank_name' => fake()->randomElement(['Bank of America', 'Chase', 'Wells Fargo', 'Citibank']),
            'card_brand' => null,
            'card_last4' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a wallet payment
     */
    public function wallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_type' => 'wallet',
            'wallet_provider' => fake()->randomElement(['paypal', 'apple_pay', 'google_pay', 'venmo']),
            'card_brand' => null,
            'card_last4' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a test transaction
     */
    public function test(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_test' => true,
        ]);
    }

    /**
     * Indicate that the transaction is a live transaction
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_test' => false,
        ]);
    }

    /**
     * Set specific amount for the transaction
     */
    public function amount(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $feeAmount = round($amount * 0.029 + 0.30, 2);
            $netAmount = round($amount - $feeAmount, 2);

            return [
                'amount' => $amount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'fees' => [
                    'fixed' => 0.30,
                    'percentage' => 2.9,
                    'calculated' => $feeAmount,
                ],
            ];
        });
    }

    /**
     * Set currency for the transaction
     */
    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => strtoupper($currency),
        ]);
    }
}
