<?php

declare(strict_types=1);

namespace App\Services\PluginSDK\Templates;

use InvalidArgumentException;

/**
 * Template Factory
 *
 * Creates plugin templates by type.
 */
class TemplateFactory
{
    /**
     * Available template types.
     */
    protected static array $templates = [
        'basic' => BasicTemplate::class,
        'entity' => EntityTemplate::class,
        'api' => ApiTemplate::class,
        'marketplace' => MarketplaceTemplate::class,
    ];

    /**
     * Create a template by type.
     */
    public static function create(string $type, string $name, array $options = []): PluginTemplate
    {
        $type = strtolower($type);

        if (!isset(self::$templates[$type])) {
            throw new InvalidArgumentException(
                "Unknown template type: {$type}. Available types: " . implode(', ', array_keys(self::$templates))
            );
        }

        $class = self::$templates[$type];

        return new $class($name, $options);
    }

    /**
     * Get all available template types.
     */
    public static function getTypes(): array
    {
        return array_keys(self::$templates);
    }

    /**
     * Get template descriptions.
     */
    public static function getDescriptions(): array
    {
        $descriptions = [];

        foreach (self::$templates as $type => $class) {
            $template = new $class('Dummy');
            $descriptions[$type] = $template->getDescription();
        }

        return $descriptions;
    }

    /**
     * Check if template type exists.
     */
    public static function exists(string $type): bool
    {
        return isset(self::$templates[strtolower($type)]);
    }

    /**
     * Register a custom template.
     */
    public static function register(string $type, string $class): void
    {
        if (!is_subclass_of($class, PluginTemplate::class)) {
            throw new InvalidArgumentException(
                "Template class must extend " . PluginTemplate::class
            );
        }

        self::$templates[strtolower($type)] = $class;
    }
}
