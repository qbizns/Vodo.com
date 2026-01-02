<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Tests for Admin AuthController.
 *
 * Covers:
 * - Login form display
 * - Successful login with admin permissions
 * - Login rejection for non-admin users
 * - Login rejection for deactivated users
 * - Invalid credentials handling
 * - Logout functionality
 * - Session regeneration
 * - Login throttling
 */
class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Login Page Tests
    // =========================================================================

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertViewIs('admin::auth.login');
    }

    public function test_authenticated_admin_redirected_to_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/login');

        $response->assertRedirect(route('admin.dashboard'));
    }

    // =========================================================================
    // Successful Login Tests
    // =========================================================================

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_remember_me_creates_remember_token(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect();
        $admin->refresh();

        $this->assertNotNull($admin->remember_token);
    }

    public function test_successful_login_regenerates_session(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $sessionId = session()->getId();

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $this->assertNotEquals($sessionId, session()->getId());
    }

    // =========================================================================
    // Login Rejection Tests
    // =========================================================================

    public function test_non_admin_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user', // Regular user, not admin
        ]);

        // Mock canAccessAdmin to return false
        $user->shouldReceive('canAccessAdmin')->andReturn(false);

        $response = $this->post('/admin/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_deactivated_admin_cannot_login(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrors([
            'email' => 'Your account has been deactivated.',
        ]);
        $this->assertGuest('admin');
    }

    public function test_invalid_password_returns_error(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_invalid_email_returns_error(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_missing_email_validation(): void
    {
        $response = $this->post('/admin/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_missing_password_validation(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_invalid_email_format_validation(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // =========================================================================
    // Logout Tests
    // =========================================================================

    public function test_admin_can_logout(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/logout');

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_logout_invalidates_session(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin');
        $sessionId = session()->getId();

        $this->post('/admin/logout');

        $this->assertNotEquals($sessionId, session()->getId());
    }

    public function test_logout_regenerates_csrf_token(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin');
        $csrfToken = csrf_token();

        $this->post('/admin/logout');

        $this->assertNotEquals($csrfToken, csrf_token());
    }

    // =========================================================================
    // Protected Routes Tests
    // =========================================================================

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
    }

    public function test_authenticated_admin_can_access_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Login Throttling Tests
    // =========================================================================

    public function test_login_is_throttled_after_too_many_attempts(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Make multiple failed attempts
        for ($i = 0; $i < 6; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // The next attempt should be throttled
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        // Either rate limited (429) or shows error
        $this->assertTrue(
            $response->status() === 429 ||
            $response->getSession()->has('errors')
        );
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createAdminUser(array $attributes = []): User
    {
        $defaults = [
            'role' => 'admin',
            'is_active' => true,
        ];

        $user = User::factory()->create(array_merge($defaults, $attributes));

        // If the user model has a method to check admin access, mock it
        // Or ensure the role/permissions are set correctly

        return $user;
    }
}
