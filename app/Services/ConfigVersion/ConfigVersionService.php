<?php

declare(strict_types=1);

namespace App\Services\ConfigVersion;

use App\Models\ConfigVersion;
use App\Models\ConfigVersionReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * ConfigVersionService - Git-like version control for business configurations.
 * 
 * Features:
 * - Version tracking for all configuration types
 * - Branching and merging
 * - Diff generation
 * - Review workflow
 * - Environment promotion (dev → staging → production)
 * - Rollback capabilities
 * - Import/Export packages
 */
class ConfigVersionService
{
    /**
     * Current tenant ID.
     */
    protected ?int $tenantId = null;

    /**
     * Set tenant context.
     */
    public function setTenant(?int $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Create a new configuration version.
     */
    public function create(
        string $configType,
        string $configName,
        array $content,
        ?string $description = null,
        string $branch = ConfigVersion::DEFAULT_BRANCH,
        ?int $parentVersionId = null
    ): ConfigVersion {
        return DB::transaction(function () use ($configType, $configName, $content, $description, $branch, $parentVersionId) {
            $version = ConfigVersion::create([
                'tenant_id' => $this->tenantId,
                'config_type' => $configType,
                'config_name' => $configName,
                'branch' => $branch,
                'parent_version_id' => $parentVersionId,
                'content' => $content,
                'description' => $description,
                'status' => ConfigVersion::STATUS_DRAFT,
                'environment' => ConfigVersion::ENV_DEVELOPMENT,
            ]);

            Log::info('Config version created', [
                'id' => $version->id,
                'type' => $configType,
                'name' => $configName,
                'version' => $version->version,
                'branch' => $branch,
            ]);

            return $version;
        });
    }

    /**
     * Update an existing draft version.
     */
    public function update(ConfigVersion $version, array $content, ?string $description = null): ConfigVersion
    {
        if (!$version->isEditable()) {
            throw new ConfigVersionException("Version {$version->full_identifier} is not editable");
        }

        $version->update([
            'content' => $content,
            'description' => $description ?? $version->description,
        ]);

        return $version->fresh();
    }

    /**
     * Create a new version based on an existing one.
     */
    public function createFrom(
        ConfigVersion $baseVersion,
        array $modifications = [],
        ?string $description = null
    ): ConfigVersion {
        $content = array_replace_recursive($baseVersion->content, $modifications);

        return $this->create(
            $baseVersion->config_type,
            $baseVersion->config_name,
            $content,
            $description ?? "Based on v{$baseVersion->version}",
            $baseVersion->branch,
            $baseVersion->id
        );
    }

    /**
     * Create a branch from an existing version.
     */
    public function branch(ConfigVersion $sourceVersion, string $newBranch, ?string $description = null): ConfigVersion
    {
        // Check if branch already exists
        $existing = ConfigVersion::ofType($sourceVersion->config_type)
            ->forConfig($sourceVersion->config_name)
            ->onBranch($newBranch)
            ->exists();

        if ($existing) {
            throw new ConfigVersionException("Branch '{$newBranch}' already exists for {$sourceVersion->config_name}");
        }

        return $this->create(
            $sourceVersion->config_type,
            $sourceVersion->config_name,
            $sourceVersion->content,
            $description ?? "Branched from {$sourceVersion->branch}:v{$sourceVersion->version}",
            $newBranch,
            $sourceVersion->id
        );
    }

    /**
     * Merge a branch into another.
     */
    public function merge(
        string $configType,
        string $configName,
        string $sourceBranch,
        string $targetBranch = ConfigVersion::DEFAULT_BRANCH,
        string $strategy = 'theirs'
    ): ConfigVersion {
        $sourceVersion = ConfigVersion::getLatest($configType, $configName, $sourceBranch);
        $targetVersion = ConfigVersion::getLatest($configType, $configName, $targetBranch);

        if (!$sourceVersion) {
            throw new ConfigVersionException("Source branch '{$sourceBranch}' not found");
        }

        if (!$targetVersion) {
            // No target exists, just copy source
            return $this->create(
                $configType,
                $configName,
                $sourceVersion->content,
                "Merged from {$sourceBranch}",
                $targetBranch,
                $sourceVersion->id
            );
        }

        // Merge content based on strategy
        $mergedContent = $this->mergeContent(
            $targetVersion->content,
            $sourceVersion->content,
            $strategy
        );

        return $this->create(
            $configType,
            $configName,
            $mergedContent,
            "Merged {$sourceBranch}:v{$sourceVersion->version} into {$targetBranch}",
            $targetBranch,
            $targetVersion->id
        );
    }

    /**
     * Merge content arrays.
     */
    protected function mergeContent(array $base, array $incoming, string $strategy): array
    {
        switch ($strategy) {
            case 'theirs':
                // Incoming wins
                return array_replace_recursive($base, $incoming);
            
            case 'ours':
                // Base wins
                return array_replace_recursive($incoming, $base);
            
            case 'union':
                // Combine arrays
                return array_merge_recursive($base, $incoming);
            
            default:
                return array_replace_recursive($base, $incoming);
        }
    }

    /**
     * Generate diff between two versions.
     */
    public function diff(ConfigVersion $oldVersion, ConfigVersion $newVersion): array
    {
        return $this->generateDiff($oldVersion->content, $newVersion->content);
    }

    /**
     * Generate diff between two arrays.
     */
    public function generateDiff(array $old, array $new, string $path = ''): array
    {
        $diff = [
            'added' => [],
            'removed' => [],
            'modified' => [],
            'unchanged' => [],
        ];

        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($allKeys as $key) {
            $currentPath = $path ? "{$path}.{$key}" : $key;
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            if (!array_key_exists($key, $old)) {
                $diff['added'][$currentPath] = $newValue;
            } elseif (!array_key_exists($key, $new)) {
                $diff['removed'][$currentPath] = $oldValue;
            } elseif (is_array($oldValue) && is_array($newValue)) {
                $nestedDiff = $this->generateDiff($oldValue, $newValue, $currentPath);
                $diff['added'] = array_merge($diff['added'], $nestedDiff['added']);
                $diff['removed'] = array_merge($diff['removed'], $nestedDiff['removed']);
                $diff['modified'] = array_merge($diff['modified'], $nestedDiff['modified']);
                $diff['unchanged'] = array_merge($diff['unchanged'], $nestedDiff['unchanged']);
            } elseif ($oldValue !== $newValue) {
                $diff['modified'][$currentPath] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            } else {
                $diff['unchanged'][$currentPath] = $oldValue;
            }
        }

        return $diff;
    }

    /**
     * Request review for a version.
     */
    public function requestReview(ConfigVersion $version, array $reviewerIds, ?string $message = null): ConfigVersion
    {
        if ($version->status !== ConfigVersion::STATUS_DRAFT) {
            throw new ConfigVersionException("Only draft versions can be submitted for review");
        }

        return DB::transaction(function () use ($version, $reviewerIds, $message) {
            $version->update(['status' => ConfigVersion::STATUS_PENDING_REVIEW]);

            foreach ($reviewerIds as $reviewerId) {
                ConfigVersionReview::create([
                    'config_version_id' => $version->id,
                    'reviewer_id' => $reviewerId,
                    'status' => ConfigVersionReview::STATUS_PENDING,
                    'comments' => $message,
                ]);
            }

            // TODO: Send notifications to reviewers

            return $version->fresh();
        });
    }

    /**
     * Submit a review.
     */
    public function submitReview(
        ConfigVersion $version,
        string $status,
        ?string $comments = null
    ): ConfigVersionReview {
        $review = ConfigVersionReview::where('config_version_id', $version->id)
            ->where('reviewer_id', Auth::id())
            ->where('status', ConfigVersionReview::STATUS_PENDING)
            ->first();

        if (!$review) {
            throw new ConfigVersionException("No pending review found for current user");
        }

        return DB::transaction(function () use ($review, $version, $status, $comments) {
            $review->update([
                'status' => $status,
                'comments' => $comments,
                'reviewed_at' => now(),
            ]);

            // Check if all reviews are complete
            $pendingReviews = ConfigVersionReview::where('config_version_id', $version->id)
                ->where('status', ConfigVersionReview::STATUS_PENDING)
                ->count();

            if ($pendingReviews === 0) {
                // All reviewed - determine final status
                $rejections = ConfigVersionReview::where('config_version_id', $version->id)
                    ->whereIn('status', [
                        ConfigVersionReview::STATUS_REJECTED,
                        ConfigVersionReview::STATUS_CHANGES_REQUESTED,
                    ])
                    ->count();

                $version->update([
                    'status' => $rejections > 0 
                        ? ConfigVersion::STATUS_REJECTED 
                        : ConfigVersion::STATUS_APPROVED,
                    'reviewed_by_id' => Auth::id(),
                    'reviewed_at' => now(),
                ]);
            }

            return $review->fresh();
        });
    }

    /**
     * Promote a version to an environment.
     */
    public function promote(
        ConfigVersion $version,
        string $targetEnvironment,
        ?\DateTime $scheduledAt = null
    ): ConfigVersion {
        if (!$version->canPromote()) {
            throw new ConfigVersionException("Version cannot be promoted. Status: {$version->status}");
        }

        // Validate environment progression
        $envOrder = [
            ConfigVersion::ENV_DEVELOPMENT => 0,
            ConfigVersion::ENV_STAGING => 1,
            ConfigVersion::ENV_PRODUCTION => 2,
        ];

        $currentOrder = $envOrder[$version->environment] ?? 0;
        $targetOrder = $envOrder[$targetEnvironment] ?? 0;

        if ($targetOrder <= $currentOrder && $targetEnvironment !== $version->environment) {
            throw new ConfigVersionException("Cannot promote backwards. Use rollback instead.");
        }

        return DB::transaction(function () use ($version, $targetEnvironment, $scheduledAt) {
            // Deactivate current active version in target environment
            ConfigVersion::ofType($version->config_type)
                ->forConfig($version->config_name)
                ->onBranch($version->branch)
                ->inEnvironment($targetEnvironment)
                ->active()
                ->update(['status' => ConfigVersion::STATUS_ARCHIVED]);

            // Create new version for target environment
            $promoted = $version->replicate();
            $promoted->environment = $targetEnvironment;
            $promoted->status = ConfigVersion::STATUS_ACTIVE;
            $promoted->promoted_at = $scheduledAt ?? now();
            $promoted->promoted_by_id = Auth::id();
            $promoted->parent_version_id = $version->id;
            $promoted->save();

            // Clear related caches
            $this->clearConfigCache($version->config_type, $version->config_name);

            Log::info('Config version promoted', [
                'source_id' => $version->id,
                'target_id' => $promoted->id,
                'environment' => $targetEnvironment,
            ]);

            return $promoted;
        });
    }

    /**
     * Rollback to a previous version.
     */
    public function rollback(
        string $configType,
        string $configName,
        int $targetVersion,
        string $environment = ConfigVersion::ENV_PRODUCTION
    ): ConfigVersion {
        $target = ConfigVersion::ofType($configType)
            ->forConfig($configName)
            ->inEnvironment($environment)
            ->where('version', $targetVersion)
            ->first();

        if (!$target) {
            throw new ConfigVersionException("Version {$targetVersion} not found in {$environment}");
        }

        return DB::transaction(function () use ($target, $configType, $configName, $environment) {
            // Deactivate current
            $current = ConfigVersion::ofType($configType)
                ->forConfig($configName)
                ->inEnvironment($environment)
                ->active()
                ->first();

            if ($current) {
                $current->update(['status' => ConfigVersion::STATUS_ARCHIVED]);
            }

            // Create rollback version
            $rollback = $target->replicate();
            $rollback->status = ConfigVersion::STATUS_ACTIVE;
            $rollback->promoted_at = now();
            $rollback->promoted_by_id = Auth::id();
            $rollback->rollback_version_id = $current?->id;
            $rollback->metadata = array_merge($rollback->metadata ?? [], [
                'rollback_from' => $current?->version,
                'rollback_to' => $target->version,
                'rollback_at' => now()->toIso8601String(),
            ]);
            $rollback->save();

            $this->clearConfigCache($configType, $configName);

            Log::warning('Config version rollback', [
                'type' => $configType,
                'name' => $configName,
                'from' => $current?->version,
                'to' => $target->version,
            ]);

            return $rollback;
        });
    }

    /**
     * Get version history.
     */
    public function getHistory(
        string $configType,
        string $configName,
        ?string $branch = null,
        ?string $environment = null,
        int $limit = 50
    ): Collection {
        $query = ConfigVersion::ofType($configType)
            ->forConfig($configName)
            ->with(['createdBy', 'reviewedBy', 'promotedBy'])
            ->orderByDesc('version');

        if ($branch) {
            $query->onBranch($branch);
        }

        if ($environment) {
            $query->inEnvironment($environment);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get all branches for a config.
     */
    public function getBranches(string $configType, string $configName): array
    {
        return ConfigVersion::ofType($configType)
            ->forConfig($configName)
            ->distinct()
            ->pluck('branch')
            ->toArray();
    }

    /**
     * Export configuration package.
     */
    public function export(array $configs, ?string $environment = null): array
    {
        $package = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'exported_by' => Auth::user()?->name,
            'environment' => $environment,
            'configs' => [],
        ];

        foreach ($configs as $config) {
            $version = ConfigVersion::getLatest(
                $config['type'],
                $config['name'],
                $config['branch'] ?? ConfigVersion::DEFAULT_BRANCH,
                $environment
            );

            if ($version) {
                $package['configs'][] = [
                    'type' => $version->config_type,
                    'name' => $version->config_name,
                    'version' => $version->version,
                    'branch' => $version->branch,
                    'content' => $version->content,
                    'content_hash' => $version->content_hash,
                ];
            }
        }

        return $package;
    }

    /**
     * Import configuration package.
     */
    public function import(array $package, bool $overwrite = false): array
    {
        $results = [
            'imported' => [],
            'skipped' => [],
            'errors' => [],
        ];

        foreach ($package['configs'] ?? [] as $config) {
            try {
                $existing = ConfigVersion::getLatest(
                    $config['type'],
                    $config['name'],
                    $config['branch'] ?? ConfigVersion::DEFAULT_BRANCH
                );

                if ($existing && !$overwrite) {
                    // Check if content is same
                    if ($existing->content_hash === $config['content_hash']) {
                        $results['skipped'][] = "{$config['type']}/{$config['name']} (unchanged)";
                        continue;
                    }

                    if (!$overwrite) {
                        $results['skipped'][] = "{$config['type']}/{$config['name']} (exists)";
                        continue;
                    }
                }

                $version = $this->create(
                    $config['type'],
                    $config['name'],
                    $config['content'],
                    "Imported from package",
                    $config['branch'] ?? ConfigVersion::DEFAULT_BRANCH,
                    $existing?->id
                );

                $results['imported'][] = $version->full_identifier;

            } catch (\Throwable $e) {
                $results['errors'][] = "{$config['type']}/{$config['name']}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Compare versions across environments.
     */
    public function compareEnvironments(
        string $configType,
        string $configName,
        string $env1 = ConfigVersion::ENV_STAGING,
        string $env2 = ConfigVersion::ENV_PRODUCTION
    ): array {
        $version1 = ConfigVersion::ofType($configType)
            ->forConfig($configName)
            ->inEnvironment($env1)
            ->active()
            ->first();

        $version2 = ConfigVersion::ofType($configType)
            ->forConfig($configName)
            ->inEnvironment($env2)
            ->active()
            ->first();

        if (!$version1 && !$version2) {
            return ['status' => 'not_found'];
        }

        if (!$version1) {
            return [
                'status' => 'missing_in_' . $env1,
                $env2 => $version2->version_label,
            ];
        }

        if (!$version2) {
            return [
                'status' => 'missing_in_' . $env2,
                $env1 => $version1->version_label,
            ];
        }

        if ($version1->content_hash === $version2->content_hash) {
            return [
                'status' => 'in_sync',
                $env1 => $version1->version_label,
                $env2 => $version2->version_label,
            ];
        }

        return [
            'status' => 'out_of_sync',
            $env1 => $version1->version_label,
            $env2 => $version2->version_label,
            'diff' => $this->diff($version2, $version1),
        ];
    }

    /**
     * Clear config cache.
     */
    protected function clearConfigCache(string $configType, string $configName): void
    {
        $cacheKeys = [
            "config:{$configType}:{$configName}",
            "config_active:{$configType}:{$configName}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['config', $configType])->flush();
        }
    }

    /**
     * Load active configuration.
     */
    public function loadActive(string $configType, string $configName): ?array
    {
        $cacheKey = "config_active:{$configType}:{$configName}:{$this->tenantId}";

        return Cache::remember($cacheKey, 3600, function () use ($configType, $configName) {
            $version = ConfigVersion::getActive($configType, $configName, $this->tenantId);
            return $version?->content;
        });
    }
}

/**
 * Exception for config version operations.
 */
class ConfigVersionException extends \Exception {}
