#!/bin/bash

# SendPortal Setup Script
# This script runs the automated setup command for SendPortal
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Setup Script"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Check if .env file exists
if [ ! -f .env ]; then
    echo "⚠️  .env file not found. Creating from .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "✓ .env file created"
    else
        echo "❌ Error: .env.example file not found!"
        exit 1
    fi
    echo ""
fi

# Run the setup command
echo "Running SendPortal setup command..."
echo "This will guide you through the setup process."
echo ""

php artisan sp:install

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Configure your email service in .env file"
echo "2. Set up queue processing (run scripts/setup-database-queue.sh or scripts/setup-redis-queue.sh)"
echo "3. Set up cron job (run scripts/setup-cron.sh)"
echo ""

