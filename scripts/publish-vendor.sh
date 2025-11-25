#!/bin/bash

# SendPortal Publish Vendor Files Script
# This script publishes SendPortal vendor files
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup
# Supports both local PHP and Docker setups

set -e

echo "=========================================="
echo "SendPortal Publish Vendor Files"
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

# Check if using Docker
if is_docker_compose; then
    echo "🐳 Detected Docker Compose setup"
    echo "Using Docker container..."
    echo ""
    PHP_CMD="docker-compose exec -T app php"
elif is_docker; then
    echo "🐳 Running inside Docker container"
    echo ""
    PHP_CMD="php"
else
    PHP_CMD=$(get_php_cmd)
    if [ -z "$PHP_CMD" ]; then
        PHP_CMD=$(find_php || true)
    fi
    
    if [ -z "$PHP_CMD" ]; then
        echo "❌ Error: PHP executable not found!"
        echo ""
        check_docker_setup
        echo "Please install PHP 8.2 or 8.3, or ensure PHP is in your PATH."
        echo ""
        echo "Common solutions:"
        echo "  1. Install PHP: sudo apt-get install php8.2 (Ubuntu/Debian)"
        echo "  2. Install PHP: sudo yum install php82 (RHEL/CentOS)"
        echo "  3. Use Docker: docker-compose up -d"
        echo "  4. Add PHP to PATH if installed in a custom location"
        echo ""
        exit 1
    fi
fi

echo "Publishing SendPortal vendor files..."
echo "This will publish config, views, languages, and assets."
echo ""

$PHP_CMD artisan vendor:publish --provider="Sendportal\\Base\\SendportalBaseServiceProvider"

echo ""
echo "=========================================="
echo "Vendor Files Published!"
echo "=========================================="
echo ""
echo "The following have been published:"
echo "  - Config files"
echo "  - View files"
echo "  - Language files"
echo "  - Asset files"
echo ""
echo "You can now customize these files as needed."
echo ""

