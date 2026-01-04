<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\StoreCustomerGroupRequest;
use VodoCommerce\Http\Requests\UpdateCustomerGroupRequest;
use VodoCommerce\Http\Resources\CustomerGroupResource;
use VodoCommerce\Models\CustomerGroup;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\CustomerGroupService;

class CustomerGroupController extends Controller
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = CustomerGroup::where('store_id', $store->id);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $groups = $query->withCount('customers')->latest()->paginate($perPage);

        return $this->successResponse(
            CustomerGroupResource::collection($groups),
            $this->getPaginationMeta($groups)
        );
    }

    public function store(StoreCustomerGroupRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();
        $service = new CustomerGroupService($store);

        $group = $service->create($request->validated());

        return $this->successResponse(
            new CustomerGroupResource($group),
            null,
            'Customer group created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $group = CustomerGroup::where('store_id', $store->id)
            ->withCount('customers')
            ->findOrFail($id);

        return $this->successResponse(new CustomerGroupResource($group));
    }

    public function update(UpdateCustomerGroupRequest $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();
        $service = new CustomerGroupService($store);

        $group = CustomerGroup::where('store_id', $store->id)->findOrFail($id);
        $group = $service->update($group, $request->validated());

        return $this->successResponse(
            new CustomerGroupResource($group),
            null,
            'Customer group updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();
        $service = new CustomerGroupService($store);

        $group = CustomerGroup::where('store_id', $store->id)->findOrFail($id);
        $service->delete($group);

        return $this->successResponse(
            null,
            null,
            'Customer group deleted successfully'
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
