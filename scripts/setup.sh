#!/bin/bash

# SendPortal Setup Script
# This script runs the automated setup command for SendPortal
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup
# Supports both local PHP and Docker setups

set -e

echo "=========================================="
echo "SendPortal Setup Script"
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
    echo "PHP 8.3 is required for SendPortal."
    echo ""
    
    # Check if we're on Ubuntu/Debian
    if [ -f /etc/debian_version ] || [ -f /etc/os-release ] && grep -q "Ubuntu\|Debian" /etc/os-release 2>/dev/null; then
        echo "Detected Ubuntu/Debian system."
        echo ""
        read -p "Would you like to install PHP 8.3 automatically? (y/n): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            echo ""
            echo "Installing PHP 8.3 and required extensions..."
            echo "This may require sudo privileges."
            echo ""
            
            # Add PHP repository if needed (for Ubuntu)
            if [ -f /etc/os-release ] && grep -q "Ubuntu" /etc/os-release 2>/dev/null; then
                echo "Adding PHP repository..."
                sudo apt-get update -qq || true
                sudo apt-get install -y -qq software-properties-common >/dev/null 2>&1 || true
                sudo add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 || true
            fi
            
            # Install PHP and extensions
            echo "Installing PHP 8.3 and extensions..."
            set +e  # Temporarily disable exit on error for installation
            sudo apt-get update -qq
            INSTALL_RESULT=$?
            if [ $INSTALL_RESULT -eq 0 ]; then
                sudo apt-get install -y php8.3 php8.3-cli php8.3-common php8.3-pgsql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath
                INSTALL_RESULT=$?
            fi
            set -e  # Re-enable exit on error
            
            if [ $INSTALL_RESULT -eq 0 ]; then
                echo "✓ PHP 8.3 installed successfully!"
                echo ""
                # Try to find PHP again
                PHP_CMD=$(find_php || true)
                if [ -z "$PHP_CMD" ]; then
                    echo "⚠️  PHP installed but not found in PATH. Please restart your terminal or run:"
                    echo "   export PATH=\$PATH:/usr/bin"
                    echo ""
                    exit 1
                fi
            else
                echo "❌ Failed to install PHP. Please install manually:"
                echo "   sudo apt-get update"
                echo "   sudo apt-get install -y php8.3 php8.3-cli php8.3-common php8.3-pgsql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath"
                echo ""
                exit 1
            fi
        else
            echo "Please install PHP 8.3 manually:"
            echo ""
            echo "  sudo apt-get update"
            echo "  sudo apt-get install -y php8.3 php8.3-cli php8.3-common php8.3-pgsql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath"
            echo ""
            echo "Or for other systems:"
            echo "  - RHEL/CentOS: sudo yum install php82"
            echo "  - Add PHP to PATH if installed in a custom location"
            echo "  - Use a PHP version manager (phpbrew, asdf, etc.)"
            echo ""
            exit 1
        fi
    else
        echo "Please install PHP 8.2 or 8.3, or ensure PHP is in your PATH."
        echo ""
        echo "Common solutions:"
        echo "  1. Ubuntu/Debian: sudo apt-get install php8.3 php8.3-cli php8.3-common php8.3-pgsql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath"
        echo "  2. RHEL/CentOS: sudo yum install php82"
        echo "  3. Add PHP to PATH if installed in a custom location"
        echo "  4. Use a PHP version manager (phpbrew, asdf, etc.)"
        echo ""
        exit 1
    fi
fi

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

# Check if using Docker
if is_docker_compose; then
    echo "🐳 Detected Docker Compose setup"
    echo "Using Docker container for setup..."
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
fi

# Run the setup command
echo "Running SendPortal setup command..."
echo "This will guide you through the setup process."
echo ""

$PHP_CMD artisan sp:install

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

