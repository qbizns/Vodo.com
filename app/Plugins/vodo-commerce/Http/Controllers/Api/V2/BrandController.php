<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Requests\StoreBrandRequest;
use VodoCommerce\Http\Requests\UpdateBrandRequest;
use VodoCommerce\Http\Resources\BrandResource;
use VodoCommerce\Models\Brand;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\BrandService;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $brandService = new BrandService($store);

        $filters = $request->only(['is_active', 'search', 'sort_by', 'sort_dir']);
        $perPage = min((int) $request->get('per_page', 20), 65);

        $brands = $brandService->list($filters, $perPage);

        return $this->successResponse(
            BrandResource::collection($brands->items()),
            $this->getPaginationMeta($brands)
        );
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $brandService = new BrandService($store);
        $brand = $brandService->create($request->validated());

        return $this->successResponse(
            new BrandResource($brand),
            null,
            'Brand created successfully',
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $brandService = new BrandService($store);
        $brand = $brandService->find($id);

        if (!$brand) {
            return $this->errorResponse('Brand not found', 404);
        }

        return $this->successResponse(new BrandResource($brand));
    }

    public function update(UpdateBrandRequest $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $brandService = new BrandService($store);
        $brand = $brandService->find($id);

        if (!$brand) {
            return $this->errorResponse('Brand not found', 404);
        }

        $brand = $brandService->update($brand, $request->validated());

        return $this->successResponse(
            new BrandResource($brand),
            null,
            'Brand updated successfully'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $brandService = new BrandService($store);
        $brand = $brandService->find($id);

        if (!$brand) {
            return $this->errorResponse('Brand not found', 404);
        }

        $brandService->delete($brand);

        return $this->successResponse(null, null, 'Brand deleted successfully');
    }

    protected function getCurrentStore(Request $request): ?Store
    {
        $storeId = $request->get('store_id') ?? $request->user()?->store_id;

        if (!$storeId) {
            return null;
        }

        return Store::find($storeId);
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

    protected function errorResponse(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'status' => $status,
            'success' => false,
            'error' => [
                'message' => $message,
            ],
        ];

        if ($errors) {
            $response['error']['fields'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function getPaginationMeta($paginator): array
    {
        return [
            'count' => $paginator->count(),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'previous' => $paginator->previousPageUrl(),
            ],
        ];
    }
}
