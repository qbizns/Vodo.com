<?php

declare(strict_types=1);

namespace App\Exceptions\Integration;

/**
 * Exception when rate limit is exceeded.
 */
class RateLimitException extends IntegrationException
{
    protected int $retryAfter;

    public function __construct(string $message = '', int $retryAfter = 60, ?\Throwable $previous = null)
    {
        parent::__construct($message, 429, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
