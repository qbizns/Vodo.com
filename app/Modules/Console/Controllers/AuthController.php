<?php

namespace App\Modules\Console\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Console Authentication Controller
 * 
 * Handles authentication for the Console panel (SaaS platform management).
 * Users must have console_admin or super_admin role to access.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        // If already logged in and has console access, redirect to dashboard
        if (Auth::guard('console')->check() && Auth::guard('console')->user()->canAccessConsole()) {
            return redirect()->route('console.dashboard');
        }

        return view('console::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('console')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('console')->user();

            // Check if user can access console panel
            if (!$user->canAccessConsole()) {
                Auth::guard('console')->logout();
                return back()->withErrors([
                    'email' => 'You do not have permission to access the Console panel.',
                ])->onlyInput('email');
            }

            // Check if user is active
            if (!$user->isActive()) {
                Auth::guard('console')->logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->recordSuccessfulLogin($request->ip());

            return redirect()->intended(route('console.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('console')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('console.login');
    }
}

