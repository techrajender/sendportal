#!/bin/bash

# SendPortal Manual Setup Script
# This script performs manual configuration steps for SendPortal
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Manual Setup Script"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Step 1: Create .env file if it doesn't exist
echo "Step 1: Checking .env file..."
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo "Creating .env file from .env.example..."
        cp .env.example .env
        echo "✓ .env file created"
        echo "⚠️  Please edit .env file with your configuration before continuing."
        echo ""
        read -p "Press Enter after you've configured .env file..."
    else
        echo "❌ Error: .env.example file not found!"
        exit 1
    fi
else
    echo "✓ .env file already exists"
fi
echo ""

# Step 2: Generate application key
echo "Step 2: Generating application key..."
if grep -q "^APP_KEY=$" .env 2>/dev/null || ! grep -q "^APP_KEY=" .env 2>/dev/null; then
    php artisan key:generate
    echo "✓ Application key generated"
else
    echo "✓ Application key already exists"
fi
echo ""

# Step 3: Set Base URL
echo "Step 3: Setting Base URL..."
if ! grep -q "^APP_URL=" .env 2>/dev/null || grep -q "^APP_URL=$" .env 2>/dev/null; then
    read -p "Enter your SendPortal base URL (e.g., https://campaigns.example.com): " APP_URL
    if [ ! -z "$APP_URL" ]; then
        # Use sed to update or add APP_URL
        if grep -q "^APP_URL=" .env; then
            sed -i.bak "s|^APP_URL=.*|APP_URL=$APP_URL|" .env
        else
            echo "APP_URL=$APP_URL" >> .env
        fi
        echo "✓ Base URL set to: $APP_URL"
    fi
else
    echo "✓ Base URL already configured"
fi
echo ""

# Step 4: Database configuration
echo "Step 4: Database Configuration"
echo "Please ensure the following are set in your .env file:"
echo "  - DB_CONNECTION (mysql or pgsql)"
echo "  - DB_HOST"
echo "  - DB_PORT"
echo "  - DB_DATABASE"
echo "  - DB_USERNAME"
echo "  - DB_PASSWORD"
echo ""
read -p "Press Enter after you've configured database settings..."

# Step 5: Test database connection
echo ""
echo "Testing database connection..."
php artisan migrate:status > /dev/null 2>&1 && echo "✓ Database connection successful" || {
    echo "❌ Database connection failed. Please check your database configuration."
    exit 1
}
echo ""

# Step 6: Run migrations
echo "Step 5: Running database migrations..."
read -p "Run database migrations? (y/n): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate
    echo "✓ Migrations completed"
else
    echo "⚠️  Migrations skipped. Run 'php artisan migrate' manually when ready."
fi
echo ""

# Step 7: Publish vendor files
echo "Step 6: Publishing vendor files..."
php artisan vendor:publish --provider="Sendportal\\Base\\SendportalBaseServiceProvider"
echo "✓ Vendor files published"
echo ""

echo "=========================================="
echo "Manual Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Create an admin user account (via web interface or setup wizard)"
echo "2. Configure email service for user management (run scripts/setup-email-config.sh)"
echo "3. Set up queue processing (run scripts/setup-database-queue.sh or scripts/setup-redis-queue.sh)"
echo "4. Set up cron job (run scripts/setup-cron.sh)"
echo ""

