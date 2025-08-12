# Étape 1 : Builder frontend
FROM node:18 AS frontend

WORKDIR /app

# Copier uniquement les fichiers nécessaires pour l'installation des dépendances
COPY package.json package-lock.json vite.config.js ./
COPY resources/css ./resources/css
COPY resources/js ./resources/js

RUN npm install --silent

# Copier le reste des fichiers et builder
COPY . .
RUN npm run build

# Étape 2 : Builder PHP
FROM composer:2 AS composer

WORKDIR /app

# Copier uniquement les fichiers nécessaires pour Composer
COPY composer.json composer.lock ./

# Installer les dépendances sans dev
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction

# Étape 3 : Image finale
FROM php:8.2-fpm

# Dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Configuration PHP
COPY ./.user.ini /usr/local/etc/php/conf.d/php.ini

# Copier l'application
COPY . /var/www/html

# Copier les dépendances et les assets construits
COPY --from=composer /app/vendor /var/www/html/vendor
COPY --from=frontend /app/public/build /var/www/html/public/build

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Installer les dépendances de production seulement (supprimer devDependencies)
RUN rm -rf /var/www/html/node_modules

# Port d'exposition
EXPOSE 8080

# Commande de démarrage
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]