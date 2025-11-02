<?php
//config/cors.php
return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'health', 'version'],

    'allowed_methods' => ['*'],

'allowed_origins' => [
        'http://localhost:4200',
        'http://127.0.0.1:4200',
        'http://localhost:4201',
        'http://127.0.0.1:4201',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        env('FRONTEND_URL', 'https://fitness-pro-frontend.vercel.app'),
        'https://fitness-pro-frontend-ajn5lmhat-ivans-projects-66d9a97b.vercel.app',
    ],

    'allowed_origins_patterns' => [
        '#^https://fitness-pro-frontend-.*\.vercel\.app$#',
        '#^https://.*\.fly\.dev$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];