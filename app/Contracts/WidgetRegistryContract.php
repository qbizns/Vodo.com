<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Widget Registry.
 *
 * Manages registration and retrieval of field widgets.
 */
interface WidgetRegistryContract
{
    /**
     * Register a widget.
     *
     * @param WidgetContract $widget The widget to register
     * @param string|null $pluginSlug Owner plugin slug
     * @return self
     */
    public function register(WidgetContract $widget, ?string $pluginSlug = null): self;

    /**
     * Unregister a widget.
     *
     * @param string $name Widget name
     * @return bool
     */
    public function unregister(string $name): bool;

    /**
     * Get a widget by name.
     *
     * @param string $name Widget name
     * @return WidgetContract|null
     */
    public function get(string $name): ?WidgetContract;

    /**
     * Check if a widget exists.
     *
     * @param string $name Widget name
     */
    public function has(string $name): bool;

    /**
     * Get all registered widgets.
     *
     * @return Collection<string, WidgetContract>
     */
    public function all(): Collection;

    /**
     * Get widgets that support a field type.
     *
     * @param string $fieldType Field type
     * @return Collection<string, WidgetContract>
     */
    public function getForType(string $fieldType): Collection;

    /**
     * Get the default widget for a field type.
     *
     * @param string $fieldType Field type
     * @return WidgetContract|null
     */
    public function getDefault(string $fieldType): ?WidgetContract;

    /**
     * Set the default widget for a field type.
     *
     * @param string $fieldType Field type
     * @param string $widgetName Widget name
     * @return self
     */
    public function setDefault(string $fieldType, string $widgetName): self;

    /**
     * Format a value using a widget.
     *
     * @param string $widgetName Widget name
     * @param mixed $value Value to format
     * @param array $options Widget options
     * @return string
     */
    public function format(string $widgetName, mixed $value, array $options = []): string;

    /**
     * Parse a value using a widget.
     *
     * @param string $widgetName Widget name
     * @param mixed $value Value to parse
     * @param array $options Widget options
     * @return mixed
     */
    public function parse(string $widgetName, mixed $value, array $options = []): mixed;
}
