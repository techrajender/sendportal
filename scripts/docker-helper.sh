#!/bin/bash

# Docker Helper Functions for SendPortal Scripts
# This file provides functions to detect Docker and execute commands appropriately

# Check if running inside Docker container
is_docker() {
    [ -f /.dockerenv ] || [ -n "$DOCKER_CONTAINER" ]
}

# Check if docker-compose is available and project is running
is_docker_compose() {
    if command -v docker-compose >/dev/null 2>&1 || command -v docker >/dev/null 2>&1; then
        # Check if coolify.yml exists (for Coolify) or containers are running
        if [ -f coolify.yml ] || docker ps --format "{{.Names}}" 2>/dev/null | grep -q "sendportal"; then
            # Check if containers are running
            if docker ps --format "{{.Names}}" 2>/dev/null | grep -q "sendportal"; then
                return 0
            fi
        fi
    fi
    return 1
}

# Get PHP command (either direct PHP or docker-compose exec)
get_php_cmd() {
    if is_docker_compose; then
        echo "docker-compose exec -T app php"
    elif is_docker; then
        echo "php"
    else
        # Try to find PHP locally
        if command -v php >/dev/null 2>&1; then
            echo "php"
        else
            # Try version-specific commands
            for php_cmd in php8.3 php8.2 php8.1 php8.0; do
                if command -v "$php_cmd" >/dev/null 2>&1; then
                    echo "$php_cmd"
                    return 0
                fi
            done
            echo ""
        fi
    fi
}

# Execute artisan command
artisan() {
    local PHP_CMD=$(get_php_cmd)
    if [ -z "$PHP_CMD" ]; then
        echo "❌ Error: PHP executable not found and Docker is not available!"
        return 1
    fi
    $PHP_CMD artisan "$@"
}

# Execute any PHP command
php_cmd() {
    local PHP_CMD=$(get_php_cmd)
    if [ -z "$PHP_CMD" ]; then
        echo "❌ Error: PHP executable not found and Docker is not available!"
        return 1
    fi
    $PHP_CMD "$@"
}

# Check if Docker setup is recommended
check_docker_setup() {
    if ! is_docker_compose && ! is_docker; then
        if [ -f docker-compose.yml ]; then
            echo "ℹ️  Docker Compose is available. You can use Docker by running:"
            echo "   docker-compose up -d"
            echo ""
        fi
    fi
}

