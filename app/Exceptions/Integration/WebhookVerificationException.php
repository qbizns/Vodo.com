<?php

declare(strict_types=1);

namespace App\Exceptions\Integration;

/**
 * Exception when webhook signature verification fails.
 */
class WebhookVerificationException extends IntegrationException
{
    public function __construct(string $message = 'Webhook verification failed', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
