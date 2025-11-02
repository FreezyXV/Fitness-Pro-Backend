# 🚀 Deployment Guide - Laravel Fitness App Backend

## Prerequisites

- PHP 8.2+
- PostgreSQL 15+ or MySQL 8.0+
- Composer
- Git
- Docker (for containerized deployment)

## 🎯 Render Deployment (Recommended)

### Quick Deploy to Render

1. **Create a Render Account**: Sign up at [render.com](https://render.com)

2. **Connect Your Repository**:
   - Go to Render Dashboard
   - Click "New +" and select "Blueprint"
   - Connect your GitHub/GitLab repository
   - Render will automatically detect the `render.yaml` file

3. **Configure Environment Variables**:
   The `render.yaml` file includes most configuration, but you may need to add:
   - `APP_KEY`: Generate with `php artisan key:generate --show`
   - Mail configuration (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD)
   - Update `FRONTEND_URL` if your frontend URL differs

4. **Deploy**:
   - Click "Apply" to create all services
   - Render will automatically build and deploy your app
   - Database migrations run automatically via the build script

### Manual Render Deployment

If you prefer manual setup:

1. **Create PostgreSQL Database**:
   - In Render Dashboard, click "New +" → "PostgreSQL"
   - Name: `fitness-pro-db`
   - Select your preferred region and plan
   - Note the connection details

2. **Create Web Service**:
   - Click "New +" → "Web Service"
   - Connect your repository
   - Configure:
     - Name: `fitness-pro-backend`
     - Runtime: Docker
     - Region: Same as database
     - Branch: `main`
     - Dockerfile Path: `./Dockerfile`

3. **Environment Variables**:
   Add these in the Render dashboard:
   ```
   APP_NAME=FitnessProBackend
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=[Generate with: php artisan key:generate --show]
   APP_URL=https://your-app.onrender.com

   DB_CONNECTION=pgsql
   DB_HOST=[From database internal connection string]
   DB_PORT=5432
   DB_DATABASE=[Your database name]
   DB_USERNAME=[Your database user]
   DB_PASSWORD=[Your database password]

   CACHE_STORE=database
   SESSION_DRIVER=database
   QUEUE_CONNECTION=database

   FRONTEND_URL=https://fitness-pro-frontend.vercel.app
   ```

4. **Deploy**: Click "Create Web Service"

### Render Configuration Files

- **[render.yaml](render.yaml:1)** - Blueprint for automatic deployment
- **[render-build.sh](render-build.sh:1)** - Build script (runs migrations)
- **[.env.render](.env.render:1)** - Environment template

## 🐳 Docker Deployment (Alternative)

### Using Docker Directly

```bash
# Build the image
docker build -t fitness-backend:latest .

# Run with environment variables
docker run -d \
  -p 80:80 \
  -e APP_ENV=production \
  -e DB_HOST=your-db-host \
  -e DB_DATABASE=your-db \
  -e DB_USERNAME=your-user \
  -e DB_PASSWORD=your-password \
  --name fitness-backend \
  fitness-backend:latest
```

### Using Docker with External Database

The Dockerfile is configured for production use with:
- Nginx web server
- PHP-FPM 8.2
- Supervisor for process management
- Optimized for performance with OPcache
- PostgreSQL support

## 🔧 Traditional Server Setup

### 1. Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx postgresql redis-server php8.2-fpm php8.2-cli \
  php8.2-mbstring php8.2-xml php8.2-zip php8.2-pgsql php8.2-redis \
  php8.2-gd php8.2-bcmath php8.2-intl

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
cp .env.example .env

# Edit .env with your production values
nano .env

# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data /var/www/fitness-app
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Nginx Configuration

Create nginx configuration (use the files in `docker/` directory as reference):

```bash
# Create nginx site configuration
sudo nano /etc/nginx/sites-available/fitness-app

# Enable site
sudo ln -s /etc/nginx/sites-available/fitness-app /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default

# Test and restart
sudo nginx -t
sudo systemctl restart nginx
```

### 5. SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

## ⚙️ Configuration

### Required Environment Variables

See [.env.render](.env.render:1) for a complete template.

Key variables:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` - Generate with `php artisan key:generate`
- `APP_URL` - Your application URL
- Database credentials (DB_*)
- `FRONTEND_URL` - Your frontend application URL

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

2. **OPcache Configuration** (included in [docker/php.ini](docker/php.ini:1)):
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

3. **Database Sessions**: Already configured for production
   - `SESSION_DRIVER=database`
   - `CACHE_STORE=database`

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

# Nginx logs (if using traditional setup)
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### Render Logs

```bash
# View logs in Render Dashboard
# Or use Render CLI
render logs -s fitness-pro-backend
```

### Database Migrations

```bash
# Run new migrations
php artisan migrate --force

# Rollback (use with caution)
php artisan migrate:rollback --force
```

### Backup Strategy

```bash
# Database backup
pg_dump fitness_app_prod > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz storage/app
```

### Queue Workers

The app uses database queue driver. For better performance in production, consider:

1. **Using Render Background Workers**:
   - Add a background worker service in `render.yaml`
   - Command: `php artisan queue:work --tries=3`

2. **Using Supervisor** (traditional setup):
```bash
sudo apt install supervisor
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

## 🚨 Troubleshooting

### Common Issues

1. **Memory Exhaustion**:
   - Increase PHP memory limit in `docker/php.ini`
   - Optimize queries and caching

2. **Permission Issues** (traditional setup):
   ```bash
   sudo chown -R www-data:www-data /var/www/fitness-app
   sudo chmod -R 775 storage bootstrap/cache
   ```

3. **Cache Issues**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

4. **Database Connection**:
   - Check PostgreSQL is running
   - Verify credentials in environment variables
   - Check firewall/security group settings
   - For Render: Ensure using internal connection string

5. **Render Specific**:
   - Build failures: Check `render-build.sh` script
   - Connection issues: Verify environment variables
   - Database issues: Use internal database URL
   - Port issues: Render automatically assigns ports

## 📊 Performance Monitoring

- Render provides built-in metrics (CPU, Memory, Response time)
- Application monitoring: Laravel Telescope (development only)
- Database query monitoring via Laravel Query Log
- Error tracking: Configure logging in `config/logging.php`

## 🔐 Security Checklist

- [x] SSL certificate (automatic on Render)
- [x] Environment variables secured (Render encrypts env vars)
- [x] Database user has minimal required permissions
- [x] File permissions set correctly (handled by Docker)
- [x] Firewall configured (handled by Render)
- [ ] Regular security updates scheduled
- [ ] Backup strategy implemented
- [ ] Log rotation configured

## 🔄 Continuous Deployment

### Render Auto-Deploy

Render automatically deploys when you push to your main branch:

1. Push changes to GitHub/GitLab
2. Render detects changes
3. Runs build script
4. Deploys new version
5. Zero-downtime deployment

### Manual Deploy

```bash
# Via Render Dashboard
# Click "Manual Deploy" → "Deploy latest commit"

# Or via Render CLI
render deploy -s fitness-pro-backend
```

## 📚 Additional Resources

- [Render Documentation](https://render.com/docs)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- Project README: [README.md](README.md:1)
