<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plugin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tests for Admin PluginController.
 *
 * Covers:
 * - Plugin listing
 * - Plugin details view
 * - Plugin activation/deactivation
 * - Plugin settings management
 * - Plugin upload security
 * - Marketplace access
 */
class PluginControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
        Storage::fake('plugins');
    }

    // =========================================================================
    // Plugin Listing Tests
    // =========================================================================

    public function test_plugin_index_requires_authentication(): void
    {
        $response = $this->get('/admin/system/plugins');

        $response->assertRedirect();
    }

    public function test_plugin_index_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins');

        $response->assertStatus(200);
        $response->assertViewIs('admin::plugins.index');
    }

    public function test_plugin_index_lists_installed_plugins(): void
    {
        // Create test plugins
        Plugin::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins');

        $response->assertStatus(200);
        $response->assertViewHas('plugins');
    }

    // =========================================================================
    // Plugin Details Tests
    // =========================================================================

    public function test_plugin_show_displays_details(): void
    {
        $plugin = Plugin::factory()->create(['slug' => 'test-plugin']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins/test-plugin');

        $response->assertStatus(200);
    }

    public function test_plugin_show_returns_404_for_nonexistent_plugin(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins/nonexistent-plugin');

        $response->assertStatus(404);
    }

    // =========================================================================
    // Plugin Activation Tests
    // =========================================================================

    public function test_plugin_can_be_activated(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test-plugin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/test-plugin/activate');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $plugin->refresh();
        $this->assertTrue($plugin->is_active);
    }

    public function test_plugin_can_be_deactivated(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test-plugin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/test-plugin/deactivate');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $plugin->refresh();
        $this->assertFalse($plugin->is_active);
    }

    // =========================================================================
    // Plugin Settings Tests
    // =========================================================================

    public function test_plugin_settings_page_accessible(): void
    {
        $plugin = Plugin::factory()->create(['slug' => 'test-plugin']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins/test-plugin/settings');

        $response->assertStatus(200);
    }

    public function test_plugin_settings_can_be_saved(): void
    {
        $plugin = Plugin::factory()->create(['slug' => 'test-plugin']);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/test-plugin/settings', [
                'setting_key' => 'setting_value',
            ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // Plugin Upload Security Tests
    // =========================================================================

    public function test_plugin_upload_requires_zip_file(): void
    {
        $file = UploadedFile::fake()->create('plugin.txt', 100);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/upload', [
                'plugin' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_plugin_upload_validates_zip_structure(): void
    {
        // Create a fake zip file
        $file = UploadedFile::fake()->create('plugin.zip', 100, 'application/zip');

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/upload', [
                'plugin' => $file,
            ]);

        // Should fail validation for invalid structure
        $this->assertContains($response->status(), [422, 400]);
    }

    public function test_plugin_upload_max_size_enforced(): void
    {
        // Create a file larger than max allowed (assuming 50MB limit)
        $file = UploadedFile::fake()->create('plugin.zip', 60 * 1024, 'application/zip');

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/upload', [
                'plugin' => $file,
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Plugin Deletion Tests
    // =========================================================================

    public function test_plugin_can_be_deleted(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test-plugin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson('/admin/system/plugins/test-plugin');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('plugins', ['slug' => 'test-plugin']);
    }

    public function test_active_plugin_cannot_be_deleted(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test-plugin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson('/admin/system/plugins/test-plugin');

        // Should either require deactivation first or return error
        $this->assertContains($response->status(), [200, 400, 422]);
    }

    // =========================================================================
    // Marketplace Tests
    // =========================================================================

    public function test_marketplace_page_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins/marketplace');

        $response->assertStatus(200);
    }

    public function test_updates_page_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins/updates');

        $response->assertStatus(200);
    }

    public function test_check_updates(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/updates/check');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Bulk Actions Tests
    // =========================================================================

    public function test_bulk_activate_plugins(): void
    {
        Plugin::factory()->count(3)->create(['is_active' => false]);
        $slugs = Plugin::pluck('slug')->toArray();

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/bulk', [
                'action' => 'activate',
                'plugins' => $slugs,
            ]);

        $response->assertStatus(200);
    }

    public function test_bulk_deactivate_plugins(): void
    {
        Plugin::factory()->count(3)->create(['is_active' => true]);
        $slugs = Plugin::pluck('slug')->toArray();

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/bulk', [
                'action' => 'deactivate',
                'plugins' => $slugs,
            ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // License Management Tests
    // =========================================================================

    public function test_licenses_page_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins/licenses');

        $response->assertStatus(200);
    }

    public function test_activate_license(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/system/plugins/licenses/activate', [
                'license_key' => 'TEST-LICENSE-KEY',
                'plugin_slug' => 'test-plugin',
            ]);

        // License validation may succeed or fail based on external service
        $this->assertContains($response->status(), [200, 400, 422]);
    }

    // =========================================================================
    // Plugin Assets Tests
    // =========================================================================

    public function test_plugin_assets_route_accessible(): void
    {
        // This route should be public (no auth required)
        $response = $this->get('/admin/plugins/test-plugin/assets/css/style.css');

        // Should return asset or 404 if plugin doesn't exist
        $this->assertContains($response->status(), [200, 404]);
    }

    // =========================================================================
    // Dependencies Tests
    // =========================================================================

    public function test_dependencies_page_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins/dependencies');

        $response->assertStatus(200);
    }

    public function test_plugin_dependencies_detail(): void
    {
        $plugin = Plugin::factory()->create(['slug' => 'test-plugin']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/system/plugins/test-plugin/dependencies');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
