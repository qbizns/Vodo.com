<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Requests\StoreProductOptionRequest;
use VodoCommerce\Http\Requests\StoreProductOptionTemplateRequest;
use VodoCommerce\Http\Requests\UpdateProductOptionRequest;
use VodoCommerce\Http\Resources\ProductOptionResource;
use VodoCommerce\Http\Resources\ProductOptionTemplateResource;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductOption;
use VodoCommerce\Models\ProductOptionTemplate;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ProductOptionService;

class ProductOptionController extends Controller
{
    // Product Options

    public function index(Request $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $optionService = new ProductOptionService($store);
        $options = $optionService->getProductOptions($product);

        return $this->successResponse(ProductOptionResource::collection($options));
    }

    public function store(StoreProductOptionRequest $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $optionService = new ProductOptionService($store);
        $option = $optionService->createOption($product, $request->validated());

        return $this->successResponse(
            new ProductOptionResource($option),
            null,
            'Product option created successfully',
            201
        );
    }

    public function update(UpdateProductOptionRequest $request, int $productId, int $optionId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $option = ProductOption::where('store_id', $store->id)
            ->where('product_id', $productId)
            ->find($optionId);

        if (!$option) {
            return $this->errorResponse('Product option not found', 404);
        }

        $optionService = new ProductOptionService($store);
        $option = $optionService->updateOption($option, $request->validated());

        return $this->successResponse(
            new ProductOptionResource($option),
            null,
            'Product option updated successfully'
        );
    }

    public function destroy(Request $request, int $productId, int $optionId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $option = ProductOption::where('store_id', $store->id)
            ->where('product_id', $productId)
            ->find($optionId);

        if (!$option) {
            return $this->errorResponse('Product option not found', 404);
        }

        $optionService = new ProductOptionService($store);
        $optionService->deleteOption($option);

        return $this->successResponse(null, null, 'Product option deleted successfully');
    }

    // Option Templates

    public function listTemplates(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $optionService = new ProductOptionService($store);
        $templates = $optionService->getTemplates();

        return $this->successResponse(ProductOptionTemplateResource::collection($templates));
    }

    public function storeTemplate(StoreProductOptionTemplateRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $optionService = new ProductOptionService($store);
        $template = $optionService->createTemplate($request->validated());

        return $this->successResponse(
            new ProductOptionTemplateResource($template),
            null,
            'Option template created successfully',
            201
        );
    }

    public function showTemplate(Request $request, int $templateId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $template = ProductOptionTemplate::where('store_id', $store->id)->find($templateId);

        if (!$template) {
            return $this->errorResponse('Option template not found', 404);
        }

        return $this->successResponse(new ProductOptionTemplateResource($template));
    }

    public function updateTemplate(StoreProductOptionTemplateRequest $request, int $templateId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $template = ProductOptionTemplate::where('store_id', $store->id)->find($templateId);

        if (!$template) {
            return $this->errorResponse('Option template not found', 404);
        }

        $optionService = new ProductOptionService($store);
        $template = $optionService->updateTemplate($template, $request->validated());

        return $this->successResponse(
            new ProductOptionTemplateResource($template),
            null,
            'Option template updated successfully'
        );
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
}
