<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use VodoCommerce\Auth\CommerceScopes;
use VodoCommerce\Auth\OAuthAuthorizationService;
use VodoCommerce\Auth\OAuthException;
use VodoCommerce\Http\Middleware\ValidateOAuthToken;
use VodoCommerce\Models\OAuthAccessToken;
use VodoCommerce\Models\OAuthApplication;

class OAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected OAuthAuthorizationService $oauthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oauthService = new OAuthAuthorizationService();
        Cache::flush();
    }

    // =========================================================================
    // OAuth Application Tests
    // =========================================================================

    public function test_can_create_oauth_application(): void
    {
        $storeId = 1;

        $result = OAuthApplication::createWithCredentials([
            'store_id' => $storeId,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ, CommerceScopes::PRODUCTS_READ],
            'description' => 'A test application',
        ]);

        $this->assertNotNull($result['application']);
        $this->assertStringStartsWith('app_', $result['client_id']);
        $this->assertStringStartsWith('secret_', $result['client_secret']);
        $this->assertEquals('active', $result['application']->status);
    }

    public function test_can_verify_client_secret(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::READ_ALL],
        ]);

        $app = $result['application'];
        $secret = $result['client_secret'];

        $this->assertTrue($app->verifySecret($secret));
        $this->assertFalse($app->verifySecret('wrong-secret'));
    }

    public function test_can_regenerate_client_secret(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::READ_ALL],
        ]);

        $app = $result['application'];
        $oldSecret = $result['client_secret'];

        $newSecret = $app->regenerateSecret();

        $this->assertFalse($app->verifySecret($oldSecret));
        $this->assertTrue($app->verifySecret($newSecret));
    }

    // =========================================================================
    // Authorization Flow Tests
    // =========================================================================

    public function test_authorization_rejects_unknown_client(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Unknown client identifier');

        $this->oauthService->startAuthorization(
            'unknown_client_id',
            'https://example.com/callback',
            [CommerceScopes::ORDERS_READ]
        );
    }

    public function test_authorization_rejects_invalid_redirect_uri(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ],
        ]);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Redirect URI not allowed');

        $this->oauthService->startAuthorization(
            $result['client_id'],
            'https://evil.com/steal-token',
            [CommerceScopes::ORDERS_READ]
        );
    }

    public function test_authorization_rejects_unauthorized_scopes(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ], // Only has read access
        ]);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid or unauthorized scopes');

        $this->oauthService->startAuthorization(
            $result['client_id'],
            'https://example.com/callback',
            [CommerceScopes::ORDERS_MANAGE] // Requesting manage scope
        );
    }

    public function test_authorization_returns_app_info_for_consent(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'My Cool App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ, CommerceScopes::PRODUCTS_READ],
            'description' => 'A cool app',
            'website' => 'https://example.com',
        ]);

        $authData = $this->oauthService->startAuthorization(
            $result['client_id'],
            'https://example.com/callback',
            [CommerceScopes::ORDERS_READ]
        );

        $this->assertEquals('My Cool App', $authData['application']['name']);
        $this->assertEquals('A cool app', $authData['application']['description']);
        $this->assertCount(1, $authData['scopes']);
    }

    // =========================================================================
    // Token Exchange Tests
    // =========================================================================

    public function test_can_exchange_code_for_token(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ],
        ]);

        $code = $this->oauthService->authorize(
            $result['application']->id,
            1, // store_id
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $tokens = $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            $result['client_secret'],
            'https://example.com/callback'
        );

        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('expires_in', $tokens);
        $this->assertEquals('Bearer', $tokens['token_type']);
    }

    public function test_code_can_only_be_used_once(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ],
        ]);

        $code = $this->oauthService->authorize(
            $result['application']->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        // First use should work
        $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            $result['client_secret'],
            'https://example.com/callback'
        );

        // Second use should fail
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Authorization code is invalid or expired');

        $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            $result['client_secret'],
            'https://example.com/callback'
        );
    }

    public function test_code_exchange_rejects_wrong_secret(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ],
        ]);

        $code = $this->oauthService->authorize(
            $result['application']->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Client authentication failed');

        $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            'wrong-secret',
            'https://example.com/callback'
        );
    }

    // =========================================================================
    // Token Refresh Tests
    // =========================================================================

    public function test_can_refresh_token(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ],
        ]);

        $code = $this->oauthService->authorize(
            $result['application']->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $tokens = $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            $result['client_secret'],
            'https://example.com/callback'
        );

        $newTokens = $this->oauthService->refreshToken(
            $tokens['refresh_token'],
            $result['client_id'],
            $result['client_secret']
        );

        $this->assertArrayHasKey('access_token', $newTokens);
        $this->assertNotEquals($tokens['access_token'], $newTokens['access_token']);
    }

    public function test_refresh_cannot_expand_scopes(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ, CommerceScopes::ORDERS_WRITE],
        ]);

        $code = $this->oauthService->authorize(
            $result['application']->id,
            1,
            [CommerceScopes::ORDERS_READ], // Only read scope
            'https://example.com/callback'
        );

        $tokens = $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            $result['client_secret'],
            'https://example.com/callback'
        );

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Cannot expand scopes on refresh');

        $this->oauthService->refreshToken(
            $tokens['refresh_token'],
            $result['client_id'],
            $result['client_secret'],
            [CommerceScopes::ORDERS_READ, CommerceScopes::ORDERS_WRITE] // Try to add write scope
        );
    }

    // =========================================================================
    // Token Validation Tests
    // =========================================================================

    public function test_can_validate_access_token(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ],
        ]);

        $code = $this->oauthService->authorize(
            $result['application']->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback'
        );

        $tokens = $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            $result['client_secret'],
            'https://example.com/callback'
        );

        $token = $this->oauthService->validateToken($tokens['access_token']);

        $this->assertNotNull($token);
        $this->assertContains(CommerceScopes::ORDERS_READ, $token->scopes);
    }

    public function test_expired_token_is_invalid(): void
    {
        $tokenData = OAuthAccessToken::createTokenPair(1, 1, [CommerceScopes::ORDERS_READ]);

        // Manually expire the token
        $tokenData['token']->update(['expires_at' => now()->subHour()]);

        $result = $this->oauthService->validateToken($tokenData['access_token']);

        $this->assertNull($result);
    }

    public function test_revoked_token_is_invalid(): void
    {
        $tokenData = OAuthAccessToken::createTokenPair(1, 1, [CommerceScopes::ORDERS_READ]);

        $tokenData['token']->revoke();

        $result = $this->oauthService->validateToken($tokenData['access_token']);

        $this->assertNull($result);
    }

    // =========================================================================
    // Scope Checking Tests
    // =========================================================================

    public function test_scope_checking_works_correctly(): void
    {
        $this->assertTrue(CommerceScopes::hasScope(
            [CommerceScopes::ORDERS_READ],
            CommerceScopes::ORDERS_READ
        ));

        $this->assertFalse(CommerceScopes::hasScope(
            [CommerceScopes::ORDERS_READ],
            CommerceScopes::ORDERS_WRITE
        ));
    }

    public function test_manage_scope_grants_read_and_write(): void
    {
        $this->assertTrue(CommerceScopes::hasScope(
            [CommerceScopes::ORDERS_MANAGE],
            CommerceScopes::ORDERS_READ
        ));

        $this->assertTrue(CommerceScopes::hasScope(
            [CommerceScopes::ORDERS_MANAGE],
            CommerceScopes::ORDERS_WRITE
        ));
    }

    public function test_manage_all_grants_everything(): void
    {
        $this->assertTrue(CommerceScopes::hasScope(
            [CommerceScopes::MANAGE_ALL],
            CommerceScopes::ORDERS_READ
        ));

        $this->assertTrue(CommerceScopes::hasScope(
            [CommerceScopes::MANAGE_ALL],
            CommerceScopes::PRODUCTS_DELETE
        ));

        $this->assertTrue(CommerceScopes::hasScope(
            [CommerceScopes::MANAGE_ALL],
            CommerceScopes::PAYMENTS_WRITE
        ));
    }

    public function test_read_all_grants_only_read_scopes(): void
    {
        $this->assertTrue(CommerceScopes::hasScope(
            [CommerceScopes::READ_ALL],
            CommerceScopes::ORDERS_READ
        ));

        $this->assertFalse(CommerceScopes::hasScope(
            [CommerceScopes::READ_ALL],
            CommerceScopes::ORDERS_WRITE
        ));
    }

    // =========================================================================
    // PKCE Tests
    // =========================================================================

    public function test_pkce_flow_works(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Public App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ],
        ]);

        // Generate PKCE challenge
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $code = $this->oauthService->authorize(
            $result['application']->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback',
            null,
            $codeChallenge,
            'S256'
        );

        // Exchange with code verifier
        $tokens = $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            $result['client_secret'],
            'https://example.com/callback',
            $codeVerifier
        );

        $this->assertArrayHasKey('access_token', $tokens);
    }

    public function test_pkce_rejects_wrong_verifier(): void
    {
        $result = OAuthApplication::createWithCredentials([
            'store_id' => 1,
            'name' => 'Public App',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => [CommerceScopes::ORDERS_READ],
        ]);

        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $code = $this->oauthService->authorize(
            $result['application']->id,
            1,
            [CommerceScopes::ORDERS_READ],
            'https://example.com/callback',
            null,
            $codeChallenge,
            'S256'
        );

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Code verifier validation failed');

        $this->oauthService->exchangeCode(
            $code,
            $result['client_id'],
            $result['client_secret'],
            'https://example.com/callback',
            'wrong-verifier'
        );
    }
}
