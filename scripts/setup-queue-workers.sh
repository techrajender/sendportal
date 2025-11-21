#!/bin/bash

# SendPortal Queue Workers Setup Script
# This script helps set up queue workers without Horizon
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Queue Workers Setup"
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
QUEUE_CONNECTION=$(grep "^QUEUE_CONNECTION=" .env | cut -d '=' -f2 || echo "sync")

if [ "$QUEUE_CONNECTION" = "sync" ]; then
    echo "⚠️  Warning: Queue connection is set to 'sync'"
    echo "Queue workers are not needed for sync queues."
    echo "For production, consider using 'database' or 'redis'."
    exit 0
fi

echo "Queue connection: $QUEUE_CONNECTION"
echo ""

# Create supervisor config file
echo "Step 1: Creating Supervisor configuration..."
echo ""
echo "Would you like to create a Supervisor configuration file? (y/n)"
read -p "> " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    SUPERVISOR_DIR="/etc/supervisor/conf.d"
    CONFIG_FILE="sendportal-queue-workers.conf"
    
    # Get absolute path
    ABS_PROJECT_DIR="$PROJECT_DIR"
    
    # Create supervisor config
    cat > "$CONFIG_FILE" << EOF
[program:sendportal-message-dispatch]
process_name=%(program_name)s_%(process_num)02d
command=php $ABS_PROJECT_DIR/artisan queue:work --queue=sendportal-message-dispatch --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$ABS_PROJECT_DIR/storage/logs/queue-worker.log
stopwaitsecs=3600

[program:sendportal-webhook-process]
process_name=%(program_name)s_%(process_num)02d
command=php $ABS_PROJECT_DIR/artisan queue:work --queue=sendportal-webhook-process --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$ABS_PROJECT_DIR/storage/logs/webhook-worker.log
stopwaitsecs=3600
EOF

    echo "✓ Supervisor configuration file created: $CONFIG_FILE"
    echo ""
    echo "To use this configuration:"
    echo "  1. Copy it to $SUPERVISOR_DIR/"
    echo "  2. Update the 'user' field if needed (currently set to www-data)"
    echo "  3. Run: sudo supervisorctl reread"
    echo "  4. Run: sudo supervisorctl update"
    echo "  5. Run: sudo supervisorctl start sendportal-*:*"
    echo ""
else
    echo "Skipping Supervisor configuration."
    echo ""
fi

echo "=========================================="
echo "Queue Workers Information"
echo "=========================================="
echo ""
echo "To run queue workers manually, use these commands:"
echo ""
echo "  # Terminal 1 - Message dispatch queue:"
echo "  php artisan queue:work --queue=sendportal-message-dispatch"
echo ""
echo "  # Terminal 2 - Webhook processing queue:"
echo "  php artisan queue:work --queue=sendportal-webhook-process"
echo ""
echo "For production, use Supervisor or a similar process manager"
echo "to keep these workers running automatically."
echo ""

