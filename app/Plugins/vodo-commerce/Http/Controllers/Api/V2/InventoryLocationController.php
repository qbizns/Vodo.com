<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Http\Resources\InventoryLocationResource;
use VodoCommerce\Models\InventoryLocation;
use VodoCommerce\Models\Store;

class InventoryLocationController extends Controller
{
    protected Store $store;

    public function __construct()
    {
        $this->store = resolve_store();
    }

    /**
     * List all inventory locations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryLocation::where('store_id', $this->store->id);

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $locations = $query->orderBy('priority', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => InventoryLocationResource::collection($locations),
            'pagination' => [
                'current_page' => $locations->currentPage(),
                'total' => $locations->total(),
                'per_page' => $locations->perPage(),
                'last_page' => $locations->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single location.
     */
    public function show(int $id): JsonResponse
    {
        $location = InventoryLocation::where('store_id', $this->store->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new InventoryLocationResource($location),
        ]);
    }

    /**
     * Create a new location.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:commerce_inventory_locations,code'],
            'type' => ['required', 'string', 'in:warehouse,store,dropshipper'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'size:2'],
            'contact_name' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // If this is set as default, unset other defaults
        if ($request->boolean('is_default')) {
            InventoryLocation::where('store_id', $this->store->id)
                ->update(['is_default' => false]);
        }

        $location = InventoryLocation::create(array_merge(
            $validator->validated(),
            ['store_id' => $this->store->id]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully',
            'data' => new InventoryLocationResource($location),
        ], 201);
    }

    /**
     * Update a location.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $location = InventoryLocation::where('store_id', $this->store->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:255', 'unique:commerce_inventory_locations,code,' . $id],
            'type' => ['sometimes', 'string', 'in:warehouse,store,dropshipper'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'size:2'],
            'contact_name' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // If this is set as default, unset other defaults
        if ($request->boolean('is_default')) {
            InventoryLocation::where('store_id', $this->store->id)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $location->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => new InventoryLocationResource($location->fresh()),
        ]);
    }

    /**
     * Delete a location.
     */
    public function destroy(int $id): JsonResponse
    {
        $location = InventoryLocation::where('store_id', $this->store->id)
            ->findOrFail($id);

        // Check if location has inventory
        if ($location->inventoryItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with existing inventory items',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully',
        ]);
    }
}
