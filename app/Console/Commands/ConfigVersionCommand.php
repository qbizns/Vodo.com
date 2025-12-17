<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConfigVersion;
use App\Services\ConfigVersion\ConfigVersionService;
use Illuminate\Console\Command;

/**
 * Command to manage configuration versions.
 */
class ConfigVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'config:version 
                            {action : Action to perform (list|show|diff|promote|rollback|export|import)}
                            {--type= : Config type (entity, workflow, view, etc.)}
                            {--name= : Config name}
                            {--branch=main : Branch name}
                            {--env= : Environment (development, staging, production)}
                            {--version= : Specific version number}
                            {--from= : From version for diff}
                            {--to= : To version for diff}
                            {--file= : File for export/import}
                            {--force : Force operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Manage configuration versions (Git-like version control for configs)';

    protected ConfigVersionService $service;

    public function __construct(ConfigVersionService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listVersions(),
            'show' => $this->showVersion(),
            'diff' => $this->showDiff(),
            'promote' => $this->promoteVersion(),
            'rollback' => $this->rollbackVersion(),
            'export' => $this->exportConfig(),
            'import' => $this->importConfig(),
            'compare' => $this->compareEnvironments(),
            default => $this->invalidAction($action),
        };
    }

    /**
     * List config versions.
     */
    protected function listVersions(): int
    {
        $type = $this->option('type');
        $name = $this->option('name');
        $branch = $this->option('branch');
        $env = $this->option('env');

        if (!$type || !$name) {
            $this->error("--type and --name are required for list action");
            return self::FAILURE;
        }

        $history = $this->service->getHistory($type, $name, $branch, $env, 20);

        if ($history->isEmpty()) {
            $this->warn("No versions found");
            return self::SUCCESS;
        }

        $this->table(
            ['Version', 'Branch', 'Environment', 'Status', 'Created By', 'Created At'],
            $history->map(fn($v) => [
                "v{$v->version}",
                $v->branch,
                $v->environment,
                $v->status,
                $v->createdBy?->name ?? 'System',
                $v->created_at->format('Y-m-d H:i'),
            ])
        );

        return self::SUCCESS;
    }

    /**
     * Show a specific version.
     */
    protected function showVersion(): int
    {
        $type = $this->option('type');
        $name = $this->option('name');
        $version = $this->option('version');
        $branch = $this->option('branch');

        if (!$type || !$name) {
            $this->error("--type and --name are required");
            return self::FAILURE;
        }

        $query = ConfigVersion::ofType($type)
            ->forConfig($name)
            ->onBranch($branch);

        if ($version) {
            $query->where('version', $version);
        } else {
            $query->orderByDesc('version');
        }

        $configVersion = $query->first();

        if (!$configVersion) {
            $this->error("Version not found");
            return self::FAILURE;
        }

        $this->info("Config: {$configVersion->full_identifier}");
        $this->line("Status: {$configVersion->status}");
        $this->line("Environment: {$configVersion->environment}");
        $this->line("Description: {$configVersion->description}");
        $this->line("Created: {$configVersion->created_at} by " . ($configVersion->createdBy?->name ?? 'System'));
        $this->newLine();
        $this->line("Content:");
        $this->line(json_encode($configVersion->content, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    /**
     * Show diff between versions.
     */
    protected function showDiff(): int
    {
        $type = $this->option('type');
        $name = $this->option('name');
        $fromVersion = $this->option('from');
        $toVersion = $this->option('to');
        $branch = $this->option('branch');

        if (!$type || !$name || !$fromVersion || !$toVersion) {
            $this->error("--type, --name, --from, and --to are required for diff");
            return self::FAILURE;
        }

        $from = ConfigVersion::ofType($type)
            ->forConfig($name)
            ->onBranch($branch)
            ->where('version', $fromVersion)
            ->first();

        $to = ConfigVersion::ofType($type)
            ->forConfig($name)
            ->onBranch($branch)
            ->where('version', $toVersion)
            ->first();

        if (!$from || !$to) {
            $this->error("One or both versions not found");
            return self::FAILURE;
        }

        $diff = $this->service->diff($from, $to);

        $this->info("Diff: v{$fromVersion} → v{$toVersion}");
        $this->newLine();

        if (!empty($diff['added'])) {
            $this->line("<fg=green>Added:</>");
            foreach ($diff['added'] as $path => $value) {
                $this->line("  <fg=green>+ {$path}:</> " . json_encode($value));
            }
        }

        if (!empty($diff['removed'])) {
            $this->line("<fg=red>Removed:</>");
            foreach ($diff['removed'] as $path => $value) {
                $this->line("  <fg=red>- {$path}:</> " . json_encode($value));
            }
        }

        if (!empty($diff['modified'])) {
            $this->line("<fg=yellow>Modified:</>");
            foreach ($diff['modified'] as $path => $change) {
                $this->line("  <fg=yellow>~ {$path}:</>");
                $this->line("    <fg=red>- " . json_encode($change['old']) . "</>");
                $this->line("    <fg=green>+ " . json_encode($change['new']) . "</>");
            }
        }

        if (empty($diff['added']) && empty($diff['removed']) && empty($diff['modified'])) {
            $this->info("No differences found");
        }

        return self::SUCCESS;
    }

    /**
     * Promote version to environment.
     */
    protected function promoteVersion(): int
    {
        $type = $this->option('type');
        $name = $this->option('name');
        $version = $this->option('version');
        $targetEnv = $this->option('env');
        $branch = $this->option('branch');

        if (!$type || !$name || !$targetEnv) {
            $this->error("--type, --name, and --env are required for promote");
            return self::FAILURE;
        }

        $query = ConfigVersion::ofType($type)
            ->forConfig($name)
            ->onBranch($branch);

        if ($version) {
            $query->where('version', $version);
        } else {
            $query->orderByDesc('version');
        }

        $configVersion = $query->first();

        if (!$configVersion) {
            $this->error("Version not found");
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Promote {$configVersion->full_identifier} to {$targetEnv}?")) {
                return self::SUCCESS;
            }
        }

        try {
            $promoted = $this->service->promote($configVersion, $targetEnv);
            $this->info("✓ Promoted to {$targetEnv} as v{$promoted->version}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Promotion failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Rollback to a previous version.
     */
    protected function rollbackVersion(): int
    {
        $type = $this->option('type');
        $name = $this->option('name');
        $version = $this->option('version');
        $env = $this->option('env') ?? ConfigVersion::ENV_PRODUCTION;

        if (!$type || !$name || !$version) {
            $this->error("--type, --name, and --version are required for rollback");
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $this->warn("WARNING: This will rollback {$type}/{$name} in {$env} to v{$version}");
            if (!$this->confirm("Are you sure?")) {
                return self::SUCCESS;
            }
        }

        try {
            $rolled = $this->service->rollback($type, $name, (int) $version, $env);
            $this->info("✓ Rolled back to v{$version}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Rollback failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Export configuration.
     */
    protected function exportConfig(): int
    {
        $type = $this->option('type');
        $name = $this->option('name');
        $env = $this->option('env');
        $file = $this->option('file') ?? 'config_export.json';

        $configs = [];

        if ($type && $name) {
            $configs[] = ['type' => $type, 'name' => $name];
        } else {
            // Export all active configs
            $versions = ConfigVersion::active()
                ->when($env, fn($q) => $q->inEnvironment($env))
                ->select('config_type', 'config_name', 'branch')
                ->distinct()
                ->get();

            foreach ($versions as $v) {
                $configs[] = ['type' => $v->config_type, 'name' => $v->config_name, 'branch' => $v->branch];
            }
        }

        $package = $this->service->export($configs, $env);

        file_put_contents($file, json_encode($package, JSON_PRETTY_PRINT));

        $this->info("✓ Exported " . count($package['configs']) . " configs to {$file}");

        return self::SUCCESS;
    }

    /**
     * Import configuration.
     */
    protected function importConfig(): int
    {
        $file = $this->option('file');

        if (!$file || !file_exists($file)) {
            $this->error("--file is required and must exist");
            return self::FAILURE;
        }

        $package = json_decode(file_get_contents($file), true);

        if (!$package || !isset($package['configs'])) {
            $this->error("Invalid package format");
            return self::FAILURE;
        }

        $overwrite = $this->option('force') || $this->confirm("Overwrite existing configs?", false);

        $results = $this->service->import($package, $overwrite);

        $this->info("Import results:");
        $this->line("  Imported: " . count($results['imported']));
        $this->line("  Skipped: " . count($results['skipped']));
        $this->line("  Errors: " . count($results['errors']));

        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }

        return empty($results['errors']) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Compare environments.
     */
    protected function compareEnvironments(): int
    {
        $type = $this->option('type');
        $name = $this->option('name');

        if (!$type || !$name) {
            $this->error("--type and --name are required");
            return self::FAILURE;
        }

        $comparison = $this->service->compareEnvironments(
            $type,
            $name,
            ConfigVersion::ENV_STAGING,
            ConfigVersion::ENV_PRODUCTION
        );

        $this->info("Environment comparison for {$type}/{$name}:");
        $this->newLine();

        $statusIcon = match ($comparison['status']) {
            'in_sync' => '<fg=green>✓ In sync</>',
            'out_of_sync' => '<fg=yellow>⚠ Out of sync</>',
            default => '<fg=red>✗ ' . str_replace('_', ' ', $comparison['status']) . '</>',
        };

        $this->line("Status: {$statusIcon}");

        if (isset($comparison['staging'])) {
            $this->line("Staging: {$comparison['staging']}");
        }
        if (isset($comparison['production'])) {
            $this->line("Production: {$comparison['production']}");
        }

        return self::SUCCESS;
    }

    /**
     * Handle invalid action.
     */
    protected function invalidAction(string $action): int
    {
        $this->error("Unknown action: {$action}");
        $this->line("Available actions: list, show, diff, promote, rollback, export, import, compare");
        return self::FAILURE;
    }
}
