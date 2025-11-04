<?php
/**
 * Memory Optimization Bootstrap
 * This file optimizes PHP memory usage for the Laravel application
 */

// Set appropriate memory limits based on environment
if (PHP_SAPI === 'cli') {
    // CLI operations (artisan commands) need more memory
    ini_set('memory_limit', '512M');

    // Check if we're running in local development
    // Look for .env file with APP_ENV=local or APP_DEBUG=true
    $envPath = dirname(__DIR__) . '/.env';
    $isLocalDev = false;

    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        // Check for local environment or debug mode
        $isLocalDev = (preg_match('/^APP_ENV\s*=\s*local/m', $envContent) ||
                      preg_match('/^APP_DEBUG\s*=\s*true/m', $envContent));
    }

    // For local development (artisan serve), allow unlimited execution
    // For production CLI tasks, enforce reasonable limits
    if ($isLocalDev) {
        ini_set('max_execution_time', 0);  // Unlimited for local dev
    } else {
        ini_set('max_execution_time', 300); // 5 minutes for production CLI
    }
} else {
    // Web requests should use less memory
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', 120);
}

// Suppress PHP notices/warnings for clean JSON responses
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING);

// Optimize garbage collection
ini_set('zend.enable_gc', '1');

// Optimize OPcache if available
if (function_exists('opcache_get_status') && opcache_get_status()) {
    ini_set('opcache.memory_consumption', '128');
    ini_set('opcache.max_accelerated_files', '4000');
    ini_set('opcache.revalidate_freq', '2');
    ini_set('opcache.fast_shutdown', '1');
}

// Log memory usage in development
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    register_shutdown_function(function() {
        $memory = memory_get_peak_usage(true);
        $memoryMB = round($memory / 1024 / 1024, 2);
        error_log("Peak memory usage: {$memoryMB} MB");
    });
}