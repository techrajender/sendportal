#!/bin/bash

# SendPortal Email Tracking Diagnostic Script
# This script helps diagnose why email opens and clicks tracking might not be working

set -e

echo "=========================================="
echo "SendPortal Email Tracking Diagnostic"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    exit 1
fi

echo "Checking configuration..."
echo ""

# 1. Check APP_URL
echo "1. APP_URL Configuration:"
APP_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "")
if [ -z "$APP_URL" ] || [ "$APP_URL" = "http://localhost" ]; then
    echo "   ❌ APP_URL is not set or is set to localhost"
    echo "   → This will prevent tracking from working!"
    echo "   → Run: scripts/update-app-url.sh https://your-domain.com"
else
    echo "   ✓ APP_URL: $APP_URL"
    
    # Test if URL is accessible
    echo "   Testing URL accessibility..."
    if curl -s -o /dev/null -w "%{http_code}" --max-time 5 "$APP_URL" | grep -q "200\|301\|302"; then
        echo "   ✓ URL is accessible"
    else
        echo "   ⚠️  URL may not be accessible (check if ngrok/server is running)"
    fi
fi
echo ""

# 2. Check config cache
echo "2. Laravel Config Cache:"
if php artisan config:show app.url > /dev/null 2>&1; then
    CACHED_URL=$(php artisan config:show app.url 2>/dev/null | grep -i "app.url" | tail -1 | awk '{print $2}' || echo "")
    if [ ! -z "$CACHED_URL" ]; then
        echo "   Current cached URL: $CACHED_URL"
        if [ "$CACHED_URL" != "$APP_URL" ]; then
            echo "   ⚠️  Cached URL differs from .env file!"
            echo "   → Run: php artisan config:clear"
        else
            echo "   ✓ Config cache matches .env"
        fi
    fi
else
    echo "   ℹ️  Config cache not found (this is OK)"
fi
echo ""

# 3. Check queue configuration
echo "3. Queue Configuration:"
QUEUE_CONNECTION=$(grep "^QUEUE_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 || echo "sync")
echo "   Queue Connection: $QUEUE_CONNECTION"

if [ "$QUEUE_CONNECTION" = "sync" ]; then
    echo "   ⚠️  Using sync queue (not recommended for production)"
else
    echo "   ✓ Using $QUEUE_CONNECTION queue"
    
    # Check if queue workers are running
    if pgrep -f "artisan.*(horizon|queue:work)" > /dev/null; then
        echo "   ✓ Queue workers are running"
    else
        echo "   ⚠️  Queue workers are not running"
        echo "   → Run: scripts/start.sh"
    fi
fi
echo ""

# 4. Check if tracking routes might be accessible
echo "4. Tracking Routes Test:"
if [ ! -z "$APP_URL" ] && [ "$APP_URL" != "http://localhost" ]; then
    # Test a common tracking endpoint pattern
    TRACKING_TEST_URL="${APP_URL}/api/track"
    echo "   Testing: $TRACKING_TEST_URL"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "$TRACKING_TEST_URL" 2>/dev/null || echo "000")
    
    if [ "$HTTP_CODE" = "404" ] || [ "$HTTP_CODE" = "405" ]; then
        echo "   ✓ Route exists (404/405 is expected for GET without params)"
    elif [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "301" ] || [ "$HTTP_CODE" = "302" ]; then
        echo "   ✓ Route is accessible"
    elif [ "$HTTP_CODE" = "000" ]; then
        echo "   ❌ Cannot reach URL (check if server/ngrok is running)"
    else
        echo "   ℹ️  HTTP $HTTP_CODE (route may require specific parameters)"
    fi
else
    echo "   ⚠️  Cannot test (APP_URL not configured)"
fi
echo ""

# 5. Check ngrok (if using)
echo "5. ngrok Status:"
if echo "$APP_URL" | grep -q "ngrok"; then
    if pgrep -f "ngrok" > /dev/null; then
        echo "   ✓ ngrok process is running"
        echo "   → Make sure ngrok is pointing to your SendPortal app"
        echo "   → Check: ngrok http 8000 (or your app port)"
    else
        echo "   ⚠️  ngrok process not found"
        echo "   → Start ngrok: ngrok http 8000"
    fi
else
    echo "   ℹ️  Not using ngrok"
fi
echo ""

# 6. Common issues checklist
echo "6. Common Issues Checklist:"
echo ""
echo "   □ APP_URL is set correctly in .env"
echo "   □ Config cache cleared (php artisan config:clear)"
echo "   □ Queue workers are running (scripts/start.sh)"
echo "   □ URL is publicly accessible"
echo "   □ If using ngrok, it's running and pointing to your app"
echo "   □ Tracking is enabled in campaign settings"
echo "   □ Emails were sent after APP_URL was updated"
echo ""

# 7. Recommendations
echo "=========================================="
echo "Recommendations:"
echo "=========================================="
echo ""

if [ -z "$APP_URL" ] || [ "$APP_URL" = "http://localhost" ]; then
    echo "1. Set APP_URL:"
    echo "   scripts/update-app-url.sh https://your-domain.com"
    echo ""
fi

echo "2. Clear config cache:"
echo "   php artisan config:clear"
echo ""

if [ "$QUEUE_CONNECTION" != "sync" ]; then
    if ! pgrep -f "artisan.*(horizon|queue:work)" > /dev/null; then
        echo "3. Start queue workers:"
        echo "   scripts/start.sh"
        echo ""
    fi
fi

echo "4. Verify tracking:"
echo "   - Send a test email campaign"
echo "   - Open the email (this should register an 'open')"
echo "   - Click a link in the email (this should register a 'click')"
echo "   - Check the campaign statistics in SendPortal"
echo ""

echo "5. If still not working:"
echo "   - Check Laravel logs: storage/logs/laravel.log"
echo "   - Check queue logs: storage/logs/queue-*.log"
echo "   - Verify the tracking URLs in sent emails are using your APP_URL"
echo ""

