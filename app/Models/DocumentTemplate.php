<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Document Template - Reusable templates for generating documents.
 * 
 * Supports:
 * - PDF documents (invoices, reports)
 * - Excel exports
 * - HTML documents
 * - Email templates
 */
class DocumentTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'entity_name',
        'document_type',
        'format',
        'content',
        'header',
        'footer',
        'styles',
        'variables',
        'config',
        'is_default',
        'is_active',
        'plugin_slug',
    ];

    protected $casts = [
        'variables' => 'array',
        'config' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Document formats.
     */
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_EXCEL = 'excel';
    public const FORMAT_HTML = 'html';
    public const FORMAT_EMAIL = 'email';
    public const FORMAT_WORD = 'word';

    /**
     * Document types.
     */
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_QUOTE = 'quote';
    public const TYPE_ORDER = 'order';
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_REPORT = 'report';
    public const TYPE_LETTER = 'letter';
    public const TYPE_LABEL = 'label';

    /**
     * Scope for entity templates.
     */
    public function scopeForEntity($query, string $entityName)
    {
        return $query->where('entity_name', $entityName);
    }

    /**
     * Scope for document type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope for format.
     */
    public function scopeInFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get defined variables.
     */
    public function getDefinedVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Get configuration option.
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
