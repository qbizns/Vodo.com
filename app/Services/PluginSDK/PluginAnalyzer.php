<?php

declare(strict_types=1);

namespace App\Services\PluginSDK;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * PluginAnalyzer - Analyzes plugins for potential issues.
 * 
 * Features:
 * - Dependency analysis
 * - Breaking change detection
 * - Performance analysis
 * - Security scanning
 * - Best practices check
 */
class PluginAnalyzer
{
    protected Filesystem $files;
    protected array $issues = [];
    protected array $warnings = [];
    protected array $info = [];

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Analyze a plugin.
     */
    public function analyze(string $pluginPath): array
    {
        $this->issues = [];
        $this->warnings = [];
        $this->info = [];

        if (!$this->files->exists($pluginPath)) {
            return ['error' => 'Plugin path does not exist'];
        }

        // Run all analyzers
        $this->analyzeStructure($pluginPath);
        $this->analyzeDependencies($pluginPath);
        $this->analyzeCode($pluginPath);
        $this->analyzeSecurity($pluginPath);
        $this->analyzePerformance($pluginPath);
        $this->analyzeBestPractices($pluginPath);

        return [
            'path' => $pluginPath,
            'issues' => $this->issues,
            'warnings' => $this->warnings,
            'info' => $this->info,
            'score' => $this->calculateScore(),
        ];
    }

    /**
     * Analyze plugin structure.
     */
    protected function analyzeStructure(string $pluginPath): void
    {
        $requiredFiles = [
            'Plugin class' => '/*Plugin.php',
        ];

        $recommendedDirs = [
            'config',
            'database/migrations',
            'Models',
            'Services',
        ];

        // Check required files
        foreach ($requiredFiles as $name => $pattern) {
            $found = $this->files->glob($pluginPath . '/' . $pattern);
            if (empty($found)) {
                $this->issues[] = [
                    'type' => 'structure',
                    'severity' => 'error',
                    'message' => "Missing required {$name}",
                ];
            }
        }

        // Check recommended directories
        foreach ($recommendedDirs as $dir) {
            if (!$this->files->exists($pluginPath . '/' . $dir)) {
                $this->info[] = [
                    'type' => 'structure',
                    'message' => "Missing recommended directory: {$dir}",
                ];
            }
        }

        // Check for README
        if (!$this->files->exists($pluginPath . '/README.md')) {
            $this->warnings[] = [
                'type' => 'documentation',
                'message' => 'Missing README.md',
            ];
        }

        // Check for tests
        if (!$this->files->exists($pluginPath . '/tests')) {
            $this->warnings[] = [
                'type' => 'testing',
                'message' => 'No tests directory found',
            ];
        }
    }

    /**
     * Analyze dependencies.
     */
    protected function analyzeDependencies(string $pluginPath): void
    {
        $composerPath = $pluginPath . '/composer.json';
        
        if (!$this->files->exists($composerPath)) {
            $this->warnings[] = [
                'type' => 'dependencies',
                'message' => 'No composer.json found',
            ];
            return;
        }

        $composer = json_decode($this->files->get($composerPath), true);

        // Check for version constraints
        $require = $composer['require'] ?? [];
        foreach ($require as $package => $version) {
            if ($version === '*') {
                $this->warnings[] = [
                    'type' => 'dependencies',
                    'message' => "Unconstrained version for {$package}",
                ];
            }
        }

        // Check for dev dependencies in production
        $requireDev = $composer['require-dev'] ?? [];
        if (empty($requireDev)) {
            $this->info[] = [
                'type' => 'dependencies',
                'message' => 'No dev dependencies defined',
            ];
        }
    }

    /**
     * Analyze code quality.
     */
    protected function analyzeCode(string $pluginPath): void
    {
        $phpFiles = $this->files->glob($pluginPath . '/**/*.php');

        foreach ($phpFiles as $file) {
            $content = $this->files->get($file);
            $relativePath = str_replace($pluginPath . '/', '', $file);

            // Check for strict types
            if (!str_contains($content, 'declare(strict_types=1)')) {
                $this->warnings[] = [
                    'type' => 'code_quality',
                    'file' => $relativePath,
                    'message' => 'Missing strict_types declaration',
                ];
            }

            // Check for namespace
            if (!preg_match('/^namespace\s+[\w\\\\]+;/m', $content)) {
                $this->issues[] = [
                    'type' => 'code_quality',
                    'file' => $relativePath,
                    'severity' => 'error',
                    'message' => 'Missing namespace declaration',
                ];
            }

            // Check for TODO/FIXME comments
            if (preg_match_all('/(TODO|FIXME|XXX|HACK):/i', $content, $matches)) {
                $this->info[] = [
                    'type' => 'code_quality',
                    'file' => $relativePath,
                    'message' => 'Contains ' . count($matches[0]) . ' TODO/FIXME comments',
                ];
            }

            // Check for long methods
            $this->checkMethodLength($content, $relativePath);

            // Check for complexity
            $this->checkComplexity($content, $relativePath);
        }
    }

    /**
     * Check method length.
     */
    protected function checkMethodLength(string $content, string $file): void
    {
        preg_match_all('/function\s+(\w+)\s*\([^)]*\)\s*(?::\s*\S+)?\s*\{/s', $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $i => $match) {
            $methodName = $match[0];
            $startPos = $matches[0][$i][1];
            
            // Find closing brace (simplified)
            $braceCount = 0;
            $methodLength = 0;
            $started = false;
            
            for ($j = $startPos; $j < strlen($content); $j++) {
                if ($content[$j] === '{') {
                    $braceCount++;
                    $started = true;
                } elseif ($content[$j] === '}') {
                    $braceCount--;
                }
                if ($content[$j] === "\n") {
                    $methodLength++;
                }
                if ($started && $braceCount === 0) {
                    break;
                }
            }

            if ($methodLength > 50) {
                $this->warnings[] = [
                    'type' => 'code_quality',
                    'file' => $file,
                    'message' => "Method '{$methodName}' is {$methodLength} lines (recommended < 50)",
                ];
            }
        }
    }

    /**
     * Check cyclomatic complexity.
     */
    protected function checkComplexity(string $content, string $file): void
    {
        // Count decision points
        $patterns = [
            '/\bif\s*\(/i',
            '/\belseif\s*\(/i',
            '/\belse\b/i',
            '/\bcase\s+/i',
            '/\bcatch\s*\(/i',
            '/\bwhile\s*\(/i',
            '/\bfor\s*\(/i',
            '/\bforeach\s*\(/i',
            '/\?\?/',
            '/\?:/',
            '/&&/',
            '/\|\|/',
        ];

        $complexity = 1;
        foreach ($patterns as $pattern) {
            $complexity += preg_match_all($pattern, $content);
        }

        // High complexity warning
        if ($complexity > 50) {
            $this->warnings[] = [
                'type' => 'complexity',
                'file' => $file,
                'message' => "High cyclomatic complexity: {$complexity}",
            ];
        }
    }

    /**
     * Analyze security.
     */
    protected function analyzeSecurity(string $pluginPath): void
    {
        $phpFiles = $this->files->glob($pluginPath . '/**/*.php');

        $dangerousPatterns = [
            '/\beval\s*\(/i' => 'Use of eval() is dangerous',
            '/\bexec\s*\(/i' => 'Use of exec() without validation',
            '/\bshell_exec\s*\(/i' => 'Use of shell_exec() without validation',
            '/\bsystem\s*\(/i' => 'Use of system() without validation',
            '/\bpassthru\s*\(/i' => 'Use of passthru() without validation',
            '/\$_GET\s*\[/i' => 'Direct use of $_GET without validation',
            '/\$_POST\s*\[/i' => 'Direct use of $_POST without validation',
            '/\$_REQUEST\s*\[/i' => 'Direct use of $_REQUEST without validation',
            '/file_get_contents\s*\(\s*\$/' => 'Dynamic file_get_contents() call',
            '/include\s*\(\s*\$/' => 'Dynamic include with variable',
            '/require\s*\(\s*\$/' => 'Dynamic require with variable',
            '/unserialize\s*\(/i' => 'Use of unserialize() - potential security risk',
        ];

        foreach ($phpFiles as $file) {
            $content = $this->files->get($file);
            $relativePath = str_replace($pluginPath . '/', '', $file);

            foreach ($dangerousPatterns as $pattern => $message) {
                if (preg_match($pattern, $content)) {
                    $this->issues[] = [
                        'type' => 'security',
                        'severity' => 'warning',
                        'file' => $relativePath,
                        'message' => $message,
                    ];
                }
            }

            // Check for SQL injection risks
            if (preg_match('/DB::raw\s*\(\s*["\'].*\$/', $content) ||
                preg_match('/->whereRaw\s*\(\s*["\'].*\$/', $content)) {
                $this->issues[] = [
                    'type' => 'security',
                    'severity' => 'error',
                    'file' => $relativePath,
                    'message' => 'Potential SQL injection - variable in raw query',
                ];
            }
        }
    }

    /**
     * Analyze performance.
     */
    protected function analyzePerformance(string $pluginPath): void
    {
        $phpFiles = $this->files->glob($pluginPath . '/**/*.php');

        foreach ($phpFiles as $file) {
            $content = $this->files->get($file);
            $relativePath = str_replace($pluginPath . '/', '', $file);

            // Check for N+1 query patterns
            if (preg_match('/foreach.*->get\(\).*foreach/s', $content)) {
                $this->warnings[] = [
                    'type' => 'performance',
                    'file' => $relativePath,
                    'message' => 'Potential N+1 query pattern detected',
                ];
            }

            // Check for queries in loops
            if (preg_match('/(?:while|for|foreach)\s*\([^)]*\)\s*\{[^}]*(?:::find|::where|DB::)/s', $content)) {
                $this->warnings[] = [
                    'type' => 'performance',
                    'file' => $relativePath,
                    'message' => 'Database query inside loop detected',
                ];
            }

            // Check for missing eager loading hints
            if (preg_match('/->(?:hasMany|belongsTo|hasOne|belongsToMany)\s*\(/i', $content) &&
                !preg_match('/->with\s*\(/i', $content)) {
                $this->info[] = [
                    'type' => 'performance',
                    'file' => $relativePath,
                    'message' => 'Has relations but no eager loading in this file',
                ];
            }
        }
    }

    /**
     * Analyze best practices.
     */
    protected function analyzeBestPractices(string $pluginPath): void
    {
        $phpFiles = $this->files->glob($pluginPath . '/**/*.php');

        foreach ($phpFiles as $file) {
            $content = $this->files->get($file);
            $relativePath = str_replace($pluginPath . '/', '', $file);

            // Check for hardcoded credentials
            if (preg_match('/(?:password|secret|key|token)\s*=\s*["\'][^"\']+["\']/i', $content)) {
                $this->issues[] = [
                    'type' => 'best_practices',
                    'severity' => 'error',
                    'file' => $relativePath,
                    'message' => 'Possible hardcoded credentials',
                ];
            }

            // Check for debugging code
            if (preg_match('/\b(?:dd|dump|var_dump|print_r)\s*\(/i', $content)) {
                $this->warnings[] = [
                    'type' => 'best_practices',
                    'file' => $relativePath,
                    'message' => 'Debug code found (dd, dump, var_dump, print_r)',
                ];
            }

            // Check for proper exception handling
            if (preg_match('/catch\s*\(\s*\\\\?Exception\s+\$\w+\s*\)\s*\{\s*\}/s', $content)) {
                $this->warnings[] = [
                    'type' => 'best_practices',
                    'file' => $relativePath,
                    'message' => 'Empty catch block found',
                ];
            }

            // Check for type hints
            preg_match_all('/function\s+\w+\s*\(([^)]*)\)/s', $content, $matches);
            foreach ($matches[1] as $params) {
                if (!empty($params) && !preg_match('/\w+\s+\$/', $params)) {
                    $this->info[] = [
                        'type' => 'best_practices',
                        'file' => $relativePath,
                        'message' => 'Function parameters without type hints',
                    ];
                    break;
                }
            }
        }
    }

    /**
     * Calculate overall score.
     */
    protected function calculateScore(): int
    {
        $score = 100;

        // Deduct for issues
        foreach ($this->issues as $issue) {
            $severity = $issue['severity'] ?? 'warning';
            $score -= match ($severity) {
                'error' => 10,
                'warning' => 5,
                default => 2,
            };
        }

        // Deduct for warnings
        $score -= count($this->warnings) * 2;

        return max(0, min(100, $score));
    }

    /**
     * Compare two plugin versions for breaking changes.
     */
    public function detectBreakingChanges(string $oldPath, string $newPath): array
    {
        $changes = [
            'breaking' => [],
            'non_breaking' => [],
        ];

        // Compare public APIs
        $oldApi = $this->extractPublicApi($oldPath);
        $newApi = $this->extractPublicApi($newPath);

        // Check for removed classes
        foreach ($oldApi['classes'] as $class => $methods) {
            if (!isset($newApi['classes'][$class])) {
                $changes['breaking'][] = "Removed class: {$class}";
                continue;
            }

            // Check for removed methods
            foreach ($methods as $method => $signature) {
                if (!isset($newApi['classes'][$class][$method])) {
                    $changes['breaking'][] = "Removed method: {$class}::{$method}";
                } elseif ($newApi['classes'][$class][$method] !== $signature) {
                    $changes['breaking'][] = "Changed signature: {$class}::{$method}";
                }
            }

            // Check for new methods
            foreach ($newApi['classes'][$class] as $method => $signature) {
                if (!isset($methods[$method])) {
                    $changes['non_breaking'][] = "Added method: {$class}::{$method}";
                }
            }
        }

        // Check for new classes
        foreach ($newApi['classes'] as $class => $methods) {
            if (!isset($oldApi['classes'][$class])) {
                $changes['non_breaking'][] = "Added class: {$class}";
            }
        }

        return $changes;
    }

    /**
     * Extract public API from plugin.
     */
    protected function extractPublicApi(string $pluginPath): array
    {
        $api = ['classes' => []];
        $phpFiles = $this->files->glob($pluginPath . '/**/*.php');

        foreach ($phpFiles as $file) {
            $content = $this->files->get($file);

            // Extract class name
            if (preg_match('/class\s+(\w+)/', $content, $classMatch)) {
                $className = $classMatch[1];
                $api['classes'][$className] = [];

                // Extract public methods
                preg_match_all('/public\s+function\s+(\w+)\s*\(([^)]*)\)/', $content, $methodMatches, PREG_SET_ORDER);
                
                foreach ($methodMatches as $match) {
                    $methodName = $match[1];
                    $params = $match[2];
                    $api['classes'][$className][$methodName] = $params;
                }
            }
        }

        return $api;
    }
}
