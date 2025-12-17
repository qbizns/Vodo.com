<?php

declare(strict_types=1);

namespace App\Exceptions\Plugins;

use Exception;
use Throwable;

/**
 * Base exception for all plugin-related errors.
 */
class PluginException extends Exception
{
    /**
     * The plugin slug associated with this exception.
     */
    protected ?string $pluginSlug = null;

    /**
     * Additional context data.
     */
    protected array $context = [];

    /**
     * Create a new plugin exception.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $pluginSlug = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->pluginSlug = $pluginSlug;
        $this->context = $context;
    }

    /**
     * Get the plugin slug.
     */
    public function getPluginSlug(): ?string
    {
        return $this->pluginSlug;
    }

    /**
     * Get additional context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create exception with plugin context.
     */
    public static function forPlugin(string $slug, string $message, array $context = []): static
    {
        return new static($message, 0, null, $slug, $context);
    }

    /**
     * Convert to array for logging.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'plugin_slug' => $this->pluginSlug,
            'context' => $this->context,
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
