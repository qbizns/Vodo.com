<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PluginSDK\PluginManifest;
use App\Enums\PluginScope;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Command to validate a plugin for marketplace submission.
 */
class PluginValidateCommand extends Command
{
    protected $signature = 'plugin:validate
                            {plugin : The plugin name or path}
                            {--json : Output as JSON}
                            {--strict : Fail on warnings}
                            {--marketplace : Validate for marketplace submission}';

    protected $description = 'Validate a plugin manifest, security scopes, and structure';

    protected array $results = [
        'plugin' => '',
        'valid' => true,
        'score' => 100,
        'errors' => [],
        'warnings' => [],
        'info' => [],
        'checks' => [],
    ];

    public function handle(): int
    {
        $pluginArg = $this->argument('plugin');
        $pluginPath = $this->resolvePluginPath($pluginArg);

        if (!$pluginPath) {
            $this->error("Plugin not found: {$pluginArg}");
            return self::FAILURE;
        }

        $this->results['plugin'] = basename($pluginPath);
        $this->info("Validating plugin: {$this->results['plugin']}");
        $this->newLine();

        // Run all validation checks
        $this->validateManifest($pluginPath);
        $this->validateStructure($pluginPath);
        $this->validateSecurity($pluginPath);
        $this->validateCode($pluginPath);

        if ($this->option('marketplace')) {
            $this->validateMarketplace($pluginPath);
        }

        // Calculate final score
        $this->calculateScore();

        // Output results
        if ($this->option('json')) {
            $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
        } else {
            $this->displayResults();
        }

        // Determine exit code
        if (!empty($this->results['errors'])) {
            return self::FAILURE;
        }

        if ($this->option('strict') && !empty($this->results['warnings'])) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function resolvePluginPath(string $plugin): ?string
    {
        // Check if it's a direct path
        if (is_dir($plugin)) {
            return realpath($plugin);
        }

        // Check in plugins directory
        $pluginName = Str::studly($plugin);
        $path = base_path("plugins/{$pluginName}");

        if (is_dir($path)) {
            return $path;
        }

        return null;
    }

    protected function validateManifest(string $pluginPath): void
    {
        $manifestPath = $pluginPath . '/plugin.json';

        $this->startCheck('Manifest file exists');

        if (!file_exists($manifestPath)) {
            $this->failCheck('plugin.json not found');
            $this->addError('Missing plugin.json manifest file');
            return;
        }

        $this->passCheck();

        // Load and validate manifest
        try {
            $manifest = PluginManifest::fromFile($manifestPath);

            $this->startCheck('Manifest JSON is valid');
            $this->passCheck();

            $this->startCheck('Manifest schema validation');
            if (!$manifest->validate()) {
                foreach ($manifest->getErrors() as $error) {
                    $this->addError($error);
                }
                foreach ($manifest->getWarnings() as $warning) {
                    $this->addWarning($warning);
                }
                $this->failCheck(count($manifest->getErrors()) . ' errors found');
            } else {
                foreach ($manifest->getWarnings() as $warning) {
                    $this->addWarning($warning);
                }
                $this->passCheck();
            }

            // Check required fields
            $this->validateManifestFields($manifest);

        } catch (\Throwable $e) {
            $this->failCheck('Invalid JSON: ' . $e->getMessage());
            $this->addError('Invalid plugin.json: ' . $e->getMessage());
        }
    }

    protected function validateManifestFields(PluginManifest $manifest): void
    {
        $this->startCheck('Has valid identifier');
        $identifier = $manifest->getIdentifier();
        if (empty($identifier)) {
            $this->failCheck('Missing identifier');
            $this->addError('Manifest must have an identifier');
        } elseif (!preg_match('/^[a-z][a-z0-9-]*$/', $identifier)) {
            $this->failCheck('Invalid format');
            $this->addError('Identifier must be lowercase with hyphens only');
        } else {
            $this->passCheck();
        }

        $this->startCheck('Has valid version');
        $version = $manifest->getVersion();
        if (empty($version)) {
            $this->failCheck('Missing version');
            $this->addError('Manifest must have a version');
        } elseif (!preg_match('/^\d+\.\d+\.\d+/', $version)) {
            $this->failCheck('Not semver');
            $this->addWarning('Version should follow semantic versioning (x.y.z)');
        } else {
            $this->passCheck();
        }

        $this->startCheck('Has description');
        if (empty($manifest->getDescription())) {
            $this->failCheck('Missing');
            $this->addWarning('Plugin should have a description');
        } else {
            $this->passCheck();
        }
    }

    protected function validateStructure(string $pluginPath): void
    {
        $pluginName = basename($pluginPath);

        // Check for main plugin class
        $this->startCheck('Main plugin class exists');
        $pluginClass = $pluginPath . "/src/{$pluginName}Plugin.php";
        $legacyPluginClass = $pluginPath . "/{$pluginName}Plugin.php";

        if (file_exists($pluginClass) || file_exists($legacyPluginClass)) {
            $this->passCheck();
        } else {
            $this->failCheck('Not found');
            $this->addError("Plugin class {$pluginName}Plugin.php not found");
        }

        // Check for required directories
        $recommendedDirs = ['config', 'routes', 'tests'];
        foreach ($recommendedDirs as $dir) {
            $this->startCheck("Has {$dir} directory");
            if (is_dir($pluginPath . '/' . $dir)) {
                $this->passCheck();
            } else {
                $this->skipCheck('Optional');
                $this->addInfo("Recommended directory '{$dir}' not found");
            }
        }

        // Check for composer.json
        $this->startCheck('Has composer.json');
        if (file_exists($pluginPath . '/composer.json')) {
            $this->passCheck();
        } else {
            $this->failCheck('Missing');
            $this->addWarning('Plugin should have a composer.json for autoloading');
        }

        // Check for README
        $this->startCheck('Has README.md');
        if (file_exists($pluginPath . '/README.md')) {
            $this->passCheck();
        } else {
            $this->skipCheck('Optional');
            $this->addInfo('README.md is recommended for documentation');
        }
    }

    protected function validateSecurity(string $pluginPath): void
    {
        $manifestPath = $pluginPath . '/plugin.json';

        if (!file_exists($manifestPath)) {
            return;
        }

        try {
            $manifest = PluginManifest::fromFile($manifestPath);
        } catch (\Throwable) {
            return;
        }

        $scopes = $manifest->getAllScopes();

        $this->startCheck('Scopes are valid');
        $validScopes = array_map(fn($s) => $s->value, PluginScope::cases());
        $invalidScopes = array_diff($scopes, $validScopes);

        if (!empty($invalidScopes)) {
            $this->failCheck(count($invalidScopes) . ' invalid');
            foreach ($invalidScopes as $scope) {
                $this->addError("Unknown scope: {$scope}");
            }
        } else {
            $this->passCheck();
        }

        // Check for dangerous scopes
        $this->startCheck('Dangerous scopes declared correctly');
        $dangerousInRegular = [];
        foreach ($manifest->getScopes() as $scope) {
            $enumScope = PluginScope::tryFrom($scope);
            if ($enumScope && $enumScope->isDangerous()) {
                $dangerousInRegular[] = $scope;
            }
        }

        if (!empty($dangerousInRegular)) {
            $this->failCheck(count($dangerousInRegular) . ' misplaced');
            foreach ($dangerousInRegular as $scope) {
                $this->addError("Dangerous scope '{$scope}' must be in 'dangerous_scopes'");
            }
        } else {
            $this->passCheck();
        }

        // Warn about dangerous scopes
        $dangerousScopes = $manifest->getDangerousScopes();
        if (!empty($dangerousScopes)) {
            $this->addWarning('Plugin requests ' . count($dangerousScopes) . ' dangerous scope(s): ' . implode(', ', $dangerousScopes));
        }

        // Check for minimal permissions
        $this->startCheck('Uses minimal permissions');
        if (in_array('system:admin', $scopes)) {
            $this->failCheck('Has system:admin');
            $this->addWarning('Plugin requests system:admin scope - this requires special approval');
        } elseif (count($scopes) > 10) {
            $this->failCheck('Too many scopes');
            $this->addWarning('Plugin requests ' . count($scopes) . ' scopes - consider reducing');
        } else {
            $this->passCheck();
        }
    }

    protected function validateCode(string $pluginPath): void
    {
        // Check for potential security issues in PHP files
        $phpFiles = glob($pluginPath . '/{,*/,*/*/,*/*/*/}*.php', GLOB_BRACE);

        $this->startCheck('No dangerous functions');
        $dangerousFunctions = ['eval', 'exec', 'shell_exec', 'system', 'passthru', 'proc_open'];
        $issues = [];

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            foreach ($dangerousFunctions as $func) {
                if (preg_match('/\b' . $func . '\s*\(/', $content)) {
                    $issues[] = basename($file) . ": uses {$func}()";
                }
            }
        }

        if (!empty($issues)) {
            $this->failCheck(count($issues) . ' found');
            foreach ($issues as $issue) {
                $this->addWarning("Potentially dangerous code: {$issue}");
            }
        } else {
            $this->passCheck();
        }

        // Check for direct env() usage
        $this->startCheck('No direct env() calls');
        $envIssues = [];

        foreach ($phpFiles as $file) {
            // Skip config files
            if (str_contains($file, '/config/')) {
                continue;
            }

            $content = file_get_contents($file);
            if (preg_match('/\benv\s*\(/', $content)) {
                $envIssues[] = basename($file);
            }
        }

        if (!empty($envIssues)) {
            $this->failCheck(count($envIssues) . ' files');
            $this->addWarning('Direct env() usage outside config files in: ' . implode(', ', $envIssues));
        } else {
            $this->passCheck();
        }
    }

    protected function validateMarketplace(string $pluginPath): void
    {
        $this->info('Running marketplace validation...');
        $this->newLine();

        $manifestPath = $pluginPath . '/plugin.json';

        if (!file_exists($manifestPath)) {
            $this->addError('Marketplace plugins must have a plugin.json manifest');
            return;
        }

        try {
            $manifest = PluginManifest::fromFile($manifestPath);
        } catch (\Throwable $e) {
            $this->addError('Invalid manifest: ' . $e->getMessage());
            return;
        }

        // Check marketplace listing enabled
        $this->startCheck('Marketplace listing enabled');
        if (!$manifest->isMarketplaceListed()) {
            $this->failCheck('Not enabled');
            $this->addError("Set 'marketplace.listed' to true for marketplace submission");
        } else {
            $this->passCheck();
        }

        // Check description length
        $this->startCheck('Description length (min 50 chars)');
        $description = $manifest->getDescription();
        if (strlen($description) < 50) {
            $this->failCheck(strlen($description) . ' chars');
            $this->addError('Marketplace description must be at least 50 characters');
        } else {
            $this->passCheck();
        }

        // Check for icon
        $this->startCheck('Has icon');
        $assets = $manifest->getAssets();
        $iconPath = $pluginPath . '/' . ($assets['icon'] ?? 'icon.png');
        if (file_exists($iconPath)) {
            $this->passCheck();
        } else {
            $this->failCheck('Missing');
            $this->addError('Marketplace plugins must have an icon');
        }

        // Check for screenshots
        $this->startCheck('Has screenshots');
        $screenshots = $assets['screenshots'] ?? [];
        if (empty($screenshots)) {
            $this->failCheck('None');
            $this->addWarning('Marketplace plugins should have at least one screenshot');
        } else {
            $this->passCheck();
        }

        // Check keywords
        $this->startCheck('Has keywords (min 3)');
        $keywords = $manifest->get('keywords', []);
        if (count($keywords) < 3) {
            $this->failCheck(count($keywords) . ' found');
            $this->addWarning('Marketplace plugins should have at least 3 keywords');
        } else {
            $this->passCheck();
        }

        // Check for tests
        $this->startCheck('Has tests');
        $testsDir = $pluginPath . '/tests';
        $hasTests = is_dir($testsDir) && count(glob($testsDir . '/*.php')) > 0;
        if ($hasTests) {
            $this->passCheck();
        } else {
            $this->failCheck('Missing');
            $this->addWarning('Marketplace plugins should have tests');
        }

        // Check for changelog
        $this->startCheck('Has CHANGELOG.md');
        if (file_exists($pluginPath . '/CHANGELOG.md') || file_exists($pluginPath . '/docs/CHANGELOG.md')) {
            $this->passCheck();
        } else {
            $this->failCheck('Missing');
            $this->addWarning('Marketplace plugins should have a changelog');
        }
    }

    protected function calculateScore(): void
    {
        $score = 100;

        // Deduct for errors (-10 each)
        $score -= count($this->results['errors']) * 10;

        // Deduct for warnings (-3 each)
        $score -= count($this->results['warnings']) * 3;

        // Bonus for passing checks
        $passedChecks = count(array_filter($this->results['checks'], fn($c) => $c['status'] === 'pass'));
        $totalChecks = count($this->results['checks']);

        if ($totalChecks > 0) {
            $checkBonus = ($passedChecks / $totalChecks) * 10;
            $score = min(100, $score + $checkBonus);
        }

        $this->results['score'] = max(0, (int) $score);
        $this->results['valid'] = empty($this->results['errors']);
    }

    protected function displayResults(): void
    {
        // Score
        $score = $this->results['score'];
        $scoreColor = match (true) {
            $score >= 80 => 'green',
            $score >= 60 => 'yellow',
            default => 'red',
        };

        $this->line(str_repeat('─', 60));
        $this->line(sprintf(
            "  Validation Score: <fg={$scoreColor};options=bold>%d/100</>",
            $score
        ));
        $this->line(str_repeat('─', 60));
        $this->newLine();

        // Checks
        $this->info('Checks:');
        foreach ($this->results['checks'] as $check) {
            $icon = match ($check['status']) {
                'pass' => '<fg=green>✓</>',
                'fail' => '<fg=red>✗</>',
                'skip' => '<fg=yellow>○</>',
                default => '?',
            };

            $this->line("  {$icon} {$check['name']}" . ($check['message'] ? " <fg=gray>({$check['message']})</>" : ''));
        }
        $this->newLine();

        // Errors
        if (!empty($this->results['errors'])) {
            $this->error('Errors (' . count($this->results['errors']) . '):');
            foreach ($this->results['errors'] as $error) {
                $this->line("  <fg=red>✗</> {$error}");
            }
            $this->newLine();
        }

        // Warnings
        if (!empty($this->results['warnings'])) {
            $this->warn('Warnings (' . count($this->results['warnings']) . '):');
            foreach ($this->results['warnings'] as $warning) {
                $this->line("  <fg=yellow>⚠</> {$warning}");
            }
            $this->newLine();
        }

        // Info
        if (!empty($this->results['info'])) {
            $this->info('Info (' . count($this->results['info']) . '):');
            foreach ($this->results['info'] as $info) {
                $this->line("  <fg=blue>ℹ</> {$info}");
            }
            $this->newLine();
        }

        // Summary
        $this->line(str_repeat('─', 60));
        if ($this->results['valid']) {
            if ($score >= 80) {
                $this->info("  ✓ Plugin is valid and ready for use");
            } else {
                $this->warn("  ⚠ Plugin is valid but has issues to address");
            }
        } else {
            $this->error("  ✗ Plugin has errors that must be fixed");
        }
        $this->newLine();
    }

    // Helper methods for tracking checks
    protected string $currentCheck = '';

    protected function startCheck(string $name): void
    {
        $this->currentCheck = $name;
    }

    protected function passCheck(): void
    {
        $this->results['checks'][] = [
            'name' => $this->currentCheck,
            'status' => 'pass',
            'message' => null,
        ];
    }

    protected function failCheck(string $message): void
    {
        $this->results['checks'][] = [
            'name' => $this->currentCheck,
            'status' => 'fail',
            'message' => $message,
        ];
    }

    protected function skipCheck(string $message): void
    {
        $this->results['checks'][] = [
            'name' => $this->currentCheck,
            'status' => 'skip',
            'message' => $message,
        ];
    }

    protected function addError(string $message): void
    {
        $this->results['errors'][] = $message;
    }

    protected function addWarning(string $message): void
    {
        $this->results['warnings'][] = $message;
    }

    protected function addInfo(string $message): void
    {
        $this->results['info'][] = $message;
    }
}
