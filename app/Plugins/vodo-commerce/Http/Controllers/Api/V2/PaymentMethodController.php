<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Resources\PaymentMethodResource;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\PaymentMethodService;

class PaymentMethodController extends Controller
{
    public function __construct(
        protected PaymentMethodService $paymentMethodService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * Get all payment methods
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $store = $this->getCurrentStore();

        $activeOnly = request()->boolean('active_only', false);
        $paymentMethods = $this->paymentMethodService->getAll($store->id, $activeOnly);

        return $this->successResponse(
            PaymentMethodResource::collection($paymentMethods),
            [
                'total' => $paymentMethods->count(),
                'active_count' => $paymentMethods->where('is_active', true)->count(),
            ]
        );
    }

    /**
     * Get payment method details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $paymentMethod = PaymentMethod::where('store_id', $store->id)
            ->with('transactions')
            ->findOrFail($id);

        return $this->successResponse(
            new PaymentMethodResource($paymentMethod)
        );
    }

    /**
     * Get supported banks for a payment method
     *
     * @param int $id
     * @return JsonResponse
     */
    public function banks(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $paymentMethod = PaymentMethod::where('store_id', $store->id)->findOrFail($id);

        $banks = $this->paymentMethodService->getSupportedBanks($paymentMethod);

        return $this->successResponse([
            'payment_method_id' => $paymentMethod->id,
            'payment_method_name' => $paymentMethod->name,
            'banks' => $banks,
            'total' => count($banks),
        ]);
    }

    /**
     * Get available payment methods for checkout
     *
     * @return JsonResponse
     */
    public function available(): JsonResponse
    {
        $store = $this->getCurrentStore();

        $amount = (float) request()->input('amount', 0);
        $currency = request()->input('currency', $store->currency ?? 'USD');
        $countryCode = request()->input('country_code');

        $paymentMethods = $this->paymentMethodService->getAvailableForCheckout(
            $store->id,
            $amount,
            $currency,
            $countryCode
        );

        return $this->successResponse(
            PaymentMethodResource::collection($paymentMethods),
            [
                'amount' => $amount,
                'currency' => $currency,
                'country_code' => $countryCode,
                'available_count' => $paymentMethods->count(),
            ]
        );
    }

    /**
     * Calculate fees for a payment method
     *
     * @param int $id
     * @return JsonResponse
     */
    public function calculateFees(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $paymentMethod = PaymentMethod::where('store_id', $store->id)->findOrFail($id);

        $amount = (float) request()->input('amount', 0);

        if ($amount <= 0) {
            return $this->errorResponse('Amount must be greater than zero', 422);
        }

        $feeCalculation = $this->paymentMethodService->calculateFees($paymentMethod, $amount);

        return $this->successResponse([
            'payment_method_id' => $paymentMethod->id,
            'payment_method_name' => $paymentMethod->name,
            ...$feeCalculation,
        ]);
    }

    /**
     * Test payment method connection
     *
     * @param int $id
     * @return JsonResponse
     */
    public function testConnection(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $paymentMethod = PaymentMethod::where('store_id', $store->id)->findOrFail($id);

        $result = $this->paymentMethodService->testConnection($paymentMethod);

        if ($result['success']) {
            return $this->successResponse($result, null, $result['message']);
        }

        return $this->errorResponse($result['message'], 422, $result);
    }
}
