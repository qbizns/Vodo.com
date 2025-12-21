<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base API Exception - Provides structured error responses for the API.
 *
 * Usage:
 * throw ApiException::validation('Invalid email format', ['email' => ['Must be valid email']]);
 * throw ApiException::notFound('User not found');
 * throw ApiException::forbidden('Access denied');
 * throw ApiException::serverError('Database connection failed');
 */
class ApiException extends \Exception
{
    protected string $errorCode;
    protected array $details;
    protected int $httpStatus;

    public function __construct(
        string $message,
        string $errorCode = 'error',
        int $httpStatus = 500,
        array $details = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->details = $details;
    }

    /**
     * Create a validation error exception.
     */
    public static function validation(string $message, array $errors = []): self
    {
        return new self($message, 'validation_error', 422, ['errors' => $errors]);
    }

    /**
     * Create a not found exception.
     */
    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self($message, 'not_found', 404);
    }

    /**
     * Create an unauthorized exception.
     */
    public static function unauthorized(string $message = 'Authentication required'): self
    {
        return new self($message, 'unauthorized', 401);
    }

    /**
     * Create a forbidden exception.
     */
    public static function forbidden(string $message = 'Access denied'): self
    {
        return new self($message, 'forbidden', 403);
    }

    /**
     * Create a bad request exception.
     */
    public static function badRequest(string $message, array $details = []): self
    {
        return new self($message, 'bad_request', 400, $details);
    }

    /**
     * Create a conflict exception.
     */
    public static function conflict(string $message, array $details = []): self
    {
        return new self($message, 'conflict', 409, $details);
    }

    /**
     * Create a rate limit exception.
     */
    public static function tooManyRequests(int $retryAfter = 60): self
    {
        return new self(
            'Too many requests. Please try again later.',
            'rate_limit_exceeded',
            429,
            ['retry_after' => $retryAfter]
        );
    }

    /**
     * Create a server error exception.
     */
    public static function serverError(string $message = 'Internal server error'): self
    {
        return new self($message, 'server_error', 500);
    }

    /**
     * Create a service unavailable exception.
     */
    public static function serviceUnavailable(string $message = 'Service temporarily unavailable'): self
    {
        return new self($message, 'service_unavailable', 503);
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the HTTP status code.
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Get additional details.
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Convert to JSON response.
     */
    public function toResponse(): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
        ];

        if (!empty($this->details)) {
            $response = array_merge($response, $this->details);
        }

        // Add request ID for tracking
        if ($requestId = request()->header('X-Request-ID')) {
            $response['request_id'] = $requestId;
        }

        return response()->json($response, $this->httpStatus);
    }

    /**
     * Render the exception as HTTP response.
     */
    public function render(): JsonResponse
    {
        return $this->toResponse();
    }
}
