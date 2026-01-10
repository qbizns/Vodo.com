<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\SubscriptionInvoiceResource;
use VodoCommerce\Models\SubscriptionInvoice;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\BillingService;

class SubscriptionInvoiceController
{
    public function __construct(
        protected BillingService $billingService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all subscription invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = SubscriptionInvoice::where('store_id', $store->id);

        // Filter by subscription
        if ($request->filled('subscription_id')) {
            $query->where('subscription_id', $request->input('subscription_id'));
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter paid invoices
        if ($request->boolean('paid_only')) {
            $query->paid();
        }

        // Filter failed invoices
        if ($request->boolean('failed_only')) {
            $query->failed();
        }

        // Filter invoices due for retry
        if ($request->boolean('due_for_retry')) {
            $query->dueForRetry();
        }

        // Search by invoice number
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->input('search') . '%');
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $invoices = $query->paginate($perPage);

        return $this->successResponse(
            SubscriptionInvoiceResource::collection($invoices),
            [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ]
        );
    }

    /**
     * Get a single invoice.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = SubscriptionInvoice::where('store_id', $store->id);

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $invoice = $query->findOrFail($id);

        return $this->successResponse(
            new SubscriptionInvoiceResource($invoice)
        );
    }

    /**
     * Retry a failed invoice payment.
     */
    public function retry(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $invoice = SubscriptionInvoice::where('store_id', $store->id)->findOrFail($id);

        if (!$invoice->canRetry()) {
            return $this->errorResponse(
                'Invoice cannot be retried. Status: ' . $invoice->status,
                400
            );
        }

        try {
            $transaction = $this->billingService->chargeInvoice($invoice);

            return $this->successResponse(
                new SubscriptionInvoiceResource($invoice->fresh(['subscription', 'transaction'])),
                null,
                'Invoice payment retried successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Payment retry failed: ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Void an invoice.
     */
    public function void(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $invoice = SubscriptionInvoice::where('store_id', $store->id)->findOrFail($id);

        if ($invoice->isPaid()) {
            return $this->errorResponse(
                'Cannot void a paid invoice. Please refund instead.',
                400
            );
        }

        $invoice->update(['status' => SubscriptionInvoice::STATUS_VOIDED]);

        return $this->successResponse(
            new SubscriptionInvoiceResource($invoice),
            null,
            'Invoice voided successfully'
        );
    }

    /**
     * Refund an invoice.
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $invoice = SubscriptionInvoice::where('store_id', $store->id)->findOrFail($id);

        if (!$invoice->isPaid()) {
            return $this->errorResponse(
                'Cannot refund an unpaid invoice',
                400
            );
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->amount_paid,
        ]);

        $invoice->refund($data['amount']);

        return $this->successResponse(
            new SubscriptionInvoiceResource($invoice->fresh()),
            null,
            'Invoice refunded successfully'
        );
    }

    /**
     * Retry all failed invoice payments.
     */
    public function retryAll(): JsonResponse
    {
        $results = $this->billingService->retryFailedPayments();

        return $this->successResponse(
            $results,
            null,
            "Retried {$results['processed']} invoices: {$results['succeeded']} succeeded, {$results['failed']} failed"
        );
    }

    protected function successResponse(mixed $data = null, ?array $pagination = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'status' => $status,
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
