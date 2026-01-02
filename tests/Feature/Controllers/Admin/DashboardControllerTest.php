<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for Admin DashboardController.
 *
 * Covers:
 * - Dashboard access control
 * - Widget management
 * - Dashboard data loading
 * - Layout persistence
 */
class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
    }

    // =========================================================================
    // Dashboard Access Tests
    // =========================================================================

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
    }

    public function test_authenticated_admin_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin');

        $response->assertStatus(200);
    }

    public function test_dashboard_returns_correct_view(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin');

        $response->assertViewIs('admin::dashboard.index');
    }

    // =========================================================================
    // Navigation Board Tests
    // =========================================================================

    public function test_navigation_board_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/navigation-board');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Widget API Tests
    // =========================================================================

    public function test_get_widgets_returns_json(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/dashboard/widgets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'widgets',
        ]);
    }

    public function test_save_widget_layout(): void
    {
        $layout = [
            ['id' => 'widget-1', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 2],
            ['id' => 'widget-2', 'x' => 4, 'y' => 0, 'w' => 4, 'h' => 2],
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/dashboard/widgets/layout', [
                'layout' => $layout,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_add_widget(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/dashboard/widgets/add', [
                'type' => 'stats',
                'config' => ['title' => 'Test Widget'],
            ]);

        $response->assertStatus(200);
    }

    public function test_remove_widget(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson('/admin/dashboard/widgets/test-widget-id');

        // Should succeed or return 404 if widget doesn't exist
        $this->assertContains($response->status(), [200, 404]);
    }

    public function test_get_widget_data(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/dashboard/widgets/stats-widget/data');

        // Should return data or 404 if widget doesn't exist
        $this->assertContains($response->status(), [200, 404]);
    }

    public function test_update_widget_settings(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/admin/dashboard/widgets/test-widget/settings', [
                'title' => 'Updated Title',
                'refreshInterval' => 60,
            ]);

        // Should succeed or return 404 if widget doesn't exist
        $this->assertContains($response->status(), [200, 404]);
    }

    public function test_reset_dashboard(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/dashboard/reset');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_get_available_widgets(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/dashboard/available-widgets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['type', 'name'],
        ]);
    }

    // =========================================================================
    // User Preferences Tests
    // =========================================================================

    public function test_get_favorite_menus(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/user/fav-menus');

        $response->assertStatus(200);
    }

    public function test_toggle_favorite_menu(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/api/user/fav-menus/toggle', [
                'menu_id' => 'plugins',
            ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // Plugin Dashboard Tests
    // =========================================================================

    public function test_plugin_dashboard_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/dashboard/test-plugin');

        // Should succeed or return 404 if plugin doesn't exist
        $this->assertContains($response->status(), [200, 404]);
    }

    // =========================================================================
    // Placeholder Route Test
    // =========================================================================

    public function test_placeholder_route_returns_view(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/some-placeholder-page');

        // Placeholder should return 200 with a view
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
