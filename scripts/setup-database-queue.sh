#!/bin/bash

# SendPortal Database Queue Setup Script
# This script sets up SendPortal to use database queue driver
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Database Queue Setup"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please run setup.sh or manual-setup.sh first."
    exit 1
fi

# Update QUEUE_CONNECTION to database
echo "Step 1: Configuring queue connection..."
if grep -q "^QUEUE_CONNECTION=" .env; then
    sed -i.bak "s/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/" .env
else
    echo "QUEUE_CONNECTION=database" >> .env
fi
echo "✓ Queue connection set to 'database'"
echo ""

# Create jobs table migration
echo "Step 2: Creating jobs table migration..."
php artisan queue:table
echo "✓ Jobs table migration created"
echo ""

# Run migrations
echo "Step 3: Running migrations to create jobs table..."
php artisan migrate
echo "✓ Jobs table created"
echo ""

echo "=========================================="
echo "Database Queue Setup Complete!"
echo "=========================================="
echo ""
echo "To process the queue, run the following commands:"
echo ""
echo "  # For message dispatch queue:"
echo "  php artisan queue:work --queue=sendportal-message-dispatch"
echo ""
echo "  # For webhook processing queue:"
echo "  php artisan queue:work --queue=sendportal-webhook-process"
echo ""
echo "Or use a process manager like Supervisor to keep these running."
echo ""

