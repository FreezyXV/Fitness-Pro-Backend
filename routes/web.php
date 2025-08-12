<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('Hello World', 200);

    // Health check endpoint pour Fly.io
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'app' => config('app.name')
    ]);
});
});
