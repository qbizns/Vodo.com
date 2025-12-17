<?php

declare(strict_types=1);

namespace App\Services\Debugging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ExplainService - Explains how record rules, computed fields, and security work.
 * 
 * Like SQL EXPLAIN but for business logic.
 */
class ExplainService
{
    /**
     * Explain why a record is accessible (or not) to a user.
     */
    public function explainAccess(Model $record, ?object $user = null, string $permission = 'read'): array
    {
        $user = $user ?? Auth::user();
        $entityName = $this->getEntityName($record);

        $explanation = [
            'entity' => $entityName,
            'record_id' => $record->getKey(),
            'user_id' => $user?->id,
            'permission' => $permission,
            'rules_evaluated' => [],
            'final_result' => null,
            'reason' => null,
        ];

        // Get applicable rules
        $recordRuleEngine = app(\App\Services\RecordRule\RecordRuleEngine::class);
        
        // Check if method exists, otherwise use reflection or manual evaluation
        if (method_exists($recordRuleEngine, 'evaluateWithExplanation')) {
            $result = $recordRuleEngine->evaluateWithExplanation($record, $user, $permission);
            $explanation = array_merge($explanation, $result);
        } else {
            // Manual explanation
            $explanation['rules_evaluated'] = $this->evaluateRulesWithExplanation(
                $record,
                $user,
                $permission
            );
            $explanation['final_result'] = $this->determineResult($explanation['rules_evaluated']);
            $explanation['reason'] = $this->generateReason($explanation);
        }

        return $explanation;
    }

    /**
     * Explain computed field calculation.
     */
    public function explainComputedField(Model $record, string $fieldName): array
    {
        $entityName = $this->getEntityName($record);

        $explanation = [
            'entity' => $entityName,
            'record_id' => $record->getKey(),
            'field' => $fieldName,
            'current_value' => $record->$fieldName,
            'dependencies' => [],
            'calculation_steps' => [],
            'formula' => null,
        ];

        $computedFieldManager = app(\App\Services\ComputedField\ComputedFieldManager::class);

        // Get field definition
        if (method_exists($computedFieldManager, 'getFieldDefinition')) {
            $definition = $computedFieldManager->getFieldDefinition($entityName, $fieldName);
            $explanation['formula'] = $definition['compute'] ?? null;
            $explanation['dependencies'] = $definition['dependencies'] ?? [];
        }

        // Get dependency values
        foreach ($explanation['dependencies'] as $dep) {
            if (str_contains($dep, '.')) {
                // Relation field
                [$relation, $field] = explode('.', $dep, 2);
                $explanation['dependency_values'][$dep] = $record->$relation?->$field;
            } else {
                $explanation['dependency_values'][$dep] = $record->$dep;
            }
        }

        // Generate calculation explanation
        $explanation['calculation_steps'] = $this->traceCalculation($record, $fieldName);

        return $explanation;
    }

    /**
     * Explain why a workflow transition is available (or not).
     */
    public function explainTransition(Model $record, string $transitionName): array
    {
        $entityName = $this->getEntityName($record);

        $explanation = [
            'entity' => $entityName,
            'record_id' => $record->getKey(),
            'transition' => $transitionName,
            'current_state' => $record->state ?? $record->status ?? null,
            'can_transition' => false,
            'conditions' => [],
            'blockers' => [],
        ];

        $workflowEngine = app(\App\Services\Workflow\WorkflowEngine::class);

        if (method_exists($workflowEngine, 'getTransitionDefinition')) {
            $transition = $workflowEngine->getTransitionDefinition($entityName, $transitionName);
            
            if (!$transition) {
                $explanation['blockers'][] = "Transition '{$transitionName}' not found";
                return $explanation;
            }

            // Check from state
            $fromStates = (array) ($transition['from'] ?? []);
            if (!in_array($explanation['current_state'], $fromStates)) {
                $explanation['blockers'][] = sprintf(
                    "Current state '%s' is not in allowed states: %s",
                    $explanation['current_state'],
                    implode(', ', $fromStates)
                );
            }

            // Evaluate conditions
            foreach ($transition['conditions'] ?? [] as $condition) {
                $result = $this->evaluateCondition($record, $condition);
                $explanation['conditions'][] = [
                    'definition' => $condition,
                    'result' => $result['passed'],
                    'actual_value' => $result['actual_value'],
                    'message' => $result['message'],
                ];

                if (!$result['passed']) {
                    $explanation['blockers'][] = $result['message'];
                }
            }

            $explanation['can_transition'] = empty($explanation['blockers']);
        }

        return $explanation;
    }

    /**
     * Explain query being executed for an entity list.
     */
    public function explainQuery(string $modelClass, ?object $user = null): array
    {
        $user = $user ?? Auth::user();
        $model = new $modelClass;
        $entityName = $this->getEntityName($model);

        $explanation = [
            'entity' => $entityName,
            'model' => $modelClass,
            'user_id' => $user?->id,
            'base_query' => null,
            'scopes_applied' => [],
            'record_rules_applied' => [],
            'final_query' => null,
        ];

        // Get base query
        $query = $modelClass::query();
        $explanation['base_query'] = $query->toSql();

        // Get applied scopes
        $explanation['scopes_applied'] = $this->getAppliedScopes($query);

        // Get record rules
        $recordRuleEngine = app(\App\Services\RecordRule\RecordRuleEngine::class);
        if (method_exists($recordRuleEngine, 'getApplicableRulesForExplain')) {
            $explanation['record_rules_applied'] = $recordRuleEngine->getApplicableRulesForExplain(
                $entityName,
                'read',
                $user
            );
        }

        // Final query with all filters
        $explanation['final_query'] = [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ];

        // Database EXPLAIN
        try {
            $explanation['database_explain'] = DB::select(
                'EXPLAIN ' . $query->toSql(),
                $query->getBindings()
            );
        } catch (\Throwable $e) {
            $explanation['database_explain_error'] = $e->getMessage();
        }

        return $explanation;
    }

    /**
     * Get entity name from model.
     */
    protected function getEntityName(Model $model): string
    {
        if (method_exists($model, 'getEntityName')) {
            return $model->getEntityName();
        }
        return $model->getTable();
    }

    /**
     * Evaluate rules with detailed explanation.
     */
    protected function evaluateRulesWithExplanation(Model $record, ?object $user, string $permission): array
    {
        $rules = [];
        
        // This would integrate with RecordRuleEngine
        // Simplified version for demonstration
        
        return $rules;
    }

    /**
     * Determine final result from evaluated rules.
     */
    protected function determineResult(array $evaluatedRules): bool
    {
        foreach ($evaluatedRules as $rule) {
            if ($rule['result'] === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate human-readable reason.
     */
    protected function generateReason(array $explanation): string
    {
        if ($explanation['final_result']) {
            $passingRules = array_filter(
                $explanation['rules_evaluated'],
                fn($r) => $r['result'] === true
            );
            $ruleNames = array_column($passingRules, 'name');
            return "Access granted by rules: " . implode(', ', $ruleNames);
        }

        return "Access denied - no matching rules granted permission";
    }

    /**
     * Trace a computed field calculation.
     */
    protected function traceCalculation(Model $record, string $fieldName): array
    {
        $steps = [];
        
        // This would integrate with ComputedFieldManager
        // Returns step-by-step calculation trace
        
        return $steps;
    }

    /**
     * Evaluate a workflow condition.
     */
    protected function evaluateCondition(Model $record, array $condition): array
    {
        $field = $condition['field'] ?? $condition[0] ?? null;
        $operator = $condition['operator'] ?? $condition[1] ?? '=';
        $expected = $condition['value'] ?? $condition[2] ?? null;
        $actual = $record->$field ?? null;

        $passed = match ($operator) {
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            'in' => in_array($actual, (array) $expected),
            'not_in' => !in_array($actual, (array) $expected),
            default => false,
        };

        return [
            'passed' => $passed,
            'actual_value' => $actual,
            'message' => $passed
                ? "Condition passed: {$field} {$operator} {$expected}"
                : "Condition failed: {$field} is '{$actual}', expected {$operator} '{$expected}'",
        ];
    }

    /**
     * Get applied scopes from query.
     */
    protected function getAppliedScopes($query): array
    {
        $scopes = [];
        
        // Get global scopes
        if (method_exists($query->getModel(), 'getGlobalScopes')) {
            foreach ($query->getModel()->getGlobalScopes() as $name => $scope) {
                $scopes[] = [
                    'type' => 'global',
                    'name' => $name,
                    'class' => is_object($scope) ? get_class($scope) : 'Closure',
                ];
            }
        }

        return $scopes;
    }
}
