<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Owner Authentication Controller
 * 
 * Handles authentication for the Owner panel (business owner management).
 * Users must have owner, console_admin, or super_admin role to access.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        // If already logged in and has owner access, redirect to dashboard
        if (Auth::guard('owner')->check() && Auth::guard('owner')->user()->canAccessOwner()) {
            return redirect()->route('owner.dashboard');
        }

        return view('owner::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('owner')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('owner')->user();

            // Check if user can access owner panel
            if (!$user->canAccessOwner()) {
                Auth::guard('owner')->logout();
                return back()->withErrors([
                    'email' => 'You do not have permission to access the Owner panel.',
                ])->onlyInput('email');
            }

            // Check if user is active
            if (!$user->isActive()) {
                Auth::guard('owner')->logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->recordSuccessfulLogin($request->ip());

            return redirect()->intended(route('owner.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('owner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login');
    }
}

