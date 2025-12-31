<?php

declare(strict_types=1);

namespace App\Services\Marketplace\Checks;

use App\Models\Marketplace\MarketplaceSubmission;

/**
 * Base Check
 *
 * Base class for all automated review checks.
 */
abstract class BaseCheck
{
    protected MarketplaceSubmission $submission;
    protected string $extractPath;
    protected array $issues = [];
    protected array $warnings = [];
    protected int $score = 100;

    /**
     * Run the check.
     */
    public function run(MarketplaceSubmission $submission, string $extractPath): array
    {
        $this->submission = $submission;
        $this->extractPath = $extractPath;
        $this->issues = [];
        $this->warnings = [];
        $this->score = 100;

        $this->execute();

        return $this->buildResult();
    }

    /**
     * Execute the check logic.
     */
    abstract protected function execute(): void;

    /**
     * Get the check category.
     */
    abstract protected function getCategory(): string;

    /**
     * Build the result array.
     */
    protected function buildResult(): array
    {
        $result = 'pass';

        if (!empty($this->issues)) {
            $result = 'fail';
        } elseif (!empty($this->warnings)) {
            $result = 'warning';
        }

        return [
            'result' => $result,
            'category' => $this->getCategory(),
            'score' => max(0, $this->score),
            'message' => $this->buildMessage(),
            'details' => [
                'issues' => $this->issues,
                'warnings' => $this->warnings,
            ],
        ];
    }

    /**
     * Build the result message.
     */
    protected function buildMessage(): string
    {
        if (empty($this->issues) && empty($this->warnings)) {
            return 'Check passed';
        }

        $parts = [];

        if (!empty($this->issues)) {
            $parts[] = count($this->issues) . ' issue(s) found';
        }

        if (!empty($this->warnings)) {
            $parts[] = count($this->warnings) . ' warning(s)';
        }

        return implode(', ', $parts);
    }

    /**
     * Add an issue (causes failure).
     */
    protected function addIssue(string $message, int $deduction = 10): void
    {
        $this->issues[] = $message;
        $this->score -= $deduction;
    }

    /**
     * Add a warning (doesn't cause failure).
     */
    protected function addWarning(string $message, int $deduction = 5): void
    {
        $this->warnings[] = $message;
        $this->score -= $deduction;
    }

    /**
     * Get all PHP files in the extracted package.
     */
    protected function getPhpFiles(): array
    {
        return $this->getFiles('*.php');
    }

    /**
     * Get files matching a pattern.
     */
    protected function getFiles(string $pattern): array
    {
        $files = glob($this->extractPath . '/' . $pattern);
        $files = array_merge($files, glob($this->extractPath . '/**/' . $pattern));
        $files = array_merge($files, glob($this->extractPath . '/**/**/' . $pattern));
        $files = array_merge($files, glob($this->extractPath . '/**/**/**/' . $pattern));

        return array_unique($files);
    }

    /**
     * Get the manifest from the submission.
     */
    protected function getManifest(): array
    {
        return $this->submission->manifest ?? [];
    }

    /**
     * Read file contents.
     */
    protected function readFile(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }

        return file_get_contents($path);
    }
}
