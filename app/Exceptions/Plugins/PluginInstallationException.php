<?php

declare(strict_types=1);

namespace App\Exceptions\Plugins;

/**
 * Exception thrown when plugin installation fails.
 */
class PluginInstallationException extends PluginException
{
    /**
     * Create exception for invalid plugin archive.
     */
    public static function invalidArchive(string $reason): static
    {
        return new static("Invalid plugin archive: {$reason}");
    }

    /**
     * Create exception for missing manifest.
     */
    public static function missingManifest(string $path): static
    {
        return new static("Plugin manifest (plugin.json) not found in: {$path}");
    }

    /**
     * Create exception for invalid manifest.
     */
    public static function invalidManifest(string $slug, string $reason): static
    {
        return static::forPlugin($slug, "Invalid plugin manifest: {$reason}");
    }

    /**
     * Create exception for extraction failure.
     */
    public static function extractionFailed(string $reason): static
    {
        return new static("Failed to extract plugin archive: {$reason}");
    }

    /**
     * Create exception for already installed plugin.
     */
    public static function alreadyInstalled(string $slug): static
    {
        return static::forPlugin($slug, "Plugin is already installed: {$slug}");
    }

    /**
     * Create exception for dependency not met.
     */
    public static function dependencyNotMet(string $slug, string $dependency, string $requiredVersion): static
    {
        return static::forPlugin(
            $slug,
            "Required dependency not met: {$dependency} ({$requiredVersion})",
            [
                'dependency' => $dependency,
                'required_version' => $requiredVersion,
            ]
        );
    }
}
