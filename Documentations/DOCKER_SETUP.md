# Docker Setup Guide

## Quick Start

1. **Update .env file for Docker:**
   ```bash
   ./docker-env-setup.sh
   ```
   
   Or manually update your `.env` file with these settings:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=saas
   DB_USERNAME=laravel
   DB_PASSWORD=laravel
   ```

2. **Start Docker containers:**
   ```bash
   docker-compose up -d
   ```

3. **Run migrations:**
   ```bash
   docker-compose exec app php artisan migrate
   ```

4. **Access phpMyAdmin:**
   - URL: http://localhost:8080
   - Username: `root`
   - Password: `root`

## Services

- **App**: Laravel application (PHP)
- **Nginx**: Web server (port 80)
- **MySQL**: Database server (port 3306)
- **phpMyAdmin**: Database management (port 8080)

## Database Credentials

### Root User (for phpMyAdmin)
- Username: `root`
- Password: `root`

### Laravel User (for application)
- Username: `laravel`
- Password: `laravel`
- Database: `saas`

## Useful Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Access app container
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan migrate:fresh
docker-compose exec app php artisan db:seed

# Access MySQL directly
docker-compose exec mysql mysql -u root -proot saas
```

## Troubleshooting

### Database connection issues
- Make sure containers are running: `docker-compose ps`
- Check MySQL is healthy: `docker-compose logs mysql`
- Verify .env has `DB_HOST=mysql` (not 127.0.0.1)

### phpMyAdmin not loading
- Check it's running: `docker-compose ps phpmyadmin`
- Verify port 8080 is not in use
- Check logs: `docker-compose logs phpmyadmin`
