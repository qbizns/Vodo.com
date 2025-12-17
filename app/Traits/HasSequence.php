<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Sequence\SequenceService;

/**
 * HasSequence - Trait for models that need auto-generated sequence numbers.
 * 
 * Usage:
 * 
 * class Invoice extends Model
 * {
 *     use HasSequence;
 *     
 *     protected string $sequenceName = 'invoice';
 *     protected string $sequenceField = 'number'; // default
 * }
 * 
 * // The number will be auto-generated on create
 * $invoice = Invoice::create([...]); // number = INV-2025-0001
 */
trait HasSequence
{
    /**
     * Boot the trait.
     */
    public static function bootHasSequence(): void
    {
        static::creating(function ($model) {
            $field = $model->getSequenceField();
            $sequenceName = $model->getSequenceName();

            // Only generate if field is empty
            if (empty($model->$field) && $sequenceName) {
                $service = app(SequenceService::class);
                $tenantId = method_exists($model, 'getTenantId') ? $model->getTenantId() : null;
                $model->$field = $service->next($sequenceName, $tenantId);
            }
        });
    }

    /**
     * Get the sequence name for this model.
     */
    public function getSequenceName(): ?string
    {
        return $this->sequenceName ?? null;
    }

    /**
     * Get the field to store the sequence value.
     */
    public function getSequenceField(): string
    {
        return $this->sequenceField ?? 'number';
    }

    /**
     * Preview the next sequence value.
     */
    public static function previewNextNumber(): ?string
    {
        $model = new static;
        $sequenceName = $model->getSequenceName();

        if (!$sequenceName) {
            return null;
        }

        $service = app(SequenceService::class);
        $tenantId = method_exists($model, 'getTenantId') ? $model->getTenantId() : null;

        return $service->preview($sequenceName, $tenantId);
    }

    /**
     * Regenerate sequence number (use with caution).
     */
    public function regenerateNumber(): string
    {
        $field = $this->getSequenceField();
        $sequenceName = $this->getSequenceName();

        if (!$sequenceName) {
            throw new \RuntimeException('No sequence name defined');
        }

        $service = app(SequenceService::class);
        $tenantId = method_exists($this, 'getTenantId') ? $this->getTenantId() : null;

        $this->$field = $service->next($sequenceName, $tenantId);
        $this->save();

        return $this->$field;
    }
}
