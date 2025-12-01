#!/bin/bash

# SendPortal After Start Script
# This script runs after the container starts
# Can be used for post-startup tasks

set -e

echo "=========================================="
echo "SendPortal After Start Script"
echo "=========================================="
echo ""

# Get the project directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Check if .env file exists
if [ ! -f .env ]; then
    echo "⚠️  Warning: .env file not found!"
    echo "Some setup tasks may be skipped."
    echo ""
fi

# Run any post-startup tasks here
echo "Running post-startup tasks..."
echo ""

# Example: You can add custom post-startup tasks here
# For example:
# - Start queue workers
# - Start Horizon
# - Verify services
# - Send notifications

echo "✓ After start tasks completed"
echo ""

