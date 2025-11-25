#!/bin/bash

# SendPortal Start Script
# This script starts SendPortal services (Horizon, queue workers, etc.)

set -e

echo "=========================================="
echo "SendPortal Start Script"
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

# Check queue connection
QUEUE_CONNECTION=$(grep "^QUEUE_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 || echo "sync")

# Function to check if Horizon is already running
check_horizon_running() {
    if pgrep -f "artisan horizon" > /dev/null; then
        return 0
    else
        return 1
    fi
}

# Function to check if queue workers are running
check_queue_workers_running() {
    if pgrep -f "artisan queue:work" > /dev/null; then
        return 0
    else
        return 1
    fi
}

# Function to check if web server is running
check_web_server_running() {
    if lsof -ti:8000 > /dev/null 2>&1 || pgrep -f "artisan serve" > /dev/null || pgrep -f "php.*-S.*8000" > /dev/null; then
        return 0
    else
        return 1
    fi
}

# Function to start web server
start_web_server() {
    if check_web_server_running; then
        echo "⚠️  Web server is already running on port 8000"
        echo "   Use 'php artisan serve' to start manually if needed"
    else
        echo "Starting Laravel development server..."
        nohup php artisan serve > storage/logs/server.log 2>&1 &
        SERVER_PID=$!
        sleep 2
        
        # Verify it started
        if check_web_server_running; then
            echo "✓ Web server started (PID: $SERVER_PID)"
            echo "  URL: http://localhost:8000"
            echo "  Logs: storage/logs/server.log"
        else
            echo "⚠️  Web server may have failed to start"
            echo "  Check logs: storage/logs/server.log"
        fi
    fi
}

echo "Queue Connection: $QUEUE_CONNECTION"
echo ""

# Start web server first
echo "Step 1: Starting web server..."
start_web_server
echo ""

# Start services based on queue connection
echo "Step 2: Starting queue services..."
if [ "$QUEUE_CONNECTION" = "redis" ]; then
    echo "Starting services for Redis queue..."
    echo ""
    
    # Check if Horizon config exists
    if [ -f config/horizon.php ]; then
        if check_horizon_running; then
            echo "⚠️  Horizon is already running"
            echo "   Use 'php artisan horizon:status' to check status"
        else
            echo "Starting Laravel Horizon..."
            nohup php artisan horizon > storage/logs/horizon.log 2>&1 &
            HORIZON_PID=$!
            echo "✓ Horizon started (PID: $HORIZON_PID)"
            echo "  Logs: storage/logs/horizon.log"
        fi
    else
        echo "⚠️  Horizon config not found. Starting queue workers instead..."
        echo ""
        
        if check_queue_workers_running; then
            echo "⚠️  Queue workers are already running"
        else
            echo "Starting queue workers..."
            
            # Start message dispatch queue worker
            nohup php artisan queue:work --queue=sendportal-message-dispatch > storage/logs/queue-message.log 2>&1 &
            MSG_PID=$!
            echo "✓ Message dispatch worker started (PID: $MSG_PID)"
            
            # Start webhook process queue worker
            nohup php artisan queue:work --queue=sendportal-webhook-process > storage/logs/queue-webhook.log 2>&1 &
            WEBHOOK_PID=$!
            echo "✓ Webhook process worker started (PID: $WEBHOOK_PID)"
        fi
    fi
    
elif [ "$QUEUE_CONNECTION" = "database" ]; then
    echo "Starting services for Database queue..."
    echo ""
    
    if check_queue_workers_running; then
        echo "⚠️  Queue workers are already running"
    else
        echo "Starting queue workers..."
        
        # Start message dispatch queue worker
        nohup php artisan queue:work --queue=sendportal-message-dispatch > storage/logs/queue-message.log 2>&1 &
        MSG_PID=$!
        echo "✓ Message dispatch worker started (PID: $MSG_PID)"
        
        # Start webhook process queue worker
        nohup php artisan queue:work --queue=sendportal-webhook-process > storage/logs/queue-webhook.log 2>&1 &
        WEBHOOK_PID=$!
        echo "✓ Webhook process worker started (PID: $WEBHOOK_PID)"
    fi
    
else
    echo "⚠️  Queue connection is set to: $QUEUE_CONNECTION"
    echo "   No queue workers needed for sync queues."
    echo ""
fi

echo ""
echo "=========================================="
echo "SendPortal Services Started!"
echo "=========================================="
echo ""
echo "Web Server:"
echo "  - URL: http://localhost:8000"
echo "  - Logs: tail -f storage/logs/server.log"
echo ""
echo "Queue Services:"
echo "  - Horizon: php artisan horizon:status"
echo "  - Queue workers: ps aux | grep 'queue:work'"
echo ""
echo "To view logs:"
echo "  - Web server: tail -f storage/logs/server.log"
echo "  - Horizon: tail -f storage/logs/horizon.log"
echo "  - Queue workers: tail -f storage/logs/queue-*.log"
echo ""

