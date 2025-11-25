# SendPortal Setup Scripts

This directory contains shell scripts to help you set up SendPortal based on the [official documentation](https://sendportal.io/docs/v1/getting-started/configuration-and-setup).

## Available Scripts

### Main Setup Scripts

1. **`setup.sh`** - Automated setup using the setup command
   - Runs `php artisan sp:install` to guide you through setup
   - Creates `.env` file if it doesn't exist
   - **Supports Docker**: Automatically detects and uses Docker if available
   - Recommended for first-time setup

2. **`setup-docker.sh`** - Docker Compose setup (NEW)
   - Complete Docker setup with all steps
   - Builds and starts containers
   - Runs migrations automatically
   - Publishes vendor files
   - Optimizes Laravel
   - Recommended for Docker deployments

3. **`manual-setup.sh`** - Manual configuration steps
   - Creates `.env` file from `.env.example`
   - Generates application key
   - Configures base URL
   - Sets up database connection
   - Runs migrations
   - Publishes vendor files

### Queue Configuration Scripts

3. **`setup-database-queue.sh`** - Sets up database queue driver
   - Configures `QUEUE_CONNECTION=database`
   - Creates jobs table migration
   - Runs migrations
   - Provides instructions for running queue workers

4. **`setup-redis-queue.sh`** - Sets up Redis queue driver
   - Configures `QUEUE_CONNECTION=redis`
   - Prompts for Redis connection details
   - Tests Redis connection automatically
   - Recommended for medium to large mailing lists

5. **`check-redis.sh`** - Tests Redis connection
   - Reads Redis config from `.env` file
   - Performs comprehensive connection tests
   - Useful for troubleshooting Redis issues

6. **`setup-horizon.sh`** - Sets up Laravel Horizon
   - Publishes Horizon assets
   - Requires Redis queue connection
   - Provides instructions for starting Horizon

7. **`setup-queue-workers.sh`** - Sets up queue workers without Horizon
   - Creates Supervisor configuration file
   - Provides manual queue worker commands
   - For use with database or Redis queues

### Additional Configuration Scripts

8. **`setup-cron.sh`** - Sets up required cron job
   - Adds cron entry for Laravel scheduler
   - Required for SendPortal background tasks
   - Runs every minute

9. **`publish-vendor.sh`** - Publishes vendor files
   - Publishes config, views, languages, and assets
   - Allows customization of SendPortal files

10. **`setup-email-config.sh`** - Configures email for user management
   - Sets up email service for registration, invitations, password resets
   - Supports SMTP, Sendmail, SES, Mailgun, and Postmark
   - Configures registration and password reset settings

11. **`update-app-url.sh`** - Updates APP_URL configuration
   - Updates the application URL in `.env` file
   - Critical for email tracking (opens and clicks) to work
   - Required for unsubscribe links and email notifications
   - Can be run with URL as argument: `./update-app-url.sh https://your-domain.com`

12. **`start.sh`** - Starts SendPortal services
   - Automatically detects queue configuration (Redis/Database)
   - Starts Horizon if Redis is configured
   - Starts queue workers if needed
   - Runs services in background with logging

13. **`stop.sh`** - Stops SendPortal services
   - Gracefully stops Horizon or queue workers
   - Detects running processes and stops them
   - Handles force termination if needed
   - Cleans up all SendPortal processes

## Usage

### Quick Start

**Option 1: Using Docker (Recommended)**
```bash
cd scripts
chmod +x *.sh
./setup-docker.sh
```

**Option 2: Local PHP Setup**
```bash
cd scripts
chmod +x *.sh
./setup.sh
```

### Step-by-Step Setup

1. **Initial Setup:**
   ```bash
   cd scripts
   ./setup.sh
   # OR
   ./manual-setup.sh
   ```

2. **Configure Queue:**
   ```bash
   cd scripts
   # For small to medium lists (database)
   ./setup-database-queue.sh
   
   # For medium to large lists (Redis)
   ./setup-redis-queue.sh
   
   # Test Redis connection (optional)
   ./check-redis.sh
   ```

3. **Set Up Queue Processing:**
   ```bash
   cd scripts
   # Option A: Use Horizon (recommended for Redis)
   ./setup-horizon.sh
   
   # Option B: Use queue workers
   ./setup-queue-workers.sh
   ```

4. **Configure Email for User Management:**
   ```bash
   cd scripts
   ./setup-email-config.sh
   ```

5. **Set Up Cron Job:**
   ```bash
   cd scripts
   ./setup-cron.sh
   ```

6. **Publish Vendor Files (if needed):**
   ```bash
   cd scripts
   ./publish-vendor.sh
   ```

7. **Update APP_URL (for email tracking):**
   ```bash
   cd scripts
   ./update-app-url.sh https://your-domain.com
   # Or run interactively:
   ./update-app-url.sh
   ```
   **Important:** APP_URL must be set correctly for email opens and clicks tracking to work!

8. **Start/Stop Services:**
   ```bash
   cd scripts
   # Start all services (Horizon or queue workers)
   ./start.sh
   
   # Stop all services
   ./stop.sh
   ```

9. **Troubleshoot Email Tracking:**
   ```bash
   cd scripts
   # Run diagnostic to check why opens/clicks aren't working
   ./check-tracking.sh
   
   # Fix common tracking issues (clears caches, verifies config)
   ./fix-tracking.sh
   ```

## Prerequisites

### For Local Setup:
- PHP 8.2 or 8.3
- Composer
- Database (MySQL or PostgreSQL)
- (Optional) Redis for queue processing
- (Optional) Supervisor for process management

### For Docker Setup:
- Docker 20.10+
- Docker Compose 2.0+
- Existing PostgreSQL and Redis containers (or use the ones in docker-compose.yml)

## Notes

- All scripts create backup files (`.bak`) when modifying `.env`
- Scripts check for existing configurations before making changes
- Some scripts require interactive input
- Make scripts executable with `chmod +x script-name.sh`

## Documentation

For more details, refer to the [SendPortal Configuration & Setup Documentation](https://sendportal.io/docs/v1/getting-started/configuration-and-setup).

