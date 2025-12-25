<?php

declare(strict_types=1);

namespace App\Exceptions\Integration;

/**
 * Exception for validation failures.
 */
class ValidationException extends IntegrationException
{
    protected array $errors;

    public function __construct(string $message = '', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
