#!/bin/bash

# SendPortal Laravel Horizon Setup Script
# This script sets up Laravel Horizon for managing Redis queues
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Laravel Horizon Setup"
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

# Check if queue connection is set to redis
if ! grep -q "^QUEUE_CONNECTION=redis" .env; then
    echo "⚠️  Warning: QUEUE_CONNECTION is not set to 'redis'"
    echo "Horizon requires Redis. Would you like to set it now? (y/n)"
    read -p "> " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        if grep -q "^QUEUE_CONNECTION=" .env; then
            sed -i.bak "s/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/" .env
        else
            echo "QUEUE_CONNECTION=redis" >> .env
        fi
        echo "✓ Queue connection set to 'redis'"
    else
        echo "❌ Horizon requires Redis queue connection. Exiting."
        exit 1
    fi
    echo ""
fi

# Publish Horizon assets
echo "Step 1: Publishing Horizon assets..."
php artisan horizon:publish
echo "✓ Horizon assets published"
echo ""

echo "=========================================="
echo "Horizon Setup Complete!"
echo "=========================================="
echo ""
echo "To start Horizon, run:"
echo "  php artisan horizon"
echo ""
echo "For production, consider using Supervisor to keep Horizon running:"
echo "  See: https://laravel.com/docs/horizon#supervisor-configuration"
echo ""
echo "Horizon will be accessible at: http://your-domain/horizon"
echo ""
echo "Queue Configuration:"
echo "  - sendportal-message-dispatch: min 2, max 20 processes"
echo "  - sendportal-webhook-process: min 2, max 10 processes"
echo ""
echo "You can adjust these in config/horizon.php if needed."
echo ""

