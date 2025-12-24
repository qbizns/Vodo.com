<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin Authentication Controller
 * 
 * Handles authentication for the Admin panel (backend administration).
 * Users must have admin, owner, console_admin, or super_admin role to access.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        // If already logged in and has admin access, redirect to dashboard
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('admin')->user();

            // Check if user can access admin panel
            if (!$user->canAccessAdmin()) {
                Auth::guard('admin')->logout();
                return back()->withErrors([
                    'email' => 'You do not have permission to access the Admin panel.',
                ])->onlyInput('email');
            }

            // Check if user is active
            if (!$user->isActive()) {
                Auth::guard('admin')->logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->recordSuccessfulLogin($request->ip());

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

