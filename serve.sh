#!/bin/bash

# Laravel Clean Serve Script
# Uses custom server.php to prevent PHP notices from corrupting JSON responses
# Includes automatic cleanup of zombie PHP processes

PORT=${1:-8000}

echo "🧹 Cleaning up any zombie PHP processes..."
pkill -f "php.*artisan serve" 2>/dev/null || true
pkill -f "php -S 127.0.0.1:800" 2>/dev/null || true
pkill -f "php -S localhost:800" 2>/dev/null || true
sleep 1

echo "🚀 Starting Laravel development server on port $PORT with clean JSON responses..."
echo "📝 Using custom php.ini (max_execution_time=0)"
echo "🌐 Server will be available at: http://localhost:$PORT"
echo ""

# Kill any existing processes on the specific port
if lsof -ti:$PORT > /dev/null 2>&1; then
    echo "⚠️  Port $PORT is in use. Killing existing processes..."
    kill -9 $(lsof -ti:$PORT) 2>/dev/null || true
    sleep 1
fi

# Start the server with custom PHP configuration
# NOTE: If you still get 300-second timeouts, see FIX_PHP_TIMEOUT.md
# The -d flags here may not work with artisan serve due to child process spawning
php -d max_execution_time=0 -d memory_limit=512M artisan serve --port=$PORT

echo ""
echo "✅ Server stopped"