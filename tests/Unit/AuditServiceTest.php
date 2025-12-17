<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Audit\AuditService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditService();
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    public function test_logs_create_event(): void
    {
        $auditId = $this->service->log(
            $this->user,
            AuditService::EVENT_CREATE,
            ['new' => ['name' => 'Test', 'email' => 'test@test.com']]
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditId,
            'auditable_type' => User::class,
            'auditable_id' => $this->user->id,
            'event' => 'create',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_logs_update_event_with_diff(): void
    {
        $oldValues = ['name' => 'Old Name', 'email' => 'old@test.com'];
        $newValues = ['name' => 'New Name', 'email' => 'old@test.com'];

        $auditId = $this->service->logUpdate($this->user, $oldValues, $newValues);

        $audit = $this->service->find($auditId);

        $this->assertEquals('update', $audit->event);
        $this->assertArrayHasKey('name', $audit->old_values);
        $this->assertArrayHasKey('name', $audit->new_values);
        // Email should not be logged since it didn't change
        $this->assertArrayNotHasKey('email', $audit->old_values);
    }

    public function test_filters_sensitive_fields(): void
    {
        $auditId = $this->service->log(
            $this->user,
            AuditService::EVENT_CREATE,
            ['new' => ['name' => 'Test', 'password' => 'secret123']]
        );

        $audit = $this->service->find($auditId);

        $this->assertArrayHasKey('name', $audit->new_values);
        $this->assertArrayNotHasKey('password', $audit->new_values);
    }

    public function test_retrieves_history(): void
    {
        // Create multiple audit entries
        for ($i = 0; $i < 5; $i++) {
            $this->service->logUpdate(
                $this->user,
                ['name' => "Name {$i}"],
                ['name' => "Name " . ($i + 1)]
            );
        }

        $history = $this->service->history($this->user);

        $this->assertCount(5, $history);
        $this->assertEquals('update', $history->first()->event);
    }

    public function test_generates_diff_between_versions(): void
    {
        $audit1 = $this->service->logUpdate(
            $this->user,
            ['name' => 'Version 1'],
            ['name' => 'Version 2']
        );

        $audit2 = $this->service->logUpdate(
            $this->user,
            ['name' => 'Version 2'],
            ['name' => 'Version 3']
        );

        $diff = $this->service->diff($this->user, $audit1, $audit2);

        $this->assertArrayHasKey('name', $diff);
        $this->assertEquals('Version 2', $diff['name']['old']);
        $this->assertEquals('Version 3', $diff['name']['new']);
    }

    public function test_can_disable_auditing(): void
    {
        $this->service->disable();

        $auditId = $this->service->log(
            $this->user,
            AuditService::EVENT_CREATE,
            ['new' => ['name' => 'Test']]
        );

        $this->assertEquals(0, $auditId);
        $this->assertDatabaseMissing('audit_logs', [
            'auditable_type' => User::class,
        ]);

        $this->service->enable();
    }

    public function test_without_auditing_callback(): void
    {
        $result = $this->service->withoutAuditing(function () {
            $this->service->log(
                $this->user,
                AuditService::EVENT_CREATE,
                ['new' => ['name' => 'Test']]
            );
            return 'completed';
        });

        $this->assertEquals('completed', $result);
        $this->assertDatabaseMissing('audit_logs', [
            'auditable_type' => User::class,
            'event' => 'create',
        ]);
    }

    public function test_search_audit_logs(): void
    {
        $this->service->logCreate($this->user);
        $this->service->logUpdate($this->user, ['name' => 'Old'], ['name' => 'New']);
        $this->service->logDelete($this->user);

        $createLogs = $this->service->search(['event' => 'create']);
        $updateLogs = $this->service->search(['event' => 'update']);

        $this->assertCount(1, $createLogs);
        $this->assertCount(1, $updateLogs);
    }

    public function test_generates_statistics(): void
    {
        $this->service->logCreate($this->user);
        $this->service->logUpdate($this->user, ['name' => 'A'], ['name' => 'B']);
        $this->service->logUpdate($this->user, ['name' => 'B'], ['name' => 'C']);

        $stats = $this->service->statistics(30);

        $this->assertEquals(3, $stats['total']);
        $this->assertArrayHasKey('create', $stats['by_event']);
        $this->assertArrayHasKey('update', $stats['by_event']);
    }

    public function test_cleanup_removes_old_logs(): void
    {
        // Create old log
        \DB::table('audit_logs')->insert([
            'auditable_type' => User::class,
            'auditable_id' => $this->user->id,
            'event' => 'create',
            'created_at' => now()->subDays(100),
        ]);

        // Create recent log
        $this->service->logCreate($this->user);

        $deleted = $this->service->cleanup(90);

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_logs_custom_event(): void
    {
        $auditId = $this->service->logCustom(
            $this->user,
            'email_sent',
            'Invoice email sent to customer',
            ['invoice_id' => 123, 'recipient' => 'test@test.com']
        );

        $audit = $this->service->find($auditId);

        $this->assertEquals('custom', $audit->event);
        $this->assertEquals('email_sent', $audit->metadata['action']);
    }
}
