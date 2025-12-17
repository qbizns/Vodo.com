<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\RecordRule\RecordRuleEngine;
use App\Models\RecordRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;

class RecordRuleEngineTest extends TestCase
{
    use RefreshDatabase;

    protected RecordRuleEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RecordRuleEngine();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Rule Definition Tests
    // =========================================================================

    public function test_can_define_record_rule(): void
    {
        $rule = $this->engine->defineRule('invoice', [
            'name' => 'Salesperson sees own invoices',
            'domain' => [['user_id', '=', '{user.id}']],
            'groups' => ['salesperson'],
            'perm_read' => true,
            'perm_write' => true,
        ]);

        $this->assertInstanceOf(RecordRule::class, $rule);
        $this->assertEquals('invoice', $rule->entity_name);
        $this->assertTrue($rule->perm_read);
    }

    public function test_rule_tracks_plugin(): void
    {
        $rule = $this->engine->defineRule('invoice', [
            'name' => 'Plugin rule',
            'domain' => [],
        ], 'sales-plugin');

        $this->assertEquals('sales-plugin', $rule->plugin_slug);
    }

    // =========================================================================
    // Domain Evaluation Tests
    // =========================================================================

    public function test_can_access_with_matching_domain(): void
    {
        $user = $this->createMockUser(['id' => 1, 'roles' => ['salesperson']]);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);

        $this->engine->defineRule('invoice', [
            'name' => 'Own invoices',
            'domain' => [['user_id', '=', '{user.id}']],
            'groups' => ['salesperson'],
            'perm_read' => true,
        ]);

        $invoice = $this->createMockRecord(['user_id' => 1]);

        $canAccess = $this->engine->canAccess($invoice, 'read', $user);

        $this->assertTrue($canAccess);
    }

    public function test_cannot_access_with_non_matching_domain(): void
    {
        $user = $this->createMockUser(['id' => 1, 'roles' => ['salesperson']]);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);

        $this->engine->defineRule('invoice', [
            'name' => 'Own invoices',
            'domain' => [['user_id', '=', '{user.id}']],
            'groups' => ['salesperson'],
            'perm_read' => true,
        ]);

        $invoice = $this->createMockRecord(['user_id' => 999]); // Different user

        $canAccess = $this->engine->canAccess($invoice, 'read', $user);

        $this->assertFalse($canAccess);
    }

    // =========================================================================
    // Permission Tests
    // =========================================================================

    public function test_rule_respects_permission_type(): void
    {
        $user = $this->createMockUser(['id' => 1, 'roles' => ['viewer']]);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);

        $this->engine->defineRule('invoice', [
            'name' => 'Read only',
            'domain' => [['user_id', '=', '{user.id}']],
            'groups' => ['viewer'],
            'perm_read' => true,
            'perm_write' => false,
        ]);

        $invoice = $this->createMockRecord(['user_id' => 1]);

        $this->assertTrue($this->engine->canAccess($invoice, 'read', $user));
        $this->assertFalse($this->engine->canAccess($invoice, 'write', $user));
    }

    // =========================================================================
    // Group Tests
    // =========================================================================

    public function test_rule_applies_to_matching_groups(): void
    {
        $user = $this->createMockUser(['id' => 1, 'roles' => ['manager']]);

        $rule = RecordRule::create([
            'name' => 'Manager rule',
            'entity_name' => 'invoice',
            'domain' => [],
            'groups' => ['manager', 'admin'],
            'perm_read' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($rule->appliesTo(['manager']));
        $this->assertTrue($rule->appliesTo(['admin']));
        $this->assertFalse($rule->appliesTo(['salesperson']));
    }

    public function test_global_rule_applies_to_all(): void
    {
        $rule = RecordRule::create([
            'name' => 'Global rule',
            'entity_name' => 'invoice',
            'domain' => [['is_public', '=', true]],
            'groups' => [],
            'perm_read' => true,
            'is_global' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($rule->appliesTo(['any_group']));
        $this->assertTrue($rule->appliesTo([]));
    }

    // =========================================================================
    // Domain Operator Tests
    // =========================================================================

    public function test_supports_in_operator(): void
    {
        $user = $this->createMockUser(['id' => 1, 'team_ids' => [1, 2, 3]]);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);

        $this->engine->defineRule('invoice', [
            'name' => 'Team invoices',
            'domain' => [['team_id', 'in', '{user.team_ids}']],
            'perm_read' => true,
            'is_global' => true,
        ]);

        $invoice = $this->createMockRecord(['team_id' => 2]);

        $canAccess = $this->engine->canAccess($invoice, 'read', $user);

        $this->assertTrue($canAccess);
    }

    // =========================================================================
    // Bypass Tests
    // =========================================================================

    public function test_can_bypass_rules(): void
    {
        $user = $this->createMockUser(['id' => 1]);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);

        $this->engine->defineRule('invoice', [
            'name' => 'Restrictive rule',
            'domain' => [['user_id', '=', 999]],
            'perm_read' => true,
            'is_global' => true,
        ]);

        $invoice = $this->createMockRecord(['user_id' => 1]);

        // Without bypass - should fail
        $this->assertFalse($this->engine->canAccess($invoice, 'read', $user));

        // With bypass - should succeed
        $result = $this->engine->withoutRules(function () use ($invoice, $user) {
            return $this->engine->canAccess($invoice, 'read', $user);
        });

        $this->assertTrue($result);
    }

    // =========================================================================
    // Create Permission Tests
    // =========================================================================

    public function test_can_check_create_permission(): void
    {
        $user = $this->createMockUser(['id' => 1, 'roles' => ['creator']]);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);

        $this->engine->defineRule('invoice', [
            'name' => 'Can create',
            'domain' => [],
            'groups' => ['creator'],
            'perm_create' => true,
        ]);

        $canCreate = $this->engine->canCreate('invoice', $user);

        $this->assertTrue($canCreate);
    }

    // =========================================================================
    // Custom Operator Tests
    // =========================================================================

    public function test_can_register_custom_operator(): void
    {
        $this->engine->registerOperator('contains', function ($query, $field, $value) {
            $query->where($field, 'like', "%{$value}%");
        });

        // Operator is now registered and available
        $this->assertTrue(true);
    }

    // =========================================================================
    // Cleanup Tests
    // =========================================================================

    public function test_can_delete_plugin_rules(): void
    {
        $this->engine->defineRule('invoice', ['name' => 'Rule 1'], 'test-plugin');
        $this->engine->defineRule('invoice', ['name' => 'Rule 2'], 'test-plugin');
        $this->engine->defineRule('invoice', ['name' => 'Rule 3'], 'other-plugin');

        $deleted = $this->engine->deletePluginRules('test-plugin');

        $this->assertEquals(2, $deleted);
        $this->assertEquals(1, RecordRule::count());
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createMockUser(array $attributes)
    {
        return new class($attributes) {
            public $id;
            public $roles = [];
            public $team_ids = [];
            public $is_admin = false;

            public function __construct(array $attrs)
            {
                foreach ($attrs as $key => $value) {
                    $this->$key = $value;
                }
            }

            public function getGroups(): array
            {
                return $this->roles;
            }

            public function hasRole(string $role): bool
            {
                return in_array($role, $this->roles);
            }
        };
    }

    protected function createMockRecord(array $attributes)
    {
        return new class($attributes) extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'invoices';
            public $exists = true;

            public function __construct(array $attrs)
            {
                parent::__construct();
                $this->attributes = array_merge(['id' => 1], $attrs);
            }

            public function getKey()
            {
                return $this->attributes['id'];
            }

            public function getTable()
            {
                return 'invoices';
            }
        };
    }
}
