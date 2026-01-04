<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\AdjustLoyaltyPointsRequest;
use VodoCommerce\Http\Resources\LoyaltyPointResource;
use VodoCommerce\Http\Resources\LoyaltyPointTransactionResource;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\LoyaltyPointService;

class LoyaltyPointController extends Controller
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function show(int $customerId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($customerId);

        $loyaltyPoints = $customer->loyaltyPoints;
        if (!$loyaltyPoints) {
            $loyaltyPoints = $customer->getLoyaltyPointsOrCreate();
        }

        return $this->successResponse(new LoyaltyPointResource($loyaltyPoints));
    }

    public function adjust(AdjustLoyaltyPointsRequest $request, int $customerId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($customerId);

        $service = new LoyaltyPointService();
        $transaction = $service->adjustPoints(
            $customer,
            $request->input('points'),
            $request->input('description')
        );

        return $this->successResponse(
            [
                'transaction' => new LoyaltyPointTransactionResource($transaction),
                'loyalty_points' => new LoyaltyPointResource($customer->loyaltyPoints),
            ],
            null,
            'Loyalty points adjusted successfully',
            201
        );
    }

    public function transactions(Request $request, int $customerId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($customerId);

        $loyaltyPoints = $customer->loyaltyPoints;
        if (!$loyaltyPoints) {
            return $this->successResponse([]);
        }

        $query = $loyaltyPoints->transactions();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $transactions = $query->latest()->paginate($perPage);

        return $this->successResponse(
            LoyaltyPointTransactionResource::collection($transactions),
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
