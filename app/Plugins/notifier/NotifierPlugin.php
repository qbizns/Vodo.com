<?php

declare(strict_types=1);

namespace App\Plugins\Notifier;

use App\Plugins\Notifier\Mail\OrderConfirmationMail;
use App\Services\Plugins\BasePlugin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use VodoCommerce\Events\CommerceEvents;

class NotifierPlugin extends BasePlugin
{
    // Hook names for extensibility
    public const FILTER_ORDER_EMAIL_DATA = 'notifier.order_email_data';
    public const FILTER_ORDER_EMAIL_RECIPIENTS = 'notifier.order_email_recipients';
    public const ACTION_BEFORE_ORDER_EMAIL = 'notifier.before_order_email';
    public const ACTION_AFTER_ORDER_EMAIL = 'notifier.after_order_email';

    public function register(): void
    {
        // Register views
        app('view')->addNamespace('notifier', $this->basePath . '/resources/views');
    }

    public function boot(): void
    {
        // Listen to order created event
        $this->addAction(CommerceEvents::ORDER_CREATED, function ($order, $store = null) {
            $this->sendOrderConfirmation($order, $store);
        });

        Log::debug('Notifier Plugin: Booted and listening for events');
    }

    protected function sendOrderConfirmation($order, $store = null): void
    {
        try {
            $email = $order->customer_email;
            
            if (empty($email)) {
                Log::warning('Notifier: No email for order', ['order_id' => $order->id]);
                return;
            }

            // Build email data - can be filtered by other plugins
            $emailData = $this->buildOrderEmailData($order, $store);
            $emailData = $this->hooks->applyFilters(self::FILTER_ORDER_EMAIL_DATA, $emailData, $order, $store);

            // Get recipients - can be filtered (e.g., add admin CC)
            $recipients = ['to' => $email, 'cc' => [], 'bcc' => []];
            $recipients = $this->hooks->applyFilters(self::FILTER_ORDER_EMAIL_RECIPIENTS, $recipients, $order, $store);

            // Before email hook
            $this->hooks->doAction(self::ACTION_BEFORE_ORDER_EMAIL, $order, $emailData);

            // Build and send email
            $mailable = new OrderConfirmationMail($emailData);
            
            $mailBuilder = Mail::to($recipients['to']);
            if (!empty($recipients['cc'])) {
                $mailBuilder->cc($recipients['cc']);
            }
            if (!empty($recipients['bcc'])) {
                $mailBuilder->bcc($recipients['bcc']);
            }
            
            $mailBuilder->queue($mailable);

            // After email hook
            $this->hooks->doAction(self::ACTION_AFTER_ORDER_EMAIL, $order, $emailData);

            Log::info('Notifier: Order confirmation queued', [
                'order_id' => $order->id,
                'email' => $email,
            ]);
        } catch (\Exception $e) {
            Log::error('Notifier: Failed to queue order confirmation', [
                'order_id' => $order->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the email data array from order.
     * All data is converted to primitives for queue serialization.
     */
    protected function buildOrderEmailData($order, $store = null): array
    {
        // Convert items to array (not Eloquent collection)
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ];
        }

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->shipping_address['first_name'] ?? 'Customer',
            'customer_email' => $order->customer_email,
            'currency' => $order->currency,
            'items' => $items,
            'subtotal' => (float) $order->subtotal,
            'discount_total' => (float) $order->discount_total,
            'discount_codes' => is_array($order->discount_codes) ? $order->discount_codes : [],
            'shipping_total' => (float) $order->shipping_total,
            'tax_total' => (float) $order->tax_total,
            'total' => (float) $order->total,
            'shipping_address' => is_array($order->shipping_address) ? $order->shipping_address : [],
            'billing_address' => is_array($order->billing_address) ? $order->billing_address : [],
            'shipping_method' => $order->shipping_method,
            'payment_method' => $order->payment_method,
            'notes' => $order->notes,
            'placed_at' => ($order->placed_at ?? $order->created_at)?->toIso8601String(),
            'store_name' => $store?->name ?? 'Store',
            'store_email' => $store?->settings['email'] ?? null,
        ];
    }
}

