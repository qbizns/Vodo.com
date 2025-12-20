<?php

use HelloWorld\Http\Controllers\Api\GreetingApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Hello World Plugin API Routes
|--------------------------------------------------------------------------
|
| These routes are automatically prefixed with /api/v1/plugins/hello-world
| and named with api.plugins.hello-world.*
|
*/

Route::middleware(['api'])->group(function () {
    // Greetings API
    Route::get('/greetings', [GreetingApiController::class, 'index'])->name('greetings.index');
    Route::post('/greetings', [GreetingApiController::class, 'store'])->name('greetings.store');
    Route::get('/greetings/{id}', [GreetingApiController::class, 'show'])->name('greetings.show');
    Route::put('/greetings/{id}', [GreetingApiController::class, 'update'])->name('greetings.update');
    Route::delete('/greetings/{id}', [GreetingApiController::class, 'destroy'])->name('greetings.destroy');
});
