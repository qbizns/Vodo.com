<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EntityDefinition;
use App\Models\EntityRecord;
use App\Services\Entity\EntityRegistry;
use App\Services\RecordRule\RecordRuleEngine;
use App\Services\View\ViewRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Entity View Controller - Generic admin controller for entity CRUD UI.
 *
 * This controller renders entity forms and lists dynamically from ViewRegistry
 * definitions, eliminating the need for manual blade files per entity.
 * 
 * Supports both:
 * - EntityRecord-based entities (stored in entity_records table)
 * - Standalone model entities (with custom table_name and model_class in config)
 *
 * @example Routes
 * - GET /admin/entities/commerce_store → index() → List view
 * - GET /admin/entities/commerce_store/create → create() → Form view (create mode)
 * - GET /admin/entities/commerce_store/5 → show() → Detail view
 * - GET /admin/entities/commerce_store/5/edit → edit() → Form view (edit mode)
 */
class EntityViewController extends Controller
{
    protected ViewRegistry $viewRegistry;

    protected EntityRegistry $entityRegistry;

    protected RecordRuleEngine $recordRules;

    public function __construct(
        ViewRegistry $viewRegistry,
        RecordRuleEngine $recordRules
    ) {
        $this->viewRegistry = $viewRegistry;
        $this->entityRegistry = EntityRegistry::getInstance();
        $this->recordRules = $recordRules;
    }

    /**
     * Get the current authenticated admin user.
     */
    protected function getUser()
    {
        return Auth::guard('admin')->user();
    }

    /**
     * Resolve entity name and ID from request parameters.
     * Handles both standard routes (/entities/{entity}/{id}) and plugin routes with defaults.
     */
    protected function resolveEntityAndId(Request $request, string $entity, string|int|null $id = null): array
    {
        // If $entity looks like an ID (numeric), swap parameters
        // This happens when plugin routes use ->defaults('entity', 'name') 
        if (is_numeric($entity) && $id === null) {
            $route = $request->route();
            
            // Try multiple ways to get the entity from route defaults
            $actualEntity = null;
            
            // Method 1: Access defaults array directly
            if (isset($route->defaults['entity'])) {
                $actualEntity = $route->defaults['entity'];
            }
            // Method 2: Use route parameter (includes defaults)
            elseif ($route->parameter('entity')) {
                $actualEntity = $route->parameter('entity');
            }
            // Method 3: Get from action defaults
            elseif (isset($route->action['defaults']['entity'])) {
                $actualEntity = $route->action['defaults']['entity'];
            }
            
            if ($actualEntity) {
                return [$actualEntity, $entity]; // $entity contains the ID
            }
        }
        
        return [$entity, $id];
    }

    /**
     * Get entity definition or fail with 404.
     */
    protected function getEntityOrFail(string $entityName): EntityDefinition
    {
        $entity = EntityDefinition::where('name', $entityName)
            ->orWhere('slug', $entityName)
            ->active()
            ->first();

        if (!$entity) {
            abort(404, "Entity '{$entityName}' not found");
        }

        return $entity;
    }

    /**
     * Check if entity uses a standalone model (not EntityRecord).
     */
    protected function isStandaloneModel(EntityDefinition $definition): bool
    {
        $tableName = $definition->table_name ?? 'entity_records';
        return $tableName !== 'entity_records';
    }

    /**
     * Get the model class for a standalone entity.
     */
    protected function getModelClass(EntityDefinition $definition): ?string
    {
        return $definition->getConfig('model_class');
    }

    /**
     * Create a query builder for the entity's records.
     * Supports both EntityRecord and standalone models.
     */
    protected function createQuery(EntityDefinition $definition, Request $request): Builder
    {
        if ($this->isStandaloneModel($definition)) {
            $modelClass = $this->getModelClass($definition);
            
            if ($modelClass && class_exists($modelClass)) {
                // Always bypass tenant scope in admin panel - show all records
                $query = $modelClass::withoutGlobalScopes();
            } else {
                // Use dynamic model with custom table
                $model = new class extends Model {
                    public $timestamps = true;
                };
                $model->setTable($definition->table_name);
                $query = $model->newQuery();
                
                // Apply manual tenant filtering for dynamic models
                $user = $this->getUser();
                if ($user && $user->tenant_id) {
                    try {
                        $query->where('tenant_id', $user->tenant_id);
                    } catch (\Exception $e) {
                        // Table might not have tenant_id column
                    }
                }
            }
            
            return $query;
        }
        
        // Default: use EntityRecord
        return EntityRecord::forEntity($definition->name)->with(['author']);
    }

    /**
     * Find a single record by ID.
     */
    protected function findRecord(EntityDefinition $definition, Request $request, string|int $id): ?Model
    {
        if ($this->isStandaloneModel($definition)) {
            $modelClass = $this->getModelClass($definition);
            
            if ($modelClass && class_exists($modelClass)) {
                // Always bypass tenant scope in admin panel - use withoutGlobalScopes to ensure it works
                $query = $modelClass::withoutGlobalScopes()->where('id', $id);
                
                return $query->first();
            }
        }
        
        $query = $this->createQuery($definition, $request);
        return $query->find($id);
    }

    /**
     * Get the base URL for entity CRUD operations based on current request path.
     */
    protected function getEntityBaseUrl(Request $request, string $entityName): string
    {
        $currentPath = $request->path();
        
        if (str_contains($currentPath, 'plugins/')) {
            // Plugin route - extract base path (e.g., /plugins/vodo-commerce/stores)
            // Remove trailing segments like /create, /{id}, /{id}/edit
            $path = '/' . preg_replace('#/(create|\d+(/edit)?)$#', '', $currentPath);
            return $path;
        }
        
        // Default generic entity route
        return "/admin/entities/{$entityName}";
    }

    /**
     * Check if current request is via plugin routes.
     */
    protected function isPluginRoute(Request $request): bool
    {
        return str_contains($request->path(), 'plugins/');
    }

    /**
     * Display list view for an entity.
     * GET /admin/entities/{entity}
     */
    public function index(Request $request, string $entity)
    {
        $definition = $this->getEntityOrFail($entity);
        $entityName = $definition->name;

        // Get list view definition from ViewRegistry
        $listView = $this->viewRegistry->getListView($entityName);

        // Create query (supports both EntityRecord and standalone models)
        $query = $this->createQuery($definition, $request);
        
        // Apply record rules only for EntityRecord-based entities
        if (!$this->isStandaloneModel($definition)) {
            $user = $this->getUser();
            if ($user) {
                $query = $this->recordRules->applyReadRules($query, $entityName, $user);
            }
        }

        // Apply search filter
        if ($search = $request->get('search')) {
            // For standalone models, search in configured columns
            if ($this->isStandaloneModel($definition)) {
                $searchColumns = $definition->getConfig('search_columns', ['name', 'title', 'slug']);
                $query->where(function ($q) use ($search, $searchColumns) {
                    foreach ($searchColumns as $col) {
                        try {
                            $q->orWhere($col, 'like', "%{$search}%");
                        } catch (\Exception $e) {
                            // Column might not exist, skip it
                        }
                    }
                });
            } else {
                $query->search($search);
            }
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply sorting
        $sortField = $request->get('sort', $listView['default_order'] ?? 'created_at desc');
        if (strpos($sortField, ' ') !== false) {
            [$sortField, $sortDir] = explode(' ', $sortField);
        } else {
            $sortDir = 'desc';
        }
        $query->orderBy($sortField, $sortDir);

        // Pagination
        $perPage = $listView['config']['per_page'] ?? 25;
        $records = $query->paginate($perPage);

        // Determine URLs based on whether this is accessed via plugin routes or generic entity routes
        $isStandalone = $this->isStandaloneModel($definition);
        $isPluginRoute = $this->isPluginRoute($request);
        $baseUrl = $this->getEntityBaseUrl($request, $entityName);

        // Normalize actions - handle both flat array and nested row/bulk structure
        $rawActions = $listView['actions'] ?? ['create', 'edit', 'delete'];
        if (isset($rawActions['row'])) {
            // Nested structure - merge row actions with 'create' for header
            $actions = array_merge(['create'], $rawActions['row'] ?? []);
        } else {
            $actions = $rawActions;
        }

        // Get columns - limit to reasonable number for list view
        $allColumns = $listView['columns'] ?? [];
        // For standalone models, show max 5 most important columns
        if ($isStandalone && count($allColumns) > 5) {
            $priorityColumns = ['name', 'title', 'slug', 'status', 'is_active', 'created_at'];
            $columns = [];
            foreach ($priorityColumns as $col) {
                if (isset($allColumns[$col])) {
                    $columns[$col] = $allColumns[$col];
                }
            }
            // Fill up to 5 with remaining columns
            foreach ($allColumns as $key => $col) {
                if (count($columns) >= 5) break;
                if (!isset($columns[$key])) {
                    $columns[$key] = $col;
                }
            }
        } else {
            $columns = $allColumns;
        }

        return response()->view('backend.entity.list', [
            'entity' => $definition,
            'entityName' => $entityName,
            'viewDefinition' => $listView,
            'records' => $records,
            'columns' => $columns,
            'actions' => $actions,
            'filters' => $request->only(['search', 'status']),
            'pageTitle' => $definition->getPluralLabel(),
            'indexUrl' => $baseUrl,
            'createUrl' => "{$baseUrl}/create",
            'editUrlBase' => $baseUrl,
            'apiUrl' => url("/api/v1/entities/{$entityName}"),
            'isStandalone' => $isStandalone,
            'useGenericRoutes' => !$isPluginRoute,
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * Display create form for an entity.
     * GET /admin/entities/{entity}/create
     */
    public function create(Request $request, string $entity)
    {
        $definition = $this->getEntityOrFail($entity);
        $entityName = $definition->name;
        $baseUrl = $this->getEntityBaseUrl($request, $entityName);

        // Get form view definition from ViewRegistry
        $formView = $this->viewRegistry->getFormView($entityName);

        // Prepare sections from the form view
        $sections = $this->prepareSectionsForModel($formView, $definition, null);

        // Use admin route for form submission (not API which requires Sanctum)
        $submitUrl = $this->isPluginRoute($request) ? $baseUrl : route('admin.entities.store', ['entity' => $entityName]);

        return response()->view('backend.entity.form', [
            'entity' => $definition,
            'entityName' => $entityName,
            'viewDefinition' => $formView,
            'sections' => $sections,
            'record' => null,
            'mode' => 'create',
            'pageTitle' => 'Create ' . $definition->getSingularLabel(),
            'submitUrl' => $submitUrl,
            'submitMethod' => 'POST',
            'cancelUrl' => $baseUrl,
            'backUrl' => $baseUrl,
            'isStandalone' => $this->isStandaloneModel($definition),
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * Display detail view for a record.
     * GET /admin/entities/{entity}/{id}
     */
    public function show(Request $request, string $entity, string|int $id = null)
    {
        [$entity, $id] = $this->resolveEntityAndId($request, $entity, $id);
        $definition = $this->getEntityOrFail($entity);
        $entityName = $definition->name;
        $baseUrl = $this->getEntityBaseUrl($request, $entityName);

        // Find record (supports both EntityRecord and standalone models)
        $record = $this->findRecord($definition, $request, $id);
        
        if (!$record) {
            abort(404, 'Record not found');
        }

        // Get form view definition (use for detail display)
        $formView = $this->viewRegistry->getFormView($entityName);
        $sections = $this->prepareSectionsForModel($formView, $definition, $record);

        return response()->view('backend.entity.show', [
            'entity' => $definition,
            'entityName' => $entityName,
            'viewDefinition' => $formView,
            'sections' => $sections,
            'record' => $record,
            'pageTitle' => $record->title ?? $record->name ?? $definition->getSingularLabel(),
            'editUrl' => "{$baseUrl}/{$id}/edit",
            'backUrl' => $baseUrl,
            'isStandalone' => $this->isStandaloneModel($definition),
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * Display edit form for a record.
     * GET /admin/entities/{entity}/{id}/edit
     */
    public function edit(Request $request, string $entity, string|int $id = null)
    {
        [$entity, $id] = $this->resolveEntityAndId($request, $entity, $id);
        $definition = $this->getEntityOrFail($entity);
        $entityName = $definition->name;
        $baseUrl = $this->getEntityBaseUrl($request, $entityName);

        // Find record (supports both EntityRecord and standalone models)
        $record = $this->findRecord($definition, $request, $id);
        
        if (!$record) {
            abort(404, 'Record not found');
        }

        // Get form view definition from ViewRegistry
        $formView = $this->viewRegistry->getFormView($entityName);
        $sections = $this->prepareSectionsForModel($formView, $definition, $record);

        // Use admin route for form submission (not API which requires Sanctum)
        $isPluginRoute = $this->isPluginRoute($request);
        $submitUrl = $isPluginRoute ? "{$baseUrl}/{$id}" : route('admin.entities.update', ['entity' => $entityName, 'id' => $id]);
        $deleteUrl = $isPluginRoute ? "{$baseUrl}/{$id}" : route('admin.entities.destroy', ['entity' => $entityName, 'id' => $id]);

        return response()->view('backend.entity.form', [
            'entity' => $definition,
            'entityName' => $entityName,
            'viewDefinition' => $formView,
            'sections' => $sections,
            'record' => $record,
            'mode' => 'edit',
            'pageTitle' => 'Edit ' . $definition->getSingularLabel(),
            'submitUrl' => $submitUrl,
            'submitMethod' => 'PUT',
            'cancelUrl' => $baseUrl,
            'backUrl' => $baseUrl,
            'deleteUrl' => $deleteUrl,
            'isStandalone' => $this->isStandaloneModel($definition),
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * Prepare sections from form view definition for any model type.
     * Normalizes the view definition into a consistent structure for the blade template.
     */
    protected function prepareSectionsForModel(array $formView, EntityDefinition $definition, ?Model $record = null): array
    {
        // Handle both 'sections' and 'groups' keys (ViewRegistry uses 'groups')
        $rawSections = $formView['sections'] ?? $formView['groups'] ?? [];

        if (empty($rawSections)) {
            // Generate default sections from entity fields
            return $this->generateDefaultSectionsForModel($definition, $record);
        }

        // Build a lookup map for entity fields
        $entityFields = [];
        foreach ($definition->fields as $field) {
            $entityFields[$field->slug] = $field;
        }

        $sections = [];
        foreach ($rawSections as $sectionKey => $section) {
            $normalizedSection = [
                'key' => $sectionKey,
                'label' => $section['label'] ?? ucfirst(str_replace('_', ' ', $sectionKey)),
                'columns' => $section['columns'] ?? 2,
                'collapsible' => $section['collapsible'] ?? false,
                'collapsed' => $section['collapsed'] ?? false,
                'fields' => [],
            ];

            $fields = $section['fields'] ?? [];
            foreach ($fields as $fieldName => $fieldConfig) {
                if (is_string($fieldConfig)) {
                    // Simple field name string
                    $fieldName = $fieldConfig;
                    $fieldConfig = [];
                }

                // Get entity field for additional metadata
                $entityField = $entityFields[$fieldName] ?? null;
                
                // Get options from view config, entity field config, or hardcoded defaults
                $options = $fieldConfig['options'] ?? [];
                if (empty($options) && $entityField) {
                    $entityConfig = $entityField->config ?? [];
                    $options = $entityConfig['options'] ?? [];
                }
                
                // Fallback to hardcoded defaults for common fields
                if (empty($options) && ($fieldConfig['widget'] ?? '') === 'selection') {
                    $defaultOptions = [
                        'status' => ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended', 'draft' => 'Draft', 'archived' => 'Archived'],
                        'stock_status' => ['in_stock' => 'In Stock', 'out_of_stock' => 'Out of Stock', 'backorder' => 'On Backorder'],
                    ];
                    $options = $defaultOptions[$fieldName] ?? [];
                }

                // Calculate span from entity field's form_width (prefer over view config)
                $span = 1; // default to half
                if ($entityField && $entityField->form_width) {
                    $span = $entityField->form_width === 'full' ? 2 : 1;
                } elseif (isset($fieldConfig['span'])) {
                    $span = $fieldConfig['span'];
                }

                $normalizedSection['fields'][$fieldName] = [
                    'name' => $fieldName,
                    'widget' => $fieldConfig['widget'] ?? 'char',
                    'label' => $fieldConfig['label'] ?? ucfirst(str_replace('_', ' ', $fieldName)),
                    'required' => $fieldConfig['required'] ?? ($entityField->is_required ?? false),
                    'readonly' => $fieldConfig['readonly'] ?? ($entityField->is_system ?? false),
                    'span' => $span,
                    'help' => $fieldConfig['help'] ?? ($entityField->description ?? null),
                    'placeholder' => $fieldConfig['placeholder'] ?? null,
                    'options' => $options,
                    'config' => $fieldConfig['config'] ?? ($entityField->config ?? []),
                    'value' => $record ? $this->getModelFieldValue($record, $fieldName) : ($entityField->default_value ?? null),
                ];
            }

            $sections[$sectionKey] = $normalizedSection;
        }

        return $sections;
    }

    /**
     * Generate default sections from entity fields when no view definition exists.
     */
    protected function generateDefaultSectionsForModel(EntityDefinition $definition, ?Model $record = null): array
    {
        $fields = $definition->fields;
        
        $mainFields = [];
        foreach ($fields as $field) {
            if (!$field->show_in_form) {
                continue;
            }

            // Get options from various possible locations in config
            $fieldConfig = $field->config ?? [];
            $options = $fieldConfig['options'] ?? [];
            
            // If no options and it's a select type, try to get hardcoded defaults
            if (empty($options) && $field->type === 'select') {
                // Hardcoded defaults for common commerce fields
                $defaultOptions = [
                    'status' => ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended', 'draft' => 'Draft', 'archived' => 'Archived'],
                    'stock_status' => ['in_stock' => 'In Stock', 'out_of_stock' => 'Out of Stock', 'backorder' => 'On Backorder'],
                ];
                $options = $defaultOptions[$field->slug] ?? [];
            }

            $mainFields[$field->slug] = [
                'name' => $field->slug,
                'widget' => $this->getWidgetForFieldType($field->type),
                'label' => $field->name,
                'required' => $field->is_required,
                'readonly' => $field->is_system,
                'span' => $field->form_width === 'full' ? 2 : 1,
                'help' => $field->description,
                'placeholder' => null,
                'options' => $options,
                'config' => $fieldConfig,
                'value' => $record ? $this->getModelFieldValue($record, $field->slug) : ($field->default_value ?? null),
            ];
        }

        return [
            'main' => [
                'key' => 'main',
                'label' => null, // No label for single section
                'columns' => 2,
                'collapsible' => false,
                'collapsed' => false,
                'fields' => $mainFields,
            ],
        ];
    }

    /**
     * Get field value from any model (handles both direct properties and meta/json fields).
     */
    protected function getModelFieldValue(?Model $record, string $fieldName): mixed
    {
        if (!$record) {
            return null;
        }

        // Check direct property/attribute first
        if (isset($record->{$fieldName})) {
            return $record->{$fieldName};
        }

        // For EntityRecord, check meta fields
        if ($record instanceof EntityRecord && isset($record->meta[$fieldName])) {
            return $record->meta[$fieldName];
        }

        // Check if it's in the attributes array
        $attributes = $record->getAttributes();
        if (array_key_exists($fieldName, $attributes)) {
            return $attributes[$fieldName];
        }

        return null;
    }

    /**
     * Map field type to widget name.
     */
    protected function getWidgetForFieldType(string $type): string
    {
        return match ($type) {
            'string' => 'char',
            'text' => 'text',
            'html' => 'html',
            'integer' => 'integer',
            'decimal', 'float' => 'float',
            'money' => 'monetary',
            'boolean' => 'checkbox',
            'date' => 'date',
            'datetime' => 'datetime',
            'time' => 'time',
            'email' => 'email',
            'url' => 'url',
            'phone' => 'phone',
            'select' => 'selection',
            'relation' => 'many2one',
            'file' => 'binary',
            'image' => 'image',
            'json' => 'json',
            'color' => 'color',
            'slug' => 'slug',
            default => 'char',
        };
    }

    /**
     * Store a new record (API endpoint).
     * POST /admin/entities/{entity}
     */
    public function store(Request $request, string $entity): JsonResponse
    {
        $definition = $this->getEntityOrFail($entity);
        $entityName = $definition->name;

        try {
            if ($this->isStandaloneModel($definition)) {
                // Handle standalone model
                $record = $this->storeStandaloneRecord($definition, $request);
            } else {
                // Use EntityRegistry for EntityRecord-based entities
                $record = $this->entityRegistry->createRecord($entityName, $request->all());
            }

            // Determine redirect URL
            $baseUrl = $this->getEntityBaseUrl($request, $entityName);

            return response()->json([
                'success' => true,
                'message' => $definition->getSingularLabel() . ' created successfully',
                'data' => $record,
                'redirect' => $baseUrl,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ' . $definition->getSingularLabel(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a record (API endpoint).
     * PUT /admin/entities/{entity}/{id}
     */
    public function update(Request $request, string $entity, string|int $id = null): JsonResponse
    {
        [$entity, $id] = $this->resolveEntityAndId($request, $entity, $id);
        $definition = $this->getEntityOrFail($entity);
        $entityName = $definition->name;

        try {
            if ($this->isStandaloneModel($definition)) {
                // Handle standalone model
                $record = $this->updateStandaloneRecord($definition, $request, $id);
            } else {
                // Use EntityRegistry for EntityRecord-based entities
                $record = EntityRecord::forEntity($entityName)->findOrFail($id);
                $this->entityRegistry->updateRecord($entityName, $record, $request->all());
                $record = $record->fresh();
            }

            $baseUrl = $this->getEntityBaseUrl($request, $entityName);

            return response()->json([
                'success' => true,
                'message' => $definition->getSingularLabel() . ' updated successfully',
                'data' => $record,
                'redirect' => $baseUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ' . $definition->getSingularLabel(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a record (API endpoint).
     * DELETE /admin/entities/{entity}/{id}
     */
    public function destroy(Request $request, string $entity, string|int $id = null): JsonResponse
    {
        [$entity, $id] = $this->resolveEntityAndId($request, $entity, $id);
        $definition = $this->getEntityOrFail($entity);
        $entityName = $definition->name;

        try {
            if ($this->isStandaloneModel($definition)) {
                // Handle standalone model
                $this->destroyStandaloneRecord($definition, $request, $id);
            } else {
                // Use EntityRecord
                $record = EntityRecord::forEntity($entityName)->findOrFail($id);
                $record->delete();
            }

            return response()->json([
                'success' => true,
                'message' => $definition->getSingularLabel() . ' deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ' . $definition->getSingularLabel(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store a standalone model record.
     */
    protected function storeStandaloneRecord(EntityDefinition $definition, Request $request): Model
    {
        $modelClass = $this->getModelClass($definition);
        
        if ($modelClass && class_exists($modelClass)) {
            $model = new $modelClass();
        } else {
            // Use DB table directly with anonymous model
            $model = new class extends Model {
                public $timestamps = true;
            };
            $model->setTable($definition->table_name);
        }

        // Build data with defaults from entity fields
        $data = [];
        foreach ($definition->fields as $field) {
            $slug = $field->slug;
            $requestValue = $request->input($slug);
            
            if ($requestValue !== null && $requestValue !== '') {
                $data[$slug] = $requestValue;
            } elseif ($field->default_value !== null) {
                // Apply default from EntityField model
                $data[$slug] = $field->default_value;
            } elseif (isset($field->config['default'])) {
                // Apply default from entity field config (legacy)
                $data[$slug] = $field->config['default'];
            } elseif ($field->type === 'json') {
                // JSON fields should default to empty array/object
                $data[$slug] = [];
            } elseif ($field->type === 'boolean') {
                // Boolean fields default to false
                $data[$slug] = false;
            } elseif ($field->type === 'integer') {
                // Integer fields with no value default to 0
                $data[$slug] = 0;
            }
        }

        // Add tenant_id if applicable
        $user = $this->getUser();
        $fillable = $model->getFillable();
        $needsTenantId = in_array('tenant_id', $fillable) || empty($fillable);
        
        if ($needsTenantId) {
            if ($user && $user->tenant_id) {
                $data['tenant_id'] = $user->tenant_id;
            } else {
                // Super admin or user without tenant - use user ID or default tenant
                $data['tenant_id'] = $user?->id ?? 1;
            }
        }

        // Add store_id if applicable (for commerce entities)
        $needsStoreId = in_array('store_id', $fillable);
        if ($needsStoreId && !isset($data['store_id'])) {
            // Get the first available store
            $storeClass = 'VodoCommerce\\Models\\Store';
            if (class_exists($storeClass)) {
                $store = $storeClass::withoutGlobalScopes()->first();
                if ($store) {
                    $data['store_id'] = $store->id;
                }
            }
        }

        $model->fill($data);
        $model->save();

        return $model;
    }

    /**
     * Update a standalone model record.
     */
    protected function updateStandaloneRecord(EntityDefinition $definition, Request $request, string|int $id): Model
    {
        $record = $this->findRecord($definition, $request, $id);
        
        if (!$record) {
            throw new \Exception('Record not found');
        }

        // Get updatable fields from entity definition - only include fields actually in the request
        $data = [];
        foreach ($definition->fields as $field) {
            $slug = $field->slug;
            
            // Only update fields that are present in the request
            if ($request->has($slug)) {
                $value = $request->input($slug);
                
                // Handle empty values for certain field types
                if ($value === '' || $value === null) {
                    // For required fields, skip empty values to keep existing data
                    if ($field->is_required) {
                        continue;
                    }
                    // For JSON fields, use empty array
                    if ($field->type === 'json') {
                        $value = [];
                    }
                }
                
                $data[$slug] = $value;
            }
        }

        if (!empty($data)) {
            $record->fill($data);
            $record->save();
        }

        return $record;
    }

    /**
     * Delete a standalone model record.
     */
    protected function destroyStandaloneRecord(EntityDefinition $definition, Request $request, string|int $id): void
    {
        $record = $this->findRecord($definition, $request, $id);
        
        if (!$record) {
            throw new \Exception('Record not found');
        }

        $record->delete();
    }
}

