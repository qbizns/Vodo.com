<?php

declare(strict_types=1);

namespace App\Services\Marketplace\Checks;

use Illuminate\Support\Str;

/**
 * Structure Check
 *
 * Validates the plugin directory structure and required files.
 */
class StructureCheck extends BaseCheck
{
    protected function execute(): void
    {
        $manifest = $this->getManifest();
        $pluginName = Str::studly($manifest['identifier'] ?? 'Unknown');

        // Check for main plugin class
        $this->checkPluginClass($pluginName);

        // Check directory structure
        $this->checkDirectories();

        // Check required files
        $this->checkRequiredFiles();

        // Check for composer.json
        $this->checkComposerJson();

        // Check for readme
        $this->checkReadme();

        // Check for tests
        $this->checkTests();

        // Check for assets
        $this->checkAssets();
    }

    protected function getCategory(): string
    {
        return 'quality';
    }

    protected function checkPluginClass(string $pluginName): void
    {
        $possiblePaths = [
            "{$this->extractPath}/src/{$pluginName}Plugin.php",
            "{$this->extractPath}/{$pluginName}Plugin.php",
        ];

        $found = false;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->addIssue("Main plugin class {$pluginName}Plugin.php not found", 20);
        }
    }

    protected function checkDirectories(): void
    {
        $recommendedDirs = [
            'config' => 'Configuration files',
            'routes' => 'Route definitions',
        ];

        $optionalDirs = [
            'src' => 'Source code',
            'tests' => 'Test files',
            'database/migrations' => 'Database migrations',
            'Resources/views' => 'View templates',
            'Resources/lang' => 'Translation files',
        ];

        foreach ($recommendedDirs as $dir => $description) {
            if (!is_dir("{$this->extractPath}/{$dir}")) {
                $this->addWarning("Missing recommended directory: {$dir} ({$description})", 3);
            }
        }

        foreach ($optionalDirs as $dir => $description) {
            // Just check, don't penalize for optional dirs
            // They're noted in the report for transparency
        }
    }

    protected function checkRequiredFiles(): void
    {
        $required = [
            'plugin.json' => 'Plugin manifest',
        ];

        foreach ($required as $file => $description) {
            if (!file_exists("{$this->extractPath}/{$file}")) {
                $this->addIssue("Missing required file: {$file} ({$description})", 15);
            }
        }
    }

    protected function checkComposerJson(): void
    {
        $composerPath = "{$this->extractPath}/composer.json";

        if (!file_exists($composerPath)) {
            $this->addWarning('Missing composer.json - recommended for proper autoloading', 5);
            return;
        }

        $content = $this->readFile($composerPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addIssue('composer.json is not valid JSON', 10);
            return;
        }

        // Check for autoload
        if (empty($data['autoload'])) {
            $this->addWarning('composer.json missing autoload configuration', 3);
        }

        // Check type
        if (($data['type'] ?? '') !== 'vodo-plugin') {
            $this->addWarning('composer.json type should be "vodo-plugin"', 2);
        }
    }

    protected function checkReadme(): void
    {
        $readmePaths = [
            "{$this->extractPath}/README.md",
            "{$this->extractPath}/readme.md",
            "{$this->extractPath}/README.txt",
        ];

        $found = false;
        foreach ($readmePaths as $path) {
            if (file_exists($path)) {
                $found = true;

                // Check readme content
                $content = $this->readFile($path);
                if (strlen($content) < 200) {
                    $this->addWarning('README is very short - consider adding more documentation', 2);
                }
                break;
            }
        }

        if (!$found) {
            $this->addWarning('Missing README file - recommended for documentation', 3);
        }
    }

    protected function checkTests(): void
    {
        $testDirs = [
            "{$this->extractPath}/tests",
            "{$this->extractPath}/Tests",
        ];

        $found = false;
        foreach ($testDirs as $dir) {
            if (is_dir($dir)) {
                $found = true;

                // Check for test files
                $testFiles = glob("{$dir}/*.php");
                $testFiles = array_merge($testFiles, glob("{$dir}/**/*.php"));

                if (empty($testFiles)) {
                    $this->addWarning('Tests directory exists but contains no test files', 3);
                }
                break;
            }
        }

        if (!$found) {
            $this->addWarning('No tests directory found - tests are recommended', 3);
        }
    }

    protected function checkAssets(): void
    {
        $manifest = $this->getManifest();
        $assets = $manifest['assets'] ?? [];

        // Check for icon
        $icon = $assets['icon'] ?? 'icon.png';
        if (!file_exists("{$this->extractPath}/{$icon}")) {
            $this->addWarning("Icon file not found: {$icon}", 3);
        }

        // Check for screenshots if defined
        $screenshots = $assets['screenshots'] ?? [];
        foreach ($screenshots as $screenshot) {
            if (!file_exists("{$this->extractPath}/{$screenshot}")) {
                $this->addWarning("Screenshot not found: {$screenshot}", 2);
            }
        }
    }
}
