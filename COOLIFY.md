# Deploying SendPortal to Coolify

This guide explains how to deploy SendPortal to Coolify with an existing PostgreSQL database and automatic migrations.

## Prerequisites

- Coolify instance running
- Existing PostgreSQL database (can be created in Coolify or external)
- (Optional) Redis instance for queues and caching

## Deployment Steps

### 1. Create New Resource in Coolify

1. Go to your Coolify dashboard
2. Click "New Resource" → "Application"
3. Select your server
4. Choose "Docker Compose" or "Dockerfile" deployment

### 2. Connect Your Repository

1. Connect your Git repository (GitHub, GitLab, etc.)
2. Select the branch you want to deploy
3. Set the build pack to "Dockerfile" or use the `coolify.yml` file

### 3. Configure Environment Variables

Add the following environment variables in Coolify:

#### Required Variables

```bash
# Application
APP_NAME=SendPortal
APP_ENV=production
APP_KEY=                    # Will be auto-generated if empty
APP_DEBUG=false
APP_URL=https://your-domain.com  # IMPORTANT: Your actual domain

# Database (Existing PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host     # e.g., postgres-container-name or external IP
DB_PORT=5432
DB_DATABASE=sendportal
DB_USERNAME=postgres
DB_PASSWORD=your-password

# Redis (if using existing Redis)
REDIS_HOST=your-redis-host    # e.g., redis-container-name or external IP
REDIS_PASSWORD=                # If your Redis has a password
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis         # or 'database' if not using Redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME=SendPortal

# SendPortal Settings
SENDPORTAL_REGISTER=false
SENDPORTAL_PASSWORD_RESET=true

# Migration Settings (Important!)
RUN_MIGRATIONS=true           # Automatically run migrations on deploy
PUBLISH_VENDOR=true           # Publish vendor files on deploy
```

### 4. Database Connection Options

#### Option A: Use Existing PostgreSQL Container in Coolify

If you have a PostgreSQL database already running in Coolify:

1. Find the PostgreSQL service name in Coolify
2. Use the service name as `DB_HOST` (e.g., `postgres-service-name`)
3. Or use the internal network IP if containers are on the same network

#### Option B: Use External PostgreSQL

If using an external PostgreSQL database:

1. Use the external hostname/IP as `DB_HOST`
2. Ensure the database is accessible from your Coolify server
3. Make sure firewall rules allow connections

#### Option C: Create New PostgreSQL in Coolify

1. Create a new PostgreSQL database resource in Coolify
2. Note the connection details
3. Use those details in your environment variables

### 5. Build and Deploy Settings

#### If using Dockerfile:

- **Build Command**: (leave empty, Dockerfile handles it)
- **Dockerfile Path**: `Dockerfile`
- **Docker Context**: `.`

#### If using Docker Compose:

- Use the `coolify.yml` file provided
- Coolify will use this file for deployment configuration

### 6. Port Configuration

- **Port**: Set to `80` or your desired port
- The Dockerfile exposes port `9000` (PHP-FPM)
- Coolify will handle the reverse proxy

### 7. Health Check

The health check is configured in `coolify.yml`. You can also add:

```yaml
healthcheck:
  test: ["CMD", "php", "artisan", "schedule:run", "--help"]
  interval: 30s
  timeout: 10s
  retries: 3
```

### 8. Deploy

1. Click "Deploy" in Coolify
2. Wait for the build to complete
3. Migrations will run automatically on first deploy (via `docker-entrypoint.sh`)
4. Check the logs to ensure migrations completed successfully

## Post-Deployment Steps

### 1. Verify Migrations

Check the deployment logs to ensure migrations ran:

```bash
# In Coolify logs, you should see:
# "Running database migrations..."
# "✓ Migrations completed"
```

If migrations didn't run, you can manually run them:

```bash
# Via Coolify terminal or SSH
docker exec -it <container-name> php artisan migrate
```

### 2. Publish Vendor Files (if needed)

```bash
docker exec -it <container-name> php artisan vendor:publish --provider="Sendportal\\Base\\SendportalBaseServiceProvider"
```

### 3. Set Up Queue Workers

If using queues, you'll need to run queue workers. Options:

**Option A: Use Laravel Horizon (Recommended for Redis)**

1. Horizon is already configured in the Dockerfile
2. You may need to add a separate service for Horizon
3. Or run it as a background process

**Option B: Use Queue Workers**

Add a separate service in Coolify or run manually:

```bash
docker exec -d <container-name> php artisan queue:work --queue=sendportal-message-dispatch
docker exec -d <container-name> php artisan queue:work --queue=sendportal-webhook-process
```

### 4. Set Up Cron Jobs

Coolify can handle cron jobs, or you can use Laravel's scheduler:

1. Add a cron job in Coolify that runs every minute:
   ```bash
   * * * * * docker exec <container-name> php artisan schedule:run
   ```

2. Or use Coolify's built-in cron functionality

### 5. Configure Domain

1. Add your domain in Coolify
2. Configure SSL (Let's Encrypt)
3. Update `APP_URL` environment variable to match your domain

## Troubleshooting

### Migrations Not Running

1. Check `RUN_MIGRATIONS=true` is set in environment variables
2. Check deployment logs for errors
3. Manually run: `docker exec <container> php artisan migrate`

### Database Connection Issues

1. Verify `DB_HOST` is correct (use service name for Coolify containers)
2. Check database credentials
3. Ensure database is accessible from the container
4. Check network connectivity

### Redis Connection Issues

1. Verify `REDIS_HOST` is correct
2. Check Redis password if set
3. Ensure Redis is accessible

### Permission Issues

The Dockerfile sets proper permissions, but if you encounter issues:

```bash
docker exec <container> chown -R www-data:www-data storage bootstrap/cache
docker exec <container> chmod -R 775 storage bootstrap/cache
```

## Environment Variables Reference

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `APP_URL` | Yes | - | Your application URL (critical for email tracking) |
| `APP_KEY` | Auto | - | Laravel encryption key (auto-generated if empty) |
| `DB_HOST` | Yes | - | PostgreSQL hostname |
| `DB_DATABASE` | Yes | - | Database name |
| `DB_USERNAME` | Yes | - | Database username |
| `DB_PASSWORD` | Yes | - | Database password |
| `RUN_MIGRATIONS` | Yes | `true` | Run migrations on deploy |
| `PUBLISH_VENDOR` | No | `true` | Publish vendor files |
| `REDIS_HOST` | If using Redis | - | Redis hostname |
| `QUEUE_CONNECTION` | No | `redis` | Queue driver (redis/database/sync) |

## Additional Resources

- [Coolify Documentation](https://coolify.io/docs)
- [SendPortal Documentation](https://sendportal.io/docs)
- [Laravel Deployment Guide](https://laravel.com/docs/deployment)

