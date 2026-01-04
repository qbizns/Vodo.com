<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\StoreAffiliateRequest;
use VodoCommerce\Http\Requests\StoreAffiliateLinkRequest;
use VodoCommerce\Http\Requests\UpdateAffiliateRequest;
use VodoCommerce\Http\Resources\AffiliateLinkResource;
use VodoCommerce\Http\Resources\AffiliateResource;
use VodoCommerce\Models\Affiliate;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\AffiliateService;

class AffiliateController extends Controller
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = Affiliate::where('store_id', $store->id)->with('customer');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('code', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        if ($request->has('approved')) {
            if ($request->input('approved')) {
                $query->whereNotNull('approved_at');
            } else {
                $query->whereNull('approved_at');
            }
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $affiliates = $query->latest()->paginate($perPage);

        return $this->successResponse(
            AffiliateResource::collection($affiliates),
            $this->getPaginationMeta($affiliates)
        );
    }

    public function store(StoreAffiliateRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($request->input('customer_id'));

        $service = new AffiliateService($store);
        $affiliate = $service->create($customer, $request->validated());

        return $this->successResponse(
            new AffiliateResource($affiliate->load('customer')),
            null,
            'Affiliate created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $affiliate = Affiliate::where('store_id', $store->id)
            ->with('customer')
            ->findOrFail($id);

        return $this->successResponse(new AffiliateResource($affiliate));
    }

    public function update(UpdateAffiliateRequest $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();
        $service = new AffiliateService($store);

        $affiliate = Affiliate::where('store_id', $store->id)->findOrFail($id);
        $affiliate = $service->update($affiliate, $request->validated());

        return $this->successResponse(
            new AffiliateResource($affiliate->load('customer')),
            null,
            'Affiliate updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $affiliate = Affiliate::where('store_id', $store->id)->findOrFail($id);
        $affiliate->delete();

        return $this->successResponse(
            null,
            null,
            'Affiliate deleted successfully'
        );
    }

    public function links(int $affiliateId): JsonResponse
    {
        $store = $this->getCurrentStore();

        $affiliate = Affiliate::where('store_id', $store->id)->findOrFail($affiliateId);
        $links = $affiliate->links;

        return $this->successResponse(AffiliateLinkResource::collection($links));
    }

    public function storeLink(StoreAffiliateLinkRequest $request, int $affiliateId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $service = new AffiliateService($store);

        $affiliate = Affiliate::where('store_id', $store->id)->findOrFail($affiliateId);
        $link = $service->createLink($affiliate, $request->validated());

        return $this->successResponse(
            new AffiliateLinkResource($link),
            null,
            'Affiliate link created successfully',
            201
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
