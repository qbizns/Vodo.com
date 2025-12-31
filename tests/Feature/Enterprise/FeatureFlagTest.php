<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Models\Enterprise\FeatureFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_flag_returns_false(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'new_feature',
            'name' => 'New Feature',
            'enabled' => false,
        ]);

        $this->assertFalse($flag->isEnabledFor(1, 1));
    }

    public function test_enabled_flag_with_100_percent_rollout(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'new_feature',
            'name' => 'New Feature',
            'enabled' => true,
            'rollout_percentage' => 100,
        ]);

        $this->assertTrue($flag->isEnabledFor(1, 1));
        $this->assertTrue($flag->isEnabledFor(999, 999));
    }

    public function test_enabled_flag_with_zero_rollout(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'new_feature',
            'name' => 'New Feature',
            'enabled' => true,
            'rollout_percentage' => 0,
        ]);

        $this->assertFalse($flag->isEnabledFor(1, 1));
    }

    public function test_explicit_tenant_list(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'beta_feature',
            'name' => 'Beta Feature',
            'enabled' => true,
            'rollout_percentage' => 0,
            'tenant_ids' => [1, 2, 3],
        ]);

        $this->assertTrue($flag->isEnabledFor(1));
        $this->assertTrue($flag->isEnabledFor(2));
        $this->assertFalse($flag->isEnabledFor(99));
    }

    public function test_explicit_user_list(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'beta_feature',
            'name' => 'Beta Feature',
            'enabled' => true,
            'rollout_percentage' => 0,
            'user_ids' => [10, 20, 30],
        ]);

        $this->assertTrue($flag->isEnabledFor(null, 10));
        $this->assertTrue($flag->isEnabledFor(null, 20));
        $this->assertFalse($flag->isEnabledFor(null, 99));
    }

    public function test_time_constraints(): void
    {
        $futureFlag = FeatureFlag::create([
            'key' => 'future_feature',
            'name' => 'Future Feature',
            'enabled' => true,
            'rollout_percentage' => 100,
            'starts_at' => now()->addDay(),
        ]);

        $expiredFlag = FeatureFlag::create([
            'key' => 'expired_feature',
            'name' => 'Expired Feature',
            'enabled' => true,
            'rollout_percentage' => 100,
            'ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($futureFlag->isEnabledFor(1));
        $this->assertFalse($expiredFlag->isEnabledFor(1));
    }

    public function test_consistent_rollout(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'gradual_rollout',
            'name' => 'Gradual Rollout',
            'enabled' => true,
            'rollout_percentage' => 50,
        ]);

        // Same tenant should always get same result
        $result1 = $flag->isEnabledFor(42);
        $result2 = $flag->isEnabledFor(42);
        $result3 = $flag->isEnabledFor(42);

        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    public function test_check_conditions(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'conditional_feature',
            'name' => 'Conditional Feature',
            'enabled' => true,
            'rollout_percentage' => 100,
            'conditions' => [
                ['field' => 'plan', 'operator' => '=', 'value' => 'enterprise'],
            ],
        ]);

        $this->assertTrue($flag->checkConditions(['plan' => 'enterprise']));
        $this->assertFalse($flag->checkConditions(['plan' => 'free']));
    }

    public function test_conditions_with_operators(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'conditional_feature',
            'name' => 'Conditional Feature',
            'enabled' => true,
            'conditions' => [
                ['field' => 'user_count', 'operator' => '>=', 'value' => 10],
            ],
        ]);

        $this->assertTrue($flag->checkConditions(['user_count' => 15]));
        $this->assertTrue($flag->checkConditions(['user_count' => 10]));
        $this->assertFalse($flag->checkConditions(['user_count' => 5]));
    }

    public function test_add_and_remove_tenant(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'beta',
            'name' => 'Beta',
            'enabled' => true,
            'tenant_ids' => [1, 2],
        ]);

        $flag->addTenant(3);
        $flag->refresh();
        $this->assertContains(3, $flag->tenant_ids);

        $flag->removeTenant(2);
        $flag->refresh();
        $this->assertNotContains(2, $flag->tenant_ids);
    }

    public function test_set_rollout_percentage(): void
    {
        $flag = FeatureFlag::create([
            'key' => 'rollout',
            'name' => 'Rollout',
            'enabled' => true,
            'rollout_percentage' => 0,
        ]);

        $flag->setRolloutPercentage(50);
        $flag->refresh();
        $this->assertEquals(50, $flag->rollout_percentage);

        $flag->setRolloutPercentage(150); // Over 100
        $flag->refresh();
        $this->assertEquals(100, $flag->rollout_percentage);
    }
}
