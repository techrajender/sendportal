#!/bin/bash

# SendPortal Tracking Fix Script
# This script fixes common issues with email tracking (opens and clicks)

set -e

echo "=========================================="
echo "SendPortal Tracking Fix Script"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Function to find PHP executable
find_php() {
    # First, try command -v (checks PATH)
    if command -v php >/dev/null 2>&1; then
        echo "php"
        return 0
    fi
    
    # Try common PHP version-specific commands
    for php_cmd in php8.3 php8.2 php8.1 php8.0 php7.4; do
        if command -v "$php_cmd" >/dev/null 2>&1; then
            echo "$php_cmd"
            return 0
        fi
    done
    
    # Try common installation paths
    for php_path in \
        /usr/bin/php \
        /usr/local/bin/php \
        /opt/homebrew/bin/php; do
        if [ -f "$php_path" ] && [ -x "$php_path" ]; then
            echo "$php_path"
            return 0
        fi
    done
    
    # Try phpbrew (if installed)
    if [ -d ~/.phpbrew/php ]; then
        for php_path in ~/.phpbrew/php/*/bin/php; do
            if [ -f "$php_path" ] && [ -x "$php_path" ]; then
                echo "$php_path"
                return 0
            fi
        done
    fi
    
    # Try asdf (if installed)
    if [ -d ~/.asdf/installs/php ]; then
        for php_path in ~/.asdf/installs/php/*/bin/php; do
            if [ -f "$php_path" ] && [ -x "$php_path" ]; then
                echo "$php_path"
                return 0
            fi
        done
    fi
    
    return 1
}

# Find PHP executable
PHP_CMD=$(find_php || true)

if [ -z "$PHP_CMD" ]; then
    echo "❌ Error: PHP executable not found!"
    echo ""
    echo "Please install PHP 8.2 or 8.3, or ensure PHP is in your PATH."
    echo ""
    echo "Common solutions:"
    echo "  1. Install PHP: sudo apt-get install php8.2 (Ubuntu/Debian)"
    echo "  2. Install PHP: sudo yum install php82 (RHEL/CentOS)"
    echo "  3. Add PHP to PATH if installed in a custom location"
    echo "  4. Use a PHP version manager (phpbrew, asdf, etc.)"
    echo ""
    exit 1
fi

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    exit 1
fi

echo "Step 1: Checking APP_URL..."
APP_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "")
if [ -z "$APP_URL" ] || [ "$APP_URL" = "http://localhost" ]; then
    echo "❌ APP_URL is not set correctly!"
    echo "   Current: $APP_URL"
    echo "   Please run: scripts/update-app-url.sh https://your-domain.com"
    exit 1
else
    echo "✓ APP_URL: $APP_URL"
fi
echo ""

echo "Step 2: Clearing all caches..."
$PHP_CMD artisan config:clear
$PHP_CMD artisan cache:clear
$PHP_CMD artisan route:clear
$PHP_CMD artisan view:clear
echo "✓ All caches cleared"
echo ""

echo "Step 3: Verifying configuration..."
CACHED_URL=$($PHP_CMD artisan tinker --execute="echo config('app.url');" 2>/dev/null | tail -1)
if [ "$CACHED_URL" = "$APP_URL" ]; then
    echo "✓ Configuration verified"
else
    echo "⚠️  Configuration mismatch detected"
    echo "   .env: $APP_URL"
    echo "   Cached: $CACHED_URL"
fi
echo ""

echo "Step 4: Checking queue workers..."
if pgrep -f "artisan.*(horizon|queue:work)" > /dev/null; then
    echo "✓ Queue workers are running"
else
    echo "⚠️  Queue workers are not running"
    echo "   Run: scripts/start.sh"
fi
echo ""

echo "Step 5: Testing tracking routes..."
if [ ! -z "$APP_URL" ]; then
    # Test if tracking routes are accessible
    TRACK_URL="${APP_URL}/api/track"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "$TRACK_URL" 2>/dev/null || echo "000")
    
    if [ "$HTTP_CODE" = "404" ] || [ "$HTTP_CODE" = "405" ] || [ "$HTTP_CODE" = "400" ]; then
        echo "✓ Tracking routes are accessible (HTTP $HTTP_CODE is expected)"
    elif [ "$HTTP_CODE" = "000" ]; then
        echo "⚠️  Cannot reach tracking URL (check if server/ngrok is running)"
    else
        echo "ℹ️  Tracking URL returned: HTTP $HTTP_CODE"
    fi
else
    echo "⚠️  Cannot test (APP_URL not configured)"
fi
echo ""

echo "=========================================="
echo "Tracking Fix Complete!"
echo "=========================================="
echo ""
echo "Important Notes:"
echo ""
echo "1. ⚠️  CRITICAL: If you sent emails BEFORE updating APP_URL,"
echo "   those emails will NOT track opens/clicks because they"
echo "   contain tracking URLs with the old APP_URL."
echo ""
echo "2. To fix tracking for existing campaigns:"
echo "   - You need to send NEW emails after APP_URL was updated"
echo "   - Old emails cannot be fixed (they have wrong tracking URLs)"
echo ""
echo "3. Verify tracking is working:"
echo "   - Send a NEW test email campaign"
echo "   - Open the email (should register an 'open')"
echo "   - Click a link (should register a 'click')"
echo "   - Check: http://localhost:8000/campaigns/{id}/report/opens"
echo ""
echo "4. If tracking still doesn't work:"
echo "   - Check campaign settings: Ensure 'Track Opens' is enabled"
echo "   - Check Laravel logs: tail -f storage/logs/laravel.log"
echo "   - Check queue logs: tail -f storage/logs/queue-*.log"
echo "   - Verify tracking pixel URLs in sent emails use your APP_URL"
echo ""

