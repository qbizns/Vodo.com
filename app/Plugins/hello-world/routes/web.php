<?php

use HelloWorld\Http\Controllers\HelloController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Hello World Plugin Routes
|--------------------------------------------------------------------------
|
| These routes are automatically prefixed with /plugins/hello-world
| and named with plugins.hello-world.*
|
*/

Route::middleware(['auth:admin,console,owner'])->group(function () {
    Route::get('/', [HelloController::class, 'index'])->name('index');
    Route::get('/greetings', [HelloController::class, 'greetings'])->name('greetings');
    Route::post('/greetings', [HelloController::class, 'store'])->name('greetings.store');
    Route::delete('/greetings/{greeting}', [HelloController::class, 'destroy'])->name('greetings.destroy');
});
