<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\EntityRecord;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Entity Record Repository Interface
 *
 * Specialized repository for EntityRecord model with domain-specific methods.
 */
interface EntityRecordRepositoryInterface extends RepositoryInterface
{
    /**
     * Find records by entity type.
     */
    public function findByEntity(string $entityName): Collection;

    /**
     * Get paginated records for a specific entity type using cursor pagination.
     *
     * @param array<string, mixed> $filters Field filters to apply
     */
    public function paginateByEntity(
        string $entityName,
        int $perPage = 15,
        array $filters = [],
        ?string $status = null
    ): CursorPaginator;

    /**
     * Find a record by entity and slug.
     */
    public function findBySlug(string $entityName, string $slug): ?EntityRecord;

    /**
     * Find records by status.
     */
    public function findByStatus(string $entityName, string $status): Collection;

    /**
     * Find records by author.
     */
    public function findByAuthor(string $entityName, int $authorId): Collection;

    /**
     * Create a new entity record with field values.
     *
     * @param array<string, mixed> $attributes Base record attributes
     * @param array<string, mixed> $fields Custom field values
     * @param array<string, array<int>> $taxonomies Taxonomy terms to attach
     */
    public function createWithFields(
        string $entityName,
        array $attributes,
        array $fields = [],
        array $taxonomies = []
    ): EntityRecord;

    /**
     * Update a record with field values.
     *
     * @param array<string, mixed> $attributes Base record attributes
     * @param array<string, mixed> $fields Custom field values
     * @param array<string, array<int>> $taxonomies Taxonomy terms to sync
     */
    public function updateWithFields(
        EntityRecord $record,
        array $attributes,
        array $fields = [],
        array $taxonomies = []
    ): EntityRecord;

    /**
     * Get records with their custom field values loaded.
     */
    public function getWithFieldValues(string $entityName, array $fieldSlugs = []): Collection;

    /**
     * Filter records by custom field value.
     *
     * @param string $fieldSlug The field to filter by
     * @param mixed $value The value to match
     * @param string $operator Comparison operator (=, !=, >, <, >=, <=, LIKE)
     */
    public function filterByField(string $fieldSlug, mixed $value, string $operator = '='): static;

    /**
     * Bulk update records.
     *
     * @param array<int> $ids Record IDs to update
     * @param array<string, mixed> $attributes Attributes to update
     * @return int Number of affected records
     */
    public function bulkUpdate(array $ids, array $attributes): int;

    /**
     * Bulk delete records (soft delete if enabled).
     *
     * @param array<int> $ids Record IDs to delete
     * @return int Number of deleted records
     */
    public function bulkDelete(array $ids): int;

    /**
     * Bulk restore soft-deleted records.
     *
     * @param array<int> $ids Record IDs to restore
     * @return int Number of restored records
     */
    public function bulkRestore(array $ids): int;

    /**
     * Get published records only.
     */
    public function published(): static;

    /**
     * Include trashed (soft-deleted) records.
     */
    public function withTrashed(): static;

    /**
     * Only get trashed (soft-deleted) records.
     */
    public function onlyTrashed(): static;
}
