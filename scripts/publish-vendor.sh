#!/bin/bash

# SendPortal Publish Vendor Files Script
# This script publishes SendPortal vendor files
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Publish Vendor Files"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

echo "Publishing SendPortal vendor files..."
echo "This will publish config, views, languages, and assets."
echo ""

php artisan vendor:publish --provider="Sendportal\\Base\\SendportalBaseServiceProvider"

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

