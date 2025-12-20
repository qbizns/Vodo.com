<?php

namespace HelloWorld\Services;

use HelloWorld\Models\Greeting;
use HelloWorld\Events\GreetingCreated;
use HelloWorld\Events\GreetingDeleted;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class GreetingService
{
    /**
     * Get all greetings with optional filtering.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getGreetings(array $filters = []): LengthAwarePaginator
    {
        $query = Greeting::query();

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply author filter
        if (!empty($filters['author'])) {
            $query->byAuthor($filters['author']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        // Paginate
        $perPage = $filters['per_page'] ?? 10;
        
        return $query->paginate($perPage);
    }

    /**
     * Get recent greetings.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentGreetings(int $limit = 5)
    {
        return Cache::remember('hello-world.recent-greetings', 60, function () use ($limit) {
            return Greeting::recent($limit)->get();
        });
    }

    /**
     * Get a greeting by ID.
     *
     * @param int $id
     * @return Greeting|null
     */
    public function findById(int $id): ?Greeting
    {
        return Greeting::find($id);
    }

    /**
     * Create a new greeting.
     *
     * @param array $data
     * @return Greeting
     */
    public function createGreeting(array $data): Greeting
    {
        // Apply hook filter before creation
        $data = $this->applyFilter('greeting.creating', $data);

        $greeting = Greeting::create([
            'message' => $data['message'],
            'author' => $data['author'] ?? 'Anonymous',
        ]);

        // Fire hook after creation
        $this->fireHook('greeting.created', $greeting);

        // Fire event
        event(new GreetingCreated($greeting));

        // Clear cache
        $this->clearCache();

        return $greeting;
    }

    /**
     * Update a greeting.
     *
     * @param Greeting $greeting
     * @param array $data
     * @return Greeting
     */
    public function updateGreeting(Greeting $greeting, array $data): Greeting
    {
        // Apply hook filter before update
        $data = $this->applyFilter('greeting.updating', $data, $greeting);

        $greeting->update($data);

        // Fire hook after update
        $this->fireHook('greeting.updated', $greeting);

        // Clear cache
        $this->clearCache();

        return $greeting->fresh();
    }

    /**
     * Delete a greeting.
     *
     * @param Greeting $greeting
     * @return bool
     */
    public function deleteGreeting(Greeting $greeting): bool
    {
        // Fire hook before deletion
        if ($this->fireHook('greeting.deleting', $greeting) === false) {
            return false;
        }

        $greetingId = $greeting->id;
        $deleted = $greeting->delete();

        if ($deleted) {
            // Fire hook after deletion
            $this->fireHook('greeting.deleted', $greetingId);

            // Fire event
            event(new GreetingDeleted($greetingId));

            // Clear cache
            $this->clearCache();
        }

        return $deleted;
    }

    /**
     * Get greeting statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return Cache::remember('hello-world.statistics', 300, function () {
            return [
                'total' => Greeting::count(),
                'today' => Greeting::whereDate('created_at', today())->count(),
                'this_week' => Greeting::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])->count(),
                'this_month' => Greeting::whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ])->count(),
            ];
        });
    }

    /**
     * Clear greeting cache.
     */
    public function clearCache(): void
    {
        Cache::forget('hello-world.greetings');
        Cache::forget('hello-world.recent-greetings');
        Cache::forget('hello-world.statistics');
    }

    /**
     * Apply a filter hook.
     *
     * @param string $hook
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    protected function applyFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (app()->bound('hooks')) {
            return app('hooks')->applyFilter($hook, $value, ...$args);
        }

        return $value;
    }

    /**
     * Fire an action hook.
     *
     * @param string $hook
     * @param mixed ...$args
     * @return mixed
     */
    protected function fireHook(string $hook, mixed ...$args): mixed
    {
        if (app()->bound('hooks')) {
            return app('hooks')->fire($hook, ...$args);
        }

        return null;
    }
}
