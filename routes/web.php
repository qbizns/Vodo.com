<?php

use Illuminate\Support\Facades\Route;

// All routes are handled by the ModuleServiceProvider
// See app/Modules/*/routes.php for subdomain-specific routes

Route::get('/debug-error', function () {
    throw new \Exception('Testing error logging');
});
