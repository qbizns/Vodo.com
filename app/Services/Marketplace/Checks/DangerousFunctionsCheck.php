<?php

declare(strict_types=1);

namespace App\Services\Marketplace\Checks;

/**
 * Dangerous Functions Check
 *
 * Detects potentially dangerous PHP functions that could be used maliciously.
 */
class DangerousFunctionsCheck extends BaseCheck
{
    /**
     * Critical functions that are never allowed.
     */
    protected array $criticalFunctions = [
        'eval',
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'proc_open',
        'popen',
        'pcntl_exec',
        'assert',
    ];

    /**
     * Functions that are allowed but flagged for review.
     */
    protected array $warningFunctions = [
        'file_get_contents',
        'file_put_contents',
        'fopen',
        'fwrite',
        'curl_exec',
        'include',
        'require',
        'include_once',
        'require_once',
        'create_function',
        'call_user_func',
        'call_user_func_array',
        'preg_replace', // with /e modifier
        'unserialize',
        'serialize',
    ];

    protected function execute(): void
    {
        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = $this->readFile($file);
            if (!$content) {
                continue;
            }

            $relativePath = str_replace($this->extractPath . '/', '', $file);

            // Check for critical functions
            foreach ($this->criticalFunctions as $func) {
                if ($this->containsFunction($content, $func)) {
                    $this->addIssue(
                        "Critical function '{$func}()' found in {$relativePath}",
                        20
                    );
                }
            }

            // Check for warning functions
            foreach ($this->warningFunctions as $func) {
                if ($this->containsFunction($content, $func)) {
                    // Some are less concerning in certain contexts
                    $isConcerning = $this->isConcerningUsage($content, $func, $relativePath);

                    if ($isConcerning) {
                        $this->addWarning(
                            "Function '{$func}()' found in {$relativePath} - verify usage is safe",
                            3
                        );
                    }
                }
            }

            // Check for dynamic code execution patterns
            $this->checkDynamicExecution($content, $relativePath);

            // Check for obfuscated code
            $this->checkObfuscation($content, $relativePath);
        }
    }

    protected function getCategory(): string
    {
        return 'security';
    }

    /**
     * Check if content contains a function call.
     */
    protected function containsFunction(string $content, string $function): bool
    {
        // Match function calls but not in comments or strings
        $pattern = '/\b' . preg_quote($function, '/') . '\s*\(/i';
        return preg_match($pattern, $content) === 1;
    }

    /**
     * Determine if a function usage is concerning based on context.
     */
    protected function isConcerningUsage(string $content, string $func, string $file): bool
    {
        // Include/require in autoload files are usually fine
        if (in_array($func, ['include', 'require', 'include_once', 'require_once'])) {
            if (str_contains($file, 'autoload') || str_contains($file, 'vendor')) {
                return false;
            }
        }

        // file_get_contents on URLs is concerning
        if ($func === 'file_get_contents') {
            if (preg_match('/file_get_contents\s*\(\s*[\'"]https?:/', $content)) {
                return true;
            }
            return false;
        }

        // curl_exec is concerning but common
        if ($func === 'curl_exec') {
            return true;
        }

        return true;
    }

    /**
     * Check for dynamic code execution patterns.
     */
    protected function checkDynamicExecution(string $content, string $file): void
    {
        // Variable function calls: $func()
        if (preg_match('/\$\w+\s*\(/', $content)) {
            $this->addWarning(
                "Variable function call detected in {$file} - verify not user-controlled",
                2
            );
        }

        // Dynamic class instantiation: new $class()
        if (preg_match('/new\s+\$\w+\s*\(/', $content)) {
            $this->addWarning(
                "Dynamic class instantiation in {$file} - verify class name is validated",
                2
            );
        }

        // preg_replace with /e modifier (deprecated but still dangerous)
        if (preg_match('/preg_replace\s*\([^,]+\/[a-z]*e[a-z]*[\'"]/', $content)) {
            $this->addIssue(
                "preg_replace with /e modifier in {$file} - code execution vulnerability",
                15
            );
        }
    }

    /**
     * Check for obfuscated code.
     */
    protected function checkObfuscation(string $content, string $file): void
    {
        // Base64 encoded strings that might be code
        if (preg_match_all('/base64_decode\s*\(/', $content, $matches)) {
            $count = count($matches[0]);
            if ($count > 3) {
                $this->addIssue(
                    "Multiple base64_decode calls ({$count}) in {$file} - possible obfuscation",
                    15
                );
            } elseif ($count > 0) {
                $this->addWarning(
                    "base64_decode in {$file} - verify not decoding executable code",
                    5
                );
            }
        }

        // Hex encoded strings
        if (preg_match('/\\\\x[0-9a-f]{2}\\\\x[0-9a-f]{2}\\\\x[0-9a-f]{2}/i', $content)) {
            $this->addWarning(
                "Hex-encoded strings in {$file} - verify not obfuscated code",
                5
            );
        }

        // Compressed/packed code
        if (preg_match('/gzinflate|gzuncompress|gzdecode/', $content)) {
            $this->addIssue(
                "Compression functions in {$file} - possible packed/obfuscated code",
                15
            );
        }

        // Very long lines (common in obfuscated code)
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            if (strlen($line) > 1000) {
                $this->addWarning(
                    "Very long line in {$file}:" . ($lineNum + 1) . " - possible obfuscation",
                    3
                );
                break;
            }
        }
    }
}
