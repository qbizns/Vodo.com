<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'endpoint_id',
        'api_key_id',
        'user_id',
        'method',
        'path',
        'ip_address',
        'user_agent',
        'status_code',
        'response_time_ms',
        'request_size',
        'response_size',
        'request_headers',
        'request_params',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_params' => 'array',
        'created_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'endpoint_id');
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // Factory Methods
    // =========================================================================

    /**
     * Create log from request/response
     */
    public static function logRequest(
        \Illuminate\Http\Request $request,
        $response,
        ?ApiEndpoint $endpoint = null,
        ?ApiKey $apiKey = null,
        ?int $userId = null,
        float $startTime = null
    ): self {
        $responseTime = $startTime 
            ? (int) ((microtime(true) - $startTime) * 1000) 
            : 0;

        $statusCode = method_exists($response, 'getStatusCode') 
            ? $response->getStatusCode() 
            : 200;

        $responseContent = method_exists($response, 'getContent')
            ? $response->getContent()
            : '';

        return static::create([
            'endpoint_id' => $endpoint?->id,
            'api_key_id' => $apiKey?->id,
            'user_id' => $userId,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'status_code' => $statusCode,
            'response_time_ms' => $responseTime,
            'request_size' => strlen($request->getContent()),
            'response_size' => strlen($responseContent),
            'request_headers' => static::sanitizeHeaders($request->headers->all()),
            'request_params' => static::sanitizeParams($request->all()),
            'error_message' => $statusCode >= 400 ? static::extractError($response) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Sanitize headers (remove sensitive data)
     */
    protected static function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-api-secret'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * Sanitize params (remove sensitive data)
     */
    protected static function sanitizeParams(array $params): array
    {
        $sensitiveParams = ['password', 'password_confirmation', 'secret', 'token', 'api_key'];
        
        foreach ($sensitiveParams as $param) {
            if (isset($params[$param])) {
                $params[$param] = '[REDACTED]';
            }
        }

        return $params;
    }

    /**
     * Extract error message from response
     */
    protected static function extractError($response): ?string
    {
        if (!method_exists($response, 'getContent')) {
            return null;
        }

        $content = $response->getContent();
        $data = json_decode($content, true);

        if (isset($data['error'])) {
            return is_string($data['error']) ? $data['error'] : json_encode($data['error']);
        }

        if (isset($data['message'])) {
            return $data['message'];
        }

        return substr($content, 0, 500);
    }

    // =========================================================================
    // Query Helpers
    // =========================================================================

    /**
     * Check if request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    /**
     * Check if request was client error
     */
    public function isClientError(): bool
    {
        return $this->status_code >= 400 && $this->status_code < 500;
    }

    /**
     * Check if request was server error
     */
    public function isServerError(): bool
    {
        return $this->status_code >= 500;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereBetween('status_code', [200, 299]);
    }

    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('status_code', '>=', 400);
    }

    public function scopeClientErrors(Builder $query): Builder
    {
        return $query->whereBetween('status_code', [400, 499]);
    }

    public function scopeServerErrors(Builder $query): Builder
    {
        return $query->where('status_code', '>=', 500);
    }

    public function scopeSince(Builder $query, $datetime): Builder
    {
        return $query->where('created_at', '>=', $datetime);
    }

    public function scopeForEndpoint(Builder $query, int $endpointId): Builder
    {
        return $query->where('endpoint_id', $endpointId);
    }

    public function scopeSlow(Builder $query, int $thresholdMs = 1000): Builder
    {
        return $query->where('response_time_ms', '>', $thresholdMs);
    }

    // =========================================================================
    // Statistics
    // =========================================================================

    /**
     * Get aggregate statistics
     */
    public static function getStats(int $days = 7, ?int $endpointId = null): array
    {
        $query = static::query()->where('created_at', '>=', now()->subDays($days));
        
        if ($endpointId) {
            $query->where('endpoint_id', $endpointId);
        }

        $total = $query->count();
        $successful = (clone $query)->successful()->count();
        $errors = (clone $query)->errors()->count();
        $avgTime = (clone $query)->avg('response_time_ms');

        return [
            'total_requests' => $total,
            'successful' => $successful,
            'errors' => $errors,
            'error_rate' => $total > 0 ? round(($errors / $total) * 100, 2) : 0,
            'avg_response_time_ms' => round($avgTime ?? 0, 2),
        ];
    }

    /**
     * Get hourly statistics
     */
    public static function getHourlyStats(int $hours = 24, ?int $endpointId = null): array
    {
        $query = static::query()
            ->where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(response_time_ms) as avg_time')
            ->selectRaw('SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors')
            ->groupBy('hour')
            ->orderBy('hour');

        if ($endpointId) {
            $query->where('endpoint_id', $endpointId);
        }

        return $query->get()->toArray();
    }
}
