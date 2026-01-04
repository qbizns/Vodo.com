<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;

class CustomerControllerTest extends TestCase
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
    // Ban Tests
    // =========================================================================

    public function test_can_ban_customer(): void
    {
        $customer = Customer::factory()->create([
            'store_id' => $this->store->id,
            'is_banned' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$customer->id}/ban", [
                'reason' => 'Fraudulent activity',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_banned', true)
            ->assertJsonPath('data.ban_reason', 'Fraudulent activity');

        $this->assertDatabaseHas('commerce_customers', [
            'id' => $customer->id,
            'is_banned' => true,
            'ban_reason' => 'Fraudulent activity',
        ]);

        $customer->refresh();
        $this->assertNotNull($customer->banned_at);
    }

    public function test_can_ban_customer_without_reason(): void
    {
        $customer = Customer::factory()->create([
            'store_id' => $this->store->id,
            'is_banned' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$customer->id}/ban");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_banned', true);

        $this->assertDatabaseHas('commerce_customers', [
            'id' => $customer->id,
            'is_banned' => true,
        ]);
    }

    public function test_cannot_ban_customer_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $customer = Customer::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$customer->id}/ban");

        $response->assertStatus(404);
    }

    public function test_banning_already_banned_customer_updates_reason(): void
    {
        $customer = Customer::factory()->create([
            'store_id' => $this->store->id,
            'is_banned' => true,
            'ban_reason' => 'Old reason',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$customer->id}/ban", [
                'reason' => 'New reason',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.ban_reason', 'New reason');

        $this->assertDatabaseHas('commerce_customers', [
            'id' => $customer->id,
            'ban_reason' => 'New reason',
        ]);
    }

    // =========================================================================
    // Unban Tests
    // =========================================================================

    public function test_can_unban_customer(): void
    {
        $customer = Customer::factory()->create([
            'store_id' => $this->store->id,
            'is_banned' => true,
            'ban_reason' => 'Test reason',
            'banned_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$customer->id}/unban");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_banned', false);

        $this->assertDatabaseHas('commerce_customers', [
            'id' => $customer->id,
            'is_banned' => false,
            'ban_reason' => null,
            'banned_at' => null,
        ]);
    }

    public function test_cannot_unban_customer_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $customer = Customer::factory()->create([
            'store_id' => $otherStore->id,
            'is_banned' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$customer->id}/unban");

        $response->assertStatus(404);
    }

    public function test_unbanning_non_banned_customer_is_idempotent(): void
    {
        $customer = Customer::factory()->create([
            'store_id' => $this->store->id,
            'is_banned' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$customer->id}/unban");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_banned', false);
    }

    // =========================================================================
    // Import Tests
    // =========================================================================

    public function test_can_import_customers_from_csv(): void
    {
        Storage::fake('local');

        $csv = "email,first_name,last_name,phone\n";
        $csv .= "john@example.com,John,Doe,1234567890\n";
        $csv .= "jane@example.com,Jane,Smith,0987654321\n";

        $file = UploadedFile::fake()->createWithContent('customers.csv', $csv);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customers/import', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['imported_count', 'failed_count', 'errors'],
            ]);

        $this->assertDatabaseHas('commerce_customers', [
            'store_id' => $this->store->id,
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('commerce_customers', [
            'store_id' => $this->store->id,
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_import_validates_csv_file_format(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('customers.txt', 100);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customers/import', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_import_requires_file(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customers/import', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_import_skips_existing_customers_by_email(): void
    {
        Storage::fake('local');

        Customer::factory()->create([
            'store_id' => $this->store->id,
            'email' => 'existing@example.com',
            'first_name' => 'Existing',
        ]);

        $csv = "email,first_name,last_name\n";
        $csv .= "existing@example.com,Updated,Name\n";
        $csv .= "new@example.com,New,Customer\n";

        $file = UploadedFile::fake()->createWithContent('customers.csv', $csv);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customers/import', [
                'file' => $file,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('commerce_customers', [
            'email' => 'existing@example.com',
            'first_name' => 'Existing',
        ]);

        $this->assertDatabaseHas('commerce_customers', [
            'email' => 'new@example.com',
            'first_name' => 'New',
        ]);
    }

    public function test_import_handles_invalid_rows_gracefully(): void
    {
        Storage::fake('local');

        $csv = "email,first_name,last_name\n";
        $csv .= "valid@example.com,Valid,Customer\n";
        $csv .= "invalid-email,Invalid,Format\n";
        $csv .= "another@example.com,Another,Customer\n";

        $file = UploadedFile::fake()->createWithContent('customers.csv', $csv);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customers/import', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertEquals(2, $data['imported_count']);
        $this->assertEquals(1, $data['failed_count']);
        $this->assertNotEmpty($data['errors']);
    }

    public function test_import_respects_store_scope(): void
    {
        Storage::fake('local');

        $csv = "email,first_name,last_name\n";
        $csv .= "customer@example.com,Test,Customer\n";

        $file = UploadedFile::fake()->createWithContent('customers.csv', $csv);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customers/import', [
                'file' => $file,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('commerce_customers', [
            'email' => 'customer@example.com',
            'store_id' => $this->store->id,
        ]);
    }

    // =========================================================================
    // Customer State Tests
    // =========================================================================

    public function test_banned_customer_has_correct_state(): void
    {
        $customer = Customer::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $customer->ban('Test ban');

        $this->assertTrue($customer->is_banned);
        $this->assertEquals('Test ban', $customer->ban_reason);
        $this->assertNotNull($customer->banned_at);
    }

    public function test_unbanned_customer_clears_ban_data(): void
    {
        $customer = Customer::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $customer->ban('Test ban');
        $customer->unban();

        $this->assertFalse($customer->is_banned);
        $this->assertNull($customer->ban_reason);
        $this->assertNull($customer->banned_at);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication_to_ban_customer(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $response = $this->postJson("/api/admin/v2/customers/{$customer->id}/ban");

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_unban_customer(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $response = $this->postJson("/api/admin/v2/customers/{$customer->id}/unban");

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_import_customers(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('customers.csv', 100);

        $response = $this->postJson('/api/admin/v2/customers/import', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }
}
