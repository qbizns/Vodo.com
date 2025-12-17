<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * ConfigVersion Model - Git-like versioning for business configurations.
 * 
 * Tracks versions of:
 * - Entity definitions
 * - Workflow definitions
 * - View definitions
 * - Record rules
 * - Computed field definitions
 * - Menu structures
 */
class ConfigVersion extends Model
{
    use SoftDeletes;

    protected $table = 'config_versions';

    protected $fillable = [
        'tenant_id',
        'config_type',
        'config_name',
        'branch',
        'version',
        'parent_version_id',
        'content',
        'content_hash',
        'description',
        'status',
        'environment',
        'created_by_id',
        'reviewed_by_id',
        'reviewed_at',
        'promoted_at',
        'promoted_by_id',
        'rollback_version_id',
        'metadata',
    ];

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
        'promoted_at' => 'datetime',
    ];

    /**
     * Configuration types.
     */
    public const TYPE_ENTITY = 'entity';
    public const TYPE_WORKFLOW = 'workflow';
    public const TYPE_VIEW = 'view';
    public const TYPE_RECORD_RULE = 'record_rule';
    public const TYPE_COMPUTED_FIELD = 'computed_field';
    public const TYPE_MENU = 'menu';
    public const TYPE_SEQUENCE = 'sequence';
    public const TYPE_PLUGIN = 'plugin';

    /**
     * Statuses.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Environments.
     */
    public const ENV_DEVELOPMENT = 'development';
    public const ENV_STAGING = 'staging';
    public const ENV_PRODUCTION = 'production';

    /**
     * Default branch name.
     */
    public const DEFAULT_BRANCH = 'main';

    /**
     * Get parent version.
     */
    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(ConfigVersion::class, 'parent_version_id');
    }

    /**
     * Get child versions.
     */
    public function childVersions(): HasMany
    {
        return $this->hasMany(ConfigVersion::class, 'parent_version_id');
    }

    /**
     * Get creator.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get reviewer.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * Get promoter.
     */
    public function promotedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by_id');
    }

    /**
     * Get rollback target version.
     */
    public function rollbackVersion(): BelongsTo
    {
        return $this->belongsTo(ConfigVersion::class, 'rollback_version_id');
    }

    /**
     * Get all reviews for this version.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ConfigVersionReview::class, 'config_version_id');
    }

    /**
     * Scope by config type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('config_type', $type);
    }

    /**
     * Scope by config name.
     */
    public function scopeForConfig($query, string $name)
    {
        return $query->where('config_name', $name);
    }

    /**
     * Scope by branch.
     */
    public function scopeOnBranch($query, string $branch)
    {
        return $query->where('branch', $branch);
    }

    /**
     * Scope by environment.
     */
    public function scopeInEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope active versions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope by tenant.
     */
    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId) {
            return $query->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            });
        }
        return $query->whereNull('tenant_id');
    }

    /**
     * Get latest version for a config.
     */
    public static function getLatest(
        string $configType,
        string $configName,
        string $branch = self::DEFAULT_BRANCH,
        ?string $environment = null
    ): ?self {
        $query = static::ofType($configType)
            ->forConfig($configName)
            ->onBranch($branch)
            ->orderByDesc('version');

        if ($environment) {
            $query->inEnvironment($environment);
        }

        return $query->first();
    }

    /**
     * Get active version for production.
     */
    public static function getActive(
        string $configType,
        string $configName,
        ?int $tenantId = null
    ): ?self {
        return static::ofType($configType)
            ->forConfig($configName)
            ->onBranch(self::DEFAULT_BRANCH)
            ->inEnvironment(self::ENV_PRODUCTION)
            ->active()
            ->forTenant($tenantId)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Check if this version can be promoted.
     */
    public function canPromote(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ACTIVE]);
    }

    /**
     * Check if this version needs review.
     */
    public function needsReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    /**
     * Check if this version is editable.
     */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Get version label.
     */
    public function getVersionLabelAttribute(): string
    {
        return "v{$this->version}";
    }

    /**
     * Get full identifier.
     */
    public function getFullIdentifierAttribute(): string
    {
        return "{$this->config_type}/{$this->config_name}@{$this->branch}:v{$this->version}";
    }

    /**
     * Boot method.
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            // Set creator
            if (empty($model->created_by_id)) {
                $model->created_by_id = Auth::id();
            }

            // Calculate content hash
            if (!empty($model->content)) {
                $model->content_hash = hash('sha256', json_encode($model->content));
            }

            // Auto-increment version
            if (empty($model->version)) {
                $latest = static::ofType($model->config_type)
                    ->forConfig($model->config_name)
                    ->onBranch($model->branch)
                    ->max('version');
                $model->version = ($latest ?? 0) + 1;
            }

            // Default branch
            if (empty($model->branch)) {
                $model->branch = self::DEFAULT_BRANCH;
            }

            // Default status
            if (empty($model->status)) {
                $model->status = self::STATUS_DRAFT;
            }

            // Default environment
            if (empty($model->environment)) {
                $model->environment = self::ENV_DEVELOPMENT;
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('content')) {
                $model->content_hash = hash('sha256', json_encode($model->content));
            }
        });
    }
}
