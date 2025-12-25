<?php

declare(strict_types=1);

namespace App\Exceptions\Integration;

/**
 * Exception for temporary/transient errors (eligible for retry).
 */
class TemporaryException extends IntegrationException
{
}
