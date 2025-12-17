<?php

declare(strict_types=1);

namespace App\Exceptions\Plugins;

/**
 * Exception thrown when a plugin is not found.
 */
class PluginNotFoundException extends PluginException
{
    /**
     * Create exception for missing plugin.
     */
    public static function withSlug(string $slug): static
    {
        return static::forPlugin($slug, "Plugin not found: {$slug}");
    }

    /**
     * Create exception for missing plugin file.
     */
    public static function fileMissing(string $slug, string $filePath): static
    {
        return static::forPlugin(
            $slug,
            "Plugin file not found: {$filePath}",
            ['file_path' => $filePath]
        );
    }

    /**
     * Create exception for missing plugin class.
     */
    public static function classMissing(string $slug, string $className): static
    {
        return static::forPlugin(
            $slug,
            "Plugin class not found: {$className}",
            ['class_name' => $className]
        );
    }
}
