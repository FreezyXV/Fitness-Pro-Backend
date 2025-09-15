<?php
// Quick route test without loading all models
ini_set('memory_limit', '512M');

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing basic Laravel setup...\n";

    // Test database connection
    $pdo = new PDO('sqlite:database/database.sqlite');
    echo "✅ Database connection: OK\n";

    // Test cache
    echo "✅ Laravel version: " . app()->version() . "\n";

    // Test config
    echo "✅ Environment: " . config('app.env') . "\n";

    // Test route count
    $routes = app('router')->getRoutes();
    echo "✅ Routes loaded: " . $routes->count() . " routes\n";

    echo "✅ Basic setup working correctly\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}