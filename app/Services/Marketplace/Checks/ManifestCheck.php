<?php

declare(strict_types=1);

namespace App\Services\Marketplace\Checks;

use App\Services\PluginSDK\PluginManifest;

/**
 * Manifest Check
 *
 * Validates the plugin manifest (plugin.json).
 */
class ManifestCheck extends BaseCheck
{
    protected function execute(): void
    {
        $manifestPath = $this->extractPath . '/plugin.json';

        // Check manifest exists
        if (!file_exists($manifestPath)) {
            $this->addIssue('plugin.json manifest file is missing', 30);
            return;
        }

        // Check manifest is valid JSON
        $content = $this->readFile($manifestPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addIssue('plugin.json is not valid JSON: ' . json_last_error_msg(), 30);
            return;
        }

        // Validate with PluginManifest
        try {
            $manifest = new PluginManifest($data);
            $manifest->validate();

            foreach ($manifest->getErrors() as $error) {
                $this->addIssue($error, 10);
            }

            foreach ($manifest->getWarnings() as $warning) {
                $this->addWarning($warning, 3);
            }

        } catch (\Throwable $e) {
            $this->addIssue('Failed to validate manifest: ' . $e->getMessage(), 20);
            return;
        }

        // Additional checks
        $this->checkRequiredFields($data);
        $this->checkVersionFormat($data);
        $this->checkIdentifierFormat($data);
        $this->checkMarketplaceFields($data);
    }

    protected function getCategory(): string
    {
        return 'quality';
    }

    protected function checkRequiredFields(array $data): void
    {
        $required = ['identifier', 'name', 'version', 'description'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->addIssue("Missing required field: {$field}", 10);
            }
        }

        // Recommended fields
        $recommended = ['author', 'license', 'homepage'];

        foreach ($recommended as $field) {
            if (empty($data[$field])) {
                $this->addWarning("Missing recommended field: {$field}", 2);
            }
        }
    }

    protected function checkVersionFormat(array $data): void
    {
        $version = $data['version'] ?? '';

        if (!preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?(\+[a-zA-Z0-9.]+)?$/', $version)) {
            $this->addWarning('Version should follow semantic versioning (x.y.z)', 3);
        }

        // Check version matches submission
        if ($version !== $this->submission->version) {
            $this->addIssue(
                "Manifest version ({$version}) doesn't match submission version ({$this->submission->version})",
                15
            );
        }
    }

    protected function checkIdentifierFormat(array $data): void
    {
        $identifier = $data['identifier'] ?? '';

        if (!preg_match('/^[a-z][a-z0-9-]*$/', $identifier)) {
            $this->addIssue('Identifier must be lowercase, start with letter, and contain only letters, numbers, hyphens', 10);
        }

        if (strlen($identifier) < 3) {
            $this->addWarning('Identifier should be at least 3 characters', 3);
        }

        if (strlen($identifier) > 50) {
            $this->addWarning('Identifier should not exceed 50 characters', 3);
        }

        // Check identifier matches submission
        if ($identifier !== $this->submission->plugin_slug) {
            $this->addIssue(
                "Manifest identifier ({$identifier}) doesn't match submission slug ({$this->submission->plugin_slug})",
                15
            );
        }
    }

    protected function checkMarketplaceFields(array $data): void
    {
        // Check for marketplace-required fields
        $marketplace = $data['marketplace'] ?? [];

        if (empty($marketplace['listed']) || $marketplace['listed'] !== true) {
            $this->addWarning('marketplace.listed should be true for marketplace submission', 5);
        }

        // Check description length for marketplace
        $description = $data['description'] ?? '';
        if (strlen($description) < 50) {
            $this->addWarning('Description should be at least 50 characters for marketplace listing', 3);
        }
        if (strlen($description) > 5000) {
            $this->addWarning('Description is very long - consider summarizing', 2);
        }

        // Check keywords
        $keywords = $data['keywords'] ?? [];
        if (count($keywords) < 3) {
            $this->addWarning('Should have at least 3 keywords for better discoverability', 2);
        }

        // Check category
        $category = $data['category'] ?? '';
        $validCategories = [
            'sales', 'inventory', 'accounting', 'hr', 'ecommerce',
            'marketing', 'integrations', 'analytics', 'communications',
            'productivity', 'shipping', 'payments', 'utilities',
        ];

        if (!in_array($category, $validCategories)) {
            $this->addWarning("Unknown category: {$category}", 3);
        }
    }
}
