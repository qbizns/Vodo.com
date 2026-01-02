<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\OAuth\OAuthAuthorizationController;
use VodoCommerce\Http\Controllers\OAuth\OAuthTokenController;

/*
|--------------------------------------------------------------------------
| OAuth 2.0 Routes
|--------------------------------------------------------------------------
|
| These routes implement the OAuth 2.0 Authorization Code flow with PKCE
| for third-party applications to access the Commerce API.
|
| RFC Compliance:
| - RFC 6749: OAuth 2.0 Authorization Framework
| - RFC 7636: PKCE (Proof Key for Code Exchange)
| - RFC 7009: Token Revocation
| - RFC 7662: Token Introspection
| - RFC 8414: OAuth 2.0 Authorization Server Metadata
|
*/

// =========================================================================
// OAuth Server Metadata (RFC 8414)
// =========================================================================

Route::get('/.well-known/oauth-authorization-server', [OAuthAuthorizationController::class, 'metadata'])
    ->name('oauth.metadata');

// =========================================================================
// Authorization Endpoints
// =========================================================================

Route::prefix('oauth')->name('oauth.')->group(function () {
    // Authorization endpoint - user consent flow
    // GET: Display consent screen
    // POST: Process user decision
    Route::get('/authorize', [OAuthAuthorizationController::class, 'authorize'])
        ->name('authorize')
        ->middleware(['web']);

    Route::post('/authorize', [OAuthAuthorizationController::class, 'confirm'])
        ->name('confirm')
        ->middleware(['web']);

    // Token endpoint - exchange code for tokens
    Route::post('/token', [OAuthTokenController::class, 'token'])
        ->name('token')
        ->middleware(['api', 'throttle:60,1']);

    // Token revocation endpoint (RFC 7009)
    Route::post('/revoke', [OAuthTokenController::class, 'revoke'])
        ->name('revoke')
        ->middleware(['api', 'throttle:60,1']);

    // Token introspection endpoint (RFC 7662)
    Route::post('/introspect', [OAuthTokenController::class, 'introspect'])
        ->name('introspect')
        ->middleware(['api', 'throttle:60,1']);

    // Scopes endpoint - list available scopes
    Route::get('/scopes', [OAuthAuthorizationController::class, 'scopes'])
        ->name('scopes');
});
