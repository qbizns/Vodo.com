<?php

declare(strict_types=1);

namespace App\Services\Sequence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Sequence Service - Generate formatted sequential IDs.
 * 
 * Features:
 * - Customizable format patterns
 * - Multiple sequences per entity
 * - Prefix/suffix support
 * - Year/month resets
 * - Gap-free sequences
 * - Multi-tenant support
 * 
 * Example usage:
 * 
 * // Define a sequence
 * $sequenceService->define('invoice', [
 *     'prefix' => 'INV-',
 *     'pattern' => '{YYYY}-{####}',   // INV-2025-0001
 *     'reset_on' => 'year',           // Reset counter each year
 *     'padding' => 4,
 * ]);
 * 
 * // Get next value
 * $number = $sequenceService->next('invoice'); // INV-2025-0001
 * $number = $sequenceService->next('invoice'); // INV-2025-0002
 * 
 * // Preview next value without incrementing
 * $preview = $sequenceService->preview('invoice'); // INV-2025-0003
 */
class SequenceService
{
    /**
     * Sequence definitions.
     * @var array<string, array>
     */
    protected array $definitions = [];

    /**
     * Default configuration.
     */
    protected array $defaults = [
        'prefix' => '',
        'suffix' => '',
        'pattern' => '{####}',
        'padding' => 4,
        'reset_on' => null, // null, 'year', 'month', 'day'
        'start_value' => 1,
        'increment' => 1,
    ];

    /**
     * Define a sequence.
     */
    public function define(string $name, array $config = []): void
    {
        $this->definitions[$name] = array_merge($this->defaults, $config);
    }

    /**
     * Get the next sequence value.
     */
    public function next(string $name, ?int $tenantId = null): string
    {
        $config = $this->getDefinition($name);
        $key = $this->buildKey($name, $config, $tenantId);

        return DB::transaction(function () use ($config, $key, $name, $tenantId) {
            // Lock and get current value
            $current = DB::table('sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (!$current) {
                // Create new sequence
                $nextValue = $config['start_value'];
                DB::table('sequences')->insert([
                    'key' => $key,
                    'name' => $name,
                    'tenant_id' => $tenantId,
                    'current_value' => $nextValue,
                    'reset_period' => $this->getCurrentPeriod($config['reset_on']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Check for period reset
                $currentPeriod = $this->getCurrentPeriod($config['reset_on']);
                if ($config['reset_on'] && $current->reset_period !== $currentPeriod) {
                    $nextValue = $config['start_value'];
                } else {
                    $nextValue = $current->current_value + $config['increment'];
                }

                DB::table('sequences')
                    ->where('key', $key)
                    ->update([
                        'current_value' => $nextValue,
                        'reset_period' => $currentPeriod,
                        'updated_at' => now(),
                    ]);
            }

            return $this->format($config, $nextValue);
        });
    }

    /**
     * Preview the next value without incrementing.
     */
    public function preview(string $name, ?int $tenantId = null): string
    {
        $config = $this->getDefinition($name);
        $key = $this->buildKey($name, $config, $tenantId);

        $current = DB::table('sequences')->where('key', $key)->first();

        if (!$current) {
            return $this->format($config, $config['start_value']);
        }

        // Check for period reset
        $currentPeriod = $this->getCurrentPeriod($config['reset_on']);
        if ($config['reset_on'] && $current->reset_period !== $currentPeriod) {
            return $this->format($config, $config['start_value']);
        }

        return $this->format($config, $current->current_value + $config['increment']);
    }

    /**
     * Get current sequence value.
     */
    public function current(string $name, ?int $tenantId = null): ?string
    {
        $config = $this->getDefinition($name);
        $key = $this->buildKey($name, $config, $tenantId);

        $current = DB::table('sequences')->where('key', $key)->first();

        if (!$current) {
            return null;
        }

        return $this->format($config, $current->current_value);
    }

    /**
     * Set sequence to a specific value.
     */
    public function set(string $name, int $value, ?int $tenantId = null): void
    {
        $config = $this->getDefinition($name);
        $key = $this->buildKey($name, $config, $tenantId);

        DB::table('sequences')->updateOrInsert(
            ['key' => $key],
            [
                'name' => $name,
                'tenant_id' => $tenantId,
                'current_value' => $value,
                'reset_period' => $this->getCurrentPeriod($config['reset_on']),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reset sequence to start value.
     */
    public function reset(string $name, ?int $tenantId = null): void
    {
        $config = $this->getDefinition($name);
        $this->set($name, $config['start_value'] - $config['increment'], $tenantId);
    }

    /**
     * Format the sequence value.
     */
    protected function format(array $config, int $value): string
    {
        $pattern = $config['pattern'];
        $result = $config['prefix'];

        // Replace date patterns
        $replacements = [
            '{YYYY}' => date('Y'),
            '{YY}' => date('y'),
            '{MM}' => date('m'),
            '{DD}' => date('d'),
            '{M}' => date('n'),
            '{D}' => date('j'),
        ];

        foreach ($replacements as $placeholder => $replacement) {
            $pattern = str_replace($placeholder, $replacement, $pattern);
        }

        // Replace sequence number
        if (preg_match('/\{(#+)\}/', $pattern, $matches)) {
            $padding = strlen($matches[1]);
            $paddedValue = str_pad((string)$value, $padding, '0', STR_PAD_LEFT);
            $pattern = preg_replace('/\{#+\}/', $paddedValue, $pattern);
        }

        // Alternative number pattern with explicit padding
        if (preg_match('/\{N(\d+)\}/', $pattern, $matches)) {
            $padding = (int)$matches[1];
            $paddedValue = str_pad((string)$value, $padding, '0', STR_PAD_LEFT);
            $pattern = preg_replace('/\{N\d+\}/', $paddedValue, $pattern);
        }

        // Simple {N} for number without padding
        $pattern = str_replace('{N}', (string)$value, $pattern);

        $result .= $pattern . $config['suffix'];

        return $result;
    }

    /**
     * Build the sequence key.
     */
    protected function buildKey(string $name, array $config, ?int $tenantId): string
    {
        $key = $name;

        if ($config['reset_on']) {
            $key .= ':' . $this->getCurrentPeriod($config['reset_on']);
        }

        if ($tenantId) {
            $key .= ':t' . $tenantId;
        }

        return $key;
    }

    /**
     * Get current period string for reset tracking.
     */
    protected function getCurrentPeriod(?string $resetOn): ?string
    {
        return match ($resetOn) {
            'year' => date('Y'),
            'month' => date('Y-m'),
            'day' => date('Y-m-d'),
            default => null,
        };
    }

    /**
     * Get sequence definition.
     */
    protected function getDefinition(string $name): array
    {
        if (!isset($this->definitions[$name])) {
            // Return defaults if not explicitly defined
            return $this->defaults;
        }

        return $this->definitions[$name];
    }

    /**
     * Check if a sequence exists.
     */
    public function exists(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    /**
     * Get all defined sequences.
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * Remove a sequence definition.
     */
    public function remove(string $name): void
    {
        unset($this->definitions[$name]);
    }

    /**
     * Parse a sequence string back to its components.
     */
    public function parse(string $name, string $value): ?array
    {
        $config = $this->getDefinition($name);

        // Remove prefix and suffix
        $inner = $value;
        if ($config['prefix']) {
            $inner = ltrim($inner, $config['prefix']);
        }
        if ($config['suffix']) {
            $inner = rtrim($inner, $config['suffix']);
        }

        // Try to extract year, month, and number
        $result = [
            'original' => $value,
            'year' => null,
            'month' => null,
            'number' => null,
        ];

        // Common patterns
        if (preg_match('/^(\d{4})-(\d+)$/', $inner, $matches)) {
            $result['year'] = (int)$matches[1];
            $result['number'] = (int)$matches[2];
        } elseif (preg_match('/^(\d{4})(\d{2})-(\d+)$/', $inner, $matches)) {
            $result['year'] = (int)$matches[1];
            $result['month'] = (int)$matches[2];
            $result['number'] = (int)$matches[3];
        } elseif (preg_match('/^(\d+)$/', $inner, $matches)) {
            $result['number'] = (int)$matches[1];
        }

        return $result;
    }

    /**
     * Bulk generate sequence values.
     */
    public function nextBatch(string $name, int $count, ?int $tenantId = null): array
    {
        $values = [];
        for ($i = 0; $i < $count; $i++) {
            $values[] = $this->next($name, $tenantId);
        }
        return $values;
    }

    /**
     * Register common sequences.
     */
    public function registerDefaults(): void
    {
        $this->define('invoice', [
            'prefix' => 'INV-',
            'pattern' => '{YYYY}-{####}',
            'reset_on' => 'year',
            'padding' => 4,
        ]);

        $this->define('order', [
            'prefix' => 'SO-',
            'pattern' => '{YYYY}{MM}-{####}',
            'reset_on' => 'month',
            'padding' => 4,
        ]);

        $this->define('purchase', [
            'prefix' => 'PO-',
            'pattern' => '{YYYY}{MM}-{####}',
            'reset_on' => 'month',
            'padding' => 4,
        ]);

        $this->define('quote', [
            'prefix' => 'QT-',
            'pattern' => '{YYYY}-{####}',
            'reset_on' => 'year',
            'padding' => 4,
        ]);

        $this->define('payment', [
            'prefix' => 'PAY-',
            'pattern' => '{YYYY}{MM}{DD}-{####}',
            'reset_on' => 'day',
            'padding' => 4,
        ]);

        $this->define('customer', [
            'prefix' => 'CUS-',
            'pattern' => '{#####}',
            'reset_on' => null,
            'padding' => 5,
        ]);

        $this->define('product', [
            'prefix' => 'PRD-',
            'pattern' => '{#####}',
            'reset_on' => null,
            'padding' => 5,
        ]);

        $this->define('ticket', [
            'prefix' => 'TKT-',
            'pattern' => '{YYYY}{MM}-{#####}',
            'reset_on' => 'month',
            'padding' => 5,
        ]);
    }
}
