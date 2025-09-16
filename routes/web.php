<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => '🏋️ Fitness Pro Backend is Active!',
        'status' => 'healthy',
        'api_version' => '2.0.0',
        'environment' => app()->environment(),
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
        'endpoints' => [
            'health' => '/api/health',
            'test' => '/api/test',
            'exercises' => '/api/exercises',
            'auth' => '/api/auth/login'
        ],
        'message_for_users' => 'Your fitness journey starts here! 💪 API endpoints are ready for your Angular frontend.',
        'timestamp' => now()->toISOString()
    ], 200, ['Content-Type' => 'application/json']);
});

// Named auth routes for Sanctum middleware
Route::name('auth.')->group(function () {
    Route::get('login', function () {
        return response()->json([
            'message' => 'Please login via API endpoints',
            'login_url' => '/api/auth/login'
        ], 401);
    })->name('login');
    
    Route::get('register', function () {
        return response()->json([
            'message' => 'Please register via API endpoints',
            'register_url' => '/api/auth/register'
        ], 401);
    })->name('register');
});
