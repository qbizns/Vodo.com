<?php

declare(strict_types=1);

namespace App\Services\Marketplace\Checks;

use App\Enums\PluginScope;

/**
 * Scopes Check
 *
 * Validates that plugin scope declarations are appropriate.
 */
class ScopesCheck extends BaseCheck
{
    protected function execute(): void
    {
        $manifest = $this->getManifest();
        $permissions = $manifest['permissions'] ?? [];

        $scopes = $permissions['scopes'] ?? [];
        $dangerousScopes = $permissions['dangerous_scopes'] ?? [];
        $allScopes = array_merge($scopes, $dangerousScopes);

        // Check for valid scopes
        $this->validateScopeNames($allScopes);

        // Check for dangerous scopes in wrong section
        $this->checkDangerousScopePlacement($scopes);

        // Check scope count
        $this->checkScopeCount($allScopes);

        // Check for overly broad scopes
        $this->checkBroadScopes($allScopes);

        // Verify scopes match actual code usage
        $this->verifyCodeUsage($allScopes);
    }

    protected function getCategory(): string
    {
        return 'compatibility';
    }

    protected function validateScopeNames(array $scopes): void
    {
        $validScopes = array_map(fn($s) => $s->value, PluginScope::cases());

        foreach ($scopes as $scope) {
            if (!in_array($scope, $validScopes)) {
                $this->addWarning("Unknown scope: {$scope}", 3);
            }
        }
    }

    protected function checkDangerousScopePlacement(array $regularScopes): void
    {
        foreach ($regularScopes as $scope) {
            $enumScope = PluginScope::tryFrom($scope);

            if ($enumScope && $enumScope->isDangerous()) {
                $this->addIssue(
                    "Dangerous scope '{$scope}' must be in 'dangerous_scopes' section",
                    10
                );
            }
        }
    }

    protected function checkScopeCount(array $scopes): void
    {
        if (count($scopes) > 15) {
            $this->addWarning(
                'Plugin requests many scopes (' . count($scopes) . ') - consider reducing',
                5
            );
        }

        if (count($scopes) === 0) {
            $this->addWarning('No scopes declared - plugin may not function correctly', 5);
        }
    }

    protected function checkBroadScopes(array $scopes): void
    {
        $broadScopes = [
            'system:admin' => 'Full system administration access',
            'entities:write' => 'Write access to all entities',
            'users:write' => 'Ability to modify users',
        ];

        foreach ($broadScopes as $scope => $description) {
            if (in_array($scope, $scopes)) {
                $this->addWarning(
                    "Broad scope requested: {$scope} ({$description}) - requires justification",
                    5
                );
            }
        }
    }

    protected function verifyCodeUsage(array $declaredScopes): void
    {
        $files = $this->getPhpFiles();
        $usedScopes = [];

        foreach ($files as $file) {
            $content = $this->readFile($file);
            if (!$content) {
                continue;
            }

            // Check for entity operations
            if (preg_match('/EntityManager|EntityRegistry/', $content)) {
                if (preg_match('/->create\(|->insert\(|->update\(|->delete\(/', $content)) {
                    $usedScopes[] = 'entities:write';
                }
                if (preg_match('/->find\(|->get\(|->query\(|->all\(/', $content)) {
                    $usedScopes[] = 'entities:read';
                }
            }

            // Check for hook operations
            if (preg_match('/HookManager|addAction|addFilter/', $content)) {
                $usedScopes[] = 'hooks:subscribe';
            }
            if (preg_match('/doAction|applyFilters/', $content)) {
                $usedScopes[] = 'hooks:dispatch';
            }

            // Check for user operations
            if (preg_match('/User::|\$user->/', $content)) {
                $usedScopes[] = 'users:read';
            }

            // Check for network operations
            if (preg_match('/Http::|\bcurl_|file_get_contents\s*\(\s*[\'"]https?:/', $content)) {
                $usedScopes[] = 'network:outbound';
            }

            // Check for storage operations
            if (preg_match('/Storage::|\bfopen\(|\bfile_put_contents\(/', $content)) {
                $usedScopes[] = 'storage:write';
            }
        }

        $usedScopes = array_unique($usedScopes);

        // Check for undeclared scopes being used
        foreach ($usedScopes as $scope) {
            if (!in_array($scope, $declaredScopes)) {
                $this->addWarning(
                    "Code appears to use '{$scope}' functionality but scope is not declared",
                    5
                );
            }
        }

        // Check for declared scopes not being used (wasteful)
        $unusedScopes = array_diff($declaredScopes, $usedScopes);
        foreach ($unusedScopes as $scope) {
            // Only warn for common scopes we can detect
            $detectableScopes = [
                'entities:read', 'entities:write',
                'hooks:subscribe', 'hooks:dispatch',
                'network:outbound', 'storage:write',
            ];

            if (in_array($scope, $detectableScopes)) {
                $this->addWarning(
                    "Scope '{$scope}' is declared but may not be used",
                    2
                );
            }
        }
    }
}
