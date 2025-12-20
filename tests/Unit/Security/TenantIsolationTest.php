<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Services\Tenant\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;

/**
 * Tests for tenant isolation via HasTenant trait.
 *
 * Covers:
 * - Automatic tenant scoping on queries
 * - Tenant ID auto-assignment on creation
 * - Cross-tenant data isolation
 * - Tenant scope bypass for admins
 * - Global record access
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected TenantManager $tenantManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test table for tenant-aware model
        Schema::create('tenant_test_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('title');
            $table->timestamps();
        });

        $this->tenantManager = app(TenantManager::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tenant_test_records');
        parent::tearDown();
    }

    // =========================================================================
    // Tenant Scoping Tests
    // =========================================================================

    public function test_records_are_scoped_to_current_tenant(): void
    {
        // Create records for different tenants
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 1, 'title' => 'Tenant 1 Record']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 2, 'title' => 'Tenant 2 Record']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 1, 'title' => 'Another Tenant 1 Record']);

        // Set current tenant to 1
        $this->tenantManager->setCurrentTenant(1);

        $records = TenantTestRecord::all();

        // Should only see tenant 1 records (and any global records if configured)
        $this->assertGreaterThanOrEqual(2, $records->count());
        foreach ($records as $record) {
            $this->assertTrue(
                $record->tenant_id === 1 || $record->tenant_id === null,
                'Record should belong to current tenant or be global'
            );
        }
    }

    public function test_records_without_tenant_scope_returns_all(): void
    {
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 1, 'title' => 'Tenant 1 Record']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 2, 'title' => 'Tenant 2 Record']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 3, 'title' => 'Tenant 3 Record']);

        $this->tenantManager->setCurrentTenant(1);

        $allRecords = TenantTestRecord::withoutTenantScope()->get();

        $this->assertCount(3, $allRecords);
    }

    public function test_for_tenant_scope_filters_specific_tenant(): void
    {
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 1, 'title' => 'Tenant 1']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 2, 'title' => 'Tenant 2']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 2, 'title' => 'Tenant 2 Again']);

        $tenant2Records = TenantTestRecord::forTenant(2)->get();

        $this->assertCount(2, $tenant2Records);
        foreach ($tenant2Records as $record) {
            $this->assertEquals(2, $record->tenant_id);
        }
    }

    // =========================================================================
    // Auto-Assignment Tests
    // =========================================================================

    public function test_tenant_id_auto_assigned_on_create(): void
    {
        $this->tenantManager->setCurrentTenant(5);

        $record = TenantTestRecord::create(['title' => 'New Record']);

        $this->assertEquals(5, $record->tenant_id);
    }

    public function test_tenant_id_not_overwritten_if_already_set(): void
    {
        $this->tenantManager->setCurrentTenant(5);

        $record = TenantTestRecord::withoutTenantScope()->create([
            'title' => 'Specific Tenant Record',
            'tenant_id' => 10,
        ]);

        $this->assertEquals(10, $record->fresh()->tenant_id);
    }

    public function test_null_tenant_id_when_no_tenant_context(): void
    {
        $this->tenantManager->clearCurrentTenant();

        $record = TenantTestRecord::withoutTenantScope()->create(['title' => 'Global Record']);

        $this->assertNull($record->tenant_id);
    }

    // =========================================================================
    // Global Records Tests
    // =========================================================================

    public function test_global_records_accessible_to_all_tenants(): void
    {
        // Create a global record (null tenant_id)
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => null, 'title' => 'Global Record']);
        // Create a tenant-specific record
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 1, 'title' => 'Tenant 1 Only']);

        // Set tenant to 1
        $this->tenantManager->setCurrentTenant(1);

        $records = TenantTestRecord::all();
        $titles = $records->pluck('title')->toArray();

        $this->assertContains('Global Record', $titles);
        $this->assertContains('Tenant 1 Only', $titles);
    }

    public function test_global_only_scope_returns_only_null_tenant(): void
    {
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => null, 'title' => 'Global 1']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => null, 'title' => 'Global 2']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 1, 'title' => 'Tenant 1']);

        $globalRecords = TenantTestRecord::globalOnly()->get();

        $this->assertCount(2, $globalRecords);
        foreach ($globalRecords as $record) {
            $this->assertNull($record->tenant_id);
        }
    }

    public function test_is_global_returns_true_for_null_tenant(): void
    {
        $globalRecord = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => null,
            'title' => 'Global',
        ]);

        $this->assertTrue($globalRecord->isGlobal());
    }

    public function test_is_global_returns_false_for_tenant_record(): void
    {
        $tenantRecord = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 1,
            'title' => 'Tenant Record',
        ]);

        $this->assertFalse($tenantRecord->isGlobal());
    }

    public function test_make_global_removes_tenant_assignment(): void
    {
        $record = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 5,
            'title' => 'Was Tenant',
        ]);

        $record->makeGlobal();

        $this->assertNull($record->fresh()->tenant_id);
        $this->assertTrue($record->isGlobal());
    }

    public function test_assign_to_tenant_sets_tenant_id(): void
    {
        $record = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => null,
            'title' => 'Was Global',
        ]);

        $record->assignToTenant(7);

        $this->assertEquals(7, $record->fresh()->tenant_id);
        $this->assertFalse($record->isGlobal());
    }

    // =========================================================================
    // Belongs To Current Tenant Tests
    // =========================================================================

    public function test_belongs_to_current_tenant_returns_true(): void
    {
        $this->tenantManager->setCurrentTenant(3);

        $record = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 3,
            'title' => 'Current Tenant Record',
        ]);

        $this->assertTrue($record->belongsToCurrentTenant());
    }

    public function test_belongs_to_current_tenant_returns_false_for_other_tenant(): void
    {
        $this->tenantManager->setCurrentTenant(3);

        $record = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 5,
            'title' => 'Other Tenant Record',
        ]);

        $this->assertFalse($record->belongsToCurrentTenant());
    }

    // =========================================================================
    // Cross-Tenant Isolation Tests
    // =========================================================================

    public function test_cannot_access_other_tenant_records_directly(): void
    {
        // Create records for different tenants
        $tenant1Record = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 1,
            'title' => 'Tenant 1 Secret',
        ]);
        TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 2,
            'title' => 'Tenant 2 Secret',
        ]);

        // Set current tenant to 1
        $this->tenantManager->setCurrentTenant(1);

        // Try to find tenant 2's record by ID - should be filtered out
        $record = TenantTestRecord::find($tenant1Record->id + 1);

        $this->assertNull($record);
    }

    public function test_update_respects_tenant_isolation(): void
    {
        $tenant2Record = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 2,
            'title' => 'Tenant 2 Record',
        ]);

        $this->tenantManager->setCurrentTenant(1);

        // Attempt to update via mass update - should affect 0 records
        $affected = TenantTestRecord::where('id', $tenant2Record->id)
            ->update(['title' => 'Hacked!']);

        $this->assertEquals(0, $affected);
        $this->assertEquals('Tenant 2 Record', $tenant2Record->fresh()->title);
    }

    public function test_delete_respects_tenant_isolation(): void
    {
        $tenant2Record = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 2,
            'title' => 'Tenant 2 Record',
        ]);

        $this->tenantManager->setCurrentTenant(1);

        // Attempt to delete via query - should affect 0 records
        $deleted = TenantTestRecord::where('id', $tenant2Record->id)->delete();

        $this->assertEquals(0, $deleted);
        $this->assertNotNull(TenantTestRecord::withoutTenantScope()->find($tenant2Record->id));
    }

    // =========================================================================
    // Tenant Column Configuration Tests
    // =========================================================================

    public function test_get_tenant_column_returns_configured_column(): void
    {
        $record = new TenantTestRecord();

        $this->assertEquals('tenant_id', $record->getTenantColumn());
    }

    public function test_get_qualified_tenant_column_includes_table(): void
    {
        $record = new TenantTestRecord();

        $this->assertEquals('tenant_test_records.tenant_id', $record->getQualifiedTenantColumn());
    }

    public function test_get_tenant_id_returns_current_value(): void
    {
        $record = TenantTestRecord::withoutTenantScope()->create([
            'tenant_id' => 42,
            'title' => 'Test',
        ]);

        $this->assertEquals(42, $record->getTenantId());
    }

    // =========================================================================
    // No Tenant Context Tests
    // =========================================================================

    public function test_only_global_records_visible_without_tenant_context(): void
    {
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => null, 'title' => 'Global']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 1, 'title' => 'Tenant 1']);
        TenantTestRecord::withoutTenantScope()->create(['tenant_id' => 2, 'title' => 'Tenant 2']);

        $this->tenantManager->clearCurrentTenant();

        $records = TenantTestRecord::all();

        $this->assertCount(1, $records);
        $this->assertNull($records->first()->tenant_id);
    }
}

/**
 * Test model with HasTenant trait.
 */
class TenantTestRecord extends Model
{
    use HasTenant;

    protected $table = 'tenant_test_records';

    protected $fillable = ['tenant_id', 'title'];

    protected string $tenantColumn = 'tenant_id';
}
