<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void extend(string $viewName, array $modification, ?string $pluginSlug = null, int $priority = 10)
 * @method static void extendMultiple(string $viewName, array $modifications, ?string $pluginSlug = null, int $priority = 10)
 * @method static array getExtensions(string $viewName)
 * @method static bool hasExtensions(string $viewName)
 * @method static void addToSlot(string $viewName, string $slotName, string|callable $content, ?string $pluginSlug = null, int $priority = 10)
 * @method static array getSlotContents(string $viewName, string $slotName)
 * @method static void replace(string $viewName, string $replacementView, ?string $pluginSlug = null, int $priority = 10)
 * @method static string|null getReplacement(string $viewName)
 * @method static void composer(string|array $views, callable $composer, ?string $pluginSlug = null)
 * @method static void removePluginExtensions(string $pluginSlug)
 * @method static array getAllExtensions()
 * @method static array getStats()
 * @method static void clear()
 *
 * @see \App\Services\View\ViewExtensionRegistry
 */
class ViewExtension extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'view.extensions';
    }
}
