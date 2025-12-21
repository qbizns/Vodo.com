<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository Interface
 *
 * Defines the contract for all repository implementations.
 * Provides a consistent API for data access across the application.
 */
interface RepositoryInterface
{
    /**
     * Find a model by its primary key.
     */
    public function find(int|string $id): ?Model;

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id): Model;

    /**
     * Find models by a specific column value.
     */
    public function findBy(string $column, mixed $value): Collection;

    /**
     * Find a single model by a specific column value.
     */
    public function findOneBy(string $column, mixed $value): ?Model;

    /**
     * Get all models.
     */
    public function all(): Collection;

    /**
     * Get paginated results using offset-based pagination.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Get paginated results using cursor-based pagination.
     *
     * Cursor pagination is more efficient for large datasets and provides
     * stable pagination when data changes between requests.
     */
    public function cursorPaginate(int $perPage = 15, array $columns = ['*']): CursorPaginator;

    /**
     * Create a new model instance.
     */
    public function create(array $attributes): Model;

    /**
     * Update an existing model.
     */
    public function update(Model $model, array $attributes): bool;

    /**
     * Delete a model.
     */
    public function delete(Model $model): bool;

    /**
     * Count total records.
     */
    public function count(): int;

    /**
     * Check if any records exist matching the criteria.
     */
    public function exists(array $criteria): bool;

    /**
     * Apply filters to the query.
     *
     * @param array<string, mixed> $filters Key-value pairs of filter conditions
     */
    public function filter(array $filters): static;

    /**
     * Apply search to the query.
     */
    public function search(string $term, array $columns = []): static;

    /**
     * Order results.
     */
    public function orderBy(string $column, string $direction = 'asc'): static;

    /**
     * Eager load relationships.
     */
    public function with(array|string $relations): static;

    /**
     * Reset the query builder to fresh state.
     */
    public function resetQuery(): static;
}
