<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Entity\EntityRegistry;
use App\Services\RecordRule\RecordRuleEngine;
use App\Models\EntityRecord;
use App\Models\EntityDefinition;
use App\Traits\AuthorizesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Entity API Controller - CRUD operations for dynamic entities.
 *
 * Security Features:
 * - Permission-based authorization for all actions
 * - Record rules (row-level security) enforcement
 * - Tenant isolation via model scopes
 * - Audit logging for all modifications
 */
class EntityApiController extends Controller
{
    use AuthorizesApiRequests;

    protected EntityRegistry $registry;
    protected RecordRuleEngine $recordRules;

    public function __construct(RecordRuleEngine $recordRules)
    {
        $this->registry = EntityRegistry::getInstance();
        $this->recordRules = $recordRules;
    }

    /**
     * Get the resource name for permission checks.
     */
    protected function getResourceName(): string
    {
        return 'entities';
    }

    /**
     * Get entity-specific permission.
     */
    protected function getEntityPermission(string $entityName, string $action): string
    {
        return "entities.{$entityName}.{$action}";
    }

    /**
     * List all registered entities.
     * GET /api/v1/entities
     */
    public function index(): JsonResponse
    {
        $this->authorizeView();

        $user = $this->getAuthUser();

        $entities = EntityDefinition::active()
            ->where('show_in_rest', true)
            ->orderBy('menu_position')
            ->get()
            ->filter(function ($entity) use ($user) {
                // Filter entities user has permission to view
                return $user->isSuperAdmin() ||
                       $user->hasPermission("entities.{$entity->name}.view") ||
                       $user->hasPermission('entities.view');
            })
            ->map(fn($entity) => [
                'name' => $entity->name,
                'slug' => $entity->slug,
                'labels' => $entity->labels,
                'icon' => $entity->icon,
                'is_hierarchical' => $entity->is_hierarchical,
                'supports' => $entity->supports,
                'endpoints' => [
                    'list' => url("/api/v1/entities/{$entity->name}"),
                    'create' => url("/api/v1/entities/{$entity->name}"),
                    'read' => url("/api/v1/entities/{$entity->name}/{id}"),
                    'update' => url("/api/v1/entities/{$entity->name}/{id}"),
                    'delete' => url("/api/v1/entities/{$entity->name}/{id}"),
                ],
            ]);

        return response()->json([
            'success' => true,
            'data' => $entities->values(),
        ]);
    }

    /**
     * Get entity schema (fields, taxonomies).
     * GET /api/v1/entities/{entity}/schema
     */
    public function schema(string $entity): JsonResponse
    {
        $this->authorizeView("entities.{$entity}");

        $definition = $this->getEntityOrFail($entity);

        $fields = $definition->fields->map(fn($field) => [
            'slug' => $field->slug,
            'name' => $field->name,
            'type' => $field->type,
            'description' => $field->description,
            'required' => $field->is_required,
            'unique' => $field->is_unique,
            'searchable' => $field->is_searchable,
            'filterable' => $field->is_filterable,
            'sortable' => $field->is_sortable,
            'default' => $field->default_value,
            'config' => $field->config,
            'form_group' => $field->form_group,
            'form_width' => $field->form_width,
        ]);

        $taxonomies = $definition->taxonomies()->map(fn($tax) => [
            'name' => $tax->name,
            'slug' => $tax->slug,
            'labels' => $tax->labels,
            'hierarchical' => $tax->is_hierarchical,
            'allow_multiple' => $tax->allow_multiple,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'entity' => [
                    'name' => $definition->name,
                    'labels' => $definition->labels,
                    'supports' => $definition->supports,
                    'is_hierarchical' => $definition->is_hierarchical,
                ],
                'fields' => $fields,
                'taxonomies' => $taxonomies,
                'validation_rules' => $this->registry->getValidationRules($entity),
            ],
        ]);
    }

    /**
     * List records for an entity.
     * GET /api/v1/entities/{entity}
     */
    public function listRecords(Request $request, string $entity): JsonResponse
    {
        $this->authorizeView("entities.{$entity}");

        $definition = $this->getEntityOrFail($entity);

        $query = EntityRecord::forEntity($entity)
            ->with(['author'])
            ->notTrashed();

        // Apply record rules (row-level security)
        $user = $this->getAuthUser();
        $query = $this->recordRules->applyReadRules($query, $entity, $user);

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search - escape wildcards for security
        if ($request->has('search')) {
            $searchTerm = $this->escapeSearchWildcards($request->search);
            $query->search($searchTerm);
        }

        // Author filter
        if ($request->has('author')) {
            $query->byAuthor((int) $request->author);
        }

        // Term filter
        if ($request->has('term')) {
            $query->withTerm((int) $request->term);
        }

        // Taxonomy term filter (taxonomy:slug format)
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'tax_')) {
                $taxonomyName = substr($key, 4);
                $query->withTermSlug($taxonomyName, $value);
            }
        }

        // Field filters
        $filterableFields = $definition->fields()
            ->where('is_filterable', true)
            ->pluck('slug')
            ->toArray();

        foreach ($filterableFields as $fieldSlug) {
            if ($request->has($fieldSlug)) {
                $query->whereHas('fieldValues', function ($q) use ($fieldSlug, $request) {
                    $q->where('field_slug', $fieldSlug)
                      ->where('value', $request->get($fieldSlug));
                });
            }
        }

        // Sorting - whitelist allowed fields
        $sortField = $request->get('sort', 'created_at');
        $sortDir = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSortFields = ['id', 'title', 'slug', 'status', 'created_at', 'updated_at', 'published_at', 'menu_order'];
        if (in_array($sortField, $allowedSortFields, true)) {
            $query->orderBy($sortField, $sortDir);
        }

        // Pagination
        $perPage = min(
            max(1, (int) $request->get('per_page', config('entity.pagination.default_per_page', 15))),
            config('entity.pagination.max_per_page', 100)
        );

        $records = $query->paginate($perPage);

        // Transform records
        $data = $records->through(function ($record) {
            return $this->transformRecord($record);
        });

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Create a new record.
     * POST /api/v1/entities/{entity}
     */
    public function store(Request $request, string $entity): JsonResponse
    {
        $this->authorizeCreate("entities.{$entity}");

        $definition = $this->getEntityOrFail($entity);

        // Build validation rules
        $rules = [
            'title' => $definition->supports('title') ? 'required|string|max:255' : 'nullable|string|max:255',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'content' => 'nullable|string|max:1000000',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'nullable|string|in:draft,published,archived',
            'parent_id' => 'nullable|integer|exists:entity_records,id',
            'featured_image' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
            'meta' => 'nullable|array',
        ];

        // Add field validation rules
        $fieldRules = $this->registry->getValidationRules($entity);
        foreach ($fieldRules as $field => $fieldRule) {
            $rules["fields.{$field}"] = $fieldRule;
        }

        // Add taxonomy validation
        foreach ($definition->taxonomies() as $taxonomy) {
            $rules["taxonomies.{$taxonomy->name}"] = 'nullable|array';
            $rules["taxonomies.{$taxonomy->name}.*"] = 'integer|exists:taxonomy_terms,id';
        }

        $validated = $request->validate($rules);

        // Use transaction for data integrity
        $record = DB::transaction(function () use ($entity, $validated, $request) {
            // Create record
            $record = $this->registry->createRecord($entity, array_merge(
                $validated,
                [
                    'author_id' => $request->user()?->id,
                    'status' => $validated['status'] ?? 'draft',
                ],
                $validated['fields'] ?? []
            ));

            // Sync taxonomies
            if (!empty($validated['taxonomies'])) {
                foreach ($validated['taxonomies'] as $taxonomyName => $termIds) {
                    $record->syncTerms($taxonomyName, $termIds);
                }
            }

            return $record;
        });

        // Fire hook
        if (function_exists('do_action')) {
            do_action('entity_record_created_via_api', $record, $definition);
        }

        // Log the creation
        \Log::info('Entity record created via API', [
            'entity' => $entity,
            'record_id' => $record->id,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transformRecord($record->fresh()),
            'message' => "{$definition->getSingularLabel()} created successfully.",
        ], 201);
    }

    /**
     * Get a single record.
     * GET /api/v1/entities/{entity}/{id}
     */
    public function show(string $entity, int $id): JsonResponse
    {
        $this->authorizeView("entities.{$entity}");

        $definition = $this->getEntityOrFail($entity);

        $query = EntityRecord::forEntity($entity)
            ->with(['author', 'terms']);

        // Apply record rules
        $user = $this->getAuthUser();
        $query = $this->recordRules->applyReadRules($query, $entity, $user);

        $record = $query->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformRecord($record, true),
        ]);
    }

    /**
     * Update a record.
     * PUT /api/v1/entities/{entity}/{id}
     */
    public function update(Request $request, string $entity, int $id): JsonResponse
    {
        $definition = $this->getEntityOrFail($entity);

        $query = EntityRecord::forEntity($entity);

        // Apply record rules for update permission
        $user = $this->getAuthUser();
        $query = $this->recordRules->applyWriteRules($query, $entity, $user);

        $record = $query->findOrFail($id);

        // Authorize update (permission or ownership)
        $this->authorizeActionOrOwnership('update', $record, 'author_id');

        // Build validation rules (all optional for updates)
        $rules = [
            'title' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'content' => 'nullable|string|max:1000000',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'nullable|string|in:draft,published,archived,trash',
            'parent_id' => 'nullable|integer|exists:entity_records,id',
            'featured_image' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
            'meta' => 'nullable|array',
        ];

        // Add field validation rules (make all nullable for updates)
        $fieldRules = $this->registry->getValidationRules($entity);
        foreach ($fieldRules as $field => $fieldRule) {
            if (is_array($fieldRule)) {
                $fieldRule = array_map(fn($r) => $r === 'required' ? 'nullable' : $r, $fieldRule);
            } elseif (is_string($fieldRule)) {
                $fieldRule = str_replace('required', 'nullable', $fieldRule);
            }
            $rules["fields.{$field}"] = $fieldRule;
        }

        // Add taxonomy validation
        foreach ($definition->taxonomies() as $taxonomy) {
            $rules["taxonomies.{$taxonomy->name}"] = 'nullable|array';
            $rules["taxonomies.{$taxonomy->name}.*"] = 'integer|exists:taxonomy_terms,id';
        }

        $validated = $request->validate($rules);

        // Use transaction for data integrity
        DB::transaction(function () use ($record, $validated) {
            // Update core fields
            $coreFields = ['title', 'slug', 'content', 'excerpt', 'status', 'parent_id', 'featured_image', 'published_at', 'meta'];
            $record->update(array_intersect_key($validated, array_flip($coreFields)));

            // Update custom fields
            if (!empty($validated['fields'])) {
                $record->setFields($validated['fields']);
                $record->saveFieldValues();
            }

            // Sync taxonomies
            if (isset($validated['taxonomies'])) {
                foreach ($validated['taxonomies'] as $taxonomyName => $termIds) {
                    $record->syncTerms($taxonomyName, $termIds);
                }
            }
        });

        // Fire hook
        if (function_exists('do_action')) {
            do_action('entity_record_updated_via_api', $record, $definition);
        }

        // Log the update
        \Log::info('Entity record updated via API', [
            'entity' => $entity,
            'record_id' => $record->id,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transformRecord($record->fresh(), true),
            'message' => "{$definition->getSingularLabel()} updated successfully.",
        ]);
    }

    /**
     * Delete a record.
     * DELETE /api/v1/entities/{entity}/{id}
     */
    public function destroy(Request $request, string $entity, int $id): JsonResponse
    {
        $definition = $this->getEntityOrFail($entity);

        $query = EntityRecord::forEntity($entity);

        // Apply record rules for delete permission
        $user = $this->getAuthUser();
        $query = $this->recordRules->applyDeleteRules($query, $entity, $user);

        $record = $query->findOrFail($id);

        // Authorize delete (permission or ownership)
        $this->authorizeActionOrOwnership('delete', $record, 'author_id');

        // Check if force delete
        $forceDelete = $request->boolean('force', false);

        // Force delete requires additional permission
        if ($forceDelete) {
            $this->authorizeAction('force_delete', "entities.{$entity}");
        }

        DB::transaction(function () use ($record, $forceDelete) {
            if ($forceDelete) {
                $record->forceDelete();
            } else {
                $record->trash();
            }
        });

        $message = $forceDelete
            ? "{$definition->getSingularLabel()} permanently deleted."
            : "{$definition->getSingularLabel()} moved to trash.";

        // Fire hook
        if (function_exists('do_action')) {
            do_action('entity_record_deleted_via_api', $record, $definition, $forceDelete);
        }

        // Log the deletion
        \Log::info('Entity record deleted via API', [
            'entity' => $entity,
            'record_id' => $id,
            'user_id' => $request->user()?->id,
            'force' => $forceDelete,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Restore a trashed record.
     * POST /api/v1/entities/{entity}/{id}/restore
     */
    public function restore(string $entity, int $id): JsonResponse
    {
        $this->authorizeAction('restore', "entities.{$entity}");

        $definition = $this->getEntityOrFail($entity);

        $record = EntityRecord::forEntity($entity)
            ->withTrashed()
            ->findOrFail($id);

        DB::transaction(function () use ($record) {
            $record->status = EntityRecord::STATUS_DRAFT;
            $record->restore();
        });

        return response()->json([
            'success' => true,
            'data' => $this->transformRecord($record->fresh()),
            'message' => "{$definition->getSingularLabel()} restored successfully.",
        ]);
    }

    /**
     * Bulk actions.
     * POST /api/v1/entities/{entity}/bulk
     */
    public function bulk(Request $request, string $entity): JsonResponse
    {
        $definition = $this->getEntityOrFail($entity);

        $validated = $request->validate([
            'action' => 'required|string|in:delete,trash,restore,publish,unpublish',
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer',
        ]);

        // Authorize bulk action
        $this->authorizeBulk($validated['action'], "entities.{$entity}");

        $user = $this->getAuthUser();
        $query = EntityRecord::forEntity($entity)->whereIn('id', $validated['ids']);

        // Apply record rules
        if (in_array($validated['action'], ['delete', 'trash'])) {
            $query = $this->recordRules->applyDeleteRules($query, $entity, $user);
        } else {
            $query = $this->recordRules->applyWriteRules($query, $entity, $user);
        }

        $count = 0;

        DB::transaction(function () use ($validated, $query, $entity, &$count) {
            switch ($validated['action']) {
                case 'delete':
                    $count = $query->forceDelete();
                    break;
                case 'trash':
                    $count = $query->update(['status' => EntityRecord::STATUS_TRASH]);
                    break;
                case 'restore':
                    $count = EntityRecord::forEntity($entity)
                        ->withTrashed()
                        ->whereIn('id', $validated['ids'])
                        ->restore();
                    break;
                case 'publish':
                    $count = $query->update([
                        'status' => EntityRecord::STATUS_PUBLISHED,
                        'published_at' => now(),
                    ]);
                    break;
                case 'unpublish':
                    $count = $query->update(['status' => EntityRecord::STATUS_DRAFT]);
                    break;
            }
        });

        // Log bulk action
        \Log::info('Bulk entity action via API', [
            'entity' => $entity,
            'action' => $validated['action'],
            'count' => $count,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Bulk {$validated['action']} completed. {$count} records affected.",
            'affected' => $count,
        ]);
    }

    /**
     * Get entity definition or fail.
     */
    protected function getEntityOrFail(string $name): EntityDefinition
    {
        $entity = $this->registry->get($name);

        if (!$entity) {
            abort(404, "Entity '{$name}' not found.");
        }

        if (!$entity->is_active) {
            abort(404, "Entity '{$name}' is not active.");
        }

        if (!$entity->show_in_rest) {
            abort(403, "Entity '{$name}' is not available via API.");
        }

        return $entity;
    }

    /**
     * Escape SQL wildcards from search term.
     */
    protected function escapeSearchWildcards(string $term): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $term);
    }

    /**
     * Transform a record for API response.
     */
    protected function transformRecord(EntityRecord $record, bool $includeFields = false): array
    {
        $data = [
            'id' => $record->id,
            'title' => $record->title,
            'slug' => $record->slug,
            'excerpt' => $record->excerpt,
            'status' => $record->status,
            'featured_image' => $record->featured_image,
            'author' => $record->author ? [
                'id' => $record->author->id,
                'name' => $record->author->name,
            ] : null,
            'parent_id' => $record->parent_id,
            'menu_order' => $record->menu_order,
            'published_at' => $record->published_at?->toIso8601String(),
            'created_at' => $record->created_at->toIso8601String(),
            'updated_at' => $record->updated_at->toIso8601String(),
        ];

        // Include content only for single record requests
        if ($includeFields) {
            $data['content'] = $record->content;
            $data['meta'] = $record->meta;
            $data['fields'] = $record->getFieldsArray();

            // Include terms grouped by taxonomy
            $terms = [];
            foreach ($record->terms as $term) {
                $taxName = $term->taxonomy_name;
                if (!isset($terms[$taxName])) {
                    $terms[$taxName] = [];
                }
                $terms[$taxName][] = [
                    'id' => $term->id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
            $data['taxonomies'] = $terms;
        }

        return $data;
    }
}
