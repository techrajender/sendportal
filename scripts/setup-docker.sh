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

# Check if docker-compose.yml exists
if [ ! -f docker-compose.yml ]; then
    echo "❌ Error: docker-compose.yml not found!"
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

echo "Step 1: Building Docker images..."
docker-compose build

echo ""
echo "Step 2: Starting containers..."
docker-compose up -d

echo ""
echo "Step 3: Waiting for services to be ready..."
sleep 5

echo ""
echo "Step 4: Running migrations..."
docker-compose exec -T app php artisan migrate --force

echo ""
echo "Step 5: Publishing vendor files..."
docker-compose exec -T app php artisan vendor:publish --provider="Sendportal\\Base\\SendportalBaseServiceProvider" --force

echo ""
echo "Step 6: Generating application key (if needed)..."
docker-compose exec -T app php artisan key:generate --force || true

echo ""
echo "Step 7: Optimizing Laravel..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

echo ""
echo "=========================================="
echo "Docker Setup Complete!"
echo "=========================================="
echo ""
echo "Services are running:"
echo "  - Web: http://localhost:8000"
echo "  - App container: sendportal-app"
echo "  - Nginx: sendportal-nginx"
echo "  - Horizon: sendportal-horizon"
echo ""
echo "Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Stop services: docker-compose down"
echo "  - Restart services: docker-compose restart"
echo "  - Run artisan: docker-compose exec app php artisan <command>"
echo ""
echo "Next steps:"
echo "1. Access the web interface and complete the setup wizard"
echo "2. Configure email service in .env file"
echo "3. Set up cron job (if not using Docker scheduler)"
echo ""

