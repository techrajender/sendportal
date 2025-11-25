#!/bin/bash

# SendPortal Docker Setup Script
# This script sets up SendPortal using Docker Compose

set -e

echo "=========================================="
echo "SendPortal Docker Setup"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Check if docker-compose is available
if ! command -v docker-compose >/dev/null 2>&1 && ! docker compose version >/dev/null 2>&1; then
    echo "❌ Error: docker-compose is not installed!"
    echo "Please install Docker Compose to use this script."
    exit 1
fi

# Check if Dockerfile exists
if [ ! -f Dockerfile ]; then
    echo "❌ Error: Dockerfile not found!"
    echo "Please ensure you're in the SendPortal project directory."
    exit 1
fi

# Check if .env file exists
if [ ! -f .env ]; then
    echo "⚠️  .env file not found. Creating from .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "✓ .env file created"
        echo ""
        echo "⚠️  Please update .env file with your configuration before continuing."
        echo "   Important settings:"
        echo "   - APP_URL (required for email tracking)"
        echo "   - DB_HOST (use 'host.docker.internal' for existing postgres)"
        echo "   - DB_DATABASE, DB_USERNAME, DB_PASSWORD"
        echo "   - REDIS_HOST (use 'host.docker.internal' for existing redis)"
        echo ""
        read -p "Press Enter after updating .env file..."
    else
        echo "❌ Error: .env.example file not found!"
        exit 1
    fi
fi

echo "⚠️  Note: This script is for local Docker development."
echo "For Coolify deployment, use the Coolify dashboard instead."
echo ""
echo "Step 1: Building Docker image..."
docker build -t sendportal:latest .

echo ""
echo "Step 2: Running container..."
docker run -d --name sendportal-app \
    -e RUN_MIGRATIONS=true \
    -e PUBLISH_VENDOR=true \
    -p 8000:9000 \
    sendportal:latest

echo ""
echo "Step 3: Waiting for services to be ready..."
sleep 5

echo ""
echo "Step 4: Checking migrations status..."
docker exec sendportal-app php artisan migrate:status || true

echo ""
echo "Step 5: Viewing logs..."
echo "Container is running. Check logs with: docker logs sendportal-app"

echo ""
echo "=========================================="
echo "Docker Setup Complete!"
echo "=========================================="
echo ""
echo "Container is running:"
echo "  - App container: sendportal-app"
echo "  - Access via reverse proxy on port 8000"
echo ""
echo "Useful commands:"
echo "  - View logs: docker logs -f sendportal-app"
echo "  - Stop container: docker stop sendportal-app"
echo "  - Remove container: docker rm sendportal-app"
echo "  - Run artisan: docker exec sendportal-app php artisan <command>"
echo ""
echo "For production deployment, use Coolify (see COOLIFY.md)"
echo ""
echo "Next steps:"
echo "1. Access the web interface and complete the setup wizard"
echo "2. Configure email service in .env file"
echo "3. Set up cron job (if not using Docker scheduler)"
echo ""

