<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * API Error Handler Middleware - Converts all exceptions to consistent JSON responses.
 *
 * Features:
 * - Consistent error response format
 * - Request ID tracking
 * - Detailed logging for debugging
 * - Production-safe error messages
 */
class ApiErrorHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * Handle exception and convert to JSON response.
     */
    protected function handleException(\Throwable $e, Request $request): JsonResponse
    {
        // Generate request ID for tracking
        $requestId = $request->header('X-Request-ID') ?? uniqid('req_');

        // Log the exception
        $this->logException($e, $request, $requestId);

        // Convert to appropriate response
        $response = match (true) {
            $e instanceof ApiException => $e->toResponse(),
            $e instanceof ValidationException => $this->handleValidation($e),
            $e instanceof AuthenticationException => $this->handleAuthentication($e),
            $e instanceof AuthorizationException => $this->handleAuthorization($e),
            $e instanceof ModelNotFoundException => $this->handleModelNotFound($e),
            $e instanceof NotFoundHttpException => $this->handleNotFound($e),
            $e instanceof TooManyRequestsHttpException => $this->handleTooManyRequests($e),
            $e instanceof HttpException => $this->handleHttpException($e),
            $e instanceof QueryException => $this->handleQueryException($e),
            default => $this->handleGenericException($e),
        };

        // Add request ID to response
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /**
     * Handle validation exception.
     */
    protected function handleValidation(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'validation_error',
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], 422);
    }

    /**
     * Handle authentication exception.
     */
    protected function handleAuthentication(AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'unauthenticated',
            'message' => 'Authentication required.',
        ], 401);
    }

    /**
     * Handle authorization exception.
     */
    protected function handleAuthorization(AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'forbidden',
            'message' => $e->getMessage() ?: 'You do not have permission to perform this action.',
        ], 403);
    }

    /**
     * Handle model not found exception.
     */
    protected function handleModelNotFound(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());

        return response()->json([
            'success' => false,
            'error' => 'not_found',
            'message' => "{$model} not found.",
        ], 404);
    }

    /**
     * Handle not found exception.
     */
    protected function handleNotFound(NotFoundHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'not_found',
            'message' => $e->getMessage() ?: 'Resource not found.',
        ], 404);
    }

    /**
     * Handle rate limit exception.
     */
    protected function handleTooManyRequests(TooManyRequestsHttpException $e): JsonResponse
    {
        $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

        return response()->json([
            'success' => false,
            'error' => 'rate_limit_exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => (int) $retryAfter,
        ], 429);
    }

    /**
     * Handle HTTP exception.
     */
    protected function handleHttpException(HttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'http_error',
            'message' => $e->getMessage() ?: 'An error occurred.',
        ], $e->getStatusCode());
    }

    /**
     * Handle database query exception.
     */
    protected function handleQueryException(QueryException $e): JsonResponse
    {
        // In production, hide SQL details
        $message = app()->isProduction()
            ? 'A database error occurred.'
            : $e->getMessage();

        return response()->json([
            'success' => false,
            'error' => 'database_error',
            'message' => $message,
        ], 500);
    }

    /**
     * Handle generic exception.
     */
    protected function handleGenericException(\Throwable $e): JsonResponse
    {
        // In production, hide exception details
        $message = app()->isProduction()
            ? 'An internal error occurred.'
            : $e->getMessage();

        $response = [
            'success' => false,
            'error' => 'server_error',
            'message' => $message,
        ];

        // In non-production, include debug info
        if (!app()->isProduction() && config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(10)->map(function ($frame) {
                    return [
                        'file' => $frame['file'] ?? null,
                        'line' => $frame['line'] ?? null,
                        'function' => $frame['function'] ?? null,
                        'class' => $frame['class'] ?? null,
                    ];
                })->toArray(),
            ];
        }

        return response()->json($response, 500);
    }

    /**
     * Log exception for debugging.
     */
    protected function logException(\Throwable $e, Request $request, string $requestId): void
    {
        $context = [
            'request_id' => $requestId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_agent' => $request->userAgent(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // Don't log expected exceptions at error level
        $expectedExceptions = [
            ValidationException::class,
            AuthenticationException::class,
            AuthorizationException::class,
            ModelNotFoundException::class,
            NotFoundHttpException::class,
            TooManyRequestsHttpException::class,
        ];

        if (in_array(get_class($e), $expectedExceptions, true)) {
            Log::info('API request exception', $context);
        } else {
            Log::error($e->getMessage(), array_merge($context, [
                'trace' => $e->getTraceAsString(),
            ]));
        }
    }
}
