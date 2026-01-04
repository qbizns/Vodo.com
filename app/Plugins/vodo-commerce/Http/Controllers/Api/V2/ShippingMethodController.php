<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\CreateShippingMethodRequest;
use VodoCommerce\Http\Requests\CreateShippingRateRequest;
use VodoCommerce\Http\Requests\UpdateShippingMethodRequest;
use VodoCommerce\Http\Resources\ShippingMethodResource;
use VodoCommerce\Http\Resources\ShippingRateResource;
use VodoCommerce\Models\ShippingMethod;
use VodoCommerce\Models\ShippingRate;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ShippingCalculationService;

class ShippingMethodController extends Controller
{
    public function __construct(
        protected ShippingCalculationService $shippingCalculationService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::first();
    }

    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $methods = ShippingMethod::where('store_id', $store->id)
            ->with('rates')
            ->when($request->input('active_only'), fn ($q) => $q->active())
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return $this->successResponse(
            ShippingMethodResource::collection($methods),
            [
                'total' => $methods->total(),
                'per_page' => $methods->perPage(),
                'current_page' => $methods->currentPage(),
                'last_page' => $methods->lastPage(),
            ]
        );
    }

    public function store(CreateShippingMethodRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $method = ShippingMethod::create([
            'store_id' => $store->id,
            ...$request->validated(),
        ]);

        do_action('commerce.shipping_method.created', $method);

        return $this->successResponse(
            new ShippingMethodResource($method),
            null,
            'Shipping method created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $method = ShippingMethod::where('store_id', $store->id)
            ->with('rates')
            ->findOrFail($id);

        return $this->successResponse(new ShippingMethodResource($method));
    }

    public function update(UpdateShippingMethodRequest $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $method = ShippingMethod::where('store_id', $store->id)->findOrFail($id);

        $method->update($request->validated());

        do_action('commerce.shipping_method.updated', $method);

        return $this->successResponse(
            new ShippingMethodResource($method->fresh('rates')),
            null,
            'Shipping method updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $method = ShippingMethod::where('store_id', $store->id)->findOrFail($id);

        do_action('commerce.shipping_method.deleting', $method);

        $method->delete();

        do_action('commerce.shipping_method.deleted', $id);

        return $this->successResponse(
            null,
            null,
            'Shipping method deleted successfully'
        );
    }

    public function activate(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $method = ShippingMethod::where('store_id', $store->id)->findOrFail($id);

        $method->activate();

        return $this->successResponse(
            new ShippingMethodResource($method->fresh()),
            null,
            'Shipping method activated successfully'
        );
    }

    public function deactivate(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $method = ShippingMethod::where('store_id', $store->id)->findOrFail($id);

        $method->deactivate();

        return $this->successResponse(
            new ShippingMethodResource($method->fresh()),
            null,
            'Shipping method deactivated successfully'
        );
    }

    public function addRate(CreateShippingRateRequest $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $method = ShippingMethod::where('store_id', $store->id)->findOrFail($id);

        $rate = ShippingRate::create($request->validated());

        return $this->successResponse(
            new ShippingRateResource($rate),
            null,
            'Shipping rate added successfully',
            201
        );
    }

    public function removeRate(int $methodId, int $rateId): JsonResponse
    {
        $store = $this->getCurrentStore();

        $method = ShippingMethod::where('store_id', $store->id)->findOrFail($methodId);

        $rate = $method->rates()->findOrFail($rateId);

        $rate->delete();

        return $this->successResponse(
            null,
            null,
            'Shipping rate removed successfully'
        );
    }

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|array',
            'address.country_code' => 'required|string|size:2',
            'cart_data' => 'required|array',
            'cart_data.subtotal' => 'required|numeric',
        ]);

        $store = $this->getCurrentStore();

        $options = $this->shippingCalculationService->getAvailableShippingOptions(
            $store,
            $request->input('address'),
            $request->input('cart_data')
        );

        return $this->successResponse([
            'shipping_options' => $options,
            'count' => count($options),
        ]);
    }
}
