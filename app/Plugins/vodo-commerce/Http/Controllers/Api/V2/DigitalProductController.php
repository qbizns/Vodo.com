<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Requests\AttachDigitalFileRequest;
use VodoCommerce\Http\Requests\GenerateDigitalCodesRequest;
use VodoCommerce\Http\Requests\ImportDigitalCodesRequest;
use VodoCommerce\Http\Resources\DigitalProductCodeResource;
use VodoCommerce\Http\Resources\DigitalProductFileResource;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\DigitalProductService;

class DigitalProductController extends Controller
{
    // Digital Files

    public function attachFile(AttachDigitalFileRequest $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $digitalService = new DigitalProductService($store);

        $file = $request->file('file');
        $data = $request->only(['name', 'download_limit']);

        $digitalFile = $digitalService->attachFile($product, $file, $data);

        return $this->successResponse(
            new DigitalProductFileResource($digitalFile),
            null,
            'Digital file attached successfully',
            201
        );
    }

    public function listFiles(Request $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $digitalService = new DigitalProductService($store);
        $files = $digitalService->getProductFiles($product);

        return $this->successResponse(DigitalProductFileResource::collection($files));
    }

    // Digital Codes

    public function generateCodes(GenerateDigitalCodesRequest $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $digitalService = new DigitalProductService($store);

        $quantity = $request->input('quantity');
        $prefix = $request->input('prefix');

        $codes = $digitalService->generateCodes($product, $quantity, $prefix);

        return $this->successResponse(
            DigitalProductCodeResource::collection($codes),
            null,
            "Generated {$quantity} codes successfully",
            201
        );
    }

    public function importCodes(ImportDigitalCodesRequest $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $digitalService = new DigitalProductService($store);

        $codes = $request->input('codes', []);
        $imported = $digitalService->importCodes($product, $codes);

        return $this->successResponse(
            DigitalProductCodeResource::collection($imported),
            null,
            "Imported {$imported->count()} codes successfully",
            201
        );
    }

    public function listCodes(Request $request, int $productId): JsonResponse
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return $this->errorResponse('Store not found', 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $digitalService = new DigitalProductService($store);

        $status = $request->get('status', 'all');

        if ($status === 'available') {
            $codes = $digitalService->getAvailableCodes($product);
        } elseif ($status === 'used') {
            $codes = $digitalService->getUsedCodes($product);
        } else {
            $codes = $product->digitalCodes;
        }

        return $this->successResponse(DigitalProductCodeResource::collection($codes));
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
