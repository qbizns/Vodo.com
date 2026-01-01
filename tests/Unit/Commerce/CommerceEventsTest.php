<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use ReflectionClass;
use Tests\TestCase;
use VodoCommerce\Events\CommerceEvents;

class CommerceEventsTest extends TestCase
{
    public function test_all_event_constants_follow_naming_convention(): void
    {
        $reflection = new ReflectionClass(CommerceEvents::class);
        $constants = $reflection->getConstants();

        foreach ($constants as $name => $value) {
            // All constants should be strings
            $this->assertIsString($value, "Constant {$name} should be a string");

            // All values should start with 'commerce.'
            $this->assertStringStartsWith(
                'commerce.',
                $value,
                "Constant {$name} value '{$value}' should start with 'commerce.'"
            );

            // No spaces in event names
            $this->assertStringNotContainsString(
                ' ',
                $value,
                "Constant {$name} value '{$value}' should not contain spaces"
            );

            // Only lowercase, dots, and underscores
            $this->assertMatchesRegularExpression(
                '/^[a-z][a-z0-9._]*$/',
                $value,
                "Constant {$name} value '{$value}' should only contain lowercase letters, numbers, dots, and underscores"
            );
        }
    }

    public function test_order_events_are_defined(): void
    {
        $this->assertEquals('commerce.order.created', CommerceEvents::ORDER_CREATED);
        $this->assertEquals('commerce.order.updated', CommerceEvents::ORDER_UPDATED);
        $this->assertEquals('commerce.order.cancelled', CommerceEvents::ORDER_CANCELLED);
        $this->assertEquals('commerce.order.status_changed', CommerceEvents::ORDER_STATUS_CHANGED);
    }

    public function test_payment_events_are_defined(): void
    {
        $this->assertEquals('commerce.payment.initiated', CommerceEvents::PAYMENT_INITIATED);
        $this->assertEquals('commerce.payment.paid', CommerceEvents::PAYMENT_PAID);
        $this->assertEquals('commerce.payment.failed', CommerceEvents::PAYMENT_FAILED);
        $this->assertEquals('commerce.payment.refunded', CommerceEvents::PAYMENT_REFUNDED);
    }

    public function test_product_events_are_defined(): void
    {
        $this->assertEquals('commerce.product.created', CommerceEvents::PRODUCT_CREATED);
        $this->assertEquals('commerce.product.updated', CommerceEvents::PRODUCT_UPDATED);
        $this->assertEquals('commerce.product.deleted', CommerceEvents::PRODUCT_DELETED);
        $this->assertEquals('commerce.product.low_stock', CommerceEvents::PRODUCT_LOW_STOCK);
        $this->assertEquals('commerce.product.out_of_stock', CommerceEvents::PRODUCT_OUT_OF_STOCK);
    }

    public function test_cart_events_are_defined(): void
    {
        $this->assertEquals('commerce.cart.item_added', CommerceEvents::CART_ITEM_ADDED);
        $this->assertEquals('commerce.cart.item_removed', CommerceEvents::CART_ITEM_REMOVED);
        $this->assertEquals('commerce.cart.item_updated', CommerceEvents::CART_ITEM_UPDATED);
        $this->assertEquals('commerce.cart.cleared', CommerceEvents::CART_CLEARED);
    }

    public function test_customer_events_are_defined(): void
    {
        $this->assertEquals('commerce.customer.registered', CommerceEvents::CUSTOMER_REGISTERED);
        $this->assertEquals('commerce.customer.updated', CommerceEvents::CUSTOMER_UPDATED);
    }

    public function test_checkout_events_are_defined(): void
    {
        $this->assertEquals('commerce.checkout.started', CommerceEvents::CHECKOUT_STARTED);
        $this->assertEquals('commerce.checkout.completed', CommerceEvents::CHECKOUT_COMPLETED);
        $this->assertEquals('commerce.checkout.abandoned', CommerceEvents::CHECKOUT_ABANDONED);
    }

    public function test_webhook_events_are_defined(): void
    {
        $this->assertEquals('commerce.webhook.payment.received', CommerceEvents::WEBHOOK_PAYMENT_RECEIVED);
    }

    public function test_filter_events_are_defined(): void
    {
        $this->assertEquals('commerce.filter.product_price', CommerceEvents::FILTER_PRODUCT_PRICE);
        $this->assertEquals('commerce.filter.cart_total', CommerceEvents::FILTER_CART_TOTAL);
        $this->assertEquals('commerce.filter.shipping_rates', CommerceEvents::FILTER_SHIPPING_RATES);
        $this->assertEquals('commerce.filter.tax_calculation', CommerceEvents::FILTER_TAX_CALCULATION);
    }

    public function test_no_duplicate_event_values(): void
    {
        $reflection = new ReflectionClass(CommerceEvents::class);
        $constants = $reflection->getConstants();
        $values = array_values($constants);
        $uniqueValues = array_unique($values);

        $this->assertCount(
            count($values),
            $uniqueValues,
            'There are duplicate event values: ' . implode(', ', array_diff_assoc($values, $uniqueValues))
        );
    }

    public function test_minimum_number_of_events_defined(): void
    {
        $reflection = new ReflectionClass(CommerceEvents::class);
        $constants = $reflection->getConstants();

        // Should have at least 30 events for comprehensive commerce coverage
        $this->assertGreaterThanOrEqual(
            30,
            count($constants),
            'CommerceEvents should define at least 30 event constants for comprehensive coverage'
        );
    }
}
