<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\ProductAttributeResource;
use VodoCommerce\Models\ProductAttribute;
use VodoCommerce\Models\Store;

class ProductAttributeController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all product attributes.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = ProductAttribute::query()->where('store_id', $store->id);

        // Filter by visibility
        if ($request->boolean('visible_only')) {
            $query->visible();
        }

        // Filter by filterability
        if ($request->boolean('filterable_only')) {
            $query->filterable();
        }

        // Filter by comparability
        if ($request->boolean('comparable_only')) {
            $query->comparable();
        }

        // Filter by required
        if ($request->boolean('required_only')) {
            $query->required();
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        // Filter by group
        if ($request->filled('group')) {
            $query->inGroup($request->input('group'));
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
            $sortBy = $request->input('sort_by', 'name');
            $sortDir = $request->input('sort_dir', 'asc');
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->input('per_page', 15);
        $attributes = $query->paginate($perPage);

        return $this->successResponse(
            ProductAttributeResource::collection($attributes),
            [
                'current_page' => $attributes->currentPage(),
                'last_page' => $attributes->lastPage(),
                'per_page' => $attributes->perPage(),
                'total' => $attributes->total(),
            ]
        );
    }

    /**
     * Get a single attribute.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $attribute = ProductAttribute::where('store_id', $store->id)->findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $attribute->load($includes);
        }

        return $this->successResponse(
            new ProductAttributeResource($attribute)
        );
    }

    /**
     * Create a new attribute.
     */
    public function store(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:commerce_product_attributes,slug',
            'description' => 'nullable|string',
            'type' => 'required|in:text,select,multiselect,boolean,number,date,color,url,textarea',
            'is_visible' => 'nullable|boolean',
            'is_filterable' => 'nullable|boolean',
            'is_comparable' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'validation_rules' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'icon' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'meta' => 'nullable|array',
        ]);

        $data['store_id'] = $store->id;

        $attribute = ProductAttribute::create($data);

        return $this->successResponse(
            new ProductAttributeResource($attribute),
            null,
            'Product attribute created successfully',
            201
        );
    }

    /**
     * Update an attribute.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $attribute = ProductAttribute::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:commerce_product_attributes,slug,' . $id,
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:text,select,multiselect,boolean,number,date,color,url,textarea',
            'is_visible' => 'nullable|boolean',
            'is_filterable' => 'nullable|boolean',
            'is_comparable' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'validation_rules' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'icon' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'meta' => 'nullable|array',
        ]);

        $attribute->update($data);

        return $this->successResponse(
            new ProductAttributeResource($attribute),
            null,
            'Product attribute updated successfully'
        );
    }

    /**
     * Delete an attribute.
     */
    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $attribute = ProductAttribute::where('store_id', $store->id)->findOrFail($id);

        $attribute->delete();

        return $this->successResponse(
            null,
            null,
            'Product attribute deleted successfully'
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
