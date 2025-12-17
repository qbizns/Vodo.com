<?php

namespace App\Contracts;

/**
 * Contract for custom field types.
 * All field type handlers must implement this interface.
 */
interface FieldTypeContract
{
    public function getName(): string;
    public function getLabel(): string;
    public function getCategory(): string;
    public function getDescription(): string;
    public function getIcon(): ?string;
    public function getStorageType(): string;
    public function requiresSerialization(): bool;
    public function getValidationRules(array $fieldConfig = [], array $context = []): array;
    public function validate($value, array $fieldConfig = [], array $context = []): bool|array;
    public function castForStorage($value, array $fieldConfig = []);
    public function castFromStorage($value, array $fieldConfig = []);
    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string;
    public function getConfigSchema(): array;
    public function getDefaultConfig(): array;
    public function validateConfig(array $config): bool|array;
    public function getFormComponent(): ?string;
    public function getListComponent(): ?string;
    public function isSearchable(): bool;
    public function isFilterable(): bool;
    public function isSortable(): bool;
    public function supportsDefault(): bool;
    public function supportsUnique(): bool;
    public function supportsMultiple(): bool;
    public function getFilterOperators(): array;
    public function applyFilter($query, string $fieldSlug, string $operator, $value, array $fieldConfig = []);
    public function beforeSave($value, array $fieldConfig = [], array $context = []);
    public function afterLoad($value, array $fieldConfig = [], array $context = []);
    public function getFormData(array $fieldConfig = [], array $context = []): array;
    public function toArray($value, array $fieldConfig = []);
    public function fromArray($data, array $fieldConfig = []);
}
