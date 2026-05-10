#!/bin/sh

# Discover packages (skipped during build due to no .env)
php artisan package:discover --ansi 2>/dev/null || true

# Cache configuration, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Seed roles and permissions (idempotent - safe to run on every deploy)
php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder --force

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground to keep container running
echo "Starting Nginx..."
nginx -g "daemon off;"
