<?php

declare(strict_types=1);

namespace App\Services\ConfigVersion;

use App\Models\ConfigVersion;
use Illuminate\Support\Facades\App;

/**
 * ConfigVersionBuilder - Fluent API for creating and managing config versions.
 * 
 * Usage:
 * $config = ConfigBuilder::create('workflow', 'invoice_approval')
 *     ->basedOn('v1')
 *     ->modify('transitions.approve.conditions', [...])
 *     ->description('Updated approval threshold')
 *     ->requestReview(['user_1', 'user_2'])
 *     ->build();
 */
class ConfigVersionBuilder
{
    protected string $configType;
    protected string $configName;
    protected ?string $branch = null;
    protected ?ConfigVersion $baseVersion = null;
    protected array $content = [];
    protected array $modifications = [];
    protected ?string $description = null;
    protected array $reviewers = [];
    protected ?string $targetEnvironment = null;
    protected ?\DateTime $scheduledPromotion = null;
    protected ?int $tenantId = null;

    /**
     * Create a new builder instance.
     */
    public static function create(string $configType, string $configName): self
    {
        $builder = new self();
        $builder->configType = $configType;
        $builder->configName = $configName;
        return $builder;
    }

    /**
     * Set the base version to build from.
     */
    public function basedOn(string|int|ConfigVersion $version): self
    {
        if ($version instanceof ConfigVersion) {
            $this->baseVersion = $version;
        } elseif (is_int($version)) {
            $this->baseVersion = ConfigVersion::ofType($this->configType)
                ->forConfig($this->configName)
                ->where('version', $version)
                ->firstOrFail();
        } else {
            // Parse version string like "v3" or "main:v3"
            if (str_contains($version, ':')) {
                [$branch, $ver] = explode(':', $version);
                $verNum = (int) ltrim($ver, 'v');
                $this->baseVersion = ConfigVersion::ofType($this->configType)
                    ->forConfig($this->configName)
                    ->onBranch($branch)
                    ->where('version', $verNum)
                    ->firstOrFail();
            } else {
                $verNum = (int) ltrim($version, 'v');
                $this->baseVersion = ConfigVersion::ofType($this->configType)
                    ->forConfig($this->configName)
                    ->where('version', $verNum)
                    ->firstOrFail();
            }
        }

        $this->content = $this->baseVersion->content;
        $this->branch = $this->baseVersion->branch;

        return $this;
    }

    /**
     * Set the branch.
     */
    public function onBranch(string $branch): self
    {
        $this->branch = $branch;
        return $this;
    }

    /**
     * Set the full content.
     */
    public function content(array $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Modify a specific path in the content.
     */
    public function modify(string $path, mixed $value): self
    {
        $this->modifications[$path] = $value;
        return $this;
    }

    /**
     * Remove a path from the content.
     */
    public function remove(string $path): self
    {
        $this->modifications[$path] = '__REMOVE__';
        return $this;
    }

    /**
     * Add to an array at a path.
     */
    public function append(string $path, mixed $value): self
    {
        $this->modifications[$path] = ['__APPEND__', $value];
        return $this;
    }

    /**
     * Set the description.
     */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Request review from users.
     */
    public function requestReview(array $reviewerIds): self
    {
        $this->reviewers = $reviewerIds;
        return $this;
    }

    /**
     * Set target environment for promotion.
     */
    public function promoteToEnvironment(string $environment): self
    {
        $this->targetEnvironment = $environment;
        return $this;
    }

    /**
     * Schedule promotion.
     */
    public function schedulePromotion(\DateTime $dateTime): self
    {
        $this->scheduledPromotion = $dateTime;
        return $this;
    }

    /**
     * Set tenant context.
     */
    public function forTenant(int $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Build the configuration version.
     */
    public function build(): ConfigVersion
    {
        $service = App::make(ConfigVersionService::class);
        
        if ($this->tenantId) {
            $service->setTenant($this->tenantId);
        }

        // Apply modifications
        $finalContent = $this->applyModifications($this->content, $this->modifications);

        // Create the version
        $version = $service->create(
            $this->configType,
            $this->configName,
            $finalContent,
            $this->description,
            $this->branch ?? ConfigVersion::DEFAULT_BRANCH,
            $this->baseVersion?->id
        );

        // Request review if reviewers specified
        if (!empty($this->reviewers)) {
            $service->requestReview($version, $this->reviewers);
            $version->refresh();
        }

        // Promote if target environment specified
        if ($this->targetEnvironment && $version->canPromote()) {
            $version = $service->promote($version, $this->targetEnvironment, $this->scheduledPromotion);
        }

        return $version;
    }

    /**
     * Apply modifications to content.
     */
    protected function applyModifications(array $content, array $modifications): array
    {
        foreach ($modifications as $path => $value) {
            if ($value === '__REMOVE__') {
                $content = $this->removePath($content, $path);
            } elseif (is_array($value) && ($value[0] ?? null) === '__APPEND__') {
                $content = $this->appendToPath($content, $path, $value[1]);
            } else {
                $content = $this->setPath($content, $path, $value);
            }
        }

        return $content;
    }

    /**
     * Set a value at a dot-notation path.
     */
    protected function setPath(array $array, string $path, mixed $value): array
    {
        $keys = explode('.', $path);
        $current = &$array;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }

        return $array;
    }

    /**
     * Remove a path from array.
     */
    protected function removePath(array $array, string $path): array
    {
        $keys = explode('.', $path);
        $current = &$array;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                unset($current[$key]);
            } else {
                if (!isset($current[$key])) {
                    return $array;
                }
                $current = &$current[$key];
            }
        }

        return $array;
    }

    /**
     * Append to array at path.
     */
    protected function appendToPath(array $array, string $path, mixed $value): array
    {
        $keys = explode('.', $path);
        $current = &$array;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current[$key][] = $value;
            } else {
                if (!isset($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }

        return $array;
    }

    /**
     * Preview the final content without building.
     */
    public function preview(): array
    {
        return $this->applyModifications($this->content, $this->modifications);
    }

    /**
     * Preview diff from base version.
     */
    public function previewDiff(): array
    {
        if (!$this->baseVersion) {
            return ['added' => $this->preview()];
        }

        $service = App::make(ConfigVersionService::class);
        return $service->generateDiff($this->baseVersion->content, $this->preview());
    }
}

// Facade-style helper
class ConfigBuilder extends ConfigVersionBuilder {}
