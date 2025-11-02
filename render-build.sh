#!/bin/bash
# Render Build Script for Laravel
# This script runs during build phase

set -e

echo "🔨 Starting Render build process..."

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force --no-interaction

# Seed the database
echo "🌱 Seeding database..."
php artisan db:seed --force --no-interaction

# Clear and cache configuration
echo "⚙️  Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Build completed successfully!"
