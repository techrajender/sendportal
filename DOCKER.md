# SendPortal Docker Setup

This guide explains how to run SendPortal using Docker and Docker Compose.

## Prerequisites

- Docker 20.10+
- Docker Compose 2.0+

## Quick Start

1. **Copy environment file:**
   ```bash
   cp .env.docker.example .env
   ```

2. **Update environment variables in `.env`:**
   - Set `APP_URL` to your domain or ngrok URL
   - Configure database credentials
   - Set mail configuration
   - Generate `APP_KEY` (will be auto-generated on first run)

3. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

4. **Run migrations:**
   ```bash
   docker-compose exec app php artisan migrate
   ```

5. **Access the application:**
   - Web: http://localhost:8000
   - Database: localhost:5432
   - Redis: localhost:6379

## Environment Variables

All configuration is done through environment variables in `.env` file or docker-compose.yml:

### Application Variables
- `APP_NAME` - Application name (default: SendPortal)
- `APP_ENV` - Environment (local, production)
- `APP_KEY` - Laravel encryption key (auto-generated if empty)
- `APP_DEBUG` - Debug mode (true/false)
- `APP_URL` - Application URL (required for email tracking)

### Database Variables
- `DB_CONNECTION` - Database driver (pgsql, mysql)
- `DB_HOST` - Database host (use `host.docker.internal` for existing containers on host, or the container name if on same network)
- `DB_PORT` - Database port (default: 5432)
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password

### Redis Variables
- `REDIS_HOST` - Redis host (use `host.docker.internal` for existing containers on host, or the container name if on same network)
- `REDIS_PASSWORD` - Redis password (optional)
- `REDIS_PORT` - Redis port (default: 6379)

### Queue Variables
- `QUEUE_CONNECTION` - Queue driver (redis, database, sync)

### Mail Variables
- `MAIL_MAILER` - Mail driver (smtp, ses, mailgun, postmark)
- `MAIL_HOST` - SMTP host
- `MAIL_PORT` - SMTP port
- `MAIL_USERNAME` - SMTP username
- `MAIL_PASSWORD` - SMTP password
- `MAIL_ENCRYPTION` - Encryption (tls, ssl)
- `MAIL_FROM_ADDRESS` - From email address
- `MAIL_FROM_NAME` - From name

### SendPortal Variables
- `SENDPORTAL_REGISTER` - Enable user registration (true/false)
- `SENDPORTAL_PASSWORD_RESET` - Enable password reset (true/false)

### Docker Variables
- `RUN_MIGRATIONS` - Auto-run migrations on startup (true/false)
- `APP_PORT` - Web server port (default: 8000)

## Docker Services

### app
Main PHP-FPM application container running Laravel.

### nginx
Nginx web server serving the application.

**Note:** This setup uses existing PostgreSQL and Redis containers. Make sure your existing postgres and redis services are accessible from the Docker containers. By default, it uses `host.docker.internal` to connect to services on the host machine.

### horizon
Laravel Horizon for queue management (optional, only if using Redis queue).

## Common Commands

### Start services
```bash
docker-compose up -d
```

### Stop services
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f horizon
```

### Run artisan commands
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
```

### Run migrations
```bash
docker-compose exec app php artisan migrate
```

### Publish vendor files
```bash
docker-compose exec app php artisan vendor:publish --provider="Sendportal\\Base\\SendportalBaseServiceProvider"
```

### Access container shell
```bash
docker-compose exec app sh
```

### Rebuild containers
```bash
docker-compose up -d --build
```

## Production Deployment

For production:

1. Set `APP_ENV=production`
2. Set `APP_DEBUG=false`
3. Use strong `APP_KEY` (auto-generated)
4. Configure proper `APP_URL`
5. Use secure database passwords
6. Enable Redis password
7. Configure proper mail settings
8. Set `RUN_MIGRATIONS=false` after initial setup

## Troubleshooting

### Permission Issues
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Clear All Caches
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Database Connection Issues
- Ensure `DB_HOST=host.docker.internal` (for existing containers on host) or use the actual container name/IP
- On Linux, if `host.docker.internal` doesn't work, use your host's IP address or add `--add-host=host.docker.internal:host-gateway` to docker run
- Check database credentials match your existing postgres container
- Verify postgres container is running: `docker ps | grep postgres`

### Redis Connection Issues
- Ensure `REDIS_HOST=host.docker.internal` (for existing containers on host) or use the actual container name/IP
- On Linux, if `host.docker.internal` doesn't work, use your host's IP address
- Check Redis password if set in your existing redis container
- Verify redis container is running: `docker ps | grep redis`

