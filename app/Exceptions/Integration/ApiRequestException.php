<?php

declare(strict_types=1);

namespace App\Exceptions\Integration;

/**
 * Exception for API request failures.
 */
class ApiRequestException extends IntegrationException
{
    protected ?array $response = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?array $response = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }
}
