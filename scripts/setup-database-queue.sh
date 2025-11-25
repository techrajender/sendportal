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

# Source docker helper functions
if [ -f "$SCRIPT_DIR/docker-helper.sh" ]; then
    source "$SCRIPT_DIR/docker-helper.sh"
fi

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

# Check if using Docker
if is_docker_compose; then
    PHP_CMD="docker-compose exec -T app php"
    echo "🐳 Using Docker Compose"
elif is_docker; then
    PHP_CMD="php"
else
    PHP_CMD=$(get_php_cmd)
    if [ -z "$PHP_CMD" ]; then
        PHP_CMD=$(find_php || true)
    fi
fi

# Create jobs table migration
echo "Step 2: Creating jobs table migration..."
$PHP_CMD artisan queue:table
echo "✓ Jobs table migration created"
echo ""

# Run migrations
echo "Step 3: Running migrations to create jobs table..."
$PHP_CMD artisan migrate
echo "✓ Jobs table created"
echo ""

echo "=========================================="
echo "Database Queue Setup Complete!"
echo "=========================================="
echo ""
echo "To process the queue, run the following commands:"
echo ""
echo "  # For message dispatch queue:"
echo "  $PHP_CMD artisan queue:work --queue=sendportal-message-dispatch"
echo ""
echo "  # For webhook processing queue:"
echo "  $PHP_CMD artisan queue:work --queue=sendportal-webhook-process"
echo ""
echo "Or use a process manager like Supervisor to keep these running."
echo ""

