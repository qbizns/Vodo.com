<?php

declare(strict_types=1);

namespace App\Exceptions\Integration;

/**
 * Exception when trying to execute an inactive flow.
 */
class FlowNotActiveException extends IntegrationException
{
}
