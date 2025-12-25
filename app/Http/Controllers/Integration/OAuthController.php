<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\Integration\Auth\AuthenticationManager;
use Illuminate\Http\Request;

/**
 * OAuth Controller
 *
 * Handles OAuth callbacks from external services.
 */
class OAuthController extends Controller
{
    public function __construct(
        protected AuthenticationManager $authManager
    ) {}

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error');

        if ($error) {
            return redirect()->route('integrations.index')->with('error',
                $request->input('error_description', 'OAuth authorization failed')
            );
        }

        if (!$code || !$state) {
            return redirect()->route('integrations.index')->with('error',
                'Invalid OAuth callback parameters'
            );
        }

        try {
            $result = $this->authManager->handleOAuthCallback($code, $state);

            if ($result['success']) {
                return redirect()->route('integrations.connections.show', $result['connection_id'])
                    ->with('success', 'Connection established successfully');
            }

            return redirect()->route('integrations.index')
                ->with('error', 'Failed to establish connection');

        } catch (\App\Exceptions\Integration\OAuthException $e) {
            return redirect()->route('integrations.index')
                ->with('error', $e->getMessage());
        }
    }
}
