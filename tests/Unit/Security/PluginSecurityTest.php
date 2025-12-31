<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Enums\PluginScope;
use App\Models\Plugin;
use App\Models\PluginApiKey;
use App\Models\PluginAuditLog;
use App\Models\PluginPermission;
use App\Models\PluginResourceUsage;
use App\Services\Plugins\Security\PluginApiKeyManager;
use App\Services\Plugins\Security\PluginPermissionRegistry;
use App\Services\Plugins\Security\PluginSandbox;
use App\Services\Plugins\Security\ScopeValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plugin Security Test
 *
 * Comprehensive tests for Phase 1: Security & Plugin Foundation
 */
class PluginSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Plugin $plugin;
    protected PluginPermissionRegistry $permissionRegistry;
    protected ScopeValidator $scopeValidator;
    protected PluginSandbox $sandbox;
    protected PluginApiKeyManager $apiKeyManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test plugin
        $this->plugin = Plugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'status' => Plugin::STATUS_ACTIVE,
        ]);

        $this->permissionRegistry = app(PluginPermissionRegistry::class);
        $this->scopeValidator = app(ScopeValidator::class);
        $this->sandbox = app(PluginSandbox::class);
        $this->apiKeyManager = app(PluginApiKeyManager::class);
    }

    // =========================================================================
    // PluginScope Enum Tests
    // =========================================================================

    public function test_plugin_scope_has_all_required_scopes(): void
    {
        $scopes = PluginScope::cases();

        $this->assertGreaterThan(10, count($scopes));
        $this->assertContains(PluginScope::ENTITIES_READ, $scopes);
        $this->assertContains(PluginScope::ENTITIES_WRITE, $scopes);
        $this->assertContains(PluginScope::HOOKS_SUBSCRIBE, $scopes);
        $this->assertContains(PluginScope::API_ACCESS, $scopes);
    }

    public function test_scope_provides_display_name_and_description(): void
    {
        $scope = PluginScope::ENTITIES_READ;

        $this->assertNotEmpty($scope->displayName());
        $this->assertNotEmpty($scope->description());
        $this->assertEquals('entities', $scope->category());
    }

    public function test_dangerous_scopes_have_high_risk_level(): void
    {
        $dangerousScopes = PluginScope::dangerous();

        $this->assertNotEmpty($dangerousScopes);

        foreach ($dangerousScopes as $scope) {
            $this->assertGreaterThanOrEqual(4, $scope->riskLevel());
            $this->assertTrue($scope->isDangerous());
        }
    }

    public function test_scope_implies_correctly(): void
    {
        $writeScope = PluginScope::ENTITIES_WRITE;
        $implied = $writeScope->implies();

        $this->assertContains(PluginScope::ENTITIES_READ, $implied);
    }

    public function test_scope_parse_handles_resource_specifier(): void
    {
        $parsed = PluginScope::parse('entities:read:product');

        $this->assertEquals(PluginScope::ENTITIES_READ, $parsed['scope']);
        $this->assertEquals('product', $parsed['resource']);
    }

    public function test_scope_to_array_returns_expected_format(): void
    {
        $array = PluginScope::ENTITIES_READ->toArray();

        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('risk_level', $array);
    }

    // =========================================================================
    // PluginPermission Model Tests
    // =========================================================================

    public function test_can_create_plugin_permission(): void
    {
        $permission = PluginPermission::create([
            'plugin_slug' => $this->plugin->slug,
            'scope' => PluginScope::ENTITIES_READ->value,
            'access_level' => PluginPermission::ACCESS_READ,
            'is_granted' => true,
            'granted_at' => now(),
        ]);

        $this->assertDatabaseHas('plugin_permissions', [
            'plugin_slug' => $this->plugin->slug,
            'scope' => 'entities:read',
        ]);
    }

    public function test_permission_allows_checks_access_hierarchy(): void
    {
        $permission = PluginPermission::create([
            'plugin_slug' => $this->plugin->slug,
            'scope' => PluginScope::ENTITIES_WRITE->value,
            'access_level' => PluginPermission::ACCESS_WRITE,
            'is_granted' => true,
            'granted_at' => now(),
        ]);

        // Write permission should allow read
        $this->assertTrue($permission->allows(PluginPermission::ACCESS_READ));
        $this->assertTrue($permission->allows(PluginPermission::ACCESS_WRITE));
        // But not delete
        $this->assertFalse($permission->allows(PluginPermission::ACCESS_DELETE));
    }

    public function test_permission_can_be_revoked(): void
    {
        $permission = PluginPermission::create([
            'plugin_slug' => $this->plugin->slug,
            'scope' => PluginScope::ENTITIES_READ->value,
            'is_granted' => true,
            'granted_at' => now(),
        ]);

        $this->assertTrue($permission->isActive());

        $permission->revoke();

        $this->assertFalse($permission->fresh()->isActive());
        $this->assertNotNull($permission->fresh()->revoked_at);
    }

    // =========================================================================
    // PluginPermissionRegistry Tests
    // =========================================================================

    public function test_registry_can_grant_permission(): void
    {
        $permission = $this->permissionRegistry->grant(
            $this->plugin->slug,
            PluginScope::ENTITIES_READ
        );

        $this->assertInstanceOf(PluginPermission::class, $permission);
        $this->assertTrue($permission->isActive());
    }

    public function test_registry_checks_permission_correctly(): void
    {
        $this->permissionRegistry->grant(
            $this->plugin->slug,
            PluginScope::ENTITIES_READ
        );

        $this->assertTrue(
            $this->permissionRegistry->hasPermission($this->plugin->slug, PluginScope::ENTITIES_READ)
        );

        $this->assertFalse(
            $this->permissionRegistry->hasPermission($this->plugin->slug, PluginScope::ENTITIES_WRITE)
        );
    }

    public function test_registry_can_revoke_permission(): void
    {
        $this->permissionRegistry->grant($this->plugin->slug, PluginScope::ENTITIES_READ);

        $this->assertTrue($this->permissionRegistry->hasPermission($this->plugin->slug, PluginScope::ENTITIES_READ));

        $this->permissionRegistry->revoke($this->plugin->slug, PluginScope::ENTITIES_READ);

        // Need to clear cache
        $this->permissionRegistry->clearCache($this->plugin->slug);

        $this->assertFalse($this->permissionRegistry->hasPermission($this->plugin->slug, PluginScope::ENTITIES_READ));
    }

    public function test_registry_parses_manifest_permissions(): void
    {
        $manifest = [
            'permissions' => [
                'entities' => ['read:*', 'write:product'],
                'hooks' => ['subscribe:order.*'],
                'api' => [
                    'rate_limit' => '100/minute',
                    'endpoints' => ['products', 'orders'],
                ],
            ],
        ];

        $parsed = $this->permissionRegistry->parseManifestPermissions($manifest);

        $this->assertArrayHasKey('required', $parsed);
        $this->assertArrayHasKey('optional', $parsed);
        $this->assertArrayHasKey('dangerous', $parsed);
        $this->assertNotEmpty($parsed['required']);
    }

    // =========================================================================
    // ScopeValidator Tests
    // =========================================================================

    public function test_scope_validator_validates_scopes(): void
    {
        $this->assertTrue($this->scopeValidator->isValidScope('entities:read'));
        $this->assertTrue($this->scopeValidator->isValidScope('entities:read:product'));
        $this->assertFalse($this->scopeValidator->isValidScope('invalid:scope'));
    }

    public function test_scope_validator_checks_access_in_context(): void
    {
        $this->permissionRegistry->grant($this->plugin->slug, PluginScope::ENTITIES_READ);

        $this->scopeValidator->setPluginContext($this->plugin->slug);

        $this->assertTrue($this->scopeValidator->canAccess(PluginScope::ENTITIES_READ));
        $this->assertFalse($this->scopeValidator->canAccess(PluginScope::ENTITIES_WRITE));
    }

    public function test_scope_validator_categorizes_for_consent(): void
    {
        $scopes = [
            'entities:read',
            'entities:write',
            'system:admin',
        ];

        $categorized = $this->scopeValidator->categorizeScopesForConsent($scopes);

        $this->assertArrayHasKey('safe', $categorized);
        $this->assertArrayHasKey('caution', $categorized);
        $this->assertArrayHasKey('dangerous', $categorized);
    }

    // =========================================================================
    // PluginApiKey Model Tests
    // =========================================================================

    public function test_can_create_api_key(): void
    {
        $result = PluginApiKey::createForPlugin(
            $this->plugin->slug,
            'Test Key',
            [PluginScope::ENTITIES_READ->value]
        );

        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('key', $result);
        $this->assertStringStartsWith('vodo_', $result['key']);
    }

    public function test_api_key_can_be_validated(): void
    {
        $result = PluginApiKey::createForPlugin(
            $this->plugin->slug,
            'Test Key'
        );

        $found = PluginApiKey::findByKey($result['key']);

        $this->assertNotNull($found);
        $this->assertEquals($result['model']->id, $found->id);
    }

    public function test_invalid_api_key_returns_null(): void
    {
        $found = PluginApiKey::findByKey('vodo_invalid_key');

        $this->assertNull($found);
    }

    public function test_api_key_checks_ip_restrictions(): void
    {
        $result = PluginApiKey::createForPlugin(
            $this->plugin->slug,
            'Test Key',
            [],
            ['127.0.0.1', '192.168.1.1']
        );

        $key = $result['model'];

        $this->assertTrue($key->isIpAllowed('127.0.0.1'));
        $this->assertTrue($key->isIpAllowed('192.168.1.1'));
        $this->assertFalse($key->isIpAllowed('10.0.0.1'));
    }

    public function test_api_key_can_be_rotated(): void
    {
        $result = PluginApiKey::createForPlugin($this->plugin->slug, 'Test Key');
        $originalKeyId = $result['model']->key_id;

        $newKey = $result['model']->rotate();

        $this->assertNotEquals($originalKeyId, $result['model']->fresh()->key_id);
        $this->assertStringStartsWith('vodo_', $newKey);
    }

    // =========================================================================
    // PluginApiKeyManager Tests
    // =========================================================================

    public function test_manager_creates_key(): void
    {
        $result = $this->apiKeyManager->createKey(
            $this->plugin->slug,
            'Test API Key',
            [PluginScope::ENTITIES_READ->value]
        );

        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('model', $result);
    }

    public function test_manager_authenticates_valid_key(): void
    {
        $result = $this->apiKeyManager->createKey(
            $this->plugin->slug,
            'Test API Key'
        );

        $authResult = $this->apiKeyManager->authenticate($result['key'], '127.0.0.1');

        $this->assertTrue($authResult['valid']);
        $this->assertNotNull($authResult['key']);
        $this->assertNull($authResult['error']);
    }

    public function test_manager_rejects_invalid_key(): void
    {
        $authResult = $this->apiKeyManager->authenticate('vodo_invalid_key', '127.0.0.1');

        $this->assertFalse($authResult['valid']);
        $this->assertNotNull($authResult['error']);
    }

    public function test_manager_revokes_key(): void
    {
        $result = $this->apiKeyManager->createKey($this->plugin->slug, 'Test Key');

        $revoked = $this->apiKeyManager->revokeKey($result['model']->id);

        $this->assertTrue($revoked);
        $this->assertFalse($result['model']->fresh()->is_active);
    }

    // =========================================================================
    // PluginSandbox Tests
    // =========================================================================

    public function test_sandbox_provides_default_limits(): void
    {
        $limits = $this->sandbox->getDefaultLimits();

        $this->assertArrayHasKey('memory_mb', $limits);
        $this->assertArrayHasKey('execution_time_seconds', $limits);
        $this->assertArrayHasKey('api_requests_per_minute', $limits);
    }

    public function test_sandbox_allows_custom_plugin_limits(): void
    {
        $customLimits = ['memory_mb' => 512];
        $this->sandbox->setPluginLimits($this->plugin->slug, $customLimits);

        $limits = $this->sandbox->getPluginLimits($this->plugin->slug);

        $this->assertEquals(512, $limits['memory_mb']);
    }

    public function test_sandbox_tracks_execution(): void
    {
        $this->sandbox->beginExecution($this->plugin->slug);

        usleep(10000); // 10ms

        $stats = $this->sandbox->endExecution();

        $this->assertEquals($this->plugin->slug, $stats['plugin']);
        $this->assertGreaterThan(0, $stats['execution_time_ms']);
    }

    public function test_sandbox_blocks_plugin(): void
    {
        $this->assertFalse($this->sandbox->isBlocked($this->plugin->slug));

        $this->sandbox->blockPlugin($this->plugin->slug, 60);

        $this->assertTrue($this->sandbox->isBlocked($this->plugin->slug));

        $this->sandbox->unblockPlugin($this->plugin->slug);

        $this->assertFalse($this->sandbox->isBlocked($this->plugin->slug));
    }

    public function test_sandbox_checks_domain_whitelist(): void
    {
        $this->sandbox->setPluginLimits($this->plugin->slug, [
            'network_whitelist' => ['api.example.com', '*.trusted.com'],
        ]);

        $this->assertTrue($this->sandbox->isDomainAllowed($this->plugin->slug, 'api.example.com'));
        $this->assertTrue($this->sandbox->isDomainAllowed($this->plugin->slug, 'sub.trusted.com'));
        $this->assertFalse($this->sandbox->isDomainAllowed($this->plugin->slug, 'malicious.com'));
    }

    // =========================================================================
    // PluginResourceUsage Tests
    // =========================================================================

    public function test_resource_usage_creates_daily_record(): void
    {
        $usage = PluginResourceUsage::forPluginToday($this->plugin->slug);

        $this->assertEquals($this->plugin->slug, $usage->plugin_slug);
        $this->assertEquals(now()->toDateString(), $usage->usage_date->toDateString());
    }

    public function test_resource_usage_increments_counters(): void
    {
        $usage = PluginResourceUsage::forPluginToday($this->plugin->slug);

        $usage->recordApiRequest();
        $usage->recordApiRequest();

        $this->assertEquals(2, $usage->fresh()->api_requests);
    }

    public function test_resource_usage_provides_summary(): void
    {
        $usage = PluginResourceUsage::forPluginToday($this->plugin->slug);
        $usage->recordApiRequest();
        $usage->recordEntityReads(10);

        $summary = $usage->getSummary();

        $this->assertArrayHasKey('api_requests', $summary);
        $this->assertArrayHasKey('entity_operations', $summary);
        $this->assertEquals(1, $summary['api_requests']);
        $this->assertEquals(10, $summary['entity_operations']['reads']);
    }

    // =========================================================================
    // PluginAuditLog Tests
    // =========================================================================

    public function test_audit_log_creates_security_event(): void
    {
        $log = PluginAuditLog::security(
            $this->plugin->slug,
            PluginAuditLog::EVENT_PERMISSION_GRANTED,
            'Test permission granted'
        );

        $this->assertDatabaseHas('plugin_audit_logs', [
            'plugin_slug' => $this->plugin->slug,
            'event_type' => 'permission_granted',
            'event_category' => 'security',
        ]);
    }

    public function test_audit_log_filters_by_severity(): void
    {
        PluginAuditLog::security($this->plugin->slug, 'test_info', 'Info message', [], PluginAuditLog::SEVERITY_INFO);
        PluginAuditLog::security($this->plugin->slug, 'test_error', 'Error message', [], PluginAuditLog::SEVERITY_ERROR);

        $errors = PluginAuditLog::forPlugin($this->plugin->slug)->errors()->get();

        $this->assertEquals(1, $errors->count());
        $this->assertEquals('test_error', $errors->first()->event_type);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    public function test_full_permission_flow(): void
    {
        // 1. Parse manifest
        $manifest = [
            'permissions' => [
                'entities' => ['read:*', 'write:product'],
            ],
        ];

        // 2. Grant permissions from manifest
        $result = $this->permissionRegistry->grantFromManifest($this->plugin->slug, $manifest);

        $this->assertNotEmpty($result['granted']);

        // 3. Validate access
        $this->scopeValidator->setPluginContext($this->plugin->slug);
        $this->assertTrue($this->scopeValidator->canAccessEntity('product', 'read'));
    }

    public function test_full_api_key_authentication_flow(): void
    {
        // 1. Create API key with scopes
        $result = $this->apiKeyManager->createKey(
            $this->plugin->slug,
            'Production Key',
            [PluginScope::ENTITIES_READ->value, PluginScope::API_ACCESS->value]
        );

        // 2. Authenticate
        $authResult = $this->apiKeyManager->authenticate($result['key'], '127.0.0.1');
        $this->assertTrue($authResult['valid']);

        // 3. Check scopes
        $this->assertTrue($authResult['key']->hasScope(PluginScope::ENTITIES_READ->value));
        $this->assertFalse($authResult['key']->hasScope(PluginScope::SYSTEM_ADMIN->value));

        // 4. Check rate limits
        $rateLimits = $this->apiKeyManager->getRateLimitStatus($authResult['key']);
        $this->assertArrayHasKey('minute', $rateLimits);
    }
}
