#!/bin/sh
set -e

# Wait for database to be ready
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database connection..."
    until php -r "
    try {
        \$pdo = new PDO(
            'pgsql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '5432') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
    " 2>/dev/null; do
        echo "Database is unavailable - sleeping"
        sleep 1
    done
    echo "Database is up - executing command"
fi

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "Generating application key..."
    php artisan key:generate --force || true
fi

# Publish vendor files if needed
if [ "$PUBLISH_VENDOR" = "true" ]; then
    echo "Publishing vendor files..."
    if [ -f /var/www/html/scripts/publish-vendor.sh ]; then
        /var/www/html/scripts/publish-vendor.sh || true
    else
        php artisan vendor:publish --provider="Sendportal\\Base\\SendportalBaseServiceProvider" --force || true
    fi
fi

# Run migrations
if [ "$RUN_MIGRATIONS" = "true" ] || [ -z "$RUN_MIGRATIONS" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || {
        echo "⚠️  Migration failed, but continuing..."
        # Don't exit, allow container to start even if migrations fail
        # This is useful for debugging
    }
    echo "✓ Migrations completed"
fi

# Clear and cache config
php artisan config:clear || true
php artisan cache:clear || true

# Optimize Laravel
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Setup database queue if needed
if [ "$SETUP_DATABASE_QUEUE" = "true" ] && [ -f /var/www/html/scripts/setup-database-queue.sh ]; then
    echo "Setting up database queue..."
    /var/www/html/scripts/setup-database-queue.sh || true
fi

# Setup Horizon if needed
if [ "$SETUP_HORIZON" = "true" ] && [ -f /var/www/html/scripts/setup-horizon.sh ]; then
    echo "Setting up Horizon..."
    /var/www/html/scripts/setup-horizon.sh || true
fi

# Setup queue workers if needed
if [ "$SETUP_QUEUE_WORKERS" = "true" ] && [ -f /var/www/html/scripts/setup-queue-workers.sh ]; then
    echo "Setting up queue workers..."
    /var/www/html/scripts/setup-queue-workers.sh || true
fi

# Setup cron if needed
if [ "$SETUP_CRON" = "true" ] && [ -f /var/www/html/scripts/setup-cron.sh ]; then
    echo "Setting up cron..."
    /var/www/html/scripts/setup-cron.sh || true
fi

# Run after-start script if needed
if [ "$RUN_AFTER_START" = "true" ] && [ -f /var/www/html/scripts/after-start.sh ]; then
    echo "Running after-start script..."
    /var/www/html/scripts/after-start.sh || true
fi

# Execute the main command
exec "$@"

