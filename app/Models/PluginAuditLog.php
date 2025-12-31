<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plugin Audit Log - Security event tracking for plugins.
 *
 * @property int $id
 * @property string $plugin_slug
 * @property string $event_type
 * @property string $event_category
 * @property string $severity
 * @property string $message
 * @property array|null $context
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $user_id
 * @property string|null $user_type
 * @property int|null $tenant_id
 * @property string|null $request_id
 * @property float|null $execution_time_ms
 * @property int|null $memory_usage_bytes
 * @property \Carbon\Carbon $created_at
 */
class PluginAuditLog extends Model
{
    // Disable updated_at since audit logs are immutable
    public const UPDATED_AT = null;

    // =========================================================================
    // Event Types
    // =========================================================================
    public const EVENT_PERMISSION_GRANTED = 'permission_granted';
    public const EVENT_PERMISSION_DENIED = 'permission_denied';
    public const EVENT_PERMISSION_REVOKED = 'permission_revoked';
    public const EVENT_API_KEY_CREATED = 'api_key_created';
    public const EVENT_API_KEY_USED = 'api_key_used';
    public const EVENT_API_KEY_REVOKED = 'api_key_revoked';
    public const EVENT_RATE_LIMIT_HIT = 'rate_limit_hit';
    public const EVENT_SANDBOX_VIOLATION = 'sandbox_violation';
    public const EVENT_SCOPE_CHECK = 'scope_check';
    public const EVENT_HOOK_EXECUTED = 'hook_executed';
    public const EVENT_HOOK_FAILED = 'hook_failed';
    public const EVENT_ENTITY_ACCESS = 'entity_access';
    public const EVENT_NETWORK_REQUEST = 'network_request';
    public const EVENT_PLUGIN_ACTIVATED = 'plugin_activated';
    public const EVENT_PLUGIN_DEACTIVATED = 'plugin_deactivated';
    public const EVENT_PLUGIN_ERROR = 'plugin_error';

    // =========================================================================
    // Event Categories
    // =========================================================================
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_ACCESS = 'access';
    public const CATEGORY_PERFORMANCE = 'performance';
    public const CATEGORY_ERROR = 'error';
    public const CATEGORY_LIFECYCLE = 'lifecycle';
    public const CATEGORY_NETWORK = 'network';

    // =========================================================================
    // Severity Levels
    // =========================================================================
    public const SEVERITY_DEBUG = 'debug';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'plugin_slug',
        'event_type',
        'event_category',
        'severity',
        'message',
        'context',
        'ip_address',
        'user_agent',
        'user_id',
        'user_type',
        'tenant_id',
        'request_id',
        'execution_time_ms',
        'memory_usage_bytes',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'execution_time_ms' => 'float',
            'memory_usage_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class, 'plugin_slug', 'slug');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeForPlugin($query, string $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeMinSeverity($query, string $minSeverity)
    {
        $levels = [
            self::SEVERITY_DEBUG => 0,
            self::SEVERITY_INFO => 1,
            self::SEVERITY_WARNING => 2,
            self::SEVERITY_ERROR => 3,
            self::SEVERITY_CRITICAL => 4,
        ];

        $minLevel = $levels[$minSeverity] ?? 0;

        return $query->where(function ($q) use ($levels, $minLevel) {
            foreach ($levels as $severity => $level) {
                if ($level >= $minLevel) {
                    $q->orWhere('severity', $severity);
                }
            }
        });
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForRequest($query, string $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeSecurityEvents($query)
    {
        return $query->inCategory(self::CATEGORY_SECURITY);
    }

    public function scopeErrors($query)
    {
        return $query->minSeverity(self::SEVERITY_ERROR);
    }

    // =========================================================================
    // Factory Methods
    // =========================================================================

    /**
     * Log a security event.
     */
    public static function security(
        string $pluginSlug,
        string $eventType,
        string $message,
        array $context = [],
        string $severity = self::SEVERITY_INFO
    ): self {
        return self::log(
            $pluginSlug,
            $eventType,
            self::CATEGORY_SECURITY,
            $message,
            $context,
            $severity
        );
    }

    /**
     * Log an access event.
     */
    public static function access(
        string $pluginSlug,
        string $eventType,
        string $message,
        array $context = [],
        string $severity = self::SEVERITY_INFO
    ): self {
        return self::log(
            $pluginSlug,
            $eventType,
            self::CATEGORY_ACCESS,
            $message,
            $context,
            $severity
        );
    }

    /**
     * Log an error event.
     */
    public static function error(
        string $pluginSlug,
        string $eventType,
        string $message,
        array $context = [],
        string $severity = self::SEVERITY_ERROR
    ): self {
        return self::log(
            $pluginSlug,
            $eventType,
            self::CATEGORY_ERROR,
            $message,
            $context,
            $severity
        );
    }

    /**
     * Log a generic event.
     */
    public static function log(
        string $pluginSlug,
        string $eventType,
        string $category,
        string $message,
        array $context = [],
        string $severity = self::SEVERITY_INFO
    ): self {
        return static::create([
            'plugin_slug' => $pluginSlug,
            'event_type' => $eventType,
            'event_category' => $category,
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'user_id' => auth()->id(),
            'user_type' => auth()->user() ? get_class(auth()->user()) : null,
            'tenant_id' => app()->bound('App\Services\Tenant\TenantManager')
                ? app('App\Services\Tenant\TenantManager')->getCurrentTenantId()
                : null,
            'request_id' => request()?->header('X-Request-ID'),
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Check if this is a security-related event.
     */
    public function isSecurityEvent(): bool
    {
        return $this->event_category === self::CATEGORY_SECURITY;
    }

    /**
     * Check if this is an error.
     */
    public function isError(): bool
    {
        return in_array($this->severity, [self::SEVERITY_ERROR, self::SEVERITY_CRITICAL], true);
    }

    /**
     * Get severity as a numeric level.
     */
    public function getSeverityLevel(): int
    {
        return match ($this->severity) {
            self::SEVERITY_DEBUG => 0,
            self::SEVERITY_INFO => 1,
            self::SEVERITY_WARNING => 2,
            self::SEVERITY_ERROR => 3,
            self::SEVERITY_CRITICAL => 4,
            default => 1,
        };
    }
}
