<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for Document Template Engine.
 *
 * Manages document templates for PDFs, invoices, quotes, etc.
 */
interface DocumentTemplateContract
{
    /**
     * Register a document template.
     *
     * @param string $name Template name
     * @param array $config Template configuration
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function register(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Get a template configuration.
     *
     * @param string $name Template name
     * @return array|null
     */
    public function get(string $name): ?array;

    /**
     * Get all templates.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Render a document.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @param array $options Render options
     * @return string Rendered HTML
     */
    public function render(string $template, array $data = [], array $options = []): string;

    /**
     * Generate PDF from template.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @param array $options PDF options
     * @return string PDF content or file path
     */
    public function toPdf(string $template, array $data = [], array $options = []): string;

    /**
     * Preview document without saving.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return array Preview data (html, metadata)
     */
    public function preview(string $template, array $data = []): array;

    /**
     * Get template variables schema.
     *
     * @param string $template Template name
     * @return array Variable definitions
     */
    public function getVariables(string $template): array;

    /**
     * Register a document layout.
     *
     * @param string $name Layout name
     * @param array $config Layout configuration
     * @return self
     */
    public function registerLayout(string $name, array $config): self;

    /**
     * Get available layouts.
     *
     * @return Collection
     */
    public function getLayouts(): Collection;
}
