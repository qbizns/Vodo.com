<?php

declare(strict_types=1);

namespace VodoCommerce\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use VodoCommerce\Api\WebhookEventCatalog;

/**
 * Tests for the Webhook Event Catalog.
 */
class WebhookEventCatalogTest extends TestCase
{
    #[Test]
    public function it_returns_all_events_organized_by_category(): void
    {
        $events = WebhookEventCatalog::all();

        $this->assertIsArray($events);
        $this->assertArrayHasKey('orders', $events);
        $this->assertArrayHasKey('products', $events);
        $this->assertArrayHasKey('customers', $events);
        $this->assertArrayHasKey('cart', $events);
        $this->assertArrayHasKey('checkout', $events);
        $this->assertArrayHasKey('payments', $events);
        $this->assertArrayHasKey('fulfillment', $events);
        $this->assertArrayHasKey('inventory', $events);
        $this->assertArrayHasKey('discounts', $events);
        $this->assertArrayHasKey('store', $events);

        // Should have 10 categories
        $this->assertCount(10, $events);
    }

    #[Test]
    public function it_returns_flat_events_list(): void
    {
        $events = WebhookEventCatalog::flat();

        $this->assertIsArray($events);
        $this->assertNotEmpty($events);

        // Check that each event has a category assigned
        foreach ($events as $name => $event) {
            $this->assertArrayHasKey('category', $event);
            $this->assertArrayHasKey('name', $event);
            $this->assertArrayHasKey('description', $event);
            $this->assertArrayHasKey('trigger', $event);
            $this->assertArrayHasKey('payload', $event);
        }
    }

    #[Test]
    public function it_returns_event_names(): void
    {
        $names = WebhookEventCatalog::names();

        $this->assertIsArray($names);
        $this->assertNotEmpty($names);

        // Check known events exist
        $this->assertContains('order.created', $names);
        $this->assertContains('product.created', $names);
        $this->assertContains('customer.created', $names);
        $this->assertContains('cart.item_added', $names);
        $this->assertContains('checkout.completed', $names);
        $this->assertContains('payment.paid', $names);
        $this->assertContains('fulfillment.shipped', $names);
        $this->assertContains('inventory.low_stock', $names);
        $this->assertContains('discount.used', $names);
        $this->assertContains('store.created', $names);
    }

    #[Test]
    public function it_returns_events_for_specific_category(): void
    {
        $orderEvents = WebhookEventCatalog::forCategory('orders');

        $this->assertIsArray($orderEvents);
        $this->assertNotEmpty($orderEvents);
        $this->assertArrayHasKey('order.created', $orderEvents);
        $this->assertArrayHasKey('order.updated', $orderEvents);
        $this->assertArrayHasKey('order.cancelled', $orderEvents);
    }

    #[Test]
    public function it_returns_empty_array_for_unknown_category(): void
    {
        $events = WebhookEventCatalog::forCategory('nonexistent');

        $this->assertIsArray($events);
        $this->assertEmpty($events);
    }

    #[Test]
    public function it_gets_specific_event_by_name(): void
    {
        $event = WebhookEventCatalog::get('order.created');

        $this->assertIsArray($event);
        $this->assertEquals('order.created', $event['name']);
        $this->assertArrayHasKey('description', $event);
        $this->assertArrayHasKey('trigger', $event);
        $this->assertArrayHasKey('payload', $event);
        $this->assertArrayHasKey('example', $event);

        // Verify payload structure
        $this->assertArrayHasKey('order_id', $event['payload']);
        $this->assertArrayHasKey('store_id', $event['payload']);
        $this->assertArrayHasKey('customer_email', $event['payload']);
        $this->assertArrayHasKey('total', $event['payload']);
    }

    #[Test]
    public function it_returns_null_for_unknown_event(): void
    {
        $event = WebhookEventCatalog::get('nonexistent.event');

        $this->assertNull($event);
    }

    #[Test]
    public function it_validates_event_names(): void
    {
        $this->assertTrue(WebhookEventCatalog::isValid('order.created'));
        $this->assertTrue(WebhookEventCatalog::isValid('product.updated'));
        $this->assertTrue(WebhookEventCatalog::isValid('checkout.completed'));

        $this->assertFalse(WebhookEventCatalog::isValid('nonexistent.event'));
        $this->assertFalse(WebhookEventCatalog::isValid('invalid'));
        $this->assertFalse(WebhookEventCatalog::isValid(''));
    }

    #[Test]
    public function it_generates_markdown_documentation(): void
    {
        $markdown = WebhookEventCatalog::toMarkdown();

        $this->assertIsString($markdown);
        $this->assertNotEmpty($markdown);

        // Check for expected content
        $this->assertStringContainsString('# Commerce Webhook Events', $markdown);
        $this->assertStringContainsString('## Table of Contents', $markdown);
        $this->assertStringContainsString('## Orders Events', $markdown);
        $this->assertStringContainsString('## Products Events', $markdown);
        $this->assertStringContainsString('### `order.created`', $markdown);
        $this->assertStringContainsString('| Field | Type | Description |', $markdown);
        $this->assertStringContainsString('**Trigger:**', $markdown);
        $this->assertStringContainsString('**Payload:**', $markdown);
        $this->assertStringContainsString('**Example:**', $markdown);
        $this->assertStringContainsString('```json', $markdown);
    }

    #[Test]
    public function it_includes_payload_field_types(): void
    {
        $event = WebhookEventCatalog::get('order.created');

        $this->assertIsArray($event['payload']);

        // Check field structure
        $orderIdField = $event['payload']['order_id'];
        $this->assertEquals('integer', $orderIdField['type']);
        $this->assertArrayHasKey('description', $orderIdField);

        // Check nullable field
        $customerIdField = $event['payload']['customer_id'];
        $this->assertTrue($customerIdField['nullable']);
    }

    #[Test]
    public function all_events_have_required_fields(): void
    {
        $events = WebhookEventCatalog::flat();

        foreach ($events as $name => $event) {
            $this->assertArrayHasKey('name', $event, "Event {$name} missing 'name' field");
            $this->assertArrayHasKey('description', $event, "Event {$name} missing 'description' field");
            $this->assertArrayHasKey('trigger', $event, "Event {$name} missing 'trigger' field");
            $this->assertArrayHasKey('payload', $event, "Event {$name} missing 'payload' field");
            $this->assertIsArray($event['payload'], "Event {$name} payload is not an array");

            // Each payload field should have type and description
            foreach ($event['payload'] as $field => $spec) {
                $this->assertArrayHasKey('type', $spec, "Event {$name} field {$field} missing 'type'");
                $this->assertArrayHasKey('description', $spec, "Event {$name} field {$field} missing 'description'");
            }
        }
    }

    #[Test]
    public function event_names_follow_naming_convention(): void
    {
        $names = WebhookEventCatalog::names();

        foreach ($names as $name) {
            // Event names should be in format: category.action or category.action_detail
            $this->assertMatchesRegularExpression(
                '/^[a-z]+\.[a-z_]+$/',
                $name,
                "Event name '{$name}' does not follow naming convention"
            );
        }
    }
}
