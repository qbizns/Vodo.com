<?php

declare(strict_types=1);

namespace App\Exceptions\Security;

use Exception;
use Throwable;

/**
 * Base exception for security-related errors.
 */
class SecurityException extends Exception
{
    /**
     * The security violation type.
     */
    protected string $violationType = 'unknown';

    /**
     * Additional context about the security issue.
     */
    protected array $context = [];

    /**
     * The IP address associated with the violation (if available).
     */
    protected ?string $ipAddress = null;

    /**
     * Create a new security exception.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->ipAddress = request()?->ip();
    }

    /**
     * Create exception for path traversal attempt.
     */
    public static function pathTraversal(string $attemptedPath, string $allowedBase): static
    {
        $exception = new static(
            "Path traversal attempt detected",
            403,
            null,
            [
                'attempted_path' => $attemptedPath,
                'allowed_base' => $allowedBase,
            ]
        );
        $exception->violationType = 'path_traversal';
        return $exception;
    }

    /**
     * Create exception for rate limit exceeded.
     */
    public static function rateLimitExceeded(string $identifier, int $limit, int $window): static
    {
        $exception = new static(
            "Rate limit exceeded",
            429,
            null,
            [
                'identifier' => $identifier,
                'limit' => $limit,
                'window_seconds' => $window,
            ]
        );
        $exception->violationType = 'rate_limit';
        return $exception;
    }

    /**
     * Create exception for invalid CSRF token.
     */
    public static function invalidCsrfToken(): static
    {
        $exception = new static("Invalid or missing CSRF token", 403);
        $exception->violationType = 'csrf';
        return $exception;
    }

    /**
     * Create exception for unauthorized plugin operation.
     */
    public static function unauthorizedPluginOperation(string $pluginSlug, string $operation): static
    {
        $exception = new static(
            "Unauthorized plugin operation: {$operation}",
            403,
            null,
            [
                'plugin_slug' => $pluginSlug,
                'operation' => $operation,
            ]
        );
        $exception->violationType = 'unauthorized_operation';
        return $exception;
    }

    /**
     * Create exception for malicious input detected.
     */
    public static function maliciousInput(string $field, string $reason): static
    {
        $exception = new static(
            "Potentially malicious input detected in field: {$field}",
            400,
            null,
            [
                'field' => $field,
                'reason' => $reason,
            ]
        );
        $exception->violationType = 'malicious_input';
        return $exception;
    }

    /**
     * Get the violation type.
     */
    public function getViolationType(): string
    {
        return $this->violationType;
    }

    /**
     * Get additional context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the IP address associated with the violation.
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Convert to array for logging.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'violation_type' => $this->violationType,
            'context' => $this->context,
            'ip_address' => $this->ipAddress,
            'code' => $this->getCode(),
        ];
    }
}
