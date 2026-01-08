<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Resources\TransactionResource;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Transaction;
use VodoCommerce\Services\TransactionService;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * Get all transactions with filtering
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $store = $this->getCurrentStore();

        $filters = [
            'status' => request()->input('status'),
            'type' => request()->input('type'),
            'order_id' => request()->input('order_id'),
            'customer_id' => request()->input('customer_id'),
            'payment_method_id' => request()->input('payment_method_id'),
            'currency' => request()->input('currency'),
            'start_date' => request()->input('start_date'),
            'end_date' => request()->input('end_date'),
        ];

        // Remove null filters
        $filters = array_filter($filters, fn($value) => $value !== null);

        $transactions = $this->transactionService->getAll($store->id, $filters);

        // Get statistics for the filtered results
        $stats = [
            'total_count' => $transactions->count(),
            'total_amount' => $transactions->where('type', Transaction::TYPE_PAYMENT)->sum('amount'),
            'total_fees' => $transactions->where('type', Transaction::TYPE_PAYMENT)->sum('fee_amount'),
            'total_net' => $transactions->where('type', Transaction::TYPE_PAYMENT)->sum('net_amount'),
            'completed_count' => $transactions->where('status', Transaction::STATUS_COMPLETED)->count(),
            'pending_count' => $transactions->where('status', Transaction::STATUS_PENDING)->count(),
            'failed_count' => $transactions->where('status', Transaction::STATUS_FAILED)->count(),
            'refund_count' => $transactions->where('type', Transaction::TYPE_REFUND)->count(),
        ];

        // Pagination
        $perPage = (int) request()->input('per_page', 50);
        $page = (int) request()->input('page', 1);
        $paginatedTransactions = $transactions->forPage($page, $perPage);

        return $this->successResponse(
            TransactionResource::collection($paginatedTransactions),
            [
                'statistics' => $stats,
                'filters' => $filters,
                'pagination' => [
                    'total' => $transactions->count(),
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($transactions->count() / $perPage),
                ],
            ]
        );
    }

    /**
     * Get transaction details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $transaction = Transaction::where('store_id', $store->id)
            ->with(['paymentMethod', 'order', 'customer', 'refunds'])
            ->findOrFail($id);

        return $this->successResponse(
            new TransactionResource($transaction)
        );
    }

    /**
     * Update transaction (limited fields)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function update(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $transaction = Transaction::where('store_id', $store->id)->findOrFail($id);

        // Only allow updating metadata and notes
        $updates = [];

        if (request()->has('metadata')) {
            $updates['metadata'] = request()->input('metadata');
        }

        if (request()->has('notes')) {
            $updates['notes'] = request()->input('notes');
        }

        if (empty($updates)) {
            return $this->errorResponse('No valid fields to update', 422);
        }

        $transaction->update($updates);

        return $this->successResponse(
            new TransactionResource($transaction->fresh()),
            null,
            'Transaction updated successfully'
        );
    }

    /**
     * Get transaction statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $store = $this->getCurrentStore();

        $currency = request()->input('currency');
        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');

        $dateRange = null;
        if ($startDate && $endDate) {
            $dateRange = ['start' => $startDate, 'end' => $endDate];
        }

        $stats = $this->transactionService->getStatistics($store->id, $currency, $dateRange);
        $refundStats = $this->transactionService->getRefundStatistics($store->id, $currency);

        return $this->successResponse([
            'payment_statistics' => $stats,
            'refund_statistics' => $refundStats,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'currency' => $currency,
            ],
        ]);
    }

    /**
     * Get revenue by payment method
     *
     * @return JsonResponse
     */
    public function revenueByPaymentMethod(): JsonResponse
    {
        $store = $this->getCurrentStore();

        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');

        $dateRange = null;
        if ($startDate && $endDate) {
            $dateRange = ['start' => $startDate, 'end' => $endDate];
        }

        $revenue = $this->transactionService->getRevenueByPaymentMethod($store->id, $dateRange);

        return $this->successResponse([
            'revenue_by_payment_method' => $revenue,
            'total_payment_methods' => $revenue->count(),
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Process a pending transaction
     *
     * @param int $id
     * @return JsonResponse
     */
    public function process(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $transaction = Transaction::where('store_id', $store->id)->findOrFail($id);

        if (!$transaction->isPending() && !$transaction->isProcessing()) {
            return $this->errorResponse('Only pending or processing transactions can be processed', 422);
        }

        $externalId = request()->input('external_id');

        $transaction = $this->transactionService->processTransaction($transaction, $externalId);

        return $this->successResponse(
            new TransactionResource($transaction),
            null,
            'Transaction processed successfully'
        );
    }

    /**
     * Fail a transaction
     *
     * @param int $id
     * @return JsonResponse
     */
    public function fail(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $transaction = Transaction::where('store_id', $store->id)->findOrFail($id);

        if ($transaction->isCompleted()) {
            return $this->errorResponse('Cannot fail a completed transaction', 422);
        }

        $reason = request()->input('reason', 'Transaction failed');
        $code = request()->input('code');

        $transaction = $this->transactionService->failTransaction($transaction, $reason, $code);

        return $this->successResponse(
            new TransactionResource($transaction),
            null,
            'Transaction marked as failed'
        );
    }

    /**
     * Create a refund
     *
     * @param int $id
     * @return JsonResponse
     */
    public function createRefund(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $transaction = Transaction::where('store_id', $store->id)->findOrFail($id);

        $amount = (float) request()->input('amount');
        $reason = request()->input('reason');

        if ($amount <= 0) {
            return $this->errorResponse('Refund amount must be greater than zero', 422);
        }

        try {
            $refund = $this->transactionService->createRefund($transaction, $amount, $reason);

            return $this->successResponse(
                new TransactionResource($refund),
                null,
                'Refund created successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Cancel a transaction
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $transaction = Transaction::where('store_id', $store->id)->findOrFail($id);

        try {
            $transaction = $this->transactionService->cancelTransaction($transaction);

            return $this->successResponse(
                new TransactionResource($transaction),
                null,
                'Transaction cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
