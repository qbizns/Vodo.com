<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\EntityDefinition;
use App\Models\EntityRecord;
use App\Repositories\Contracts\EntityRecordRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Entity Record Repository
 *
 * Handles all data access for EntityRecord models, including
 * custom field values and taxonomy relationships.
 */
class EntityRecordRepository extends BaseRepository implements EntityRecordRepositoryInterface
{
    /**
     * The current entity name filter.
     */
    protected ?string $entityName = null;

    /**
     * Whether to include trashed records.
     */
    protected bool $includeTrashed = false;

    /**
     * Whether to only show trashed records.
     */
    protected bool $onlyTrashedRecords = false;

    /**
     * Custom field filters to apply.
     *
     * @var array<array{slug: string, value: mixed, operator: string}>
     */
    protected array $fieldFilters = [];

    /**
     * {@inheritdoc}
     */
    protected array $searchableColumns = ['title', 'content', 'excerpt', 'slug'];

    /**
     * {@inheritdoc}
     */
    protected array $filterableColumns = ['status', 'author_id', 'parent_id', 'entity_name'];

    /**
     * {@inheritdoc}
     */
    protected function model(): string
    {
        return EntityRecord::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByEntity(string $entityName): Collection
    {
        return $this->getQuery()
            ->where('entity_name', $entityName)
            ->orderBy($this->defaultSortColumn, $this->defaultSortDirection)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function paginateByEntity(
        string $entityName,
        int $perPage = 15,
        array $filters = [],
        ?string $status = null
    ): CursorPaginator {
        $query = $this->getQuery()
            ->where('entity_name', $entityName)
            ->with(['author']);

        // Apply status filter
        if ($status !== null) {
            $query->where('status', $status);
        }

        // Apply soft delete handling
        if ($this->includeTrashed) {
            $query->withTrashed();
        } elseif ($this->onlyTrashedRecords) {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
        }

        // Apply custom field filters
        foreach ($this->fieldFilters as $filter) {
            $query->whereHas('fieldValues', function ($q) use ($filter) {
                $q->where('field_slug', $filter['slug']);

                if ($filter['operator'] === 'LIKE') {
                    $q->where('value', 'LIKE', '%' . $filter['value'] . '%');
                } else {
                    $q->where('value', $filter['operator'], $filter['value']);
                }
            });
        }

        // Apply additional column filters
        foreach ($filters as $column => $value) {
            if (in_array($column, $this->filterableColumns, true)) {
                $query->where($column, $value);
            }
        }

        $result = $query
            ->orderBy($this->defaultSortColumn, $this->defaultSortDirection)
            ->cursorPaginate($perPage);

        $this->resetQuery();
        $this->resetFilters();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $entityName, string $slug): ?EntityRecord
    {
        return $this->getQuery()
            ->where('entity_name', $entityName)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByStatus(string $entityName, string $status): Collection
    {
        return $this->getQuery()
            ->where('entity_name', $entityName)
            ->where('status', $status)
            ->orderBy($this->defaultSortColumn, $this->defaultSortDirection)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByAuthor(string $entityName, int $authorId): Collection
    {
        return $this->getQuery()
            ->where('entity_name', $entityName)
            ->where('author_id', $authorId)
            ->orderBy($this->defaultSortColumn, $this->defaultSortDirection)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function createWithFields(
        string $entityName,
        array $attributes,
        array $fields = [],
        array $taxonomies = []
    ): EntityRecord {
        return DB::transaction(function () use ($entityName, $attributes, $fields, $taxonomies) {
            // Set the entity name
            $attributes['entity_name'] = $entityName;

            // Create the base record
            $record = $this->model->newInstance($attributes);
            $record->save();

            // Save custom field values
            foreach ($fields as $slug => $value) {
                $record->setField($slug, $value);
            }
            $record->saveFieldValues();

            // Attach taxonomy terms
            foreach ($taxonomies as $taxonomyName => $termIds) {
                $record->syncTerms($taxonomyName, $termIds);
            }

            return $record->fresh(['author', 'fieldValues', 'terms']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function updateWithFields(
        EntityRecord $record,
        array $attributes,
        array $fields = [],
        array $taxonomies = []
    ): EntityRecord {
        return DB::transaction(function () use ($record, $attributes, $fields, $taxonomies) {
            // Update base attributes
            $record->fill($attributes);
            $record->save();

            // Update custom field values
            foreach ($fields as $slug => $value) {
                $record->setField($slug, $value);
            }
            $record->saveFieldValues();

            // Sync taxonomy terms
            foreach ($taxonomies as $taxonomyName => $termIds) {
                $record->syncTerms($taxonomyName, $termIds);
            }

            return $record->fresh(['author', 'fieldValues', 'terms']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getWithFieldValues(string $entityName, array $fieldSlugs = []): Collection
    {
        $query = $this->getQuery()
            ->where('entity_name', $entityName)
            ->with(['fieldValues']);

        if (!empty($fieldSlugs)) {
            $query->with(['fieldValues' => function ($q) use ($fieldSlugs) {
                $q->whereIn('field_slug', $fieldSlugs);
            }]);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function filterByField(string $fieldSlug, mixed $value, string $operator = '='): static
    {
        $this->fieldFilters[] = [
            'slug' => $fieldSlug,
            'value' => $value,
            'operator' => $operator,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bulkUpdate(array $ids, array $attributes): int
    {
        if (empty($ids)) {
            return 0;
        }

        return DB::transaction(function () use ($ids, $attributes) {
            return $this->model->newQuery()
                ->whereIn('id', $ids)
                ->update($attributes);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return DB::transaction(function () use ($ids) {
            return $this->model->newQuery()
                ->whereIn('id', $ids)
                ->delete();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function bulkRestore(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return DB::transaction(function () use ($ids) {
            return $this->model->newQuery()
                ->withTrashed()
                ->whereIn('id', $ids)
                ->restore();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function published(): static
    {
        $this->getQuery()->where('status', EntityRecord::STATUS_PUBLISHED);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withTrashed(): static
    {
        $this->includeTrashed = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onlyTrashed(): static
    {
        $this->onlyTrashedRecords = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resetQuery(): static
    {
        parent::resetQuery();
        $this->resetFilters();

        return $this;
    }

    /**
     * Reset all filters to default state.
     */
    protected function resetFilters(): void
    {
        $this->entityName = null;
        $this->includeTrashed = false;
        $this->onlyTrashedRecords = false;
        $this->fieldFilters = [];
    }

    /**
     * Get the entity definition for a given entity name.
     */
    protected function getEntityDefinition(string $entityName): ?EntityDefinition
    {
        return EntityDefinition::where('name', $entityName)->first();
    }
}
