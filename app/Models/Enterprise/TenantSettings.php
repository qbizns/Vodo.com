<?php

declare(strict_types=1);

namespace App\Models\Enterprise;

use Illuminate\Database\Eloquent\Model;

class TenantSettings extends Model
{
    protected $table = 'tenant_settings';

    protected $fillable = [
        'tenant_id',
        'plan',
        'status',
        'max_users',
        'max_plugins',
        'storage_limit_bytes',
        'storage_used_bytes',
        'api_rate_limit',
        'audit_logging_enabled',
        'audit_retention_days',
        'advanced_analytics_enabled',
        'custom_domain_enabled',
        'sso_enabled',
        'sso_config',
        'ip_whitelist_enabled',
        'ip_whitelist',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'max_users' => 'integer',
            'max_plugins' => 'integer',
            'storage_limit_bytes' => 'integer',
            'storage_used_bytes' => 'integer',
            'api_rate_limit' => 'integer',
            'audit_logging_enabled' => 'boolean',
            'audit_retention_days' => 'integer',
            'advanced_analytics_enabled' => 'boolean',
            'custom_domain_enabled' => 'boolean',
            'sso_enabled' => 'boolean',
            'sso_config' => 'encrypted:array',
            'ip_whitelist_enabled' => 'boolean',
            'ip_whitelist' => 'array',
            'metadata' => 'array',
        ];
    }

    public function getStorageUsagePercentage(): float
    {
        if ($this->storage_limit_bytes === 0) {
            return 0;
        }

        return round(($this->storage_used_bytes / $this->storage_limit_bytes) * 100, 2);
    }

    public function getStorageLimitFormatted(): string
    {
        return $this->formatBytes($this->storage_limit_bytes);
    }

    public function getStorageUsedFormatted(): string
    {
        return $this->formatBytes($this->storage_used_bytes);
    }

    public function hasStorageAvailable(int $bytes): bool
    {
        return ($this->storage_used_bytes + $bytes) <= $this->storage_limit_bytes;
    }

    public function incrementStorage(int $bytes): void
    {
        $this->increment('storage_used_bytes', $bytes);
    }

    public function decrementStorage(int $bytes): void
    {
        $this->decrement('storage_used_bytes', min($bytes, $this->storage_used_bytes));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function isIpAllowed(string $ip): bool
    {
        if (!$this->ip_whitelist_enabled) {
            return true;
        }

        $whitelist = $this->ip_whitelist ?? [];

        if (empty($whitelist)) {
            return true;
        }

        foreach ($whitelist as $allowed) {
            if ($this->ipMatches($ip, $allowed)) {
                return true;
            }
        }

        return false;
    }

    public function hasFeature(string $feature): bool
    {
        return match ($feature) {
            'advanced_analytics' => $this->advanced_analytics_enabled,
            'custom_domain' => $this->custom_domain_enabled,
            'sso' => $this->sso_enabled,
            'audit_logging' => $this->audit_logging_enabled,
            'ip_whitelist' => $this->ip_whitelist_enabled,
            default => false,
        };
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function ipMatches(string $ip, string $pattern): bool
    {
        if ($ip === $pattern) {
            return true;
        }

        // CIDR notation support
        if (str_contains($pattern, '/')) {
            return $this->ipInCidr($ip, $pattern);
        }

        // Wildcard support (e.g., 192.168.1.*)
        if (str_contains($pattern, '*')) {
            $regex = str_replace(['*', '.'], ['.*', '\\.'], $pattern);
            return preg_match("/^{$regex}$/", $ip) === 1;
        }

        return false;
    }

    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    public function scopeByPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }
}
