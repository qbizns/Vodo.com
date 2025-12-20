<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\EntityRecord;
use App\Models\EntityDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

/**
 * Tests for EntityApiController authorization.
 *
 * Covers:
 * - Permission-based endpoint protection
 * - Role-based access control
 * - Record-level security
 * - Admin bypass capability
 * - Unauthenticated access rejection
 */
class EntityApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->createTestEntity();
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/entities/test-entity');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_api(): void
    {
        $user = $this->createUserWithPermission('entities.view');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/entities/test-entity');

        $response->assertStatus(200);
    }

    // =========================================================================
    // View Permission Tests
    // =========================================================================

    public function test_user_with_view_permission_can_list_entities(): void
    {
        $user = $this->createUserWithPermission('entities.view');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/entities/test-entity');

        $response->assertStatus(200);
    }

    public function test_user_without_view_permission_cannot_list_entities(): void
    {
        $user = $this->createBasicUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/entities/test-entity');

        $response->assertStatus(403);
    }

    public function test_user_with_view_permission_can_show_entity(): void
    {
        $user = $this->createUserWithPermission('entities.view');
        $record = $this->createTestRecord($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/entities/test-entity/{$record->id}");

        $response->assertStatus(200);
    }

    // =========================================================================
    // Create Permission Tests
    // =========================================================================

    public function test_user_with_create_permission_can_store_entity(): void
    {
        $user = $this->createUserWithPermission('entities.create');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/entities/test-entity', [
                'title' => 'New Entity',
                'content' => 'Test content',
            ]);

        $response->assertStatus(201);
    }

    public function test_user_without_create_permission_cannot_store_entity(): void
    {
        $user = $this->createUserWithPermission('entities.view');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/entities/test-entity', [
                'title' => 'New Entity',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // Update Permission Tests
    // =========================================================================

    public function test_user_with_update_permission_can_update_entity(): void
    {
        $user = $this->createUserWithPermission('entities.update');
        $record = $this->createTestRecord($user);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/entities/test-entity/{$record->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200);
    }

    public function test_user_without_update_permission_cannot_update_entity(): void
    {
        $user = $this->createUserWithPermission('entities.view');
        $record = $this->createTestRecord($user);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/entities/test-entity/{$record->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // Delete Permission Tests
    // =========================================================================

    public function test_user_with_delete_permission_can_delete_entity(): void
    {
        $user = $this->createUserWithPermission('entities.delete');
        $record = $this->createTestRecord($user);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/entities/test-entity/{$record->id}");

        $response->assertStatus(200);
    }

    public function test_user_without_delete_permission_cannot_delete_entity(): void
    {
        $user = $this->createUserWithPermission('entities.view');
        $record = $this->createTestRecord($user);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/entities/test-entity/{$record->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // Bulk Operations Permission Tests
    // =========================================================================

    public function test_user_with_bulk_delete_permission_can_bulk_delete(): void
    {
        $user = $this->createUserWithPermission('entities.bulk_delete');
        $records = collect([
            $this->createTestRecord($user),
            $this->createTestRecord($user),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/entities/test-entity/bulk-delete', [
                'ids' => $records->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200);
    }

    public function test_user_without_bulk_delete_permission_cannot_bulk_delete(): void
    {
        $user = $this->createUserWithPermission('entities.delete');
        $records = collect([
            $this->createTestRecord($user),
            $this->createTestRecord($user),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/entities/test-entity/bulk-delete', [
                'ids' => $records->pluck('id')->toArray(),
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // Admin Bypass Tests
    // =========================================================================

    public function test_admin_can_access_all_endpoints(): void
    {
        $admin = $this->createAdminUser();
        $record = $this->createTestRecord($admin);

        // List
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/entities/test-entity')
            ->assertStatus(200);

        // Show
        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/entities/test-entity/{$record->id}")
            ->assertStatus(200);

        // Create
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/entities/test-entity', ['title' => 'Admin Created'])
            ->assertStatus(201);

        // Update
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/entities/test-entity/{$record->id}", ['title' => 'Updated'])
            ->assertStatus(200);
    }

    public function test_super_admin_bypasses_all_permission_checks(): void
    {
        $superAdmin = $this->createSuperAdminUser();

        // Super admin can do everything without explicit permissions
        $response = $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/entities/test-entity', ['title' => 'Super Admin Created']);

        $response->assertStatus(201);
    }

    // =========================================================================
    // Entity-Specific Permission Tests
    // =========================================================================

    public function test_entity_specific_permission_works(): void
    {
        $user = $this->createUserWithPermission('entities.test-entity.view');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/entities/test-entity');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Account Status Tests
    // =========================================================================

    public function test_suspended_user_cannot_access_api(): void
    {
        $user = $this->createUserWithPermission('entities.view');
        $user->suspend();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/entities/test-entity');

        // Should be forbidden or unauthorized
        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    public function test_unverified_user_cannot_access_api(): void
    {
        $user = $this->createUserWithPermission('entities.view');
        $user->email_verified_at = null;
        $user->save();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/entities/test-entity');

        // Should be forbidden or require verification
        $this->assertTrue(in_array($response->status(), [401, 403, 409]));
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function seedRolesAndPermissions(): void
    {
        // Create roles
        Role::firstOrCreate(['slug' => Role::ROLE_SUPER_ADMIN], [
            'name' => 'Super Admin',
            'level' => 100,
        ]);

        Role::firstOrCreate(['slug' => Role::ROLE_ADMIN], [
            'name' => 'Admin',
            'level' => 90,
        ]);

        // Create permissions
        $permissions = [
            'entities.view',
            'entities.create',
            'entities.update',
            'entities.delete',
            'entities.bulk_delete',
            'entities.test-entity.view',
        ];

        foreach ($permissions as $slug) {
            Permission::firstOrCreate(['slug' => $slug], [
                'name' => ucwords(str_replace('.', ' ', $slug)),
                'group' => 'entities',
            ]);
        }
    }

    protected function createTestEntity(): void
    {
        EntityDefinition::firstOrCreate(['name' => 'test-entity'], [
            'singular_name' => 'Test Entity',
            'plural_name' => 'Test Entities',
            'table_name' => 'entity_records',
            'is_active' => true,
        ]);
    }

    protected function createTestRecord(User $user): EntityRecord
    {
        return EntityRecord::create([
            'entity_name' => 'test-entity',
            'title' => 'Test Record',
            'content' => 'Test content',
            'author_id' => $user->id,
            'status' => 'published',
        ]);
    }

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

        $role = Role::firstOrCreate(['slug' => 'test_role_' . md5($permissionSlug)], [
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
        $adminRole = Role::where('slug', Role::ROLE_ADMIN)->first();
        $user->assignRole($adminRole);
        return $user->fresh();
    }

    protected function createSuperAdminUser(): User
    {
        $user = $this->createBasicUser();
        $superAdminRole = Role::where('slug', Role::ROLE_SUPER_ADMIN)->first();
        $user->assignRole($superAdminRole);
        return $user->fresh();
    }
}
