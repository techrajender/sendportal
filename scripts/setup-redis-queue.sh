#!/bin/bash

# SendPortal Redis Queue Setup Script
# This script sets up SendPortal to use Redis queue driver
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Redis Queue Setup"
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

# Check if Redis is installed
if ! command -v redis-cli &> /dev/null; then
    echo "⚠️  Warning: redis-cli not found. Please ensure Redis is installed."
    echo ""
fi

# Function to check Redis connection
check_redis_connection() {
    local host=$1
    local port=$2
    local password=$3
    
    echo "Testing Redis connection..."
    echo "  Host: $host"
    echo "  Port: $port"
    if [ ! -z "$password" ]; then
        echo "  Password: [configured]"
    fi
    
    # Check if Docker is available and Redis might be in Docker
    local use_docker=false
    local redis_container=""
    
    if command -v docker &> /dev/null; then
        redis_container=$(docker ps --format "{{.Names}}" | grep -i redis | head -1 || echo "")
        # If host is localhost/127.0.0.1 and port is 6379, likely Docker
        if [ ! -z "$redis_container" ] && ([ "$host" = "127.0.0.1" ] || [ "$host" = "localhost" ] || [ -z "$host" ]); then
            if [ "$port" = "6379" ] || [ -z "$port" ]; then
                use_docker=true
                echo "  Detected Docker container: $redis_container"
            fi
        fi
    fi
    
    # Build redis-cli command
    local redis_cmd=""
    if [ "$use_docker" = true ] && [ ! -z "$redis_container" ]; then
        # Use Docker exec
        redis_cmd="docker exec $redis_container redis-cli"
        if [ ! -z "$password" ]; then
            redis_cmd="$redis_cmd -a $password"
        fi
    else
        # Use direct connection
        if ! command -v redis-cli &> /dev/null; then
            # Try Docker as fallback
            if [ ! -z "$redis_container" ]; then
                redis_cmd="docker exec $redis_container redis-cli"
                if [ ! -z "$password" ]; then
                    redis_cmd="$redis_cmd -a $password"
                fi
            else
                echo "❌ redis-cli not found and no Docker container detected"
                return 1
            fi
        else
            redis_cmd="redis-cli -h $host -p $port"
            if [ ! -z "$password" ]; then
                redis_cmd="$redis_cmd -a $password"
            fi
        fi
    fi
    
    # Test connection with ping
    local ping_response
    if ping_response=$($redis_cmd ping 2>&1); then
        # Check if response contains PONG
        if echo "$ping_response" | grep -qi "PONG"; then
            echo "✓ Redis connection successful!"
            echo "  Response: $(echo "$ping_response" | grep -i PONG | head -1)"
            return 0
        elif echo "$ping_response" | grep -qi "NOAUTH"; then
            echo "❌ Redis authentication failed!"
            echo "  Error: Authentication required"
            echo "  Please check your Redis password"
            return 1
        else
            echo "⚠️  Redis responded but with unexpected response: $ping_response"
            return 1
        fi
    else
        local error_msg=$(echo "$ping_response" | head -1)
        if echo "$error_msg" | grep -qi "NOAUTH\|AUTH"; then
            echo "❌ Redis authentication failed!"
            echo "  Error: $error_msg"
            echo "  Please check your Redis password"
        else
            echo "❌ Redis connection failed!"
            echo "  Error: $error_msg"
            echo "  Please check:"
            echo "    - Redis server is running"
            echo "    - Host and port are correct"
            echo "    - Firewall allows connection"
            echo "    - Password is correct (if required)"
        fi
        return 1
    fi
}

# Update QUEUE_CONNECTION to redis
echo "Step 1: Configuring queue connection..."
if grep -q "^QUEUE_CONNECTION=" .env; then
    sed -i.bak "s/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/" .env
else
    echo "QUEUE_CONNECTION=redis" >> .env
fi
echo "✓ Queue connection set to 'redis'"
echo ""

# Configure Redis settings
echo "Step 2: Configuring Redis connection..."
echo "Please provide your Redis configuration:"
echo ""

# Get existing Redis config or set defaults
REDIS_HOST=$(grep "^REDIS_HOST=" .env 2>/dev/null | cut -d '=' -f2 || echo "")
REDIS_PORT=$(grep "^REDIS_PORT=" .env 2>/dev/null | cut -d '=' -f2 || echo "")
REDIS_PASSWORD=$(grep "^REDIS_PASSWORD=" .env 2>/dev/null | cut -d '=' -f2 || echo "")

# REDIS_HOST
if [ -z "$REDIS_HOST" ]; then
    read -p "Redis Host [127.0.0.1]: " REDIS_HOST
    REDIS_HOST=${REDIS_HOST:-127.0.0.1}
    echo "REDIS_HOST=$REDIS_HOST" >> .env
    echo "✓ Redis host set to: $REDIS_HOST"
else
    REDIS_HOST=$(echo "$REDIS_HOST" | tr -d '"' | tr -d "'")
    echo "✓ Redis host already configured: $REDIS_HOST"
fi

# REDIS_PORT
if [ -z "$REDIS_PORT" ]; then
    read -p "Redis Port [6379]: " REDIS_PORT
    REDIS_PORT=${REDIS_PORT:-6379}
    echo "REDIS_PORT=$REDIS_PORT" >> .env
    echo "✓ Redis port set to: $REDIS_PORT"
else
    REDIS_PORT=$(echo "$REDIS_PORT" | tr -d '"' | tr -d "'")
    echo "✓ Redis port already configured: $REDIS_PORT"
fi

# REDIS_PASSWORD
if [ -z "$REDIS_PASSWORD" ] || [ "$REDIS_PASSWORD" = "" ]; then
    echo "⚠️  Note: Redis may require a password for authentication."
    read -p "Redis Password (required if Redis is password-protected): " REDIS_PASSWORD
    if [ ! -z "$REDIS_PASSWORD" ]; then
        echo "REDIS_PASSWORD=$REDIS_PASSWORD" >> .env
        echo "✓ Redis password configured"
    else
        echo "REDIS_PASSWORD=" >> .env
        echo "⚠️  No password set. If Redis requires authentication, connection will fail."
        REDIS_PASSWORD=""
    fi
else
    REDIS_PASSWORD=$(echo "$REDIS_PASSWORD" | tr -d '"' | tr -d "'")
    echo "✓ Redis password already configured"
fi

echo ""

# Test Redis connection
if command -v redis-cli &> /dev/null; then
    echo "Step 3: Testing Redis connection..."
    echo ""
    if check_redis_connection "$REDIS_HOST" "$REDIS_PORT" "$REDIS_PASSWORD"; then
        echo ""
        echo "✓ Redis is working correctly!"
    else
        echo ""
        echo "⚠️  Warning: Redis connection test failed."
        echo "You can still proceed, but make sure Redis is accessible before using the queue."
        read -p "Continue anyway? (y/n): " -n 1 -r
        echo ""
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo "Exiting. Please fix Redis connection and try again."
            exit 1
        fi
    fi
    echo ""
else
    echo "⚠️  Skipping Redis connection test (redis-cli not found)"
    echo ""
fi

echo "=========================================="
echo "Redis Queue Configuration Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Choose one of the following:"
echo "   - Run queue workers manually (scripts/setup-queue-workers.sh)"
echo "   - Set up Laravel Horizon (scripts/setup-horizon.sh)"
echo ""
echo "To test Redis connection, run:"
echo "  cd scripts && ./check-redis.sh"
echo ""
echo "Or test manually:"
if [ ! -z "$REDIS_PASSWORD" ]; then
    echo "  redis-cli -h $REDIS_HOST -p $REDIS_PORT -a 'YOUR_PASSWORD' ping"
else
    echo "  redis-cli -h $REDIS_HOST -p $REDIS_PORT ping"
fi
echo ""

