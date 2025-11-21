#!/bin/bash

# SendPortal Redis Connection Check Script
# This script tests the Redis connection using configuration from .env file

set -e

echo "=========================================="
echo "SendPortal Redis Connection Check"
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

# Check if redis-cli is installed or Docker is available
REDIS_CLI_AVAILABLE=false
DOCKER_AVAILABLE=false
REDIS_CONTAINER=""

if command -v redis-cli &> /dev/null; then
    REDIS_CLI_AVAILABLE=true
fi

if command -v docker &> /dev/null; then
    DOCKER_AVAILABLE=true
    # Try to find Redis container
    REDIS_CONTAINER=$(docker ps --format "{{.Names}}" | grep -i redis | head -1 || echo "")
fi

if [ "$REDIS_CLI_AVAILABLE" = false ] && [ "$DOCKER_AVAILABLE" = false ]; then
    echo "❌ Error: Neither redis-cli nor docker found!"
    echo "Please install Redis client tools or Docker."
    echo ""
    echo "On Ubuntu/Debian: sudo apt-get install redis-tools"
    echo "On macOS: brew install redis"
    echo "On CentOS/RHEL: sudo yum install redis"
    exit 1
fi

# Get Redis configuration from .env
REDIS_HOST=$(grep "^REDIS_HOST=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "127.0.0.1")
REDIS_PORT=$(grep "^REDIS_PORT=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "6379")
REDIS_PASSWORD=$(grep "^REDIS_PASSWORD=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "")

# Use defaults if empty
REDIS_HOST=${REDIS_HOST:-127.0.0.1}
REDIS_PORT=${REDIS_PORT:-6379}

echo "Redis Configuration:"
echo "  Host: $REDIS_HOST"
echo "  Port: $REDIS_PORT"
if [ ! -z "$REDIS_PASSWORD" ]; then
    echo "  Password: [configured]"
else
    echo "  Password: [none]"
    if [ ! -z "$REDIS_CONTAINER" ]; then
        echo "  ⚠️  Note: If Redis requires a password, set REDIS_PASSWORD in .env file"
    fi
fi
echo ""

# Determine connection method
USE_DOCKER=false
if [ ! -z "$REDIS_CONTAINER" ] && [ "$DOCKER_AVAILABLE" = true ]; then
    # Check if host is localhost/127.0.0.1 and port is 6379 (default Docker mapping)
    if [ "$REDIS_HOST" = "127.0.0.1" ] || [ "$REDIS_HOST" = "localhost" ] || [ -z "$REDIS_HOST" ]; then
        if [ "$REDIS_PORT" = "6379" ] || [ -z "$REDIS_PORT" ]; then
            USE_DOCKER=true
            echo "ℹ️  Detected Redis running in Docker container: $REDIS_CONTAINER"
            echo ""
        fi
    fi
fi

# Build redis-cli command
if [ "$USE_DOCKER" = true ]; then
    # Use Docker exec
    REDIS_CMD="docker exec $REDIS_CONTAINER redis-cli"
    if [ ! -z "$REDIS_PASSWORD" ]; then
        REDIS_CMD="$REDIS_CMD -a $REDIS_PASSWORD"
    fi
else
    # Use direct connection
    if [ "$REDIS_CLI_AVAILABLE" = false ]; then
        echo "⚠️  Warning: redis-cli not found. Trying Docker exec method..."
        if [ ! -z "$REDIS_CONTAINER" ]; then
            REDIS_CMD="docker exec $REDIS_CONTAINER redis-cli"
            if [ ! -z "$REDIS_PASSWORD" ]; then
                REDIS_CMD="$REDIS_CMD -a $REDIS_PASSWORD"
            fi
        else
            echo "❌ Error: Cannot connect to Redis. redis-cli not found and no Redis container detected."
            exit 1
        fi
    else
        REDIS_CMD="redis-cli -h $REDIS_HOST -p $REDIS_PORT"
        if [ ! -z "$REDIS_PASSWORD" ]; then
            REDIS_CMD="$REDIS_CMD -a $REDIS_PASSWORD"
        fi
    fi
fi

# Test connection
echo "Testing Redis connection..."
echo ""

# Test 1: Ping
echo "1. Testing PING command..."
PING_RESPONSE=$($REDIS_CMD ping 2>&1)
PING_EXIT_CODE=$?

if [ $PING_EXIT_CODE -eq 0 ]; then
    if echo "$PING_RESPONSE" | grep -qi "PONG"; then
        echo "   ✓ PING successful: $(echo "$PING_RESPONSE" | grep -i PONG | head -1)"
        PING_OK=true
    elif echo "$PING_RESPONSE" | grep -qi "NOAUTH\|AUTH"; then
        echo "   ❌ Authentication failed: $PING_RESPONSE"
        echo "   ⚠️  Redis requires a password. Please set REDIS_PASSWORD in .env file"
        PING_OK=false
    else
        echo "   ❌ PING failed: $PING_RESPONSE"
        PING_OK=false
    fi
else
    if echo "$PING_RESPONSE" | grep -qi "NOAUTH\|AUTH"; then
        echo "   ❌ Authentication failed: $PING_RESPONSE"
        echo "   ⚠️  Redis requires a password. Please set REDIS_PASSWORD in .env file"
    else
        echo "   ❌ Connection failed: $PING_RESPONSE"
    fi
    PING_OK=false
fi
echo ""

# Test 2: Info (if ping succeeded)
if [ "$PING_OK" = true ]; then
    echo "2. Getting Redis server information..."
    if INFO_RESPONSE=$($REDIS_CMD info server 2>&1 | head -5); then
        echo "   ✓ Server info retrieved:"
        echo "$INFO_RESPONSE" | sed 's/^/   /'
    else
        echo "   ⚠️  Could not retrieve server info"
    fi
    echo ""
    
    # Test 3: Check Redis version
    echo "3. Checking Redis version..."
    if VERSION=$($REDIS_CMD info server 2>/dev/null | grep "redis_version" | cut -d ':' -f2 | tr -d '\r'); then
        echo "   ✓ Redis version: $VERSION"
    else
        echo "   ⚠️  Could not determine Redis version"
    fi
    echo ""
    
    # Test 4: Test write/read
    echo "4. Testing write/read operations..."
    TEST_KEY="sendportal_test_$(date +%s)"
    TEST_VALUE="test_value_$(date +%s)"
    
    if $REDIS_CMD set "$TEST_KEY" "$TEST_VALUE" > /dev/null 2>&1; then
        if READ_VALUE=$($REDIS_CMD get "$TEST_KEY" 2>/dev/null); then
            if [ "$READ_VALUE" = "$TEST_VALUE" ]; then
                echo "   ✓ Write/read test successful"
                # Clean up
                $REDIS_CMD del "$TEST_KEY" > /dev/null 2>&1
            else
                echo "   ❌ Read value mismatch: expected '$TEST_VALUE', got '$READ_VALUE'"
            fi
        else
            echo "   ❌ Could not read test key"
        fi
    else
        echo "   ❌ Could not write test key"
    fi
    echo ""
fi

# Final result
echo "=========================================="
if [ "$PING_OK" = true ]; then
    echo "✓ Redis connection is working!"
    echo "=========================================="
    echo ""
    echo "Redis is ready to use with SendPortal."
    exit 0
else
    echo "❌ Redis connection failed!"
    echo "=========================================="
    echo ""
    echo "Troubleshooting steps:"
    if [ "$USE_DOCKER" = true ] || [ ! -z "$REDIS_CONTAINER" ]; then
        echo "1. Check if Redis Docker container is running:"
        echo "   docker ps | grep redis"
        echo "   docker logs $REDIS_CONTAINER"
        echo ""
        if [ -z "$REDIS_PASSWORD" ]; then
            echo "2. ⚠️  Redis may require a password!"
            echo "   Add REDIS_PASSWORD to your .env file:"
            echo "   REDIS_PASSWORD=your_redis_password"
            echo ""
            echo "3. Test connection via Docker:"
            echo "   docker exec $REDIS_CONTAINER redis-cli -a 'YOUR_PASSWORD' ping"
        else
            echo "2. Test connection via Docker:"
            echo "   docker exec $REDIS_CONTAINER redis-cli -a 'YOUR_PASSWORD' ping"
            echo ""
            echo "3. If password is incorrect, update REDIS_PASSWORD in .env file"
        fi
    else
        echo "1. Check if Redis server is running:"
        echo "   - systemctl status redis (Linux)"
        echo "   - brew services list (macOS)"
        echo "   - docker ps | grep redis (if using Docker)"
        echo ""
        if [ -z "$REDIS_PASSWORD" ]; then
            echo "2. ⚠️  Redis may require a password!"
            echo "   Add REDIS_PASSWORD to your .env file:"
            echo "   REDIS_PASSWORD=your_redis_password"
            echo ""
            echo "3. Verify host and port are correct"
            echo ""
            echo "4. Check firewall settings"
            echo ""
            echo "5. Test connection manually:"
            echo "   redis-cli -h $REDIS_HOST -p $REDIS_PORT -a 'YOUR_PASSWORD' ping"
        else
            echo "2. Verify host and port are correct"
            echo ""
            echo "3. Check firewall settings"
            echo ""
            echo "4. Verify password is correct"
            echo ""
            echo "5. Test connection manually:"
            echo "   redis-cli -h $REDIS_HOST -p $REDIS_PORT -a 'YOUR_PASSWORD' ping"
        fi
    fi
    echo ""
    exit 1
fi

