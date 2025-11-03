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

# Seed the database when explicitly requested
if [ "${RUN_DB_SEEDERS:-false}" = "true" ]; then
    SEEDER_CLASS="${DB_SEEDER_CLASS:-DatabaseSeeder}"
    echo "🌱 Seeding database during build using ${SEEDER_CLASS}..."
    php artisan db:seed --class="${SEEDER_CLASS}" --force --no-interaction
else
    echo "🌱 Skipping database seeding during build (set RUN_DB_SEEDERS=true to enable)."
fi

# Clear and cache configuration
echo "⚙️  Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Build completed successfully!"
