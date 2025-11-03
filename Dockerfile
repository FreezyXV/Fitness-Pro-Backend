# Production Dockerfile for Laravel Fitness App
FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    postgresql-dev \
    nginx \
    supervisor \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files first
COPY composer.json composer.lock ./

# Copy artisan file (renamed from artisan_cli) for composer scripts
COPY artisan_cli ./artisan
RUN chmod +x artisan

# Install PHP dependencies (skip Laravel post-autoload scripts for now)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application files
COPY . .

# Create storage and cache directories first
RUN mkdir -p storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Run Laravel post-autoload scripts now that all files and directories are present
RUN composer run-script post-autoload-dump

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create production .env file with database sessions
RUN if [ ! -f .env ]; then cp .env.example .env; fi \
    && sed -i 's/SESSION_DRIVER=file/SESSION_DRIVER=database/' .env \
    && sed -i 's/CACHE_STORE=file/CACHE_STORE=database/' .env \
    && sed -i 's/QUEUE_CONNECTION=database/QUEUE_CONNECTION=database/' .env \
    && php artisan key:generate --no-interaction

# Optimize Laravel (skip config:cache as it requires DB connection)
RUN php artisan route:cache \
    && php artisan view:cache

# Create nginx directories
RUN mkdir -p /var/log/nginx /var/cache/nginx

# Copy and set permissions for startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Expose port
EXPOSE 80

# Start services with startup script
CMD ["/usr/local/bin/startup.sh"]