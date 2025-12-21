<?php

namespace App\Rules;

use Closure;
use DOMDocument;
use DOMXPath;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a string is a valid XPath expression.
 *
 * Security: This rule validates XPath syntax and optionally restricts
 * dangerous patterns that could be used for injection attacks.
 */
class ValidXPath implements ValidationRule
{
    /**
     * Patterns that are potentially dangerous and should be blocked.
     * These could be used for XPath injection or DoS attacks.
     */
    protected array $dangerousPatterns = [
        // Function calls that could be exploited
        '/\bdocument\s*\(/i',
        '/\beval\s*\(/i',
        // Excessive recursion patterns
        '/\/\/\*\[.*\/\/\*\[.*\/\/\*\[/i',
        // Comments that might hide malicious content
        '/<!--.*-->/s',
    ];

    /**
     * Whether to check for dangerous patterns.
     */
    protected bool $checkDangerousPatterns;

    /**
     * Maximum allowed length for XPath expressions.
     */
    protected int $maxLength;

    /**
     * Create a new rule instance.
     *
     * @param bool $checkDangerousPatterns Whether to check for dangerous patterns
     * @param int $maxLength Maximum allowed length (default 500)
     */
    public function __construct(bool $checkDangerousPatterns = true, int $maxLength = 500)
    {
        $this->checkDangerousPatterns = $checkDangerousPatterns;
        $this->maxLength = $maxLength;
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        // Check length
        if (strlen($value) > $this->maxLength) {
            $fail("The :attribute must not exceed {$this->maxLength} characters.");
            return;
        }

        // Check for empty XPath
        if (trim($value) === '') {
            $fail('The :attribute cannot be empty.');
            return;
        }

        // Check for dangerous patterns
        if ($this->checkDangerousPatterns) {
            foreach ($this->dangerousPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $fail('The :attribute contains disallowed patterns.');
                    return;
                }
            }
        }

        // Validate XPath syntax by attempting to execute it
        if (!$this->isValidXPathSyntax($value)) {
            $fail('The :attribute must be a valid XPath expression.');
            return;
        }
    }

    /**
     * Check if the XPath expression has valid syntax.
     *
     * @param string $xpath
     * @return bool
     */
    protected function isValidXPathSyntax(string $xpath): bool
    {
        try {
            // Create a minimal DOM document for testing
            $dom = new DOMDocument();
            $dom->loadHTML(
                '<html><body><div></div></body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
            );

            $xpathObj = new DOMXPath($dom);

            // Suppress errors and try to execute the query
            $previous = libxml_use_internal_errors(true);
            $result = @$xpathObj->query($xpath);
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            // If query returns false or there are errors, it's invalid
            if ($result === false || !empty($errors)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add a dangerous pattern to block.
     *
     * @param string $pattern Regex pattern
     * @return static
     */
    public function addDangerousPattern(string $pattern): static
    {
        $this->dangerousPatterns[] = $pattern;
        return $this;
    }

    /**
     * Set whether to check for dangerous patterns.
     *
     * @param bool $check
     * @return static
     */
    public function checkDangerous(bool $check): static
    {
        $this->checkDangerousPatterns = $check;
        return $this;
    }
}

