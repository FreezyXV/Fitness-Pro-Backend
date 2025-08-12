<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:4200',
        'http://127.0.0.1:4200',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        env('FRONTEND_URL', 'http://localhost:4200'),
        // Production frontend URLs - add your actual deployed URLs
        'https://your-frontend-app.vercel.app', // Update this!
    ],

    'allowed_origins_patterns' => [
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
        '#^https://.*\.vercel\.app$#', // Allow all Vercel apps
        '#^https://.*\.netlify\.app$#', // Allow Netlify if you use it
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];