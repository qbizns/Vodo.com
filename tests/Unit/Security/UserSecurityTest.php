<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Tests for User model security features.
 *
 * Covers:
 * - Account lockout protection
 * - Password policy enforcement
 * - Two-factor authentication
 * - Account status management
 * - Login security
 */
class UserSecurityTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Account Status Tests
    // =========================================================================

    public function test_user_default_status_is_active(): void
    {
        $user = User::factory()->create(['status' => null]);

        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $this->assertTrue($user->isActive());
    }

    public function test_is_active_returns_false_for_other_statuses(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_SUSPENDED]);
        $this->assertFalse($user->isActive());

        $user->status = User::STATUS_INACTIVE;
        $this->assertFalse($user->isActive());

        $user->status = User::STATUS_PENDING;
        $this->assertFalse($user->isActive());
    }

    public function test_is_suspended_returns_true_for_suspended_status(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_SUSPENDED]);

        $this->assertTrue($user->isSuspended());
    }

    public function test_suspend_changes_status_and_logs(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $user->suspend('Violated terms of service');

        $this->assertEquals(User::STATUS_SUSPENDED, $user->status);
        $this->assertTrue($user->isSuspended());
    }

    public function test_activate_restores_active_status_and_clears_lockout(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_SUSPENDED,
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(15),
        ]);

        $user->activate();

        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    // =========================================================================
    // Account Lockout Tests
    // =========================================================================

    public function test_is_locked_out_returns_false_when_not_locked(): void
    {
        $user = User::factory()->create(['locked_until' => null]);

        $this->assertFalse($user->isLockedOut());
    }

    public function test_is_locked_out_returns_true_when_locked(): void
    {
        $user = User::factory()->create([
            'locked_until' => now()->addMinutes(10),
        ]);

        $this->assertTrue($user->isLockedOut());
    }

    public function test_is_locked_out_returns_false_when_lockout_expired(): void
    {
        $user = User::factory()->create([
            'locked_until' => now()->subMinutes(1),
        ]);

        $this->assertFalse($user->isLockedOut());
    }

    public function test_record_failed_login_increments_attempts(): void
    {
        $user = User::factory()->create(['failed_login_attempts' => 0]);

        $user->recordFailedLogin();

        $this->assertEquals(1, $user->fresh()->failed_login_attempts);
    }

    public function test_record_failed_login_locks_account_after_max_attempts(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => User::MAX_LOGIN_ATTEMPTS - 1,
        ]);

        $user->recordFailedLogin();

        $user->refresh();
        $this->assertEquals(User::MAX_LOGIN_ATTEMPTS, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->isLockedOut());
    }

    public function test_lockout_duration_is_correct(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => User::MAX_LOGIN_ATTEMPTS - 1,
        ]);

        Carbon::setTestNow(now());
        $user->recordFailedLogin();

        $user->refresh();
        $expectedLockoutEnd = now()->addMinutes(User::LOCKOUT_DURATION);

        $this->assertTrue(
            $user->locked_until->diffInMinutes($expectedLockoutEnd) < 1
        );

        Carbon::setTestNow();
    }

    public function test_record_successful_login_clears_lockout(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(15),
        ]);

        $user->recordSuccessfulLogin('192.168.1.1');

        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertNotNull($user->last_login_at);
        $this->assertEquals('192.168.1.1', $user->last_login_ip);
    }

    public function test_get_remaining_lockout_seconds(): void
    {
        Carbon::setTestNow(now());

        $user = User::factory()->create([
            'locked_until' => now()->addMinutes(5),
        ]);

        $remaining = $user->getRemainingLockoutSeconds();
        $this->assertEqualsWithDelta(300, $remaining, 2); // 5 minutes = 300 seconds

        Carbon::setTestNow();
    }

    public function test_get_remaining_lockout_seconds_returns_zero_when_not_locked(): void
    {
        $user = User::factory()->create(['locked_until' => null]);

        $this->assertEquals(0, $user->getRemainingLockoutSeconds());
    }

    // =========================================================================
    // Can Login Tests
    // =========================================================================

    public function test_can_login_returns_true_for_active_verified_unlocked_user(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'locked_until' => null,
        ]);

        $this->assertTrue($user->canLogin());
    }

    public function test_can_login_returns_false_for_inactive_user(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_INACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->assertFalse($user->canLogin());
    }

    public function test_can_login_returns_false_for_suspended_user(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_SUSPENDED,
            'email_verified_at' => now(),
        ]);

        $this->assertFalse($user->canLogin());
    }

    public function test_can_login_returns_false_for_unverified_user(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => null,
        ]);

        $this->assertFalse($user->canLogin());
    }

    public function test_can_login_returns_false_for_locked_out_user(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'locked_until' => now()->addMinutes(10),
        ]);

        $this->assertFalse($user->canLogin());
    }

    // =========================================================================
    // Password Security Tests
    // =========================================================================

    public function test_must_change_password_returns_true_when_flag_set(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->assertTrue($user->mustChangePassword());
    }

    public function test_must_change_password_returns_false_when_flag_not_set(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->assertFalse($user->mustChangePassword());
    }

    public function test_must_change_password_checks_expiry_when_configured(): void
    {
        config(['auth.password_expiry_days' => 30]);

        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now()->subDays(40),
        ]);

        $this->assertTrue($user->mustChangePassword());
    }

    public function test_must_change_password_returns_false_when_password_is_recent(): void
    {
        config(['auth.password_expiry_days' => 30]);

        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now()->subDays(10),
        ]);

        $this->assertFalse($user->mustChangePassword());
    }

    public function test_update_password_updates_password_and_timestamp(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'password_changed_at' => null,
        ]);

        Carbon::setTestNow(now());
        $user->updatePassword('new_secure_password_123');

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('new_secure_password_123', $user->password));

        Carbon::setTestNow();
    }

    // =========================================================================
    // Two-Factor Authentication Tests
    // =========================================================================

    public function test_has_two_factor_enabled_returns_false_when_disabled(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_has_two_factor_enabled_returns_false_when_no_secret(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => null,
        ]);

        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_has_two_factor_enabled_returns_true_when_fully_configured(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('TESTSECRET123'),
        ]);

        $this->assertTrue($user->hasTwoFactorEnabled());
    }

    public function test_enable_two_factor_sets_secret_and_flag(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        $user->enableTwoFactor('NEWSECRET456');

        $user->refresh();
        $this->assertTrue($user->two_factor_enabled);
        $this->assertNotNull($user->two_factor_secret);
        $this->assertEquals('NEWSECRET456', decrypt($user->two_factor_secret));
    }

    public function test_disable_two_factor_clears_secret_and_flag(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('TESTSECRET123'),
        ]);

        $user->disableTwoFactor();

        $user->refresh();
        $this->assertFalse($user->two_factor_enabled);
        $this->assertNull($user->two_factor_secret);
    }

    // =========================================================================
    // Role & Permission Tests
    // =========================================================================

    public function test_is_super_admin_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => Role::ROLE_SUPER_ADMIN], [
            'name' => 'Super Admin',
            'level' => 100,
        ]);
        $user->assignRole($role);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->isSuperuser());
    }

    public function test_is_super_admin_returns_false_for_regular_admin(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => Role::ROLE_ADMIN], [
            'name' => 'Admin',
            'level' => 90,
        ]);
        $user->assignRole($role);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => Role::ROLE_ADMIN], [
            'name' => 'Admin',
            'level' => 90,
        ]);
        $user->assignRole($role);

        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => Role::ROLE_SUPER_ADMIN], [
            'name' => 'Super Admin',
            'level' => 100,
        ]);
        $user->assignRole($role);

        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => Role::ROLE_SUBSCRIBER], [
            'name' => 'Subscriber',
            'level' => 10,
        ]);
        $user->assignRole($role);

        $this->assertFalse($user->isAdmin());
    }

    public function test_get_groups_returns_role_slugs(): void
    {
        $user = User::factory()->create();

        $role1 = Role::firstOrCreate(['slug' => 'test_role_1'], ['name' => 'Test 1', 'level' => 10]);
        $role2 = Role::firstOrCreate(['slug' => 'test_role_2'], ['name' => 'Test 2', 'level' => 20]);

        $user->assignRole($role1);
        $user->assignRole($role2);

        $groups = $user->getGroups();

        $this->assertContains('test_role_1', $groups);
        $this->assertContains('test_role_2', $groups);
    }

    public function test_has_permission_returns_true_with_direct_permission(): void
    {
        $user = User::factory()->create();
        $permission = Permission::firstOrCreate(['slug' => 'test.permission'], [
            'name' => 'Test Permission',
            'group' => 'test',
        ]);
        $user->grantPermission($permission);

        $this->assertTrue($user->hasPermission('test.permission'));
    }

    public function test_has_permission_returns_true_through_role(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'test_role'], ['name' => 'Test', 'level' => 10]);
        $permission = Permission::firstOrCreate(['slug' => 'test.role_permission'], [
            'name' => 'Test Role Permission',
            'group' => 'test',
        ]);

        $role->grantPermission($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermission('test.role_permission'));
    }

    public function test_deny_permission_overrides_role_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'test_role'], ['name' => 'Test', 'level' => 10]);
        $permission = Permission::firstOrCreate(['slug' => 'test.denied'], [
            'name' => 'Test Denied',
            'group' => 'test',
        ]);

        $role->grantPermission($permission);
        $user->assignRole($role);
        $user->denyPermission($permission);

        $this->assertFalse($user->hasPermission('test.denied'));
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => Role::ROLE_SUPER_ADMIN], [
            'name' => 'Super Admin',
            'level' => 100,
        ]);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermission('any.permission'));
        $this->assertTrue($user->hasPermission('another.random.permission'));
        $this->assertTrue($user->hasPermission('nonexistent.permission'));
    }

    // =========================================================================
    // Scopes Tests
    // =========================================================================

    public function test_scope_active_filters_active_users(): void
    {
        User::factory()->create(['status' => User::STATUS_ACTIVE]);
        User::factory()->create(['status' => User::STATUS_INACTIVE]);
        User::factory()->create(['status' => User::STATUS_SUSPENDED]);

        $activeUsers = User::active()->get();

        $this->assertCount(1, $activeUsers);
        $this->assertEquals(User::STATUS_ACTIVE, $activeUsers->first()->status);
    }

    public function test_scope_verified_filters_verified_users(): void
    {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['email_verified_at' => null]);

        $verifiedUsers = User::verified()->get();

        $this->assertCount(1, $verifiedUsers);
        $this->assertNotNull($verifiedUsers->first()->email_verified_at);
    }

    public function test_scope_with_role_filters_by_role(): void
    {
        $role = Role::firstOrCreate(['slug' => 'special_role'], ['name' => 'Special', 'level' => 50]);

        $userWithRole = User::factory()->create();
        $userWithRole->assignRole($role);

        User::factory()->create(); // User without role

        $usersWithRole = User::withRole('special_role')->get();

        $this->assertCount(1, $usersWithRole);
        $this->assertEquals($userWithRole->id, $usersWithRole->first()->id);
    }
}
