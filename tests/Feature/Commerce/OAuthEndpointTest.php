<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use VodoCommerce\Auth\CommerceScopes;
use VodoCommerce\Auth\OAuthAuthorizationService;
use VodoCommerce\Models\OAuthApplication;

/**
 * OAuthEndpointTest - Integration tests for OAuth 2.0 HTTP endpoints.
 *
 * Tests the full HTTP request/response cycle for:
 * - Authorization endpoint (GET/POST /oauth/authorize)
 * - Token endpoint (POST /oauth/token)
 * - Token revocation (POST /oauth/revoke)
 * - Token introspection (POST /oauth/introspect)
 * - OAuth metadata (GET /.well-known/oauth-authorization-server)
 */
class OAuthEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected array $appCredentials;
    protected OAuthApplication $app;
    protected OAuthAuthorizationService $oauthService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oauthService = new OAuthAuthorizationService();
        Cache::flush();

        // Create a test OAuth application
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test Application',
            'redirect_uris' => [
                'https://example.com/callback',
                'http://localhost:3000/callback',
            ],
            'scopes' => [
                CommerceScopes::PRODUCTS_READ,
                CommerceScopes::ORDERS_READ,
                CommerceScopes::ORDERS_WRITE,
            ],
            'description' => 'A test application for OAuth flow testing',
            'website' => 'https://example.com',
        ]);

        $this->app = $result['application'];
        $this->appCredentials = [
            'client_id' => $result['client_id'],
            'client_secret' => $result['client_secret'],
        ];
    }

    // =========================================================================
    // OAuth Metadata Endpoint Tests
    // =========================================================================

    public function test_oauth_metadata_endpoint_returns_correct_structure(): void
    {
        $response = $this->getJson('/.well-known/oauth-authorization-server');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'issuer',
                'authorization_endpoint',
                'token_endpoint',
                'revocation_endpoint',
                'scopes_supported',
                'response_types_supported',
                'grant_types_supported',
                'token_endpoint_auth_methods_supported',
                'code_challenge_methods_supported',
            ])
            ->assertJsonFragment([
                'response_types_supported' => ['code'],
                'grant_types_supported' => ['authorization_code', 'refresh_token'],
                'code_challenge_methods_supported' => ['S256', 'plain'],
            ]);
    }

    // =========================================================================
    // Scopes Endpoint Tests
    // =========================================================================

    public function test_scopes_endpoint_returns_all_scopes(): void
    {
        $response = $this->getJson('/oauth/scopes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'scopes',
                'grouped',
                'presets',
            ]);

        $scopes = $response->json('scopes');
        $this->assertArrayHasKey(CommerceScopes::ORDERS_READ, $scopes);
        $this->assertArrayHasKey(CommerceScopes::PRODUCTS_READ, $scopes);
    }

    // =========================================================================
    // Token Endpoint Tests
    // =========================================================================

    public function test_token_endpoint_exchanges_code_for_tokens(): void
    {
        // Generate authorization code
        $code = $this->oauthService->authorize(
            $this->app->id,
            1, // store_id
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://example.com/callback',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'refresh_token',
                'scope',
            ])
            ->assertJsonFragment([
                'token_type' => 'Bearer',
            ]);
    }

    public function test_token_endpoint_supports_basic_auth(): void
    {
        $code = $this->oauthService->authorize(
            $this->app->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $credentials = base64_encode(
            urlencode($this->appCredentials['client_id']) . ':' .
            urlencode($this->appCredentials['client_secret'])
        );

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://example.com/callback',
        ], [
            'Authorization' => 'Basic ' . $credentials,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'refresh_token']);
    }

    public function test_token_endpoint_rejects_invalid_client(): void
    {
        $code = $this->oauthService->authorize(
            $this->app->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://example.com/callback',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment([
                'error' => 'invalid_client',
            ]);
    }

    public function test_token_endpoint_rejects_expired_code(): void
    {
        // Create and immediately expire code
        $code = 'code_expired_' . bin2hex(random_bytes(24));

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://example.com/callback',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'error' => 'invalid_grant',
            ]);
    }

    public function test_token_endpoint_refreshes_token(): void
    {
        // Get initial tokens
        $code = $this->oauthService->authorize(
            $this->app->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $tokens = $this->oauthService->exchangeCode(
            $code,
            $this->appCredentials['client_id'],
            $this->appCredentials['client_secret'],
            'https://example.com/callback'
        );

        // Refresh the token
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'refresh_token']);

        // New token should be different
        $this->assertNotEquals(
            $tokens['access_token'],
            $response->json('access_token')
        );
    }

    public function test_token_endpoint_rejects_unsupported_grant(): void
    {
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'password',
            'username' => 'user',
            'password' => 'pass',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'error' => 'unsupported_grant_type',
            ]);
    }

    // =========================================================================
    // Token Revocation Tests
    // =========================================================================

    public function test_revoke_endpoint_revokes_access_token(): void
    {
        // Get tokens
        $code = $this->oauthService->authorize(
            $this->app->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $tokens = $this->oauthService->exchangeCode(
            $code,
            $this->appCredentials['client_id'],
            $this->appCredentials['client_secret'],
            'https://example.com/callback'
        );

        // Revoke the access token
        $response = $this->postJson('/oauth/revoke', [
            'token' => $tokens['access_token'],
            'token_type_hint' => 'access_token',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
        ]);

        // RFC 7009: Always return 200 OK
        $response->assertStatus(200);

        // Token should now be invalid
        $validated = $this->oauthService->validateToken($tokens['access_token']);
        $this->assertNull($validated);
    }

    public function test_revoke_endpoint_succeeds_for_nonexistent_token(): void
    {
        // RFC 7009: Revocation always succeeds, even for non-existent tokens
        $response = $this->postJson('/oauth/revoke', [
            'token' => 'nonexistent_token_12345',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
        ]);

        $response->assertStatus(200);
    }

    // =========================================================================
    // Token Introspection Tests
    // =========================================================================

    public function test_introspect_endpoint_returns_active_token_info(): void
    {
        // Get tokens
        $code = $this->oauthService->authorize(
            $this->app->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $tokens = $this->oauthService->exchangeCode(
            $code,
            $this->appCredentials['client_id'],
            $this->appCredentials['client_secret'],
            'https://example.com/callback'
        );

        // Introspect the token
        $response = $this->postJson('/oauth/introspect', [
            'token' => $tokens['access_token'],
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['active' => true])
            ->assertJsonStructure([
                'active',
                'scope',
                'client_id',
                'token_type',
                'exp',
                'iat',
            ]);
    }

    public function test_introspect_endpoint_returns_inactive_for_invalid_token(): void
    {
        $response = $this->postJson('/oauth/introspect', [
            'token' => 'invalid_token_12345',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['active' => false]);
    }

    public function test_introspect_endpoint_returns_inactive_for_other_apps_token(): void
    {
        // Create another app
        $otherApp = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Other App',
            'redirect_uris' => ['https://other.com/callback'],
            'scopes' => [CommerceScopes::PRODUCTS_READ],
        ]);

        // Get tokens for first app
        $code = $this->oauthService->authorize(
            $this->app->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $tokens = $this->oauthService->exchangeCode(
            $code,
            $this->appCredentials['client_id'],
            $this->appCredentials['client_secret'],
            'https://example.com/callback'
        );

        // Try to introspect using the other app's credentials
        $response = $this->postJson('/oauth/introspect', [
            'token' => $tokens['access_token'],
            'client_id' => $otherApp['client_id'],
            'client_secret' => $otherApp['client_secret'],
        ]);

        // Should return inactive because the token belongs to a different app
        $response->assertStatus(200)
            ->assertJsonFragment(['active' => false]);
    }

    // =========================================================================
    // PKCE Flow Tests
    // =========================================================================

    public function test_token_endpoint_validates_pkce(): void
    {
        // Generate PKCE challenge
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        // Get authorization code with PKCE challenge
        $code = $this->oauthService->authorize(
            $this->app->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback',
            null,
            $codeChallenge,
            'S256'
        );

        // Exchange with correct verifier
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://example.com/callback',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
            'code_verifier' => $codeVerifier,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token']);
    }

    public function test_token_endpoint_rejects_wrong_pkce_verifier(): void
    {
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $code = $this->oauthService->authorize(
            $this->app->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback',
            null,
            $codeChallenge,
            'S256'
        );

        // Exchange with wrong verifier
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://example.com/callback',
            'client_id' => $this->appCredentials['client_id'],
            'client_secret' => $this->appCredentials['client_secret'],
            'code_verifier' => 'wrong-verifier-value',
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'error' => 'invalid_grant',
            ]);
    }
}
