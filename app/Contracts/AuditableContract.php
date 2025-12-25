<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for auditable models.
 */
interface AuditableContract
{
    /**
     * Get the audit log for this model.
     *
     * @return Collection
     */
    public function getAuditLog(): Collection;

    /**
     * Get fields that should be audited.
     *
     * @return array<string>
     */
    public function getAuditableFields(): array;

    /**
     * Get fields that should be excluded from audit.
     *
     * @return array<string>
     */
    public function getExcludedAuditFields(): array;

    /**
     * Check if auditing is enabled for this model.
     *
     * @return bool
     */
    public function isAuditingEnabled(): bool;
}
