<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PluginDependency Model - Tracks dependencies between plugins.
 */
class PluginDependency extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    protected $fillable = [
        'plugin_id',
        'dependency_slug',
        'version_constraint',
        'is_optional',
        'is_dev_only',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'is_dev_only' => 'boolean',
        'created_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    /**
     * Get the plugin that has this dependency.
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    // ==================== Accessors ====================

    /**
     * Get the dependency plugin model.
     */
    public function getDependencyPluginAttribute(): ?Plugin
    {
        return Plugin::where('slug', $this->dependency_slug)->first();
    }

    /**
     * Get the dependency status.
     */
    public function getStatusAttribute(): string
    {
        $dependency = $this->dependency_plugin;

        if (!$dependency) {
            return 'missing';
        }

        if ($dependency->status !== 'active') {
            return 'inactive';
        }

        if (!$this->checkVersionSatisfied($dependency->version)) {
            return 'version_mismatch';
        }

        return 'satisfied';
    }

    // ==================== Methods ====================

    /**
     * Get the dependency plugin model (method version).
     */
    public function dependencyPlugin(): ?Plugin
    {
        return $this->dependency_plugin;
    }

    /**
     * Check if the dependency is satisfied.
     */
    public function isSatisfied(): bool
    {
        $dependency = $this->dependency_plugin;

        if (!$dependency) {
            return false;
        }

        if ($dependency->status !== 'active') {
            return false;
        }

        return $this->checkVersionSatisfied($dependency->version);
    }

    /**
     * Check if an installed version satisfies the constraint.
     */
    public function checkVersionSatisfied(string $installedVersion): bool
    {
        $constraint = $this->version_constraint;

        // Handle simple constraints
        if (str_starts_with($constraint, '^')) {
            // Caret constraint: ^1.2.3 allows >=1.2.3 <2.0.0
            return $this->checkCaretConstraint($installedVersion, substr($constraint, 1));
        }

        if (str_starts_with($constraint, '~')) {
            // Tilde constraint: ~1.2.3 allows >=1.2.3 <1.3.0
            return $this->checkTildeConstraint($installedVersion, substr($constraint, 1));
        }

        if (str_starts_with($constraint, '>=')) {
            return version_compare($installedVersion, substr($constraint, 2), '>=');
        }

        if (str_starts_with($constraint, '>')) {
            return version_compare($installedVersion, substr($constraint, 1), '>');
        }

        if (str_starts_with($constraint, '<=')) {
            return version_compare($installedVersion, substr($constraint, 2), '<=');
        }

        if (str_starts_with($constraint, '<')) {
            return version_compare($installedVersion, substr($constraint, 1), '<');
        }

        if (str_starts_with($constraint, '=')) {
            return version_compare($installedVersion, substr($constraint, 1), '==');
        }

        // Exact match
        if (preg_match('/^\d+\.\d+\.\d+$/', $constraint)) {
            return version_compare($installedVersion, $constraint, '==');
        }

        // Wildcard (1.2.* or 1.*)
        if (str_contains($constraint, '*')) {
            return $this->checkWildcardConstraint($installedVersion, $constraint);
        }

        // Default: exact match
        return version_compare($installedVersion, $constraint, '>=');
    }

    /**
     * Check caret constraint (^1.2.3 = >=1.2.3 <2.0.0).
     */
    protected function checkCaretConstraint(string $installed, string $base): bool
    {
        if (version_compare($installed, $base, '<')) {
            return false;
        }

        $parts = explode('.', $base);
        $major = (int) ($parts[0] ?? 0);
        $nextMajor = ($major + 1) . '.0.0';

        return version_compare($installed, $nextMajor, '<');
    }

    /**
     * Check tilde constraint (~1.2.3 = >=1.2.3 <1.3.0).
     */
    protected function checkTildeConstraint(string $installed, string $base): bool
    {
        if (version_compare($installed, $base, '<')) {
            return false;
        }

        $parts = explode('.', $base);
        $major = (int) ($parts[0] ?? 0);
        $minor = (int) ($parts[1] ?? 0);
        $nextMinor = $major . '.' . ($minor + 1) . '.0';

        return version_compare($installed, $nextMinor, '<');
    }

    /**
     * Check wildcard constraint (1.2.* or 1.*).
     */
    protected function checkWildcardConstraint(string $installed, string $constraint): bool
    {
        $pattern = str_replace('.', '\.', $constraint);
        $pattern = str_replace('*', '\d+', $pattern);
        $pattern = '/^' . $pattern . '/';

        return (bool) preg_match($pattern, $installed);
    }
}
