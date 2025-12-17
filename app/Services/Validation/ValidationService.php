<?php

declare(strict_types=1);

namespace App\Services\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Centralized validation service for plugin system.
 * 
 * Provides reusable validation rules and methods for:
 * - Plugin manifests
 * - Entity definitions
 * - Field configurations
 * - API requests
 */
class ValidationService
{
    /**
     * Validate plugin manifest data.
     *
     * @throws ValidationException
     */
    public function validateManifest(array $data): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/'],
            'version' => ['required', 'string', 'regex:/^\d+\.\d+(\.\d+)?(-[a-z0-9]+)?$/i'],
            'description' => ['nullable', 'string', 'max:1000'],
            'author' => ['nullable', 'string', 'max:255'],
            'author_url' => ['nullable', 'url', 'max:255'],
            'main' => ['required', 'string', 'regex:/^[A-Za-z][A-Za-z0-9]*Plugin\.php$/'],
            'requires' => ['nullable', 'array'],
            'requires.php' => ['nullable', 'string', 'regex:/^\d+\.\d+(\.\d+)?$/'],
            'requires.laravel' => ['nullable', 'string', 'regex:/^\d+\.\d+(\.\d+)?$/'],
        ];

        $validator = Validator::make($data, $rules, [
            'slug.regex' => 'Plugin slug must be lowercase alphanumeric with hyphens.',
            'version.regex' => 'Version must be in format X.Y or X.Y.Z (optionally with suffix like -beta).',
            'main.regex' => 'Main file must be a valid PHP class filename ending in Plugin.php.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate entity definition.
     *
     * @throws ValidationException
     */
    public function validateEntityDefinition(array $data): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z][a-z0-9\-]*$/'],
            'table_name' => ['nullable', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'labels' => ['nullable', 'array'],
            'labels.singular' => ['nullable', 'string', 'max:64'],
            'labels.plural' => ['nullable', 'string', 'max:64'],
            'config' => ['nullable', 'array'],
            'supports' => ['nullable', 'array'],
            'supports.*' => ['string', 'in:title,content,author,thumbnail,excerpt,comments,revisions,custom-fields'],
            'icon' => ['nullable', 'string', 'max:64'],
            'menu_position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_public' => ['nullable', 'boolean'],
            'has_archive' => ['nullable', 'boolean'],
            'show_in_menu' => ['nullable', 'boolean'],
            'show_in_rest' => ['nullable', 'boolean'],
            'is_hierarchical' => ['nullable', 'boolean'],
            'fields' => ['nullable', 'array'],
        ];

        $validator = Validator::make($data, $rules, [
            'name.regex' => 'Entity name must be lowercase alphanumeric with underscores, starting with a letter.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validate individual fields if present
        if (!empty($data['fields'])) {
            foreach ($data['fields'] as $slug => $fieldConfig) {
                $this->validateFieldDefinition($fieldConfig, is_numeric($slug) ? null : $slug);
            }
        }

        return $validator->validated();
    }

    /**
     * Validate field definition.
     *
     * @throws ValidationException
     */
    public function validateFieldDefinition(array $data, ?string $slug = null): array
    {
        // If slug not provided as key, it should be in the data
        if ($slug === null && empty($data['slug']) && empty($data['name'])) {
            throw ValidationException::withMessages([
                'slug' => ['Field must have a slug or name.'],
            ]);
        }

        $rules = [
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'name' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:32'],
            'config' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
            'description' => ['nullable', 'string', 'max:500'],
            'help' => ['nullable', 'string', 'max:500'],
            'default' => ['nullable'],
            'default_value' => ['nullable'],
            'required' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'unique' => ['nullable', 'boolean'],
            'is_unique' => ['nullable', 'boolean'],
            'searchable' => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
            'filterable' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
            'sortable' => ['nullable', 'boolean'],
            'is_sortable' => ['nullable', 'boolean'],
            'show_in_list' => ['nullable', 'boolean'],
            'list' => ['nullable', 'boolean'],
            'show_in_form' => ['nullable', 'boolean'],
            'form' => ['nullable', 'boolean'],
            'show_in_rest' => ['nullable', 'boolean'],
            'rest' => ['nullable', 'boolean'],
            'group' => ['nullable', 'string', 'max:64'],
            'form_group' => ['nullable', 'string', 'max:64'],
            'width' => ['nullable', 'string', 'in:full,half,third,quarter'],
            'form_width' => ['nullable', 'string', 'in:full,half,third,quarter'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate API endpoint configuration.
     *
     * @throws ValidationException
     */
    public function validateApiEndpoint(array $data): array
    {
        $rules = [
            'method' => ['required', 'string', 'in:GET,POST,PUT,PATCH,DELETE'],
            'path' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-\/\{\}]+$/i'],
            'handler' => ['required', 'string'],
            'middleware' => ['nullable', 'array'],
            'middleware.*' => ['string'],
            'rate_limit' => ['nullable', 'string', 'regex:/^\d+:\d+$/'],
            'auth_required' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ];

        $validator = Validator::make($data, $rules, [
            'path.regex' => 'API path must be lowercase alphanumeric with hyphens, slashes, and parameter placeholders.',
            'rate_limit.regex' => 'Rate limit must be in format "requests:seconds" (e.g., "60:60").',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate shortcode definition.
     *
     * @throws ValidationException
     */
    public function validateShortcode(array $data): array
    {
        $rules = [
            'tag' => ['required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_]*$/'],
            'handler' => ['required'],
            'description' => ['nullable', 'string', 'max:500'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.name' => ['required', 'string'],
            'attributes.*.type' => ['nullable', 'string'],
            'attributes.*.default' => ['nullable'],
            'attributes.*.required' => ['nullable', 'boolean'],
            'supports_content' => ['nullable', 'boolean'],
            'cacheable' => ['nullable', 'boolean'],
            'cache_ttl' => ['nullable', 'integer', 'min:0'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate menu item configuration.
     *
     * @throws ValidationException
     */
    public function validateMenuItem(array $data): array
    {
        $rules = [
            'id' => ['required', 'string', 'max:64'],
            'label' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'route' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
            'parent' => ['nullable', 'string', 'max:64'],
            'position' => ['nullable', 'integer'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'badge' => ['nullable', 'array'],
            'badge.text' => ['nullable', 'string', 'max:32'],
            'badge.color' => ['nullable', 'string', 'max:32'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Sanitize a string for safe display.
     */
    public function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $value;
    }

    /**
     * Sanitize HTML content (allow safe tags).
     */
    public function sanitizeHtml(string $html, array $allowedTags = null): string
    {
        $allowedTags = $allowedTags ?? [
            'p', 'br', 'b', 'i', 'u', 'strong', 'em', 'a', 'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre',
            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img', 'hr', 'span', 'div',
        ];

        return strip_tags($html, $allowedTags);
    }

    /**
     * Validate and sanitize a slug.
     */
    public function sanitizeSlug(string $value): string
    {
        // Convert to lowercase
        $value = strtolower($value);
        
        // Replace spaces and underscores with hyphens
        $value = preg_replace('/[\s_]+/', '-', $value);
        
        // Remove any character that isn't alphanumeric or hyphen
        $value = preg_replace('/[^a-z0-9\-]/', '', $value);
        
        // Remove multiple consecutive hyphens
        $value = preg_replace('/-+/', '-', $value);
        
        // Trim hyphens from start and end
        return trim($value, '-');
    }
}
