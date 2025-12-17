<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTask;
use App\Models\TaskLog;
use App\Models\RecurringJob;
use App\Models\EventSubscription;
use App\Services\Scheduler\TaskScheduler;
use App\Services\Scheduler\EventDispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SchedulerApiController extends Controller
{
    public function __construct(
        protected TaskScheduler $scheduler,
        protected EventDispatcher $dispatcher
    ) {}

    // =========================================================================
    // Scheduled Tasks
    // =========================================================================

    public function indexTasks(Request $request): JsonResponse
    {
        $query = ScheduledTask::query();

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }
        if ($request->boolean('active_only', true)) {
            $query->active();
        }
        if ($request->boolean('due_only')) {
            $query->due();
        }

        $tasks = $query->byPriority()->with('latestLog')->get();

        return response()->json(['success' => true, 'data' => $tasks]);
    }

    public function showTask(string $slug): JsonResponse
    {
        $task = ScheduledTask::findBySlug($slug);
        if (!$task) {
            return response()->json(['success' => false, 'error' => 'Task not found'], 404);
        }

        $task->load(['logs' => fn($q) => $q->orderByDesc('started_at')->limit(10)]);

        return response()->json(['success' => true, 'data' => $task]);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:150'],
            'handler' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:callback,job,command'],
            'expression' => ['required', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'parameters' => ['nullable', 'array'],
            'without_overlapping' => ['nullable', 'boolean'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'priority' => ['nullable', 'integer'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $task = $this->scheduler->register($validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $task], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function updateTask(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'handler' => ['nullable', 'string', 'max:255'],
            'expression' => ['nullable', 'string', 'max:100'],
            'parameters' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $task = $this->scheduler->update($slug, $validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $task]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function destroyTask(Request $request, string $slug): JsonResponse
    {
        $pluginSlug = $request->input('plugin_slug');
        
        try {
            $this->scheduler->unregister($slug, $pluginSlug);
            return response()->json(['success' => true, 'message' => 'Task deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function runTask(Request $request, string $slug): JsonResponse
    {
        $task = ScheduledTask::findBySlug($slug);
        if (!$task) {
            return response()->json(['success' => false, 'error' => 'Task not found'], 404);
        }

        $result = $this->scheduler->runTask($task);

        return response()->json(['success' => true, 'data' => $result]);
    }

    // =========================================================================
    // Task Logs
    // =========================================================================

    public function taskLogs(Request $request, string $slug): JsonResponse
    {
        $task = ScheduledTask::findBySlug($slug);
        if (!$task) {
            return response()->json(['success' => false, 'error' => 'Task not found'], 404);
        }

        $query = $task->logs()->orderByDesc('started_at');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('limit')) {
            $query->limit($request->limit);
        }

        $logs = $query->get();

        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function allLogs(Request $request): JsonResponse
    {
        $query = TaskLog::with('task:id,slug,name')->orderByDesc('started_at');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('hours')) {
            $query->recent($request->hours);
        }

        $logs = $query->limit($request->input('limit', 100))->get();

        return response()->json(['success' => true, 'data' => $logs]);
    }

    // =========================================================================
    // Recurring Jobs
    // =========================================================================

    public function indexJobs(Request $request): JsonResponse
    {
        $query = RecurringJob::query();

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }
        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $jobs = $query->get();

        return response()->json(['success' => true, 'data' => $jobs]);
    }

    public function storeJob(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:150'],
            'handler' => ['required', 'string', 'max:255'],
            'interval_type' => ['required', 'string', 'in:seconds,minutes,hours,days,weeks'],
            'interval_value' => ['required', 'integer', 'min:1'],
            'parameters' => ['nullable', 'array'],
            'run_after' => ['nullable', 'date_format:H:i'],
            'run_before' => ['nullable', 'date_format:H:i'],
            'run_on_days' => ['nullable', 'array'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $job = $this->scheduler->registerRecurringJob($validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $job], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function destroyJob(Request $request, string $slug): JsonResponse
    {
        $job = RecurringJob::findBySlug($slug);
        if (!$job) {
            return response()->json(['success' => false, 'error' => 'Job not found'], 404);
        }

        $pluginSlug = $request->input('plugin_slug');
        if ($pluginSlug && $job->plugin_slug !== $pluginSlug) {
            return response()->json(['success' => false, 'error' => 'Not authorized'], 403);
        }

        $job->delete();

        return response()->json(['success' => true, 'message' => 'Job deleted']);
    }

    // =========================================================================
    // Event Subscriptions
    // =========================================================================

    public function indexSubscriptions(Request $request): JsonResponse
    {
        $query = EventSubscription::query();

        if ($request->has('event')) {
            $query->forEvent($request->event);
        }
        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }
        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $subscriptions = $query->byPriority()->get();

        return response()->json(['success' => true, 'data' => $subscriptions]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'max:150'],
            'listener' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'integer'],
            'async' => ['nullable', 'boolean'],
            'queue' => ['nullable', 'string', 'max:50'],
            'conditions' => ['nullable', 'array'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        $subscription = $this->dispatcher->subscribe(
            $validated['event'],
            $validated['listener'],
            $validated,
            $validated['plugin_slug']
        );

        return response()->json(['success' => true, 'data' => $subscription], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string'],
            'listener' => ['required', 'string'],
            'plugin_slug' => ['required', 'string'],
        ]);

        $result = $this->dispatcher->unsubscribe(
            $validated['event'],
            $validated['listener'],
            $validated['plugin_slug']
        );

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Unsubscribed' : 'Subscription not found',
        ]);
    }

    public function dispatchEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'max:150'],
            'payload' => ['nullable', 'array'],
        ]);

        $results = $this->dispatcher->dispatch(
            $validated['event'],
            $validated['payload'] ?? []
        );

        return response()->json(['success' => true, 'data' => $results]);
    }

    // =========================================================================
    // Statistics & Meta
    // =========================================================================

    public function stats(Request $request): JsonResponse
    {
        $hours = $request->input('hours', 24);
        $stats = $this->scheduler->getStats($hours);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function expressions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ScheduledTask::getCommonExpressions(),
        ]);
    }

    public function taskTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ScheduledTask::getTypes(),
        ]);
    }

    public function intervalTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => RecurringJob::getIntervalTypes(),
        ]);
    }

    public function runDue(): JsonResponse
    {
        $results = $this->scheduler->runDueTasks();

        return response()->json(['success' => true, 'data' => $results]);
    }
}
