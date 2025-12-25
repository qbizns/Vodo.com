<?php

declare(strict_types=1);

namespace App\Services\Data;

use App\Contracts\SequenceContract;
use App\Models\Sequence;
use Illuminate\Support\Facades\DB;

/**
 * Sequence Service
 *
 * Generates sequential, formatted identifiers for records.
 * Supports patterns with variables like year, month, prefix.
 *
 * @example Register a sequence
 * ```php
 * $service->register('invoice', [
 *     'pattern' => 'INV-{YYYY}-{#####}',
 *     'start' => 1,
 *     'increment' => 1,
 *     'reset' => 'yearly',
 * ]);
 * ```
 *
 * @example Get next sequence
 * ```php
 * $number = $service->next('invoice'); // INV-2024-00001
 * ```
 */
class SequenceService implements SequenceContract
{
    /**
     * Registered sequence configurations.
     *
     * @var array<string, array>
     */
    protected array $sequences = [];

    /**
     * Default sequence configuration.
     */
    protected array $defaultConfig = [
        'pattern' => '{PREFIX}-{#####}',
        'start' => 1,
        'increment' => 1,
        'prefix' => '',
        'suffix' => '',
        'reset' => null, // yearly, monthly, daily, never
        'padding' => 5,
    ];

    public function next(string $name, array $context = []): string
    {
        return DB::transaction(function () use ($name, $context) {
            $config = $this->getConfig($name);
            $sequence = $this->getOrCreateSequence($name, $config);

            // Check if reset is needed
            $this->checkReset($sequence, $config);

            // Get next number
            $number = $sequence->current_value + $config['increment'];
            $sequence->current_value = $number;
            $sequence->last_used_at = now();
            $sequence->save();

            // Format the sequence
            return $this->formatSequence($number, $config, $context);
        });
    }

    public function current(string $name): int
    {
        $sequence = Sequence::where('name', $name)->first();

        return $sequence ? $sequence->current_value : 0;
    }

    public function reset(string $name): bool
    {
        $config = $this->getConfig($name);
        $sequence = Sequence::where('name', $name)->first();

        if (!$sequence) {
            return false;
        }

        $sequence->current_value = $config['start'] - $config['increment'];
        $sequence->last_reset_at = now();
        $sequence->save();

        return true;
    }

    public function register(string $name, array $config): self
    {
        $this->sequences[$name] = array_merge($this->defaultConfig, $config);

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->sequences[$name]) || Sequence::where('name', $name)->exists();
    }

    /**
     * Get sequence configuration.
     */
    protected function getConfig(string $name): array
    {
        if (isset($this->sequences[$name])) {
            return $this->sequences[$name];
        }

        $sequence = Sequence::where('name', $name)->first();
        if ($sequence) {
            return array_merge($this->defaultConfig, $sequence->config ?? []);
        }

        return $this->defaultConfig;
    }

    /**
     * Get or create sequence record.
     */
    protected function getOrCreateSequence(string $name, array $config): Sequence
    {
        return Sequence::lockForUpdate()->firstOrCreate(
            ['name' => $name],
            [
                'current_value' => $config['start'] - $config['increment'],
                'config' => $config,
            ]
        );
    }

    /**
     * Check if sequence needs reset.
     */
    protected function checkReset(Sequence $sequence, array $config): void
    {
        $reset = $config['reset'] ?? null;
        if (!$reset || $reset === 'never') {
            return;
        }

        $lastReset = $sequence->last_reset_at ?? $sequence->created_at;
        $needsReset = match ($reset) {
            'yearly' => $lastReset->year !== now()->year,
            'monthly' => $lastReset->format('Y-m') !== now()->format('Y-m'),
            'daily' => $lastReset->format('Y-m-d') !== now()->format('Y-m-d'),
            default => false,
        };

        if ($needsReset) {
            $sequence->current_value = $config['start'] - $config['increment'];
            $sequence->last_reset_at = now();
        }
    }

    /**
     * Format sequence number using pattern.
     */
    protected function formatSequence(int $number, array $config, array $context = []): string
    {
        $pattern = $config['pattern'];
        $padding = $config['padding'];

        // Replace number placeholder
        $pattern = preg_replace_callback('/\{#+\}/', function ($match) use ($number) {
            $length = strlen($match[0]) - 2; // Remove braces

            return str_pad((string) $number, $length, '0', STR_PAD_LEFT);
        }, $pattern);

        // Replace standard variables
        $replacements = [
            '{YYYY}' => date('Y'),
            '{YY}' => date('y'),
            '{MM}' => date('m'),
            '{DD}' => date('d'),
            '{PREFIX}' => $config['prefix'] ?? '',
            '{SUFFIX}' => $config['suffix'] ?? '',
            '{NUMBER}' => str_pad((string) $number, $padding, '0', STR_PAD_LEFT),
        ];

        // Add context variables
        foreach ($context as $key => $value) {
            $replacements['{' . strtoupper($key) . '}'] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    /**
     * Preview next sequence number without incrementing.
     *
     * @param string $name Sequence name
     * @param array $context Context variables
     * @return string
     */
    public function preview(string $name, array $context = []): string
    {
        $config = $this->getConfig($name);
        $sequence = Sequence::where('name', $name)->first();

        $currentValue = $sequence ? $sequence->current_value : ($config['start'] - $config['increment']);
        $nextValue = $currentValue + $config['increment'];

        return $this->formatSequence($nextValue, $config, $context);
    }

    /**
     * Get all registered sequences.
     *
     * @return array
     */
    public function all(): array
    {
        $dbSequences = Sequence::all()->keyBy('name')->toArray();

        return array_merge($dbSequences, $this->sequences);
    }

    /**
     * Delete a sequence.
     *
     * @param string $name Sequence name
     * @return bool
     */
    public function delete(string $name): bool
    {
        unset($this->sequences[$name]);

        return Sequence::where('name', $name)->delete() > 0;
    }
}
