<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\CreateTaxZoneRequest;
use VodoCommerce\Http\Requests\UpdateTaxZoneRequest;
use VodoCommerce\Http\Resources\TaxZoneResource;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxZone;
use VodoCommerce\Services\TaxZoneService;

class TaxZoneController extends Controller
{
    public function __construct(
        protected TaxZoneService $taxZoneService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::first();
    }

    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zones = TaxZone::where('store_id', $store->id)
            ->with(['locations', 'rates'])
            ->when($request->input('active_only'), fn ($q) => $q->active())
            ->ordered()
            ->paginate($request->input('per_page', 15));

        return $this->successResponse(
            TaxZoneResource::collection($zones),
            [
                'total' => $zones->total(),
                'per_page' => $zones->perPage(),
                'current_page' => $zones->currentPage(),
                'last_page' => $zones->lastPage(),
            ]
        );
    }

    public function store(CreateTaxZoneRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = $this->taxZoneService->createZone($store, $request->validated());

        return $this->successResponse(
            new TaxZoneResource($zone),
            null,
            'Tax zone created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = TaxZone::where('store_id', $store->id)
            ->with(['locations', 'rates'])
            ->findOrFail($id);

        return $this->successResponse(new TaxZoneResource($zone));
    }

    public function update(UpdateTaxZoneRequest $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = TaxZone::where('store_id', $store->id)->findOrFail($id);

        $updatedZone = $this->taxZoneService->updateZone($zone, $request->validated());

        return $this->successResponse(
            new TaxZoneResource($updatedZone),
            null,
            'Tax zone updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = TaxZone::where('store_id', $store->id)->findOrFail($id);

        $this->taxZoneService->deleteZone($zone);

        return $this->successResponse(
            null,
            null,
            'Tax zone deleted successfully'
        );
    }

    public function activate(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = TaxZone::where('store_id', $store->id)->findOrFail($id);

        $zone->activate();

        return $this->successResponse(
            new TaxZoneResource($zone->fresh()),
            null,
            'Tax zone activated successfully'
        );
    }

    public function deactivate(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $zone = TaxZone::where('store_id', $store->id)->findOrFail($id);

        $zone->deactivate();

        return $this->successResponse(
            new TaxZoneResource($zone->fresh()),
            null,
            'Tax zone deactivated successfully'
        );
    }
}
