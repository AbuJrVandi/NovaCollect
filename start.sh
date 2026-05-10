#!/bin/sh

# Ensure persistent directory exists
mkdir -p /var/www/html/database/data

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/html/database/data/database.sqlite ]; then
    touch /var/www/html/database/data/database.sqlite
    echo "Created new database.sqlite in persistent storage."
fi

# Set proper permissions for the database
chown -R www-data:www-data /var/www/html/database/data
chmod 775 /var/www/html/database/data
chmod 664 /var/www/html/database/data/database.sqlite

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
