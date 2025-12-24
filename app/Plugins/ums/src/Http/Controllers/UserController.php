<?php

namespace Ums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ums\Services\UserService;
use Ums\Http\Requests\StoreUserRequest;
use Ums\Http\Requests\UpdateUserRequest;

/**
 * User Controller
 */
class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role', 'status', 'sort', 'direction']);
        $users = $this->userService->getUsers($filters, config('ums.users_per_page', 25));
        $roles = Role::active()->orderBy('name')->get();

        return view('ums::users.index', compact('users', 'roles', 'filters'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::active()->orderBy('name')->get();

        return view('ums::users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user,
            'redirect' => route('plugins.ums.users.index'),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load('roles');

        return view('ums::users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::active()->orderBy('name')->get();

        return view('ums::users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user,
            'redirect' => route('plugins.ums.users.index'),
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        $this->userService->deleteUser($user);

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Impersonate a user.
     */
    public function impersonate(User $user)
    {
        // Store original user ID in session
        session(['impersonating_from' => Auth::id()]);

        Auth::login($user);

        return response()->json([
            'success' => true,
            'message' => "Now impersonating {$user->name}.",
            'redirect' => route('admin.dashboard'),
        ]);
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user)
    {
        // Prevent self-deactivation
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 403);
        }

        $user = $this->userService->toggleStatus($user);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "User {$status} successfully.",
            'data' => $user,
        ]);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->userService->resetPassword($user, $request->password);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }

    /**
     * Show user activity.
     */
    public function activity(User $user)
    {
        // Get user activity from audit logs
        $activities = [];

        return view('ums::users.activity', compact('user', 'activities'));
    }
}

