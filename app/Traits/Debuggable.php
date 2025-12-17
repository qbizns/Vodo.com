<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Debugging\DebugManager;
use App\Services\Debugging\DebugModelWrapper;
use App\Services\Debugging\ExplainService;
use Illuminate\Support\Facades\App;

/**
 * Debuggable Trait - Adds debug capabilities to Eloquent models.
 * 
 * Usage:
 * class Invoice extends Model {
 *     use Debuggable;
 * }
 * 
 * // Debug a create operation
 * $result = Invoice::debug()->create([...]);
 * $result->getTrace();     // Detailed execution trace
 * $result->getSummary();   // Performance summary
 * $result->getResult();    // The actual model
 * 
 * // Explain access
 * $invoice = Invoice::find(1);
 * $invoice->explainAccess('write');
 * 
 * // Explain computed field
 * $invoice->explainField('total');
 */
trait Debuggable
{
    /**
     * Start a debug session for this model.
     */
    public static function debug(): DebugModelWrapper
    {
        $manager = App::make(DebugManager::class);
        return $manager->forModel(static::class);
    }

    /**
     * Explain access to this record.
     */
    public function explainAccess(string $permission = 'read', ?object $user = null): array
    {
        $explainService = App::make(ExplainService::class);
        return $explainService->explainAccess($this, $user, $permission);
    }

    /**
     * Explain a computed field calculation.
     */
    public function explainField(string $fieldName): array
    {
        $explainService = App::make(ExplainService::class);
        return $explainService->explainComputedField($this, $fieldName);
    }

    /**
     * Explain workflow transition availability.
     */
    public function explainTransition(string $transitionName): array
    {
        $explainService = App::make(ExplainService::class);
        return $explainService->explainTransition($this, $transitionName);
    }

    /**
     * Explain query that would be used to list this entity.
     */
    public static function explainQuery(?object $user = null): array
    {
        $explainService = App::make(ExplainService::class);
        return $explainService->explainQuery(static::class, $user);
    }

    /**
     * Get debug info for this record.
     */
    public function getDebugInfo(): array
    {
        return [
            'model' => static::class,
            'key' => $this->getKey(),
            'attributes' => $this->attributesToArray(),
            'dirty' => $this->getDirty(),
            'original' => $this->getOriginal(),
            'relations_loaded' => array_keys($this->getRelations()),
            'exists' => $this->exists,
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Dump debug info.
     */
    public function dd(): void
    {
        dd($this->getDebugInfo());
    }

    /**
     * Dump debug info without dying.
     */
    public function dumpDebug(): self
    {
        dump($this->getDebugInfo());
        return $this;
    }
}
