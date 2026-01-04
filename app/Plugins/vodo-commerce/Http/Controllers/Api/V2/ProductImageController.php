<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Requests\StoreProductImageRequest;
use VodoCommerce\Http\Resources\ProductImageResource;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductImage;
use VodoCommerce\Models\Store;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $data = $request->validated();
        $data['store_id'] = $store->id;
        $data['product_id'] = $product->id;

        if (empty($data['position'])) {
            $data['position'] = ProductImage::where('product_id', $product->id)->count();
        }

        $image = ProductImage::create($data);

        do_action('commerce.product_image.attached', $image, $product);

        return $this->successResponse(
            new ProductImageResource($image),
            null,
            'Product image added successfully',
            201
        );
    }

    public function destroy(Request $request, int $productId, int $imageId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $image = ProductImage::where('store_id', $store->id)
            ->where('product_id', $productId)
            ->find($imageId);

        if (!$image) {
            return $this->errorResponse('Product image not found', 404);
        }

        $image->delete();

        do_action('commerce.product_image.deleted', $image);

        return $this->successResponse(null, null, 'Product image deleted successfully');
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
