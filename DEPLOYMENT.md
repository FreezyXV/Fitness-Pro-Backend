# 🚀 Deployment Guide - Laravel Fitness App Backend

## Prerequisites

- PHP 8.2+
- PostgreSQL 15+ or MySQL 8.0+
- Redis 7+
- Nginx
- Composer
- Git

## 🔧 Production Setup

### 1. Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx postgresql redis-server php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml php8.2-zip php8.2-pgsql php8.2-redis php8.2-gd php8.2-bcmath php8.2-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Database Setup

```bash
# PostgreSQL setup
sudo -u postgres createdb fitness_app_prod
sudo -u postgres createuser fitness_user --password
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE fitness_app_prod TO fitness_user;"
```

### 3. Application Deployment

```bash
# Clone repository
git clone <your-repo-url> /var/www/fitness-app
cd /var/www/fitness-app

# Copy environment file
cp .env.production .env

# Edit .env with your production values
nano .env

# Run deployment script
chmod +x deploy.sh
sudo ./deploy.sh
```

### 4. Nginx Configuration

```bash
# Copy nginx configuration
sudo cp nginx.conf /etc/nginx/sites-available/fitness-app
sudo ln -s /etc/nginx/sites-available/fitness-app /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default

# Test and restart nginx
sudo nginx -t
sudo systemctl restart nginx
```

### 5. SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

## 🐳 Docker Deployment

### Option 1: Docker Compose

```bash
# Copy environment file
cp .env.production .env

# Edit .env with your values
nano .env

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose exec app php artisan migrate --force
```

### Option 2: Individual Containers

```bash
# Build production image
docker build -f Dockerfile.prod -t fitness-app:prod .

# Run with docker run commands (see docker-compose.prod.yml for reference)
```

## ⚙️ Configuration

### Required Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-32-character-key
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=fitness_app_prod
DB_USERNAME=fitness_user
DB_PASSWORD=your-secure-password

CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your-redis-password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
```

### Performance Optimization

1. **PHP-FPM Configuration** (`/etc/php/8.2/fpm/pool.d/www.conf`):
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
```

2. **OPcache Configuration** (already in `php.ini`):
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

3. **Redis Configuration**:
```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

## 🔍 Monitoring & Maintenance

### Health Checks

```bash
# API Health
curl https://your-domain.com/api/health

# Database connection
php artisan tinker --execute="DB::connection()->getPdo();"
```

### Log Management

```bash
# Application logs
tail -f storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/fitness-app-access.log
tail -f /var/log/nginx/fitness-app-error.log
```

### Backup Strategy

```bash
# Database backup
pg_dump fitness_app_prod > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz storage/app
```

### Queue Workers (if using queues)

```bash
# Install supervisor
sudo apt install supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

## 🚨 Troubleshooting

### Common Issues

1. **Memory Exhaustion**:
   - Increase PHP memory limit in `php.ini`
   - Optimize queries and caching

2. **Permission Issues**:
   ```bash
   sudo chown -R www-data:www-data /var/www/fitness-app
   sudo chmod -R 775 storage bootstrap/cache
   ```

3. **Cache Issues**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

4. **Database Connection**:
   - Check PostgreSQL is running
   - Verify credentials in `.env`
   - Check firewall settings

## 📊 Performance Monitoring

- Set up application monitoring (Laravel Telescope for development)
- Monitor server resources (htop, iotop)
- Database query monitoring
- Redis memory usage
- Nginx access patterns

## 🔐 Security Checklist

- [ ] SSL certificate installed and working
- [ ] Environment variables secured
- [ ] Database user has minimal required permissions
- [ ] File permissions set correctly
- [ ] Firewall configured
- [ ] Regular security updates scheduled
- [ ] Backup strategy implemented
- [ ] Log rotation configured