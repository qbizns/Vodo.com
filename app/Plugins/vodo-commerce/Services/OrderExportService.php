<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Store;

class OrderExportService
{
    /**
     * Export orders to CSV format.
     */
    public function exportOrders(Store $store, array $filters = [], string $format = 'csv'): string
    {
        $orders = $this->getExportableOrders($store, $filters);

        if ($format === 'csv') {
            return $this->exportToCsv($orders);
        }

        throw new \InvalidArgumentException("Unsupported export format: {$format}");
    }

    /**
     * Export a single order.
     */
    public function exportOrder(Order $order, string $format = 'csv'): string
    {
        $orders = collect([$order]);

        if ($format === 'csv') {
            return $this->exportToCsv($orders);
        }

        throw new \InvalidArgumentException("Unsupported export format: {$format}");
    }

    /**
     * Mark an order as exported.
     */
    public function markAsExported(Order $order): void
    {
        $order->export();

        do_action('commerce.order.exported', $order);
    }

    /**
     * Get exportable orders based on filters.
     */
    public function getExportableOrders(Store $store, array $filters): Collection
    {
        $query = Order::where('store_id', $store->id)
            ->with(['customer', 'items']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('placed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('placed_at', '<=', $filters['date_to']);
        }

        if (isset($filters['not_exported']) && $filters['not_exported']) {
            $query->where('is_exported', false);
        }

        return $query->orderBy('placed_at', 'desc')->get();
    }

    /**
     * Export orders to CSV.
     */
    protected function exportToCsv(Collection $orders): string
    {
        $filename = 'orders-export-' . now()->format('Y-m-d-His') . '.csv';
        $path = 'exports/' . $filename;

        $handle = fopen(Storage::path($path), 'w');

        // Write headers
        fputcsv($handle, [
            'Order Number',
            'Date',
            'Customer Email',
            'Customer Name',
            'Status',
            'Payment Status',
            'Fulfillment Status',
            'Items Count',
            'Subtotal',
            'Discount',
            'Shipping',
            'Tax',
            'Total',
            'Refund Total',
            'Currency',
            'Payment Method',
            'Shipping Method',
            'Notes',
        ]);

        // Write data
        foreach ($orders as $order) {
            fputcsv($handle, [
                $order->order_number,
                $order->placed_at?->format('Y-m-d H:i:s'),
                $order->customer_email,
                $order->customer ? "{$order->customer->first_name} {$order->customer->last_name}" : '',
                $order->status,
                $order->payment_status,
                $order->fulfillment_status,
                $order->getItemCount(),
                $order->subtotal,
                $order->discount_total,
                $order->shipping_total,
                $order->tax_total,
                $order->total,
                $order->refund_total,
                $order->currency,
                $order->payment_method,
                $order->shipping_method,
                $order->notes,
            ]);
        }

        fclose($handle);

        return $path;
    }

    /**
     * Bulk export and mark orders as exported.
     */
    public function bulkExportAndMark(Store $store, array $orderIds): string
    {
        $orders = Order::where('store_id', $store->id)
            ->whereIn('id', $orderIds)
            ->with(['customer', 'items'])
            ->get();

        $path = $this->exportToCsv($orders);

        // Mark all as exported
        foreach ($orders as $order) {
            $this->markAsExported($order);
        }

        return $path;
    }
}
