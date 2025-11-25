#!/bin/bash

# SendPortal Stop Script
# This script stops SendPortal services (Horizon, queue workers, etc.)

set -e

echo "=========================================="
echo "SendPortal Stop Script"
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

# Check queue connection
QUEUE_CONNECTION=$(grep "^QUEUE_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 || echo "sync")

echo "Queue Connection: $QUEUE_CONNECTION"
echo ""

# Function to stop Horizon
stop_horizon() {
    if pgrep -f "artisan horizon" > /dev/null; then
        echo "Stopping Laravel Horizon..."
        php artisan horizon:terminate 2>/dev/null || {
            echo "⚠️  Could not terminate gracefully, killing processes..."
            pkill -f "artisan horizon" || true
        }
        sleep 2
        
        # Check if still running
        if pgrep -f "artisan horizon" > /dev/null; then
            echo "⚠️  Force killing Horizon processes..."
            pkill -9 -f "artisan horizon" || true
            sleep 1
        fi
        
        if ! pgrep -f "artisan horizon" > /dev/null; then
            echo "✓ Horizon stopped"
        else
            echo "❌ Failed to stop Horizon"
        fi
    else
        echo "ℹ️  Horizon is not running"
    fi
}

# Function to stop queue workers
stop_queue_workers() {
    if pgrep -f "artisan queue:work" > /dev/null; then
        echo "Stopping queue workers..."
        
        # Get PIDs
        PIDS=$(pgrep -f "artisan queue:work")
        
        # Try graceful shutdown first
        for PID in $PIDS; do
            echo "  Stopping queue worker (PID: $PID)..."
            kill -TERM $PID 2>/dev/null || true
        done
        
        # Wait a bit
        sleep 3
        
        # Force kill if still running
        REMAINING=$(pgrep -f "artisan queue:work" || true)
        if [ ! -z "$REMAINING" ]; then
            echo "  Force stopping remaining workers..."
            for PID in $REMAINING; do
                kill -9 $PID 2>/dev/null || true
            done
            sleep 1
        fi
        
        if ! pgrep -f "artisan queue:work" > /dev/null; then
            echo "✓ Queue workers stopped"
        else
            echo "⚠️  Some queue workers may still be running"
        fi
    else
        echo "ℹ️  Queue workers are not running"
    fi
}

# Function to stop web server (artisan serve or PHP built-in server)
stop_web_server() {
    echo "Checking for web server on port 8000..."
    
    # Check for processes on port 8000
    PORT_PID=$(lsof -ti:8000 2>/dev/null || echo "")
    
    # Check for artisan serve
    ARTISAN_SERVE_PID=$(pgrep -f "artisan serve" || echo "")
    
    # Check for PHP built-in server
    PHP_SERVER_PID=$(pgrep -f "php.*-S.*8000" || echo "")
    
    if [ ! -z "$PORT_PID" ] || [ ! -z "$ARTISAN_SERVE_PID" ] || [ ! -z "$PHP_SERVER_PID" ]; then
        echo "Stopping web server..."
        
        # Stop artisan serve
        if [ ! -z "$ARTISAN_SERVE_PID" ]; then
            echo "  Stopping artisan serve (PID: $ARTISAN_SERVE_PID)..."
            kill -TERM $ARTISAN_SERVE_PID 2>/dev/null || true
            sleep 2
            if pgrep -f "artisan serve" > /dev/null; then
                kill -9 $ARTISAN_SERVE_PID 2>/dev/null || true
            fi
        fi
        
        # Stop PHP built-in server
        if [ ! -z "$PHP_SERVER_PID" ]; then
            echo "  Stopping PHP built-in server (PID: $PHP_SERVER_PID)..."
            kill -TERM $PHP_SERVER_PID 2>/dev/null || true
            sleep 2
            if pgrep -f "php.*-S.*8000" > /dev/null; then
                kill -9 $PHP_SERVER_PID 2>/dev/null || true
            fi
        fi
        
        # Stop any process on port 8000
        if [ ! -z "$PORT_PID" ]; then
            echo "  Stopping process on port 8000 (PID: $PORT_PID)..."
            kill -TERM $PORT_PID 2>/dev/null || true
            sleep 2
            if lsof -ti:8000 > /dev/null 2>&1; then
                kill -9 $PORT_PID 2>/dev/null || true
            fi
        fi
        
        sleep 1
        
        # Verify
        if ! lsof -ti:8000 > /dev/null 2>&1 && ! pgrep -f "artisan serve" > /dev/null && ! pgrep -f "php.*-S.*8000" > /dev/null; then
            echo "✓ Web server stopped"
        else
            echo "⚠️  Some web server processes may still be running"
        fi
    else
        echo "ℹ️  Web server is not running on port 8000"
    fi
}

# Stop web server first
stop_web_server
echo ""

# Stop services based on queue connection
if [ "$QUEUE_CONNECTION" = "redis" ]; then
    echo "Stopping services for Redis queue..."
    echo ""
    
    # Check if Horizon config exists
    if [ -f config/horizon.php ]; then
        stop_horizon
    else
        stop_queue_workers
    fi
    
elif [ "$QUEUE_CONNECTION" = "database" ]; then
    echo "Stopping services for Database queue..."
    echo ""
    stop_queue_workers
    
else
    echo "ℹ️  Queue connection is set to: $QUEUE_CONNECTION"
    echo "   No queue workers to stop for sync queues."
    echo ""
fi

# Also check for any remaining artisan processes (just in case)
REMAINING_PROCESSES=$(pgrep -f "artisan.*(horizon|queue:work)" || true)
if [ ! -z "$REMAINING_PROCESSES" ]; then
    echo ""
    echo "⚠️  Found remaining processes:"
    ps aux | grep -E "artisan.*(horizon|queue:work)" | grep -v grep || true
    echo ""
    read -p "Force kill all remaining processes? (y/n): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        pkill -9 -f "artisan.*(horizon|queue:work)" || true
        echo "✓ All processes killed"
    fi
fi

echo ""
echo "=========================================="
echo "SendPortal Services Stopped!"
echo "=========================================="
echo ""
echo "To verify, check running processes:"
echo "  ps aux | grep 'artisan' | grep -v grep"
echo "  lsof -ti:8000  # Check if port 8000 is still in use"
echo ""

