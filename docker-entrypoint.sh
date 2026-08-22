#!/bin/sh
set -e

# Ensure database directory exists and has permissions
mkdir -p /var/www/html/database
chmod -R 777 /var/www/html/database

# Copy credentials.example.php if credentials.php does not exist
if [ ! -f /var/www/html/config/credentials.php ]; then
    echo "Creating default credentials.php from template..."
    cp /var/www/html/config/credentials.example.php /var/www/html/config/credentials.php
fi

# Initialize database if events.db does not exist
if [ ! -f /var/www/html/database/events.db ]; then
    echo "Initializing database..."
    php /var/www/html/database/init.php
fi

echo "Starting Realtime Map Event Grid on http://0.0.0.0:8081..."
exec php -S 0.0.0.0:8081 -t /var/www/html/public
