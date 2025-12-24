<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for View Renderer implementations.
 *
 * Renderers are responsible for converting view definitions
 * and data into rendered HTML output.
 */
interface ViewRendererContract
{
    /**
     * Render a view with the given data.
     *
     * @param array $definition The view definition
     * @param array $data The data to render
     * @param array $context Additional context (user, permissions, etc.)
     * @return string Rendered HTML
     */
    public function render(array $definition, array $data = [], array $context = []): string;

    /**
     * Get the view type this renderer handles.
     */
    public function getViewType(): string;

    /**
     * Check if this renderer can handle the given view definition.
     */
    public function canRender(array $definition): bool;

    /**
     * Pre-process data before rendering.
     *
     * @param array $definition The view definition
     * @param array $data Raw data
     * @return array Processed data
     */
    public function prepareData(array $definition, array $data): array;

    /**
     * Get the Blade view path for this renderer.
     */
    public function getViewPath(): string;
}
