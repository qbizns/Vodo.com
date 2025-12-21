<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Base Eloquent Repository
 *
 * Provides common data access functionality for all repositories.
 * Subclasses should extend this and implement domain-specific methods.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The Eloquent model instance.
     */
    protected Model $model;

    /**
     * The query builder instance for chainable operations.
     */
    protected ?Builder $query = null;

    /**
     * Columns that can be searched.
     *
     * @var array<string>
     */
    protected array $searchableColumns = [];

    /**
     * Columns that can be filtered.
     *
     * @var array<string>
     */
    protected array $filterableColumns = [];

    /**
     * Default sort column.
     */
    protected string $defaultSortColumn = 'created_at';

    /**
     * Default sort direction.
     */
    protected string $defaultSortDirection = 'desc';

    /**
     * Create a new repository instance.
     */
    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Specify the model class name.
     *
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    /**
     * Create a new model instance.
     */
    protected function makeModel(): Model
    {
        $modelClass = $this->model();
        return new $modelClass;
    }

    /**
     * Get the query builder, initializing if needed.
     */
    protected function getQuery(): Builder
    {
        if ($this->query === null) {
            $this->query = $this->model->newQuery();
        }

        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int|string $id): ?Model
    {
        return $this->getQuery()->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->getQuery()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(string $column, mixed $value): Collection
    {
        return $this->getQuery()->where($column, $value)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(string $column, mixed $value): ?Model
    {
        return $this->getQuery()->where($column, $value)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return $this->getQuery()
            ->orderBy($this->defaultSortColumn, $this->defaultSortDirection)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $result = $this->getQuery()
            ->orderBy($this->defaultSortColumn, $this->defaultSortDirection)
            ->paginate($perPage, $columns);

        $this->resetQuery();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function cursorPaginate(int $perPage = 15, array $columns = ['*']): CursorPaginator
    {
        $result = $this->getQuery()
            ->orderBy($this->defaultSortColumn, $this->defaultSortDirection)
            ->cursorPaginate($perPage, $columns);

        $this->resetQuery();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes): Model
    {
        return DB::transaction(function () use ($attributes) {
            $model = $this->model->newInstance($attributes);
            $model->save();

            return $model;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function update(Model $model, array $attributes): bool
    {
        return DB::transaction(function () use ($model, $attributes) {
            return $model->update($attributes);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Model $model): bool
    {
        return DB::transaction(function () use ($model) {
            return $model->delete();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $count = $this->getQuery()->count();
        $this->resetQuery();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(array $criteria): bool
    {
        $query = $this->getQuery();

        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        $exists = $query->exists();
        $this->resetQuery();

        return $exists;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters): static
    {
        $query = $this->getQuery();

        foreach ($filters as $column => $value) {
            // Only filter on allowed columns
            if (!in_array($column, $this->filterableColumns, true)) {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($column, $value);
            } elseif ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $term, array $columns = []): static
    {
        $searchColumns = !empty($columns) ? $columns : $this->searchableColumns;

        if (empty($searchColumns)) {
            return $this;
        }

        // Escape special characters for LIKE queries
        $term = $this->escapeSearchTerm($term);

        $this->getQuery()->where(function (Builder $query) use ($term, $searchColumns) {
            foreach ($searchColumns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->$method($column, 'LIKE', "%{$term}%");
            }
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->getQuery()->orderBy($column, $direction);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function with(array|string $relations): static
    {
        $this->getQuery()->with($relations);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resetQuery(): static
    {
        $this->query = null;

        return $this;
    }

    /**
     * Escape special characters in search terms for LIKE queries.
     */
    protected function escapeSearchTerm(string $term): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $term
        );
    }

    /**
     * Begin a database transaction.
     */
    protected function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * Commit a database transaction.
     */
    protected function commit(): void
    {
        DB::commit();
    }

    /**
     * Rollback a database transaction.
     */
    protected function rollback(): void
    {
        DB::rollBack();
    }
}
