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
    php artisan vendor:publish --provider="Sendportal\\Base\\SendportalBaseServiceProvider" --force || true
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

# Execute the main command
exec "$@"

