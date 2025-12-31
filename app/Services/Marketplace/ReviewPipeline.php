<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Enums\SubmissionStatus;
use App\Models\Marketplace\MarketplaceSubmission;
use App\Models\Marketplace\MarketplaceReviewResult;
use App\Services\PluginSDK\PluginManifest;
use Illuminate\Support\Facades\Log;

/**
 * Review Pipeline
 *
 * Automated review system for plugin submissions.
 * Runs security, quality, and compatibility checks.
 */
class ReviewPipeline
{
    protected MarketplaceSubmission $submission;
    protected array $results = [];
    protected int $totalScore = 0;
    protected int $checkCount = 0;

    /**
     * Registered check classes.
     *
     * @var array<string, class-string>
     */
    protected array $checks = [
        // Security checks
        'security.dangerous_functions' => Checks\DangerousFunctionsCheck::class,
        'security.file_operations' => Checks\FileOperationsCheck::class,
        'security.network_calls' => Checks\NetworkCallsCheck::class,
        'security.sql_injection' => Checks\SqlInjectionCheck::class,
        'security.xss' => Checks\XssCheck::class,

        // Quality checks
        'quality.manifest' => Checks\ManifestCheck::class,
        'quality.structure' => Checks\StructureCheck::class,
        'quality.coding_standards' => Checks\CodingStandardsCheck::class,
        'quality.documentation' => Checks\DocumentationCheck::class,

        // Compatibility checks
        'compatibility.php_version' => Checks\PhpVersionCheck::class,
        'compatibility.dependencies' => Checks\DependenciesCheck::class,
        'compatibility.scopes' => Checks\ScopesCheck::class,

        // Performance checks
        'performance.file_size' => Checks\FileSizeCheck::class,
        'performance.complexity' => Checks\ComplexityCheck::class,
    ];

    /**
     * Run the full automated review pipeline.
     */
    public function run(MarketplaceSubmission $submission): array
    {
        $this->submission = $submission;
        $this->results = [];
        $this->totalScore = 0;
        $this->checkCount = 0;

        Log::info("Starting automated review for submission #{$submission->id}");

        // Transition to automated review status
        $submission->startAutomatedReview();

        try {
            // Extract package to temp directory
            $extractPath = $this->extractPackage();

            // Run all checks
            foreach ($this->checks as $checkName => $checkClass) {
                $this->runCheck($checkName, $checkClass, $extractPath);
            }

            // Calculate final score
            $finalScore = $this->checkCount > 0
                ? (int) round($this->totalScore / $this->checkCount)
                : 0;

            // Determine outcome
            $passed = $this->didPass();

            // Store summary result
            $this->storeResult([
                'check_name' => 'automated_review_summary',
                'result' => $passed ? 'pass' : 'fail',
                'category' => 'summary',
                'message' => $passed
                    ? 'Automated review passed'
                    : 'Automated review failed - manual review required',
                'score' => $finalScore,
                'details' => [
                    'checks_run' => $this->checkCount,
                    'passed' => count(array_filter($this->results, fn($r) => $r['result'] === 'pass')),
                    'failed' => count(array_filter($this->results, fn($r) => $r['result'] === 'fail')),
                    'warnings' => count(array_filter($this->results, fn($r) => $r['result'] === 'warning')),
                ],
            ]);

            // Transition based on results
            if ($passed) {
                $submission->startManualReview(0); // Assign to reviewer queue
            } else {
                // Auto-reject if critical security issues
                if ($this->hasCriticalSecurityIssues()) {
                    $submission->reject('Automatic rejection due to critical security issues');
                } else {
                    $submission->requestChanges('Please address the issues found in automated review');
                }
            }

            // Cleanup
            $this->cleanup($extractPath);

            Log::info("Completed automated review for submission #{$submission->id}", [
                'score' => $finalScore,
                'passed' => $passed,
            ]);

            return [
                'passed' => $passed,
                'score' => $finalScore,
                'results' => $this->results,
            ];

        } catch (\Throwable $e) {
            Log::error("Automated review failed for submission #{$submission->id}", [
                'error' => $e->getMessage(),
            ]);

            $this->storeResult([
                'check_name' => 'pipeline_error',
                'result' => 'error',
                'category' => 'system',
                'message' => 'Review pipeline encountered an error: ' . $e->getMessage(),
            ]);

            return [
                'passed' => false,
                'score' => 0,
                'error' => $e->getMessage(),
                'results' => $this->results,
            ];
        }
    }

    /**
     * Run a single check.
     */
    protected function runCheck(string $checkName, string $checkClass, string $extractPath): void
    {
        try {
            if (!class_exists($checkClass)) {
                // Use fallback check
                $check = new Checks\BaseCheck();
            } else {
                $check = new $checkClass();
            }

            $result = $check->run($this->submission, $extractPath);

            $this->results[$checkName] = $result;
            $this->storeResult(array_merge(['check_name' => $checkName], $result));

            if (isset($result['score'])) {
                $this->totalScore += $result['score'];
                $this->checkCount++;
            }

        } catch (\Throwable $e) {
            $this->results[$checkName] = [
                'result' => 'error',
                'message' => $e->getMessage(),
            ];

            $this->storeResult([
                'check_name' => $checkName,
                'result' => 'error',
                'category' => 'system',
                'message' => "Check failed: {$e->getMessage()}",
            ]);
        }
    }

    /**
     * Store a review result.
     */
    protected function storeResult(array $data): void
    {
        MarketplaceReviewResult::create(array_merge([
            'submission_id' => $this->submission->id,
            'review_type' => 'automated',
        ], $data));
    }

    /**
     * Check if all required checks passed.
     */
    protected function didPass(): bool
    {
        $requiredChecks = [
            'security.dangerous_functions',
            'security.sql_injection',
            'quality.manifest',
            'quality.structure',
        ];

        foreach ($requiredChecks as $check) {
            if (isset($this->results[$check]) && $this->results[$check]['result'] === 'fail') {
                return false;
            }
        }

        // Also fail if score is below threshold
        $avgScore = $this->checkCount > 0 ? $this->totalScore / $this->checkCount : 0;

        return $avgScore >= 60;
    }

    /**
     * Check for critical security issues that warrant auto-rejection.
     */
    protected function hasCriticalSecurityIssues(): bool
    {
        $criticalChecks = [
            'security.dangerous_functions',
            'security.sql_injection',
        ];

        foreach ($criticalChecks as $check) {
            if (isset($this->results[$check])) {
                $result = $this->results[$check];
                if ($result['result'] === 'fail' && ($result['score'] ?? 100) < 30) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract the submission package.
     */
    protected function extractPackage(): string
    {
        $tempDir = storage_path('app/review/' . $this->submission->id);

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // In a real implementation, extract the ZIP package here
        // For now, we assume the manifest contains the path
        $packagePath = $this->submission->package_path;

        if (is_dir($packagePath)) {
            // Package is already extracted (development mode)
            return $packagePath;
        }

        // Extract ZIP
        $zip = new \ZipArchive();
        if ($zip->open($packagePath) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
        }

        return $tempDir;
    }

    /**
     * Cleanup temporary files.
     */
    protected function cleanup(string $extractPath): void
    {
        // Don't delete if it's the original package path
        if ($extractPath === $this->submission->package_path) {
            return;
        }

        // Remove temp directory
        if (is_dir($extractPath)) {
            $this->deleteDirectory($extractPath);
        }
    }

    /**
     * Recursively delete a directory.
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * Register a custom check.
     */
    public function registerCheck(string $name, string $class): void
    {
        $this->checks[$name] = $class;
    }

    /**
     * Get all registered checks.
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
}
