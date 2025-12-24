<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for Widget implementations.
 *
 * Widgets are UI components that render and handle field data.
 * Each field type can have multiple widget options.
 */
interface WidgetContract
{
    /**
     * Get the widget's unique identifier.
     */
    public function getName(): string;

    /**
     * Get the human-readable label.
     */
    public function getLabel(): string;

    /**
     * Get field types this widget supports.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array;

    /**
     * Check if this widget supports a field type.
     *
     * @param string $type Field type
     */
    public function supports(string $type): bool;

    /**
     * Get the Blade component name for rendering.
     */
    public function getComponent(): string;

    /**
     * Get default widget options.
     */
    public function getDefaultOptions(): array;

    /**
     * Format a value for display.
     *
     * @param mixed $value Raw value
     * @param array $options Widget options
     * @return string Formatted value
     */
    public function format(mixed $value, array $options = []): string;

    /**
     * Parse a value from input.
     *
     * @param mixed $value Input value
     * @param array $options Widget options
     * @return mixed Parsed value
     */
    public function parse(mixed $value, array $options = []): mixed;

    /**
     * Validate a value.
     *
     * @param mixed $value Value to validate
     * @param array $options Widget options
     * @return array Validation errors (empty if valid)
     */
    public function validate(mixed $value, array $options = []): array;

    /**
     * Get JavaScript dependencies for this widget.
     *
     * @return array<string>
     */
    public function getJsDependencies(): array;

    /**
     * Get CSS dependencies for this widget.
     *
     * @return array<string>
     */
    public function getCssDependencies(): array;
}
