#!/bin/bash

# SendPortal Cron Job Setup Script
# This script helps set up the required cron job for SendPortal
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Cron Job Setup"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Get absolute path
ABS_PROJECT_DIR="$PROJECT_DIR"

# Create cron entry
CRON_ENTRY="* * * * * cd $ABS_PROJECT_DIR && php artisan schedule:run >> /dev/null 2>&1"

echo "SendPortal requires a cron job to run every minute."
echo ""
echo "Cron entry:"
echo "  $CRON_ENTRY"
echo ""

# Check if cron entry already exists
if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
    echo "✓ Cron job already exists in crontab"
    echo ""
    echo "Current crontab entries:"
    crontab -l 2>/dev/null | grep "artisan schedule:run" || true
    echo ""
    read -p "Would you like to add a new entry anyway? (y/n): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Skipping cron job addition."
        exit 0
    fi
fi

# Add cron job
echo ""
echo "Would you like to add this cron job to your crontab? (y/n)"
read -p "> " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Backup existing crontab
    crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null || true
    
    # Add new entry
    (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
    
    echo "✓ Cron job added successfully!"
    echo ""
    echo "Current crontab:"
    crontab -l
    echo ""
else
    echo ""
    echo "To add the cron job manually, run:"
    echo "  crontab -e"
    echo ""
    echo "Then add this line:"
    echo "  $CRON_ENTRY"
    echo ""
fi

echo "=========================================="
echo "Cron Setup Complete!"
echo "=========================================="
echo ""
echo "The cron job will run Laravel's scheduler every minute."
echo "This is required for SendPortal's background tasks."
echo ""

