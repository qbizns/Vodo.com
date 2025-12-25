<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for Sequence Generator.
 */
interface SequenceContract
{
    /**
     * Get the next sequence number.
     *
     * @param string $name Sequence name
     * @param array $context Optional context for pattern variables
     * @return string Formatted sequence number
     */
    public function next(string $name, array $context = []): string;

    /**
     * Get the current sequence number without incrementing.
     *
     * @param string $name Sequence name
     * @return int Current number
     */
    public function current(string $name): int;

    /**
     * Reset a sequence to its initial value.
     *
     * @param string $name Sequence name
     * @return bool
     */
    public function reset(string $name): bool;

    /**
     * Register a new sequence.
     *
     * @param string $name Sequence name
     * @param array $config Sequence configuration
     * @return self
     */
    public function register(string $name, array $config): self;

    /**
     * Check if a sequence exists.
     *
     * @param string $name Sequence name
     * @return bool
     */
    public function has(string $name): bool;
}
