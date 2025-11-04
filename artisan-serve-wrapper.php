#!/usr/bin/env php
<?php

/**
 * Laravel Artisan Serve Wrapper
 * Sets max_execution_time to 0 before starting the server
 */

// Set unlimited execution time
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

// Verify settings
echo "PHP Configuration:\n";
echo "  max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "  memory_limit: " . ini_get('memory_limit') . "\n\n";

// Get the port from command line arguments
$port = $argv[1] ?? 8000;

echo "Starting Laravel development server on port {$port}...\n\n";

// Change to the correct directory
chdir(__DIR__);

// Execute artisan serve
passthru("php artisan serve --port={$port}");
