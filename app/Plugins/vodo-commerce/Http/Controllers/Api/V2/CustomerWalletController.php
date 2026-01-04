<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\WalletDepositRequest;
use VodoCommerce\Http\Requests\WalletWithdrawRequest;
use VodoCommerce\Http\Resources\CustomerWalletResource;
use VodoCommerce\Http\Resources\WalletTransactionResource;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\CustomerWalletService;

class CustomerWalletController extends Controller
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function deposit(WalletDepositRequest $request, int $customerId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($customerId);

        $service = new CustomerWalletService();
        $transaction = $service->deposit(
            $customer,
            $request->input('amount'),
            $request->input('description'),
            $request->input('reference')
        );

        return $this->successResponse(
            [
                'transaction' => new WalletTransactionResource($transaction),
                'wallet' => new CustomerWalletResource($customer->wallet),
            ],
            null,
            'Deposit successful',
            201
        );
    }

    public function withdraw(WalletWithdrawRequest $request, int $customerId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($customerId);

        try {
            $service = new CustomerWalletService();
            $transaction = $service->withdraw(
                $customer,
                $request->input('amount'),
                $request->input('description'),
                $request->input('reference')
            );

            return $this->successResponse(
                [
                    'transaction' => new WalletTransactionResource($transaction),
                    'wallet' => new CustomerWalletResource($customer->wallet),
                ],
                null,
                'Withdrawal successful',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function transactions(Request $request, int $customerId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($customerId);

        $wallet = $customer->wallet;
        if (!$wallet) {
            return $this->successResponse([]);
        }

        $query = $wallet->transactions();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $transactions = $query->latest()->paginate($perPage);

        return $this->successResponse(
            WalletTransactionResource::collection($transactions),
            $this->getPaginationMeta($transactions)
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

    protected function getPaginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
