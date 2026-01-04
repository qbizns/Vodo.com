<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Requests\StoreProductTagRequest;
use VodoCommerce\Http\Resources\ProductTagResource;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductTag;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ProductTagService;

class ProductTagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $tagService = new ProductTagService($store);

        $filters = $request->only(['search', 'sort_by', 'sort_dir']);
        $perPage = min((int) $request->get('per_page', 50), 65);

        $tags = $tagService->list($filters, $perPage);

        return $this->successResponse(
            ProductTagResource::collection($tags->items()),
            $this->getPaginationMeta($tags)
        );
    }

    public function store(StoreProductTagRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $tagService = new ProductTagService($store);
        $tag = $tagService->create($request->validated());

        return $this->successResponse(
            new ProductTagResource($tag),
            null,
            'Tag created successfully',
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $tag = ProductTag::where('store_id', $store->id)->find($id);

        if (!$tag) {
            return $this->errorResponse('Tag not found', 404);
        }

        return $this->successResponse(new ProductTagResource($tag));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $tagService = new ProductTagService($store);
        $tag = $tagService->find($id);

        if (!$tag) {
            return $this->errorResponse('Tag not found', 404);
        }

        $tagService->delete($tag);

        return $this->successResponse(null, null, 'Tag deleted successfully');
    }

    public function attachToProduct(Request $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $tagIds = $request->input('tag_ids', []);

        $tagService = new ProductTagService($store);
        $tagService->attachToProduct($product, $tagIds);

        return $this->successResponse(null, null, 'Tags attached to product successfully');
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
