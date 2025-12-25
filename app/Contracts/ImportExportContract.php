<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for Import/Export operations.
 */
interface ImportExportContract
{
    /**
     * Import data from a file.
     *
     * @param string $entityName Target entity
     * @param string $filePath Path to import file
     * @param array $options Import options
     * @return array Import result with counts and errors
     */
    public function import(string $entityName, string $filePath, array $options = []): array;

    /**
     * Export data to a file.
     *
     * @param string $entityName Source entity
     * @param array $filters Optional filters
     * @param array $options Export options
     * @return string Path to exported file
     */
    public function export(string $entityName, array $filters = [], array $options = []): string;

    /**
     * Get import template for an entity.
     *
     * @param string $entityName Entity name
     * @param string $format Format (csv, xlsx)
     * @return string Path to template file
     */
    public function getTemplate(string $entityName, string $format = 'csv'): string;

    /**
     * Validate import data before processing.
     *
     * @param string $entityName Target entity
     * @param string $filePath Path to import file
     * @return array Validation results
     */
    public function validate(string $entityName, string $filePath): array;

    /**
     * Get supported import formats.
     *
     * @return array<string>
     */
    public function getSupportedFormats(): array;
}
