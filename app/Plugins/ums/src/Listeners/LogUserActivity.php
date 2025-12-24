<?php

namespace Ums\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

/**
 * Log User Activity Listener
 */
class LogUserActivity
{
    /**
     * Handle user login event.
     */
    public function handleLogin(Login $event): void
    {
        Log::info('UMS: User logged in', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle user logout event.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            Log::info('UMS: User logged out', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ]);
        }
    }

    /**
     * Handle failed login event.
     */
    public function handleFailed(Failed $event): void
    {
        Log::warning('UMS: Failed login attempt', [
            'email' => $event->credentials['email'] ?? 'unknown',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

