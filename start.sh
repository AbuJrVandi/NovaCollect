#!/bin/sh

# Cache configuration, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground to keep container running
echo "Starting Nginx..."
nginx -g "daemon off;"
