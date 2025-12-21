<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use Illuminate\Support\Str;

/**
 * Transform raw plugin data from various sources into the standardized
 * Plugin Card Data Structure format for marketplace display.
 *
 * Handles data from:
 * - marketplace_plugins.json (mock data)
 * - Remote marketplace API responses
 * - Installed plugin manifests (plugin.json)
 */
class PluginDataTransformer
{
    /**
     * Current system version for compatibility checks.
     */
    protected string $systemVersion;

    /**
     * Current PHP version for compatibility checks.
     */
    protected string $phpVersion;

    public function __construct()
    {
        $this->systemVersion = config('app.version', '1.0.0');
        $this->phpVersion = PHP_VERSION;
    }

    /**
     * Transform raw plugin data into Plugin Card Data Structure format.
     *
     * @param array $plugin Raw plugin data from any source
     * @return array Standardized plugin data
     */
    public function transform(array $plugin): array
    {
        $slug = $this->extractSlug($plugin);
        $description = $this->extractDescription($plugin);
        $author = $this->normalizeAuthor($plugin);
        $requires = $this->extractRequires($plugin);
        $requiresPhp = $this->extractRequiresPhp($plugin);
        $dependencies = $this->extractDependencies($plugin);

        return [
            'slug' => $slug,
            'name' => $plugin['name'] ?? $plugin['title'] ?? Str::title(str_replace('-', ' ', $slug)),
            'description' => $description,
            'short_description' => $this->extractShortDescription($plugin, $description),
            'version' => $plugin['version'] ?? $plugin['latest_version'] ?? '1.0.0',
            'author' => $author,
            'icon' => $this->extractIcon($plugin),
            'screenshots' => $this->extractScreenshots($plugin),
            'rating' => (float) ($plugin['rating'] ?? 0),
            'reviews_count' => (int) ($plugin['reviews_count'] ?? $plugin['rating_count'] ?? 0),
            'downloads' => (int) ($plugin['downloads'] ?? $plugin['active_installs'] ?? 0),
            'category' => $plugin['category'] ?? 'utilities',
            'tags' => $this->extractTags($plugin),
            'requires' => $requires,
            'tested' => $plugin['tested'] ?? $plugin['tested_up_to'] ?? $this->systemVersion,
            'requires_php' => $requiresPhp,
            'dependencies' => $dependencies,
            'is_premium' => (bool) ($plugin['is_premium'] ?? $plugin['requires_license'] ?? false),
            'price' => $this->extractPrice($plugin),
            'compatibility' => $this->calculateCompatibility($requires, $requiresPhp, $dependencies),
        ];
    }

    /**
     * Transform multiple plugins at once.
     *
     * @param array $plugins Array of raw plugin data
     * @return array Array of transformed plugin data
     */
    public function transformMany(array $plugins): array
    {
        return array_map(fn($plugin) => $this->transform($plugin), $plugins);
    }

    /**
     * Extract slug from plugin data.
     */
    protected function extractSlug(array $plugin): string
    {
        if (!empty($plugin['slug'])) {
            return $plugin['slug'];
        }

        if (!empty($plugin['name'])) {
            return Str::slug($plugin['name']);
        }

        return 'unknown-plugin';
    }

    /**
     * Extract full description from plugin data.
     */
    protected function extractDescription(array $plugin): string
    {
        // Check various description fields
        if (!empty($plugin['description'])) {
            return $plugin['description'];
        }

        if (!empty($plugin['manifest']['description'])) {
            return $plugin['manifest']['description'];
        }

        return '';
    }

    /**
     * Extract or generate short description.
     */
    protected function extractShortDescription(array $plugin, string $fullDescription): string
    {
        // Use explicit short_description if available
        if (!empty($plugin['short_description'])) {
            return $plugin['short_description'];
        }

        if (!empty($plugin['manifest']['short_description'])) {
            return $plugin['manifest']['short_description'];
        }

        // Generate from full description
        if (!empty($fullDescription)) {
            // Get first sentence or first 150 chars
            $firstSentence = Str::before($fullDescription, '.');
            if (strlen($firstSentence) <= 150 && strlen($firstSentence) > 10) {
                return $firstSentence . '.';
            }

            return Str::limit($fullDescription, 150);
        }

        return '';
    }

    /**
     * Normalize author field to object with name and url.
     */
    protected function normalizeAuthor(array $plugin): array
    {
        // Already in correct format
        if (isset($plugin['author']) && is_array($plugin['author'])) {
            return [
                'name' => $plugin['author']['name'] ?? 'Unknown',
                'url' => $plugin['author']['url'] ?? $plugin['author']['email'] ?? null,
            ];
        }

        // String format
        if (isset($plugin['author']) && is_string($plugin['author'])) {
            return [
                'name' => $plugin['author'],
                'url' => $plugin['author_url'] ?? $plugin['homepage'] ?? null,
            ];
        }

        // No author info
        return [
            'name' => 'Unknown',
            'url' => null,
        ];
    }

    /**
     * Extract icon URL from plugin data.
     */
    protected function extractIcon(array $plugin): ?string
    {
        return $plugin['icon'] ?? $plugin['icon_url'] ?? null;
    }

    /**
     * Extract screenshots from plugin data.
     */
    protected function extractScreenshots(array $plugin): array
    {
        if (!empty($plugin['screenshots']) && is_array($plugin['screenshots'])) {
            return $plugin['screenshots'];
        }

        if (!empty($plugin['manifest']['screenshots']) && is_array($plugin['manifest']['screenshots'])) {
            return $plugin['manifest']['screenshots'];
        }

        return [];
    }

    /**
     * Extract tags from plugin data.
     */
    protected function extractTags(array $plugin): array
    {
        if (!empty($plugin['tags']) && is_array($plugin['tags'])) {
            return $plugin['tags'];
        }

        return [];
    }

    /**
     * Extract minimum system version requirement.
     */
    protected function extractRequires(array $plugin): string
    {
        // Direct requires field
        if (!empty($plugin['requires']) && is_string($plugin['requires'])) {
            return $this->cleanVersionConstraint($plugin['requires']);
        }

        // From min_system_version
        if (!empty($plugin['min_system_version'])) {
            return $this->cleanVersionConstraint($plugin['min_system_version']);
        }

        // From requirements object (plugin.json format)
        if (!empty($plugin['requirements']['system'])) {
            return $this->cleanVersionConstraint($plugin['requirements']['system']);
        }

        return '1.0.0';
    }

    /**
     * Extract PHP version requirement.
     */
    protected function extractRequiresPhp(array $plugin): string
    {
        // Direct requires_php field
        if (!empty($plugin['requires_php'])) {
            return $this->cleanVersionConstraint($plugin['requires_php']);
        }

        // From min_php_version
        if (!empty($plugin['min_php_version'])) {
            return $this->cleanVersionConstraint($plugin['min_php_version']);
        }

        // From requirements object (plugin.json format)
        if (!empty($plugin['requirements']['php'])) {
            return $this->cleanVersionConstraint($plugin['requirements']['php']);
        }

        return '8.1';
    }

    /**
     * Extract dependencies as array of plugin slugs.
     */
    protected function extractDependencies(array $plugin): array
    {
        $dependencies = [];

        // Check dependencies field
        $deps = $plugin['dependencies'] ?? [];

        if (is_array($deps)) {
            foreach ($deps as $key => $value) {
                if (is_string($key)) {
                    // Format: ['plugin-slug' => '^1.0.0']
                    // Skip non-plugin dependencies
                    if (!in_array($key, ['php', 'laravel', 'system', 'extensions'])) {
                        $dependencies[] = $key;
                    }
                } elseif (is_array($value) && !empty($value['name'])) {
                    // Format: [['name' => 'plugin-slug', 'required_version' => '^1.0.0']]
                    $dependencies[] = $value['name'];
                } elseif (is_string($value)) {
                    // Format: ['plugin-slug', 'another-plugin']
                    if (!in_array($value, ['php', 'laravel', 'system', 'extensions'])) {
                        $dependencies[] = $value;
                    }
                }
            }
        }

        return array_values(array_unique($dependencies));
    }

    /**
     * Extract price information.
     */
    protected function extractPrice(array $plugin): ?float
    {
        if (isset($plugin['price'])) {
            $price = $plugin['price'];
            return $price > 0 ? (float) $price : null;
        }

        // Premium but no price specified
        if (!empty($plugin['is_premium']) || !empty($plugin['requires_license'])) {
            return null; // Price unknown
        }

        return null; // Free
    }

    /**
     * Calculate compatibility status based on requirements.
     *
     * @return string One of: 'compatible', 'requires_update', 'incompatible'
     */
    protected function calculateCompatibility(string $requires, string $requiresPhp, array $dependencies): string
    {
        // Check PHP version
        if (!$this->checkVersionSatisfied($this->phpVersion, $requiresPhp)) {
            return 'incompatible';
        }

        // Check system version
        if (!$this->checkVersionSatisfied($this->systemVersion, $requires)) {
            // Determine if it's a minor update needed vs major incompatibility
            $requiredMajor = (int) explode('.', $requires)[0];
            $currentMajor = (int) explode('.', $this->systemVersion)[0];

            if ($requiredMajor > $currentMajor) {
                return 'incompatible';
            }

            return 'requires_update';
        }

        // Check dependencies (basic check - just if they exist)
        // In a real scenario, you'd check if these plugins are installed
        // For marketplace display, we assume compatible if requirements pass

        return 'compatible';
    }

    /**
     * Check if installed version satisfies a version constraint.
     */
    protected function checkVersionSatisfied(string $installed, string $constraint): bool
    {
        $constraint = $this->cleanVersionConstraint($constraint);

        // Handle caret constraint (^1.2.3 = >=1.2.3 <2.0.0)
        if (str_starts_with($constraint, '^')) {
            $required = substr($constraint, 1);
            return version_compare($installed, $required, '>=');
        }

        // Handle tilde constraint (~1.2.3 = >=1.2.3 <1.3.0)
        if (str_starts_with($constraint, '~')) {
            $required = substr($constraint, 1);
            return version_compare($installed, $required, '>=');
        }

        // Handle >= constraint
        if (str_starts_with($constraint, '>=')) {
            return version_compare($installed, substr($constraint, 2), '>=');
        }

        // Handle > constraint
        if (str_starts_with($constraint, '>')) {
            return version_compare($installed, substr($constraint, 1), '>');
        }

        // Handle <= constraint
        if (str_starts_with($constraint, '<=')) {
            return version_compare($installed, substr($constraint, 2), '<=');
        }

        // Handle < constraint
        if (str_starts_with($constraint, '<')) {
            return version_compare($installed, substr($constraint, 1), '<');
        }

        // Handle = constraint
        if (str_starts_with($constraint, '=')) {
            return version_compare($installed, substr($constraint, 1), '==');
        }

        // Exact match or minimum version
        return version_compare($installed, $constraint, '>=');
    }

    /**
     * Clean version constraint string to extract version number.
     */
    protected function cleanVersionConstraint(string $constraint): string
    {
        // Already has constraint operator
        if (preg_match('/^[<>=^~]/', $constraint)) {
            return trim($constraint);
        }

        // Just a version number
        return trim($constraint);
    }

    /**
     * Set system version for testing.
     */
    public function setSystemVersion(string $version): self
    {
        $this->systemVersion = $version;
        return $this;
    }

    /**
     * Set PHP version for testing.
     */
    public function setPhpVersion(string $version): self
    {
        $this->phpVersion = $version;
        return $this;
    }
}

