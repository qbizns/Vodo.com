<?php

declare(strict_types=1);

namespace App\Exceptions\Plugins;

/**
 * Sandbox Violation Exception - Thrown when a plugin violates sandbox rules.
 */
class SandboxViolationException extends PluginException
{
    protected string $pluginSlug;
    protected string $violationType;
    protected array $details;

    public function __construct(
        string $message,
        string $pluginSlug,
        string $violationType,
        array $details = [],
        int $code = 429,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->pluginSlug = $pluginSlug;
        $this->violationType = $violationType;
        $this->details = $details;
    }

    /**
     * Create exception for rate limit exceeded.
     */
    public static function rateLimitExceeded(string $pluginSlug, string $limitType, int $limit): self
    {
        return new self(
            "Plugin '{$pluginSlug}' exceeded rate limit for {$limitType} ({$limit} per period)",
            $pluginSlug,
            'rate_limit',
            [
                'limit_type' => $limitType,
                'limit' => $limit,
            ]
        );
    }

    /**
     * Create exception for memory limit exceeded.
     */
    public static function memoryLimitExceeded(string $pluginSlug, int $limitMb, float $usedMb): self
    {
        return new self(
            "Plugin '{$pluginSlug}' exceeded memory limit ({$usedMb}MB used, {$limitMb}MB allowed)",
            $pluginSlug,
            'memory_limit',
            [
                'limit_mb' => $limitMb,
                'used_mb' => $usedMb,
            ]
        );
    }

    /**
     * Create exception for execution time exceeded.
     */
    public static function executionTimeExceeded(string $pluginSlug, int $limitSeconds, float $elapsed): self
    {
        return new self(
            "Plugin '{$pluginSlug}' exceeded execution time limit ({$elapsed}s elapsed, {$limitSeconds}s allowed)",
            $pluginSlug,
            'execution_time',
            [
                'limit_seconds' => $limitSeconds,
                'elapsed_seconds' => $elapsed,
            ],
            408
        );
    }

    /**
     * Create exception for storage limit exceeded.
     */
    public static function storageLimitExceeded(string $pluginSlug, int $limitMb): self
    {
        return new self(
            "Plugin '{$pluginSlug}' exceeded storage limit ({$limitMb}MB)",
            $pluginSlug,
            'storage_limit',
            [
                'limit_mb' => $limitMb,
            ]
        );
    }

    /**
     * Create exception for network limit exceeded.
     */
    public static function networkLimitExceeded(string $pluginSlug, int $limitBytes): self
    {
        $limitMb = round($limitBytes / 1024 / 1024, 2);
        return new self(
            "Plugin '{$pluginSlug}' exceeded network transfer limit ({$limitMb}MB)",
            $pluginSlug,
            'network_limit',
            [
                'limit_bytes' => $limitBytes,
                'limit_mb' => $limitMb,
            ]
        );
    }

    /**
     * Create exception for blocked domain.
     */
    public static function domainNotAllowed(string $pluginSlug, string $domain): self
    {
        return new self(
            "Plugin '{$pluginSlug}' attempted to access blocked domain: {$domain}",
            $pluginSlug,
            'domain_blocked',
            [
                'domain' => $domain,
            ],
            403
        );
    }

    /**
     * Create exception for blocked plugin.
     */
    public static function pluginBlocked(string $pluginSlug): self
    {
        return new self(
            "Plugin '{$pluginSlug}' is temporarily blocked due to repeated violations",
            $pluginSlug,
            'plugin_blocked',
            [],
            503
        );
    }

    /**
     * Get the plugin slug.
     */
    public function getPluginSlug(): string
    {
        return $this->pluginSlug;
    }

    /**
     * Get the violation type.
     */
    public function getViolationType(): string
    {
        return $this->violationType;
    }

    /**
     * Get the violation details.
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Convert to array for logging.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'plugin' => $this->pluginSlug,
            'violation_type' => $this->violationType,
            'details' => $this->details,
            'code' => $this->getCode(),
        ];
    }
}
