#!/bin/bash

# SendPortal APP_URL Update Script
# This script updates the APP_URL in .env file
# APP_URL is critical for email tracking (opens and clicks) to work

set -e

echo "=========================================="
echo "SendPortal APP_URL Configuration"
echo "=========================================="
echo ""
echo "APP_URL is used for:"
echo "  - Email tracking (opens and clicks)"
echo "  - Unsubscribe links"
echo "  - User registration emails"
echo "  - Password reset links"
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

# Get current APP_URL
CURRENT_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "")

if [ ! -z "$CURRENT_URL" ] && [ "$CURRENT_URL" != "http://localhost" ]; then
    echo "Current APP_URL: $CURRENT_URL"
    echo ""
    read -p "Do you want to update it? (y/n): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Keeping current APP_URL: $CURRENT_URL"
        exit 0
    fi
fi

# Get new URL
if [ -z "$1" ]; then
    echo "Enter your SendPortal application URL:"
    echo "  Examples:"
    echo "    - https://2e8f4edb9432.ngrok-free.app (ngrok)"
    echo "    - https://sendportal.yourdomain.com (production)"
    echo "    - http://localhost:8000 (local development)"
    echo ""
    read -p "APP_URL: " NEW_URL
else
    NEW_URL="$1"
fi

# Validate URL format
if [[ ! $NEW_URL =~ ^https?:// ]]; then
    echo "❌ Error: URL must start with http:// or https://"
    exit 1
fi

# Remove trailing slash
NEW_URL=$(echo "$NEW_URL" | sed 's|/$||')

# Update .env file
if grep -q "^APP_URL=" .env; then
    # Backup .env file
    cp .env .env.bak.$(date +%Y%m%d_%H%M%S)
    
    # Update APP_URL
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        sed -i.bak "s|^APP_URL=.*|APP_URL=$NEW_URL|" .env
    else
        # Linux
        sed -i "s|^APP_URL=.*|APP_URL=$NEW_URL|" .env
    fi
    echo "✓ APP_URL updated to: $NEW_URL"
else
    echo "APP_URL=$NEW_URL" >> .env
    echo "✓ APP_URL set to: $NEW_URL"
fi

echo ""
echo "=========================================="
echo "APP_URL Configuration Complete!"
echo "=========================================="
echo ""
echo "Important Notes:"
echo "1. Clear Laravel config cache:"
echo "   php artisan config:clear"
echo ""
echo "2. For email tracking to work:"
echo "   - The URL must be publicly accessible"
echo "   - Tracking pixels and click links need to be reachable"
echo "   - If using ngrok, make sure it's running and pointing to your app"
echo ""
echo "3. After updating APP_URL, you may need to:"
echo "   - Restart your queue workers"
echo "   - Clear application cache"
echo ""

