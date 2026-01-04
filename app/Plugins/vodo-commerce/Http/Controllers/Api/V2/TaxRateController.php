<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\CreateTaxExemptionRequest;
use VodoCommerce\Http\Requests\CreateTaxRateRequest;
use VodoCommerce\Http\Resources\TaxExemptionResource;
use VodoCommerce\Http\Resources\TaxRateResource;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxExemption;
use VodoCommerce\Models\TaxRate;
use VodoCommerce\Models\TaxZone;
use VodoCommerce\Services\TaxCalculationService;

class TaxRateController extends Controller
{
    public function __construct(
        protected TaxCalculationService $taxCalculationService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::first();
    }

    // =========================================================================
    // Tax Rates
    // =========================================================================

    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = TaxRate::query()
            ->whereHas('taxZone', fn ($q) => $q->where('store_id', $store->id))
            ->with(['taxZone', 'category']);

        if ($request->input('zone_id')) {
            $query->where('tax_zone_id', $request->input('zone_id'));
        }

        if ($request->input('active_only')) {
            $query->active();
        }

        $rates = $query->ordered()->paginate($request->input('per_page', 15));

        return $this->successResponse(
            TaxRateResource::collection($rates),
            [
                'total' => $rates->total(),
                'per_page' => $rates->perPage(),
                'current_page' => $rates->currentPage(),
                'last_page' => $rates->lastPage(),
            ]
        );
    }

    public function store(CreateTaxRateRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        // Verify tax zone belongs to store
        $zone = TaxZone::where('store_id', $store->id)
            ->findOrFail($request->input('tax_zone_id'));

        $rate = TaxRate::create($request->validated());

        do_action('commerce.tax_rate.created', $rate);

        return $this->successResponse(
            new TaxRateResource($rate->load('taxZone')),
            null,
            'Tax rate created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $rate = TaxRate::whereHas('taxZone', fn ($q) => $q->where('store_id', $store->id))
            ->with(['taxZone', 'category'])
            ->findOrFail($id);

        return $this->successResponse(new TaxRateResource($rate));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $rate = TaxRate::whereHas('taxZone', fn ($q) => $q->where('store_id', $store->id))
            ->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|nullable|string|max:50',
            'rate' => 'sometimes|numeric|min:0',
            'type' => 'sometimes|in:percentage,fixed',
            'compound' => 'sometimes|boolean',
            'shipping_taxable' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'category_id' => 'sometimes|nullable|integer|exists:commerce_categories,id',
        ]);

        $rate->update($request->all());

        do_action('commerce.tax_rate.updated', $rate);

        return $this->successResponse(
            new TaxRateResource($rate->fresh(['taxZone', 'category'])),
            null,
            'Tax rate updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $rate = TaxRate::whereHas('taxZone', fn ($q) => $q->where('store_id', $store->id))
            ->findOrFail($id);

        do_action('commerce.tax_rate.deleting', $rate);

        $rate->delete();

        do_action('commerce.tax_rate.deleted', $id);

        return $this->successResponse(
            null,
            null,
            'Tax rate deleted successfully'
        );
    }

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|array',
            'address.country_code' => 'required|string|size:2',
            'cart_data' => 'required|array',
            'cart_data.subtotal' => 'required|numeric',
            'cart_data.items' => 'sometimes|array',
            'customer_id' => 'sometimes|nullable|integer',
            'customer_group_id' => 'sometimes|nullable|integer',
        ]);

        $store = $this->getCurrentStore();

        $calculation = $this->taxCalculationService->calculateTax(
            $store,
            $request->input('address'),
            $request->input('cart_data'),
            $request->input('customer_id'),
            $request->input('customer_group_id')
        );

        return $this->successResponse([
            'tax_calculation' => $calculation,
            'formatted_breakdown' => $this->taxCalculationService->getTaxBreakdownForDisplay($calculation),
        ]);
    }

    // =========================================================================
    // Tax Exemptions
    // =========================================================================

    public function indexExemptions(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = TaxExemption::where('store_id', $store->id);

        if ($request->input('type')) {
            $query->forType($request->input('type'));
        }

        if ($request->input('active_only')) {
            $query->active();
        }

        if ($request->input('valid_only')) {
            $query->valid();
        }

        $exemptions = $query->orderBy('name')->paginate($request->input('per_page', 15));

        return $this->successResponse(
            TaxExemptionResource::collection($exemptions),
            [
                'total' => $exemptions->total(),
                'per_page' => $exemptions->perPage(),
                'current_page' => $exemptions->currentPage(),
                'last_page' => $exemptions->lastPage(),
            ]
        );
    }

    public function storeExemption(CreateTaxExemptionRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $exemption = TaxExemption::create([
            'store_id' => $store->id,
            ...$request->validated(),
        ]);

        do_action('commerce.tax_exemption.created', $exemption);

        return $this->successResponse(
            new TaxExemptionResource($exemption),
            null,
            'Tax exemption created successfully',
            201
        );
    }

    public function showExemption(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $exemption = TaxExemption::where('store_id', $store->id)->findOrFail($id);

        return $this->successResponse(new TaxExemptionResource($exemption));
    }

    public function destroyExemption(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $exemption = TaxExemption::where('store_id', $store->id)->findOrFail($id);

        do_action('commerce.tax_exemption.deleting', $exemption);

        $exemption->delete();

        do_action('commerce.tax_exemption.deleted', $id);

        return $this->successResponse(
            null,
            null,
            'Tax exemption deleted successfully'
        );
    }
}
