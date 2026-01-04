<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\CreateShippingZoneRequest;
use VodoCommerce\Http\Requests\UpdateShippingZoneRequest;
use VodoCommerce\Http\Resources\ShippingZoneResource;
use VodoCommerce\Models\ShippingZone;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ShippingZoneService;

class ShippingZoneController extends Controller
{
    public function __construct(
        protected ShippingZoneService $shippingZoneService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::first();
    }

    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zones = ShippingZone::where('store_id', $store->id)
            ->with(['locations', 'rates'])
            ->when($request->input('active_only'), fn ($q) => $q->active())
            ->ordered()
            ->paginate($request->input('per_page', 15));

        return $this->successResponse(
            ShippingZoneResource::collection($zones),
            [
                'total' => $zones->total(),
                'per_page' => $zones->perPage(),
                'current_page' => $zones->currentPage(),
                'last_page' => $zones->lastPage(),
            ]
        );
    }

    public function store(CreateShippingZoneRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = $this->shippingZoneService->createZone($store, $request->validated());

        return $this->successResponse(
            new ShippingZoneResource($zone),
            null,
            'Shipping zone created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = ShippingZone::where('store_id', $store->id)
            ->with(['locations', 'rates'])
            ->findOrFail($id);

        return $this->successResponse(new ShippingZoneResource($zone));
    }

    public function update(UpdateShippingZoneRequest $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = ShippingZone::where('store_id', $store->id)->findOrFail($id);

        $updatedZone = $this->shippingZoneService->updateZone($zone, $request->validated());

        return $this->successResponse(
            new ShippingZoneResource($updatedZone),
            null,
            'Shipping zone updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = ShippingZone::where('store_id', $store->id)->findOrFail($id);

        $this->shippingZoneService->deleteZone($zone);

        return $this->successResponse(
            null,
            null,
            'Shipping zone deleted successfully'
        );
    }

    public function activate(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = ShippingZone::where('store_id', $store->id)->findOrFail($id);

        $zone->activate();

        return $this->successResponse(
            new ShippingZoneResource($zone->fresh()),
            null,
            'Shipping zone activated successfully'
        );
    }

    public function deactivate(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = ShippingZone::where('store_id', $store->id)->findOrFail($id);

        $zone->deactivate();

        return $this->successResponse(
            new ShippingZoneResource($zone->fresh()),
            null,
            'Shipping zone deactivated successfully'
        );
    }
}
