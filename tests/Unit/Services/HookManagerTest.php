<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\Plugins\HookManager;

class HookManagerTest extends TestCase
{
    protected HookManager $hooks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hooks = new HookManager();
    }

    protected function tearDown(): void
    {
        $this->hooks->clear();
        parent::tearDown();
    }

    // =========================================================================
    // Action Tests
    // =========================================================================

    public function test_can_add_and_execute_action(): void
    {
        $executed = false;
        
        $this->hooks->addAction('test_action', function () use (&$executed) {
            $executed = true;
        });

        $this->hooks->doAction('test_action');

        $this->assertTrue($executed);
    }

    public function test_action_receives_arguments(): void
    {
        $receivedArgs = [];
        
        $this->hooks->addAction('test_action', function ($a, $b) use (&$receivedArgs) {
            $receivedArgs = [$a, $b];
        });

        $this->hooks->doAction('test_action', 'foo', 'bar');

        $this->assertEquals(['foo', 'bar'], $receivedArgs);
    }

    public function test_actions_execute_in_priority_order(): void
    {
        $order = [];
        
        $this->hooks->addAction('test_action', function () use (&$order) {
            $order[] = 'normal';
        }, HookManager::PRIORITY_NORMAL);

        $this->hooks->addAction('test_action', function () use (&$order) {
            $order[] = 'early';
        }, HookManager::PRIORITY_EARLY);

        $this->hooks->addAction('test_action', function () use (&$order) {
            $order[] = 'late';
        }, HookManager::PRIORITY_LATE);

        $this->hooks->doAction('test_action');

        $this->assertEquals(['early', 'normal', 'late'], $order);
    }

    public function test_has_action_returns_true_when_registered(): void
    {
        $callback = fn() => null;
        
        $this->hooks->addAction('test_action', $callback);

        $this->assertTrue($this->hooks->hasAction('test_action'));
        $this->assertTrue($this->hooks->hasAction('test_action', $callback));
    }

    public function test_has_action_returns_false_when_not_registered(): void
    {
        $this->assertFalse($this->hooks->hasAction('nonexistent_action'));
    }

    public function test_can_remove_action(): void
    {
        $executed = false;
        $callback = function () use (&$executed) {
            $executed = true;
        };
        
        $this->hooks->addAction('test_action', $callback);
        $this->hooks->removeAction('test_action', $callback);
        $this->hooks->doAction('test_action');

        $this->assertFalse($executed);
    }

    public function test_can_remove_all_actions(): void
    {
        $count = 0;
        
        $this->hooks->addAction('test_action', fn() => $count++);
        $this->hooks->addAction('test_action', fn() => $count++);
        
        $this->hooks->removeAllActions('test_action');
        $this->hooks->doAction('test_action');

        $this->assertEquals(0, $count);
    }

    public function test_can_remove_actions_by_wildcard(): void
    {
        $count = 0;
        
        $this->hooks->addAction('plugin_test_action', fn() => $count++);
        $this->hooks->addAction('plugin_test_filter', fn() => $count++);
        $this->hooks->addAction('other_action', fn() => $count++);
        
        $this->hooks->removeAllActions('plugin_test_*');
        
        $this->hooks->doAction('plugin_test_action');
        $this->hooks->doAction('plugin_test_filter');
        $this->hooks->doAction('other_action');

        $this->assertEquals(1, $count);
    }

    // =========================================================================
    // Filter Tests
    // =========================================================================

    public function test_can_add_and_apply_filter(): void
    {
        $this->hooks->addFilter('test_filter', fn($value) => $value . '_filtered');

        $result = $this->hooks->applyFilters('test_filter', 'original');

        $this->assertEquals('original_filtered', $result);
    }

    public function test_filter_receives_additional_arguments(): void
    {
        $this->hooks->addFilter('test_filter', function ($value, $suffix) {
            return $value . $suffix;
        });

        $result = $this->hooks->applyFilters('test_filter', 'hello', '_world');

        $this->assertEquals('hello_world', $result);
    }

    public function test_filters_chain_correctly(): void
    {
        $this->hooks->addFilter('test_filter', fn($v) => $v . 'A');
        $this->hooks->addFilter('test_filter', fn($v) => $v . 'B');
        $this->hooks->addFilter('test_filter', fn($v) => $v . 'C');

        $result = $this->hooks->applyFilters('test_filter', '');

        $this->assertEquals('ABC', $result);
    }

    public function test_filter_returns_original_value_when_no_filters(): void
    {
        $result = $this->hooks->applyFilters('nonexistent_filter', 'original');

        $this->assertEquals('original', $result);
    }

    public function test_can_check_current_filter(): void
    {
        $currentFilter = null;
        
        $this->hooks->addFilter('test_filter', function ($value) use (&$currentFilter) {
            $currentFilter = $this->hooks->currentFilter();
            return $value;
        });

        $this->hooks->applyFilters('test_filter', 'value');

        $this->assertEquals('test_filter', $currentFilter);
    }

    public function test_doing_filter_returns_correct_state(): void
    {
        $wasDoing = null;
        
        $this->hooks->addFilter('test_filter', function ($value) use (&$wasDoing) {
            $wasDoing = $this->hooks->doingFilter('test_filter');
            return $value;
        });

        $this->assertFalse($this->hooks->doingFilter('test_filter'));
        $this->hooks->applyFilters('test_filter', 'value');
        $this->assertTrue($wasDoing);
        $this->assertFalse($this->hooks->doingFilter('test_filter'));
    }

    // =========================================================================
    // Plugin Context Tests
    // =========================================================================

    public function test_tracks_plugin_context(): void
    {
        $this->hooks->setPluginContext('my-plugin');
        $this->hooks->addAction('test_action', fn() => null);
        $this->hooks->setPluginContext(null);

        $pluginHooks = $this->hooks->getPluginHooks('my-plugin');

        $this->assertContains('test_action', $pluginHooks['actions']);
    }

    public function test_can_remove_plugin_hooks(): void
    {
        $this->hooks->setPluginContext('my-plugin');
        $this->hooks->addAction('test_action1', fn() => null);
        $this->hooks->addAction('test_action2', fn() => null);
        $this->hooks->addFilter('test_filter', fn($v) => $v);
        $this->hooks->setPluginContext(null);

        $removed = $this->hooks->removePluginHooks('my-plugin');

        $this->assertEquals(3, $removed);
        $this->assertFalse($this->hooks->hasAction('test_action1'));
        $this->assertFalse($this->hooks->hasAction('test_action2'));
        $this->assertFalse($this->hooks->hasFilter('test_filter'));
    }

    // =========================================================================
    // Constants Tests
    // =========================================================================

    public function test_hook_constants_are_defined(): void
    {
        $this->assertEquals('plugin_activated', HookManager::HOOK_PLUGIN_ACTIVATED);
        $this->assertEquals('plugin_deactivated', HookManager::HOOK_PLUGIN_DEACTIVATED);
        $this->assertEquals('entity_registered', HookManager::HOOK_ENTITY_REGISTERED);
        $this->assertEquals('entity_record_created', HookManager::HOOK_ENTITY_RECORD_CREATED);
    }

    public function test_priority_constants_are_in_order(): void
    {
        $this->assertLessThan(HookManager::PRIORITY_EARLY, HookManager::PRIORITY_EARLIEST);
        $this->assertLessThan(HookManager::PRIORITY_NORMAL, HookManager::PRIORITY_EARLY);
        $this->assertLessThan(HookManager::PRIORITY_LATE, HookManager::PRIORITY_NORMAL);
        $this->assertLessThan(HookManager::PRIORITY_LATEST, HookManager::PRIORITY_LATE);
    }

    // =========================================================================
    // Statistics Tests
    // =========================================================================

    public function test_tracks_execution_statistics(): void
    {
        $this->hooks->addAction('counted_action', fn() => null);
        
        $this->hooks->doAction('counted_action');
        $this->hooks->doAction('counted_action');
        $this->hooks->doAction('counted_action');

        $stats = $this->hooks->getStats();

        $this->assertEquals(3, $stats['execution_counts']['counted_action']);
    }

    // =========================================================================
    // Clear Tests
    // =========================================================================

    public function test_clear_removes_all_hooks(): void
    {
        $this->hooks->addAction('action1', fn() => null);
        $this->hooks->addAction('action2', fn() => null);
        $this->hooks->addFilter('filter1', fn($v) => $v);

        $this->hooks->clear();

        $this->assertEmpty($this->hooks->getActions());
        $this->assertEmpty($this->hooks->getFilters());
    }
}
