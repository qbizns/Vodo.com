<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\Security\SecurityException;

/**
 * Middleware to sanitize and validate input across all requests.
 * Provides defense-in-depth against common injection attacks.
 */
class InputSanitizationMiddleware
{
    /**
     * Fields that should never be sanitized (e.g., password, content editors).
     */
    protected array $excludedFields = [
        'password',
        'password_confirmation',
        'current_password',
        'content',
        'body',
        'html',
        'editor_content',
    ];

    /**
     * Dangerous patterns to detect and block.
     */
    protected array $dangerousPatterns = [
        // PHP code injection
        '/<\?php/i',
        '/<\?=/i',
        '/\beval\s*\(/i',
        '/\bexec\s*\(/i',
        '/\bsystem\s*\(/i',
        '/\bpassthru\s*\(/i',
        '/\bshell_exec\s*\(/i',
        '/\bproc_open\s*\(/i',
        '/\bpopen\s*\(/i',
        '/\bbase64_decode\s*\(/i',
        
        // SQL injection basics (additional to parameterized queries)
        '/\bUNION\s+SELECT\b/i',
        '/\bDROP\s+TABLE\b/i',
        '/\bDELETE\s+FROM\b/i',
        '/\bINSERT\s+INTO\b/i',
        '/\b--\s*$/m',
        '/\/\*.*\*\//s',
        
        // Path traversal
        '/\.\.\//',
        '/\.\.\\\\/',
        '/%2e%2e%2f/i',
        '/%2e%2e\//i',
        '/\.\.%2f/i',
        '/%252e%252e%252f/i',
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize input data
        $this->sanitizeRequest($request);

        // Check for dangerous patterns
        $this->checkDangerousPatterns($request);

        // Validate file uploads
        $this->validateFileUploads($request);

        return $next($request);
    }

    /**
     * Sanitize request input.
     */
    protected function sanitizeRequest(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        $request->merge($sanitized);
    }

    /**
     * Recursively sanitize an array.
     */
    protected function sanitizeArray(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            // Skip excluded fields
            if ($this->isExcludedField($fullKey, $key)) {
                $result[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->sanitizeArray($value, $fullKey);
            } elseif (is_string($value)) {
                $result[$key] = $this->sanitizeString($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if a field should be excluded from sanitization.
     */
    protected function isExcludedField(string $fullKey, string $key): bool
    {
        foreach ($this->excludedFields as $excluded) {
            if ($key === $excluded || $fullKey === $excluded || str_ends_with($fullKey, ".{$excluded}")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sanitize a string value.
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Normalize line endings
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        // Remove invisible unicode characters (except normal whitespace)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        // Trim excessive whitespace
        $value = preg_replace('/\s{10,}/', '          ', $value);

        return trim($value);
    }

    /**
     * Check for dangerous patterns in request data.
     */
    protected function checkDangerousPatterns(Request $request): void
    {
        $input = $request->all();
        $this->checkArrayForPatterns($input, '');
    }

    /**
     * Recursively check array for dangerous patterns.
     */
    protected function checkArrayForPatterns(array $data, string $prefix): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            // Skip excluded fields
            if ($this->isExcludedField($fullKey, $key)) {
                continue;
            }

            if (is_array($value)) {
                $this->checkArrayForPatterns($value, $fullKey);
            } elseif (is_string($value)) {
                $this->checkStringForPatterns($value, $fullKey);
            }
        }
    }

    /**
     * Check a string for dangerous patterns.
     */
    protected function checkStringForPatterns(string $value, string $field): void
    {
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $this->logSuspiciousInput($field, $pattern, $value);

                // For path traversal, always throw
                if (str_contains($pattern, '\.\.')) {
                    throw SecurityException::maliciousInput($field, 'Path traversal attempt detected');
                }

                // For code injection, always throw
                if (str_contains($pattern, 'eval') || str_contains($pattern, 'exec') || str_contains($pattern, 'php')) {
                    throw SecurityException::maliciousInput($field, 'Code injection attempt detected');
                }

                // For SQL injection, log but don't throw (rely on parameterized queries)
                // This is defense-in-depth logging
            }
        }
    }

    /**
     * Validate file uploads for security.
     */
    protected function validateFileUploads(Request $request): void
    {
        foreach ($request->allFiles() as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    $this->validateSingleFile($f, $key);
                }
            } else {
                $this->validateSingleFile($file, $key);
            }
        }
    }

    /**
     * Validate a single uploaded file.
     */
    protected function validateSingleFile($file, string $key): void
    {
        if (!$file || !$file->isValid()) {
            return;
        }

        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        // Check for path traversal in filename
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw SecurityException::maliciousInput($key, 'Invalid filename');
        }

        // Block dangerous file extensions
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'exe', 'sh', 'bash', 'bat', 'cmd', 'com', 'cgi', 'pl', 'py'];
        if (in_array($extension, $dangerousExtensions, true)) {
            throw SecurityException::maliciousInput($key, "Dangerous file type: {$extension}");
        }

        // Check for double extensions
        if (preg_match('/\.(php|phtml|exe|sh|bash|bat|cmd)\./i', $filename)) {
            throw SecurityException::maliciousInput($key, 'Double extension detected');
        }

        // Validate MIME type matches extension for images
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $this->validateImageFile($file, $key);
        }
    }

    /**
     * Validate image file integrity.
     */
    protected function validateImageFile($file, string $key): void
    {
        $imageInfo = @getimagesize($file->getRealPath());
        
        if ($imageInfo === false) {
            throw SecurityException::maliciousInput($key, 'Invalid image file');
        }

        // Check for PHP code in image (basic check)
        $content = file_get_contents($file->getRealPath());
        if (preg_match('/<\?php/i', $content)) {
            throw SecurityException::maliciousInput($key, 'PHP code detected in image');
        }
    }

    /**
     * Log suspicious input for security monitoring.
     */
    protected function logSuspiciousInput(string $field, string $pattern, string $value): void
    {
        Log::warning('Suspicious input detected', [
            'field' => $field,
            'pattern' => $pattern,
            'value_preview' => substr($value, 0, 100),
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'path' => request()->path(),
        ]);
    }

    /**
     * Add a field to the exclusion list.
     */
    public function addExcludedField(string $field): void
    {
        $this->excludedFields[] = $field;
    }

    /**
     * Add a dangerous pattern to check.
     */
    public function addDangerousPattern(string $pattern): void
    {
        $this->dangerousPatterns[] = $pattern;
    }
}
