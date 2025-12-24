<?php

namespace App\Modules\ClientArea\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Client Area Authentication Controller
 * 
 * Handles authentication for the Client Area panel.
 * Any authenticated user can access the client area.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (Auth::guard('client')->check()) {
            return redirect()->route('clientarea.dashboard');
        }

        return view('clientarea::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('client')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('client')->user();

            // Check if user is active
            if (!$user->isActive()) {
                Auth::guard('client')->logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->recordSuccessfulLogin($request->ip());

            return redirect()->intended(route('clientarea.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('clientarea.login');
    }
}

