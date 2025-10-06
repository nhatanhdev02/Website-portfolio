#!/bin/bash

# Production Deployment Script for Nhật Anh Dev Admin Backend
# This script handles the deployment and configuration of the Laravel backend in production

set -e

echo "🚀 Starting production deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root for security reasons"
   exit 1
fi

# Check if .env.production exists
if [ ! -f ".env.production" ]; then
    print_error ".env.production file not found. Please create it first."
    exit 1
fi

print_status "Copying production environment file..."
cp .env.production .env

# Install/Update Composer dependencies
print_status "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Generate application key if not set
if grep -q "APP_KEY=$" .env; then
    print_status "Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache configuration
print_status "Optimizing application..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

# Run database migrations
print_status "Running database migrations..."
php artisan migrate --force

# Seed database if needed
read -p "Do you want to seed the database? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_status "Seeding database..."
    php artisan db:seed --force
fi

# Create storage symlink
print_status "Creating storage symlink..."
php artisan storage:link

# Set proper permissions
print_status "Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chmod -R 775 storage/framework/cache
chmod -R 775 storage/framework/sessions
chmod -R 775 storage/framework/views

# Clear application cache
print_status "Clearing application cache..."
php artisan cache:clear
php artisan queue:clear

# Warm up caches
print_status "Warming up caches..."
php artisan admin:cache:warm

# Install SSL certificate (if using Let's Encrypt)
read -p "Do you want to install SSL certificate with Certbot? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Enter your domain name: " domain
    print_status "Installing SSL certificate for $domain..."
    sudo certbot --nginx -d $domain
fi

# Configure Nginx (if exists)
if command -v nginx &> /dev/null; then
    print_status "Nginx detected. Please ensure your configuration includes:"
    echo "
    server {
        listen 443 ssl http2;
        server_name your-domain.com;
        root /path/to/your/project/public;

        ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

        # Security headers
        add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\" always;
        add_header X-Content-Type-Options nosniff always;
        add_header X-Frame-Options DENY always;
        add_header X-XSS-Protection \"1; mode=block\" always;

        # PHP-FPM configuration
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
            include fastcgi_params;
        }

        # Laravel routes
        location / {
            try_files \$uri \$uri/ /index.php?\$query_string;
        }
    }
    "
fi

# Setup supervisor for queue workers (if exists)
if command -v supervisorctl &> /dev/null; then
    print_status "Setting up Supervisor for queue workers..."
    cat > /tmp/laravel-worker.conf << EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $(pwd)/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$(whoami)
numprocs=2
redirect_stderr=true
stdout_logfile=$(pwd)/storage/logs/worker.log
stopwaitsecs=3600
EOF

    sudo cp /tmp/laravel-worker.conf /etc/supervisor/conf.d/
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start laravel-worker:*
fi

# Setup cron jobs
print_status "Setting up cron jobs..."
(crontab -l 2>/dev/null; echo "* * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Test application
print_status "Testing application..."
php artisan admin:health:check

# Setup monitoring (if New Relic is configured)
if [ ! -z "$NEW_RELIC_LICENSE_KEY" ]; then
    print_status "New Relic detected. Deployment will be recorded."
    php artisan newrelic:deployment
fi

# Final security check
print_status "Running security checks..."
if [ -f ".env" ]; then
    if grep -q "APP_DEBUG=true" .env; then
        print_error "APP_DEBUG is set to true in production! This is a security risk."
        exit 1
    fi
fi

# Restart services
print_status "Restarting services..."
if command -v nginx &> /dev/null; then
    sudo systemctl reload nginx
fi

if command -v php-fpm8.2 &> /dev/null; then
    sudo systemctl reload php8.2-fpm
fi

print_status "✅ Production deployment completed successfully!"
print_warning "Please verify the following:"
echo "1. SSL certificate is properly installed"
echo "2. Database connection is working"
echo "3. File uploads are working with S3"
echo "4. Cache is functioning properly"
echo "5. Queue workers are running"
echo "6. Monitoring is active"

print_status "🎉 Your Laravel Admin Backend is now live in production!"
