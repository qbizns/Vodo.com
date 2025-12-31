<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Models\Enterprise\AuditLog;
use App\Services\Enterprise\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuditService::class);
    }

    public function test_log_created_records_new_values(): void
    {
        $model = $this->createTestModel(['name' => 'Test', 'status' => 'active']);

        $log = $this->service->logCreated($model);

        $this->assertEquals('created', $log->event);
        $this->assertNull($log->old_values);
        $this->assertArrayHasKey('name', $log->new_values);
        $this->assertEquals('Test', $log->new_values['name']);
    }

    public function test_log_updated_records_changes(): void
    {
        $model = $this->createTestModel(['name' => 'Old Name']);
        $model->name = 'New Name';

        // Simulate dirty state
        $model->syncOriginal();
        $model->name = 'New Name';

        $log = $this->service->logUpdated($model);

        $this->assertEquals('updated', $log->event);
    }

    public function test_log_deleted_records_old_values(): void
    {
        $model = $this->createTestModel(['name' => 'Test']);

        $log = $this->service->logDeleted($model);

        $this->assertEquals('deleted', $log->event);
        $this->assertArrayHasKey('name', $log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_sensitive_fields_are_redacted(): void
    {
        $model = $this->createTestModel([
            'name' => 'Test',
            'password' => 'secret123',
            'api_key' => 'key_12345',
        ]);

        $log = $this->service->logCreated($model);

        $this->assertEquals('[REDACTED]', $log->new_values['password']);
        $this->assertEquals('[REDACTED]', $log->new_values['api_key']);
    }

    public function test_pii_fields_are_masked(): void
    {
        $model = $this->createTestModel([
            'name' => 'Test',
            'email' => 'user@example.com',
        ]);

        $log = $this->service->logCreated($model);

        $this->assertNotEquals('user@example.com', $log->new_values['email']);
        $this->assertStringContainsString('*', $log->new_values['email']);
    }

    public function test_financial_models_get_financial_tag(): void
    {
        $model = new class extends Model {
            protected $table = 'payment_transactions';
            protected $fillable = ['amount'];
        };
        $model->amount = 100;

        $log = $this->service->logCreated($model);

        $this->assertContains('financial', $log->tags);
    }

    public function test_security_event_logging(): void
    {
        $log = $this->service->logSecurity(
            'suspicious_login',
            'Multiple failed login attempts detected',
            ['attempts' => 5]
        );

        $this->assertEquals('suspicious_login', $log->event);
        $this->assertContains('security', $log->tags);
        $this->assertEquals('Multiple failed login attempts detected', $log->metadata['description']);
    }

    public function test_get_audit_trail(): void
    {
        $model = $this->createTestModel(['name' => 'Test']);

        $this->service->logCreated($model);
        $this->service->logUpdated($model);
        $this->service->logAccessed($model, ['name']);

        $trail = $this->service->getAuditTrail($model);

        $this->assertCount(3, $trail);
    }

    public function test_search_by_filters(): void
    {
        $model = $this->createTestModel(['name' => 'Test']);
        $model->tenant_id = 1;

        $this->service->logCreated($model);
        $this->service->logUpdated($model);

        $results = $this->service->search([
            'event' => 'created',
        ]);

        $this->assertEquals(1, $results->total());
    }

    public function test_cleanup_respects_retention(): void
    {
        // Create old log
        AuditLog::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'event' => 'test',
            'auditable_type' => 'TestModel',
            'auditable_id' => 1,
            'created_at' => now()->subDays(100),
        ]);

        // Create recent log
        AuditLog::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'event' => 'test',
            'auditable_type' => 'TestModel',
            'auditable_id' => 2,
            'created_at' => now(),
        ]);

        $deleted = $this->service->cleanup(90);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(1, AuditLog::count());
    }

    protected function createTestModel(array $attributes): Model
    {
        return new class($attributes) extends Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'status', 'password', 'api_key', 'email'];
            public $tenant_id = null;

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);
                $this->exists = true;
                $this->id = 1;
            }

            public function getKey()
            {
                return $this->id;
            }
        };
    }
}
