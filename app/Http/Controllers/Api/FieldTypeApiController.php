<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FieldType;
use App\Services\Field\FieldTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FieldTypeApiController extends Controller
{
    protected FieldTypeRegistry $registry;

    public function __construct(FieldTypeRegistry $registry)
    {
        $this->registry = $registry;
    }

    // =========================================================================
    // List & Retrieve
    // =========================================================================

    /**
     * List all field types
     * GET /api/v1/field-types
     */
    public function index(Request $request): JsonResponse
    {
        $query = FieldType::query();

        // Filters
        if ($request->has('category')) {
            $query->inCategory($request->category);
        }

        if ($request->boolean('system_only')) {
            $query->system();
        }

        if ($request->boolean('custom_only')) {
            $query->custom();
        }

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }

        if ($request->boolean('searchable')) {
            $query->searchable();
        }

        if ($request->boolean('filterable')) {
            $query->filterable();
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        // Sorting
        $query->orderBy('category')->orderBy('name');

        $fieldTypes = $query->get();

        // Format for response
        $data = $request->boolean('full', false)
            ? $fieldTypes->map(fn($type) => $type->toDefinitionArray())
            : $fieldTypes;

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $fieldTypes->count(),
            ],
        ]);
    }

    /**
     * Get a single field type
     * GET /api/v1/field-types/{name}
     */
    public function show(string $name): JsonResponse
    {
        $fieldType = $this->registry->get($name);

        if (!$fieldType) {
            return response()->json([
                'success' => false,
                'error' => 'Field type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $fieldType->toDefinitionArray(),
        ]);
    }

    /**
     * Get field type schema and configuration
     * GET /api/v1/field-types/{name}/schema
     */
    public function schema(string $name): JsonResponse
    {
        $fieldType = $this->registry->get($name);

        if (!$fieldType) {
            return response()->json([
                'success' => false,
                'error' => 'Field type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $fieldType->name,
                'config_schema' => $fieldType->config_schema,
                'default_config' => $fieldType->default_config,
                'filter_operators' => $fieldType->getFilterOperators(),
                'form_data' => $fieldType->getFormData(),
            ],
        ]);
    }

    // =========================================================================
    // Registration (Admin/Plugin API)
    // =========================================================================

    /**
     * Register a custom field type
     * POST /api/v1/field-types
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'handler_class' => ['required', 'string'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $fieldType = $this->registry->register(
                $validated['handler_class'],
                $validated['plugin_slug']
            );

            return response()->json([
                'success' => true,
                'data' => $fieldType->toDefinitionArray(),
                'message' => "Field type '{$fieldType->name}' registered successfully",
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Unregister a field type
     * DELETE /api/v1/field-types/{name}
     */
    public function destroy(Request $request, string $name): JsonResponse
    {
        $pluginSlug = $request->input('plugin_slug');

        if (!$pluginSlug) {
            return response()->json([
                'success' => false,
                'error' => 'plugin_slug is required',
            ], 400);
        }

        try {
            $this->registry->unregister($name, $pluginSlug);

            return response()->json([
                'success' => true,
                'message' => "Field type '{$name}' unregistered successfully",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // =========================================================================
    // Validation & Operations
    // =========================================================================

    /**
     * Validate a value against a field type
     * POST /api/v1/field-types/{name}/validate
     */
    public function validateValue(Request $request, string $name): JsonResponse
    {
        $fieldType = $this->registry->get($name);

        if (!$fieldType) {
            return response()->json([
                'success' => false,
                'error' => 'Field type not found',
            ], 404);
        }

        $validated = $request->validate([
            'value' => ['present'],
            'config' => ['nullable', 'array'],
            'context' => ['nullable', 'array'],
        ]);

        $result = $this->registry->validate(
            $name,
            $validated['value'],
            $validated['config'] ?? [],
            $validated['context'] ?? []
        );

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $result === true,
                'errors' => $result === true ? [] : $result,
            ],
        ]);
    }

    /**
     * Format a value for display
     * POST /api/v1/field-types/{name}/format
     */
    public function formatValue(Request $request, string $name): JsonResponse
    {
        $fieldType = $this->registry->get($name);

        if (!$fieldType) {
            return response()->json([
                'success' => false,
                'error' => 'Field type not found',
            ], 404);
        }

        $validated = $request->validate([
            'value' => ['present'],
            'config' => ['nullable', 'array'],
            'format' => ['nullable', 'string'],
        ]);

        $formatted = $this->registry->formatForDisplay(
            $name,
            $validated['value'],
            $validated['config'] ?? [],
            $validated['format'] ?? 'default'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'formatted' => $formatted,
            ],
        ]);
    }

    /**
     * Cast a value for storage
     * POST /api/v1/field-types/{name}/cast-storage
     */
    public function castForStorage(Request $request, string $name): JsonResponse
    {
        $fieldType = $this->registry->get($name);

        if (!$fieldType) {
            return response()->json([
                'success' => false,
                'error' => 'Field type not found',
            ], 404);
        }

        $validated = $request->validate([
            'value' => ['present'],
            'config' => ['nullable', 'array'],
        ]);

        $casted = $this->registry->castForStorage(
            $name,
            $validated['value'],
            $validated['config'] ?? []
        );

        return response()->json([
            'success' => true,
            'data' => [
                'casted' => $casted,
            ],
        ]);
    }

    /**
     * Get validation rules for a field type
     * GET /api/v1/field-types/{name}/validation-rules
     */
    public function validationRules(Request $request, string $name): JsonResponse
    {
        $fieldType = $this->registry->get($name);

        if (!$fieldType) {
            return response()->json([
                'success' => false,
                'error' => 'Field type not found',
            ], 404);
        }

        $config = $request->input('config', []);
        $rules = $this->registry->getValidationRules($name, $config);

        return response()->json([
            'success' => true,
            'data' => [
                'rules' => $rules,
            ],
        ]);
    }

    // =========================================================================
    // Meta Information
    // =========================================================================

    /**
     * Get available categories
     * GET /api/v1/field-types/meta/categories
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->registry->getCategories(),
        ]);
    }

    /**
     * Get available storage types
     * GET /api/v1/field-types/meta/storage-types
     */
    public function storageTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->registry->getStorageTypes(),
        ]);
    }

    /**
     * Get grouped field types for UI
     * GET /api/v1/field-types/grouped
     */
    public function grouped(): JsonResponse
    {
        $fieldTypes = $this->registry->all();
        
        $grouped = $fieldTypes->groupBy('category')->map(function ($types, $category) {
            return [
                'category' => $category,
                'label' => $this->registry->getCategories()[$category] ?? ucfirst($category),
                'types' => $types->map(fn($type) => [
                    'name' => $type->name,
                    'label' => $type->label,
                    'icon' => $type->icon,
                    'description' => $type->description,
                ]),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Get filter operators for a field type
     * GET /api/v1/field-types/{name}/filter-operators
     */
    public function filterOperators(string $name): JsonResponse
    {
        $fieldType = $this->registry->get($name);

        if (!$fieldType) {
            return response()->json([
                'success' => false,
                'error' => 'Field type not found',
            ], 404);
        }

        $operators = $fieldType->getFilterOperators();

        // Add labels for operators
        $operatorLabels = [
            'equals' => 'Equals',
            'not_equals' => 'Not Equals',
            'contains' => 'Contains',
            'not_contains' => 'Does Not Contain',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'greater_than' => 'Greater Than',
            'less_than' => 'Less Than',
            'between' => 'Between',
            'in' => 'In List',
            'not_in' => 'Not In List',
            'is_null' => 'Is Empty',
            'is_not_null' => 'Is Not Empty',
            'within_radius' => 'Within Radius',
        ];

        $data = array_map(fn($op) => [
            'operator' => $op,
            'label' => $operatorLabels[$op] ?? ucwords(str_replace('_', ' ', $op)),
        ], $operators);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
