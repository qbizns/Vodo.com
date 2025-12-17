<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SchedulerApiController;

Route::prefix('api/v1/scheduler')->group(function () {

    // Public routes
    Route::middleware(['api'])->group(function () {
        Route::get('meta/expressions', [SchedulerApiController::class, 'expressions'])->name('scheduler.expressions');
        Route::get('meta/task-types', [SchedulerApiController::class, 'taskTypes'])->name('scheduler.task-types');
        Route::get('meta/interval-types', [SchedulerApiController::class, 'intervalTypes'])->name('scheduler.interval-types');
    });

    // Authenticated routes
    Route::middleware(['api', 'auth:sanctum'])->group(function () {
        // Scheduled Tasks
        Route::get('tasks', [SchedulerApiController::class, 'indexTasks'])->name('scheduler.tasks.index');
        Route::post('tasks', [SchedulerApiController::class, 'storeTask'])->name('scheduler.tasks.store');
        Route::get('tasks/{slug}', [SchedulerApiController::class, 'showTask'])->name('scheduler.tasks.show');
        Route::put('tasks/{slug}', [SchedulerApiController::class, 'updateTask'])->name('scheduler.tasks.update');
        Route::delete('tasks/{slug}', [SchedulerApiController::class, 'destroyTask'])->name('scheduler.tasks.destroy');
        Route::post('tasks/{slug}/run', [SchedulerApiController::class, 'runTask'])->name('scheduler.tasks.run');
        Route::get('tasks/{slug}/logs', [SchedulerApiController::class, 'taskLogs'])->name('scheduler.tasks.logs');

        // Task Logs
        Route::get('logs', [SchedulerApiController::class, 'allLogs'])->name('scheduler.logs.index');

        // Recurring Jobs
        Route::get('jobs', [SchedulerApiController::class, 'indexJobs'])->name('scheduler.jobs.index');
        Route::post('jobs', [SchedulerApiController::class, 'storeJob'])->name('scheduler.jobs.store');
        Route::delete('jobs/{slug}', [SchedulerApiController::class, 'destroyJob'])->name('scheduler.jobs.destroy');

        // Event Subscriptions
        Route::get('subscriptions', [SchedulerApiController::class, 'indexSubscriptions'])->name('scheduler.subscriptions.index');
        Route::post('subscriptions', [SchedulerApiController::class, 'subscribe'])->name('scheduler.subscriptions.store');
        Route::delete('subscriptions', [SchedulerApiController::class, 'unsubscribe'])->name('scheduler.subscriptions.destroy');
        Route::post('events/dispatch', [SchedulerApiController::class, 'dispatchEvent'])->name('scheduler.events.dispatch');

        // Statistics & Control
        Route::get('stats', [SchedulerApiController::class, 'stats'])->name('scheduler.stats');
        Route::post('run-due', [SchedulerApiController::class, 'runDue'])->name('scheduler.run-due');
    });
});
