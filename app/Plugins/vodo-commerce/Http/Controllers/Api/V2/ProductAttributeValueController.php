<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\ProductAttributeValueResource;
use VodoCommerce\Models\ProductAttributeValue;
use VodoCommerce\Models\Store;

class ProductAttributeValueController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all attribute values.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductAttributeValue::query();

        // Filter by product
        if ($request->filled('product_id')) {
            $query->forProduct($request->input('product_id'));
        }

        // Filter by attribute
        if ($request->filled('attribute_id')) {
            $query->forAttribute($request->input('attribute_id'));
        }

        // Filter by numeric range
        if ($request->filled('min_numeric') && $request->filled('max_numeric')) {
            $query->numericRange(
                (float) $request->input('min_numeric'),
                (float) $request->input('max_numeric')
            );
        }

        // Filter by boolean value
        if ($request->filled('boolean_value')) {
            $query->booleanValue($request->boolean('boolean_value'));
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        if ($request->boolean('ordered')) {
            $query->ordered();
        } else {
            $sortBy = $request->input('sort_by', 'id');
            $sortDir = $request->input('sort_dir', 'asc');
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->input('per_page', 15);
        $values = $query->paginate($perPage);

        return $this->successResponse(
            ProductAttributeValueResource::collection($values),
            [
                'current_page' => $values->currentPage(),
                'last_page' => $values->lastPage(),
                'per_page' => $values->perPage(),
                'total' => $values->total(),
            ]
        );
    }

    /**
     * Get a single attribute value.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $value = ProductAttributeValue::findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $value->load($includes);
        }

        return $this->successResponse(
            new ProductAttributeValueResource($value)
        );
    }

    /**
     * Create a new attribute value.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:commerce_products,id',
            'attribute_id' => 'required|exists:commerce_product_attributes,id',
            'value' => 'required|string',
            'value_text' => 'nullable|string',
            'value_numeric' => 'nullable|numeric',
            'value_date' => 'nullable|date',
            'value_boolean' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'meta' => 'nullable|array',
        ]);

        $value = ProductAttributeValue::create($data);

        return $this->successResponse(
            new ProductAttributeValueResource($value),
            null,
            'Attribute value created successfully',
            201
        );
    }

    /**
     * Update an attribute value.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $value = ProductAttributeValue::findOrFail($id);

        $data = $request->validate([
            'value' => 'sometimes|required|string',
            'value_text' => 'nullable|string',
            'value_numeric' => 'nullable|numeric',
            'value_date' => 'nullable|date',
            'value_boolean' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'meta' => 'nullable|array',
        ]);

        $value->update($data);

        return $this->successResponse(
            new ProductAttributeValueResource($value),
            null,
            'Attribute value updated successfully'
        );
    }

    /**
     * Delete an attribute value.
     */
    public function destroy(int $id): JsonResponse
    {
        $value = ProductAttributeValue::findOrFail($id);

        $value->delete();

        return $this->successResponse(
            null,
            null,
            'Attribute value deleted successfully'
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
}
