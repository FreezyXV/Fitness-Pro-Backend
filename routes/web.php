<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
