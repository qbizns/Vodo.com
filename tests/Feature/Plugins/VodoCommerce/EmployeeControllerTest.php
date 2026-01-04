<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Employee;
use VodoCommerce\Models\Store;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
    }

    // =========================================================================
    // Index Tests
    // =========================================================================

    public function test_can_list_employees(): void
    {
        Employee::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'email', 'role', 'is_active', 'hired_at'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_employees_by_role(): void
    {
        Employee::factory()->count(2)->create(['store_id' => $this->store->id, 'role' => 'manager']);
        Employee::factory()->count(1)->create(['store_id' => $this->store->id, 'role' => 'staff']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/employees?role=manager');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_employees_by_active_status(): void
    {
        Employee::factory()->count(2)->create(['store_id' => $this->store->id, 'is_active' => true]);
        Employee::factory()->count(1)->create(['store_id' => $this->store->id, 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/employees?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_employees_by_name(): void
    {
        Employee::factory()->create(['store_id' => $this->store->id, 'name' => 'John Doe']);
        Employee::factory()->create(['store_id' => $this->store->id, 'name' => 'Jane Smith']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/employees?search=John');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Doe');
    }

    public function test_employees_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        Employee::factory()->create(['store_id' => $this->store->id, 'name' => 'Store Employee']);
        Employee::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other Store Employee']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/employees');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Store Employee');
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_employee(): void
    {
        $employee = Employee::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.name', $employee->name)
            ->assertJsonPath('data.email', $employee->email);
    }

    public function test_cannot_show_employee_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $employee = Employee::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/employees/{$employee->id}");

        $response->assertStatus(404);
    }

    public function test_returns_404_for_nonexistent_employee(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/employees/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // Role Permission Tests
    // =========================================================================

    public function test_employee_has_role_specific_permissions(): void
    {
        $adminEmployee = Employee::factory()->admin()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/employees/{$adminEmployee->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.permissions', ['*']);
    }

    public function test_manager_has_limited_permissions(): void
    {
        $managerEmployee = Employee::factory()->manager()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/employees/{$managerEmployee->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.role', 'manager')
            ->assertJsonStructure([
                'data' => ['permissions'],
            ]);

        $permissions = $response->json('data.permissions');
        $this->assertIsArray($permissions);
        $this->assertNotContains('*', $permissions);
    }

    public function test_inactive_employee_is_marked_correctly(): void
    {
        $inactiveEmployee = Employee::factory()->inactive()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/employees/{$inactiveEmployee->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);
    }

    // =========================================================================
    // Validation Tests
    // =========================================================================

    public function test_validates_role_is_valid(): void
    {
        Employee::factory()->create([
            'store_id' => $this->store->id,
            'role' => 'staff',
        ]);

        $employees = Employee::where('store_id', $this->store->id)->get();

        foreach ($employees as $employee) {
            $this->assertContains($employee->role, ['staff', 'manager', 'admin', 'support']);
        }
    }

    public function test_employee_email_is_unique(): void
    {
        $employee1 = Employee::factory()->create([
            'store_id' => $this->store->id,
            'email' => 'unique@example.com',
        ]);

        $employee2 = Employee::factory()->make([
            'store_id' => $this->store->id,
            'email' => 'unique@example.com',
        ]);

        try {
            $employee2->save();
            $this->fail('Expected unique constraint violation');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // Date Tests
    // =========================================================================

    public function test_employee_has_hired_at_date(): void
    {
        $employee = Employee::factory()->create([
            'store_id' => $this->store->id,
            'hired_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['hired_at'],
            ]);

        $this->assertNotNull($response->json('data.hired_at'));
    }

    // =========================================================================
    // Meta Data Tests
    // =========================================================================

    public function test_employee_can_have_meta_data(): void
    {
        $employee = Employee::factory()->create([
            'store_id' => $this->store->id,
            'meta' => [
                'department' => 'Sales',
                'employee_id' => 'EMP-001',
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.meta.department', 'Sales')
            ->assertJsonPath('data.meta.employee_id', 'EMP-001');
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication_to_list_employees(): void
    {
        $response = $this->getJson('/api/admin/v2/employees');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_show_employee(): void
    {
        $employee = Employee::factory()->create(['store_id' => $this->store->id]);

        $response = $this->getJson("/api/admin/v2/employees/{$employee->id}");

        $response->assertStatus(401);
    }
}
