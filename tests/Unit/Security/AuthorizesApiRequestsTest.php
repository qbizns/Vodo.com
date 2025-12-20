<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Traits\AuthorizesApiRequests;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;

/**
 * Tests for the AuthorizesApiRequests trait.
 *
 * Covers:
 * - Permission-based authorization checks
 * - Role-based access control
 * - Ownership verification
 * - Admin bypass capability
 */
class AuthorizesApiRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected object $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test controller using the trait
        $this->controller = new class {
            use AuthorizesApiRequests;

            // Make protected methods public for testing
            public function testGetResourceName(): string
            {
                return $this->getResourceName();
            }

            public function testAuthorizeAction(string $action, ?string $resourceType = null): void
            {
                $this->authorizeAction($action, $resourceType);
            }

            public function testAuthorizeView(?string $resourceType = null): void
            {
                $this->authorizeView($resourceType);
            }

            public function testAuthorizeCreate(?string $resourceType = null): void
            {
                $this->authorizeCreate($resourceType);
            }

            public function testAuthorizeUpdate(?string $resourceType = null): void
            {
                $this->authorizeUpdate($resourceType);
            }

            public function testAuthorizeDelete(?string $resourceType = null): void
            {
                $this->authorizeDelete($resourceType);
            }

            public function testAuthorizeBulk(string $action, ?string $resourceType = null): void
            {
                $this->authorizeBulk($action, $resourceType);
            }

            public function testAuthorizeAdmin(): void
            {
                $this->authorizeAdmin();
            }

            public function testAuthorizeSuperAdmin(): void
            {
                $this->authorizeSuperAdmin();
            }

            public function testAuthorizeOwnership($model, string $ownerField = 'user_id'): void
            {
                $this->authorizeOwnership($model, $ownerField);
            }

            public function testAuthorizeActionOrOwnership(string $action, $model, ?string $ownerField = null): void
            {
                $this->authorizeActionOrOwnership($action, $model, $ownerField);
            }

            public function testEnsureAuthenticated(): void
            {
                $this->ensureAuthenticated();
            }

            public function testUserHasPermission(User $user, string $permission): bool
            {
                return $this->userHasPermission($user, $permission);
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_ensure_authenticated_throws_when_not_authenticated(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->controller->testEnsureAuthenticated();
    }

    public function test_ensure_authenticated_passes_when_authenticated(): void
    {
        Auth::shouldReceive('check')->andReturn(true);

        // Should not throw
        $this->controller->testEnsureAuthenticated();
        $this->assertTrue(true);
    }

    // =========================================================================
    // Permission Authorization Tests
    // =========================================================================

    public function test_authorize_action_passes_with_permission(): void
    {
        $user = $this->createUserWithPermission('entities.view');
        $this->actingAs($user);

        // Should not throw
        $this->controller->testAuthorizeAction('view', 'entities');
        $this->assertTrue(true);
    }

    public function test_authorize_action_fails_without_permission(): void
    {
        $user = $this->createBasicUser();
        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to view entities.');

        $this->controller->testAuthorizeAction('view', 'entities');
    }

    public function test_authorize_view_passes_with_view_permission(): void
    {
        $user = $this->createUserWithPermission('entities.view');
        $this->actingAs($user);

        $this->controller->testAuthorizeView('entities');
        $this->assertTrue(true);
    }

    public function test_authorize_create_passes_with_create_permission(): void
    {
        $user = $this->createUserWithPermission('entities.create');
        $this->actingAs($user);

        $this->controller->testAuthorizeCreate('entities');
        $this->assertTrue(true);
    }

    public function test_authorize_update_passes_with_update_permission(): void
    {
        $user = $this->createUserWithPermission('entities.update');
        $this->actingAs($user);

        $this->controller->testAuthorizeUpdate('entities');
        $this->assertTrue(true);
    }

    public function test_authorize_delete_passes_with_delete_permission(): void
    {
        $user = $this->createUserWithPermission('entities.delete');
        $this->actingAs($user);

        $this->controller->testAuthorizeDelete('entities');
        $this->assertTrue(true);
    }

    public function test_authorize_bulk_requires_bulk_permission(): void
    {
        $user = $this->createUserWithPermission('entities.bulk_delete');
        $this->actingAs($user);

        $this->controller->testAuthorizeBulk('delete', 'entities');
        $this->assertTrue(true);
    }

    public function test_authorize_bulk_fails_without_bulk_permission(): void
    {
        // User only has regular delete, not bulk_delete
        $user = $this->createUserWithPermission('entities.delete');
        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);

        $this->controller->testAuthorizeBulk('delete', 'entities');
    }

    // =========================================================================
    // Role-Based Authorization Tests
    // =========================================================================

    public function test_authorize_admin_passes_for_admin_user(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $this->controller->testAuthorizeAdmin();
        $this->assertTrue(true);
    }

    public function test_authorize_admin_fails_for_regular_user(): void
    {
        $user = $this->createBasicUser();
        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Admin access required.');

        $this->controller->testAuthorizeAdmin();
    }

    public function test_authorize_super_admin_passes_for_super_admin(): void
    {
        $user = $this->createSuperAdminUser();
        $this->actingAs($user);

        $this->controller->testAuthorizeSuperAdmin();
        $this->assertTrue(true);
    }

    public function test_authorize_super_admin_fails_for_regular_admin(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Super admin access required.');

        $this->controller->testAuthorizeSuperAdmin();
    }

    public function test_super_admin_bypasses_all_permission_checks(): void
    {
        $user = $this->createSuperAdminUser();
        $this->actingAs($user);

        // Super admin should pass any permission check without having explicit permission
        $this->assertTrue($this->controller->testUserHasPermission($user, 'any.permission'));
        $this->assertTrue($this->controller->testUserHasPermission($user, 'nonexistent.permission'));
    }

    // =========================================================================
    // Ownership Authorization Tests
    // =========================================================================

    public function test_authorize_ownership_passes_for_owner(): void
    {
        $user = $this->createBasicUser();
        $this->actingAs($user);

        $model = new class($user->id) {
            public $user_id;
            public function __construct($userId) { $this->user_id = $userId; }
        };

        $this->controller->testAuthorizeOwnership($model);
        $this->assertTrue(true);
    }

    public function test_authorize_ownership_fails_for_non_owner(): void
    {
        $user = $this->createBasicUser();
        $this->actingAs($user);

        $model = new class(999) {
            public $user_id;
            public function __construct($userId) { $this->user_id = $userId; }
        };

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have access to this resource.');

        $this->controller->testAuthorizeOwnership($model);
    }

    public function test_admin_bypasses_ownership_check(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $model = new class(999) {
            public $user_id;
            public function __construct($userId) { $this->user_id = $userId; }
        };

        // Admin should bypass ownership check
        $this->controller->testAuthorizeOwnership($model);
        $this->assertTrue(true);
    }

    public function test_authorize_ownership_uses_custom_owner_field(): void
    {
        $user = $this->createBasicUser();
        $this->actingAs($user);

        $model = new class($user->id) {
            public $author_id;
            public function __construct($authorId) { $this->author_id = $authorId; }
        };

        $this->controller->testAuthorizeOwnership($model, 'author_id');
        $this->assertTrue(true);
    }

    // =========================================================================
    // Action OR Ownership Tests
    // =========================================================================

    public function test_authorize_action_or_ownership_passes_with_permission(): void
    {
        $user = $this->createUserWithPermission('entities.update');
        $this->actingAs($user);

        // Model owned by someone else
        $model = new class(999) {
            public $user_id;
            public function __construct($userId) { $this->user_id = $userId; }
        };

        // Should pass because user has permission, even though not owner
        $this->controller->testAuthorizeActionOrOwnership('update', $model);
        $this->assertTrue(true);
    }

    public function test_authorize_action_or_ownership_passes_with_ownership(): void
    {
        $user = $this->createBasicUser();
        $this->actingAs($user);

        // Model owned by current user
        $model = new class($user->id) {
            public $user_id;
            public function __construct($userId) { $this->user_id = $userId; }
        };

        // Should pass because user owns resource, even without update permission
        $this->controller->testAuthorizeActionOrOwnership('update', $model);
        $this->assertTrue(true);
    }

    public function test_authorize_action_or_ownership_fails_without_both(): void
    {
        $user = $this->createBasicUser();
        $this->actingAs($user);

        // Model owned by someone else
        $model = new class(999) {
            public $user_id;
            public function __construct($userId) { $this->user_id = $userId; }
        };

        $this->expectException(AuthorizationException::class);

        // User has neither permission nor ownership
        $this->controller->testAuthorizeActionOrOwnership('update', $model);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createBasicUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    protected function createUserWithPermission(string $permissionSlug): User
    {
        $user = $this->createBasicUser();
        $permission = Permission::firstOrCreate(['slug' => $permissionSlug], [
            'name' => ucwords(str_replace('.', ' ', $permissionSlug)),
            'group' => explode('.', $permissionSlug)[0],
        ]);

        $role = Role::firstOrCreate(['slug' => 'test_role'], [
            'name' => 'Test Role',
            'level' => 10,
        ]);

        $role->grantPermission($permission);
        $user->assignRole($role);

        return $user->fresh();
    }

    protected function createAdminUser(): User
    {
        $user = $this->createBasicUser();

        $adminRole = Role::firstOrCreate(['slug' => Role::ROLE_ADMIN], [
            'name' => 'Admin',
            'level' => 90,
        ]);

        $user->assignRole($adminRole);

        return $user->fresh();
    }

    protected function createSuperAdminUser(): User
    {
        $user = $this->createBasicUser();

        $superAdminRole = Role::firstOrCreate(['slug' => Role::ROLE_SUPER_ADMIN], [
            'name' => 'Super Admin',
            'level' => 100,
        ]);

        $user->assignRole($superAdminRole);

        return $user->fresh();
    }
}
