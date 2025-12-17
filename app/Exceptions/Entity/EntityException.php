<?php

declare(strict_types=1);

namespace App\Exceptions\Entity;

use Exception;
use Throwable;

/**
 * Base exception for entity-related errors.
 */
class EntityException extends Exception
{
    /**
     * The entity name associated with this exception.
     */
    protected ?string $entityName = null;

    /**
     * Additional context data.
     */
    protected array $context = [];

    /**
     * Create a new entity exception.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $entityName = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->entityName = $entityName;
        $this->context = $context;
    }

    /**
     * Create exception with entity context.
     */
    public static function forEntity(string $name, string $message, array $context = []): static
    {
        return new static($message, 0, null, $name, $context);
    }

    /**
     * Get the entity name.
     */
    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    /**
     * Get additional context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convert to array for logging.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'entity_name' => $this->entityName,
            'context' => $this->context,
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
