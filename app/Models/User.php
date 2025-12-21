<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPermissions;
use App\Traits\HasTenant;
use App\Traits\HasAudit;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * User Model - Core authentication and authorization.
 *
 * Security Features:
 * - Role-based access control (RBAC) via HasPermissions trait
 * - Multi-tenant isolation via HasTenant trait
 * - Audit logging via HasAudit trait
 * - Account lockout protection
 * - Password policy enforcement
 * - 2FA support (optional)
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;
    use HasPermissions;
    use HasTenant;
    use HasAudit;

    /**
     * Account status constants.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';

    /**
     * Maximum failed login attempts before lockout.
     */
    public const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lockout duration in minutes.
     */
    public const LOCKOUT_DURATION = 15;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'tenant_id',
        'company_id',
        'branch_id',
        'phone',
        'avatar',
        'timezone',
        'locale',
        'settings',
        'two_factor_enabled',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'two_factor_enabled' => 'boolean',
            'must_change_password' => 'boolean',
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * The tenant column for multi-tenancy.
     */
    protected string $tenantColumn = 'tenant_id';

    // =========================================================================
    // Boot Methods
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        // Set default status on creation
        static::creating(function ($user) {
            if (empty($user->status)) {
                $user->status = self::STATUS_ACTIVE;
            }
        });

        // Assign default role on creation
        static::created(function ($user) {
            $defaultRole = Role::getDefault();
            if ($defaultRole) {
                $user->assignRole($defaultRole);
            }
        });
    }

    // =========================================================================
    // Account Status
    // =========================================================================

    /**
     * Check if account is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if account is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if account is locked out.
     */
    public function isLockedOut(): bool
    {
        if (!$this->locked_until) {
            return false;
        }
        return $this->locked_until->isFuture();
    }

    /**
     * Check if account can login.
     */
    public function canLogin(): bool
    {
        return $this->isActive() && !$this->isLockedOut() && $this->email_verified_at !== null;
    }

    /**
     * Suspend the account.
     */
    public function suspend(string $reason = null): self
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->save();

        // Log the action
        activity()
            ->performedOn($this)
            ->withProperties(['reason' => $reason])
            ->log('Account suspended');

        return $this;
    }

    /**
     * Activate the account.
     */
    public function activate(): self
    {
        $this->status = self::STATUS_ACTIVE;
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();

        return $this;
    }

    // =========================================================================
    // Login Security
    // =========================================================================

    /**
     * Record a failed login attempt.
     */
    public function recordFailedLogin(): self
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $this->locked_until = now()->addMinutes(self::LOCKOUT_DURATION);
            $this->save();

            // Log security event
            \Log::warning('User account locked due to failed login attempts', [
                'user_id' => $this->id,
                'email' => $this->email,
                'attempts' => $this->failed_login_attempts,
            ]);
        }

        return $this;
    }

    /**
     * Record a successful login.
     */
    public function recordSuccessfulLogin(string $ip = null): self
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);

        return $this;
    }

    /**
     * Get remaining lockout time in seconds.
     */
    public function getRemainingLockoutSeconds(): int
    {
        if (!$this->isLockedOut()) {
            return 0;
        }
        return $this->locked_until->diffInSeconds(now());
    }

    // =========================================================================
    // Password Security
    // =========================================================================

    /**
     * Check if password needs to be changed.
     */
    public function mustChangePassword(): bool
    {
        if ($this->must_change_password) {
            return true;
        }

        // Check password age policy
        $maxAge = config('auth.password_expiry_days', 0);
        if ($maxAge > 0 && $this->password_changed_at) {
            return $this->password_changed_at->addDays($maxAge)->isPast();
        }

        return false;
    }

    /**
     * Update password with security logging.
     */
    public function updatePassword(string $newPassword): self
    {
        $this->update([
            'password' => $newPassword,
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);

        // Log security event
        \Log::info('User password changed', [
            'user_id' => $this->id,
            'ip' => request()->ip(),
        ]);

        return $this;
    }

    // =========================================================================
    // Two-Factor Authentication
    // =========================================================================

    /**
     * Check if 2FA is enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && !empty($this->two_factor_secret);
    }

    /**
     * Enable 2FA.
     */
    public function enableTwoFactor(string $secret): self
    {
        $this->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
        ]);

        return $this;
    }

    /**
     * Disable 2FA.
     */
    public function disableTwoFactor(): self
    {
        $this->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        return $this;
    }

    // =========================================================================
    // Authorization Helpers
    // =========================================================================

    /**
     * Check if user is a superuser (bypasses all permission checks).
     */
    public function isSuperuser(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Get user's groups (for record rules).
     */
    public function getGroups(): array
    {
        return $this->getRoleSlugs();
    }

    /**
     * Get user's accessible tenant IDs.
     */
    public function getAccessibleTenants(): array
    {
        // Super admin can access all tenants
        if ($this->isSuperAdmin()) {
            return Tenant::pluck('id')->toArray();
        }

        $tenants = [];

        if ($this->tenant_id) {
            $tenants[] = $this->tenant_id;
        }

        // Add any additional tenant access from settings
        if (isset($this->settings['accessible_tenants'])) {
            $tenants = array_merge($tenants, $this->settings['accessible_tenants']);
        }

        return array_unique($tenants);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the company this user belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Tenant::class, 'company_id');
    }

    /**
     * Get the branch this user belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Tenant::class, 'branch_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope: Active users only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Users with a specific role.
     */
    public function scopeWithRole(Builder $query, string $roleSlug): Builder
    {
        return $query->whereHas('roles', fn($q) => $q->where('slug', $roleSlug));
    }

    /**
     * Scope: Users with a specific permission.
     */
    public function scopeWithPermission(Builder $query, string $permissionSlug): Builder
    {
        return $query->where(function ($q) use ($permissionSlug) {
            // Direct permission
            $q->whereHas('permissions', fn($pq) =>
                $pq->where('slug', $permissionSlug)->where('user_permissions.granted', true)
            );
            // Or through roles
            $q->orWhereHas('roles.permissions', fn($pq) =>
                $pq->where('slug', $permissionSlug)->where('role_permissions.granted', true)
            );
        });
    }

    /**
     * Scope: Verified users only.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Get user's display name.
     */
    public function getDisplayName(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Get user's initials.
     */
    public function getInitials(): string
    {
        $words = explode(' ', $this->name ?? '');
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials ?: strtoupper(substr($this->email, 0, 2));
    }

    /**
     * Get avatar URL.
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Gravatar fallback
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }

    /**
     * Convert to array for API.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->getAvatarUrl(),
            'roles' => $this->getRoleSlugs(),
            'permissions' => $this->getAllPermissionSlugs(),
            'is_admin' => $this->isAdmin(),
            'tenant_id' => $this->tenant_id,
            'company_id' => $this->company_id,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
        ];
    }
}
