#!/bin/bash

# Laravel Clean Serve Script
# Uses custom server.php to prevent PHP notices from corrupting JSON responses

PORT=${1:-8000}

echo "🚀 Starting Laravel development server on port $PORT with clean JSON responses..."
echo "📝 Using custom server.php to suppress PHP notices"
echo "🌐 Server will be available at: http://localhost:$PORT"
echo ""

# Kill any existing processes on the port
if lsof -ti:$PORT > /dev/null 2>&1; then
    echo "⚠️  Port $PORT is in use. Killing existing processes..."
    kill -9 $(lsof -ti:$PORT) 2>/dev/null || true
    sleep 1
fi

# Start the server with our custom router
php -S localhost:$PORT -t public server.php

echo ""
echo "✅ Server stopped"