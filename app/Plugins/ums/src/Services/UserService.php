<?php

namespace Ums\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * User Service
 * 
 * Handles user management operations.
 */
class UserService
{
    /**
     * Get paginated users with optional filters.
     */
    public function getUsers(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = User::query()->with('roles');

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('slug', $filters['role']);
            });
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status']);
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Assign roles
            if (!empty($data['roles'])) {
                $user->roles()->sync($data['roles']);
            }

            // Clear cache
            $this->clearUserCache($user->id);

            return $user;
        });
    }

    /**
     * Update a user.
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $updateData = [
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }

            $user->update($updateData);

            // Update roles
            if (isset($data['roles'])) {
                $user->roles()->sync($data['roles']);
            }

            // Clear cache
            $this->clearUserCache($user->id);

            return $user->fresh(['roles']);
        });
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Detach roles
            $user->roles()->detach();

            // Clear cache
            $this->clearUserCache($user->id);

            return $user->delete();
        });
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);
        $this->clearUserCache($user->id);
        
        return $user;
    }

    /**
     * Reset user password.
     */
    public function resetPassword(User $user, string $newPassword): User
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Invalidate all sessions (optional)
        // DB::table('sessions')->where('user_id', $user->id)->delete();

        return $user;
    }

    /**
     * Assign roles to a user.
     */
    public function assignRoles(User $user, array $roleIds): User
    {
        $user->roles()->sync($roleIds);
        $this->clearUserCache($user->id);
        
        return $user->fresh(['roles']);
    }

    /**
     * Get user statistics.
     */
    public function getStatistics(): array
    {
        return Cache::remember('ums.user_statistics', 300, function () {
            return [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'today' => User::whereDate('created_at', today())->count(),
                'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            ];
        });
    }

    /**
     * Get recent users.
     */
    public function getRecentUsers(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return User::with('roles')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clear user-related cache.
     */
    protected function clearUserCache(?int $userId = null): void
    {
        Cache::forget('ums.user_statistics');
        
        if ($userId) {
            Cache::forget("ums.user.{$userId}");
        }
    }
}

