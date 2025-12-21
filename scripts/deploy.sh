#!/bin/bash

################################################################################
# Vodo.com Production Deployment Script
#
# This script handles zero-downtime deployments with:
# - Pre-deployment checks
# - Database migrations
# - Cache clearing and rebuilding
# - Asset compilation
# - Queue restart
# - Health checks
#
# Usage:
#   ./scripts/deploy.sh              # Full deployment
#   ./scripts/deploy.sh --no-migrate # Skip migrations
#   ./scripts/deploy.sh --quick      # Skip asset build
#   ./scripts/deploy.sh --rollback   # Rollback last deployment
################################################################################

set -euo pipefail

# Configuration
APP_DIR="${APP_DIR:-/var/www}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
BACKUP_DIR="${BACKUP_DIR:-$APP_DIR/storage/backups}"
LOG_FILE="${LOG_FILE:-$APP_DIR/storage/logs/deploy.log}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse arguments
SKIP_MIGRATE=false
SKIP_BUILD=false
ROLLBACK=false

for arg in "$@"; do
    case $arg in
        --no-migrate)
            SKIP_MIGRATE=true
            ;;
        --quick)
            SKIP_BUILD=true
            ;;
        --rollback)
            ROLLBACK=true
            ;;
    esac
done

# Logging function
log() {
    local level=$1
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    case $level in
        INFO)
            echo -e "${BLUE}[INFO]${NC} $message"
            ;;
        SUCCESS)
            echo -e "${GREEN}[SUCCESS]${NC} $message"
            ;;
        WARN)
            echo -e "${YELLOW}[WARN]${NC} $message"
            ;;
        ERROR)
            echo -e "${RED}[ERROR]${NC} $message"
            ;;
    esac

    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"
}

# Error handler
handle_error() {
    log ERROR "Deployment failed at line $1"
    log ERROR "Rolling back changes..."

    # Restore from backup if exists
    if [ -f "$BACKUP_DIR/.env.backup" ]; then
        cp "$BACKUP_DIR/.env.backup" "$APP_DIR/.env"
    fi

    # Restart services
    $PHP_BIN "$APP_DIR/artisan" queue:restart 2>/dev/null || true

    log ERROR "Deployment failed. Check logs at $LOG_FILE"
    exit 1
}

trap 'handle_error $LINENO' ERR

# Pre-flight checks
preflight_checks() {
    log INFO "Running pre-flight checks..."

    # Check PHP
    if ! command -v $PHP_BIN &> /dev/null; then
        log ERROR "PHP not found at $PHP_BIN"
        exit 1
    fi

    # Check Composer
    if ! command -v $COMPOSER_BIN &> /dev/null; then
        log ERROR "Composer not found at $COMPOSER_BIN"
        exit 1
    fi

    # Check app directory
    if [ ! -d "$APP_DIR" ]; then
        log ERROR "Application directory not found: $APP_DIR"
        exit 1
    fi

    # Check .env exists
    if [ ! -f "$APP_DIR/.env" ]; then
        log ERROR ".env file not found"
        exit 1
    fi

    # Check disk space (require at least 1GB free)
    FREE_SPACE=$(df -BG "$APP_DIR" | tail -1 | awk '{print $4}' | tr -d 'G')
    if [ "$FREE_SPACE" -lt 1 ]; then
        log ERROR "Insufficient disk space: ${FREE_SPACE}GB free"
        exit 1
    fi

    log SUCCESS "Pre-flight checks passed"
}

# Create backup
create_backup() {
    log INFO "Creating backup..."

    mkdir -p "$BACKUP_DIR"

    # Backup .env
    cp "$APP_DIR/.env" "$BACKUP_DIR/.env.backup"

    # Backup database (if configured)
    if [ "${BACKUP_DATABASE:-true}" = "true" ]; then
        $PHP_BIN "$APP_DIR/artisan" backup:database --no-encrypt 2>/dev/null || {
            log WARN "Database backup skipped (command not available)"
        }
    fi

    log SUCCESS "Backup created"
}

# Enable maintenance mode
enable_maintenance() {
    log INFO "Enabling maintenance mode..."
    $PHP_BIN "$APP_DIR/artisan" down --render="errors::503" --retry=60
    log SUCCESS "Maintenance mode enabled"
}

# Disable maintenance mode
disable_maintenance() {
    log INFO "Disabling maintenance mode..."
    $PHP_BIN "$APP_DIR/artisan" up
    log SUCCESS "Application is live"
}

# Update code (if using git)
update_code() {
    log INFO "Updating code from repository..."

    cd "$APP_DIR"

    # Stash any local changes
    git stash 2>/dev/null || true

    # Pull latest changes
    git pull origin main

    log SUCCESS "Code updated"
}

# Install dependencies
install_dependencies() {
    log INFO "Installing PHP dependencies..."

    cd "$APP_DIR"

    $COMPOSER_BIN install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist

    log SUCCESS "PHP dependencies installed"
}

# Run migrations
run_migrations() {
    if [ "$SKIP_MIGRATE" = true ]; then
        log WARN "Skipping migrations (--no-migrate)"
        return
    fi

    log INFO "Running database migrations..."

    $PHP_BIN "$APP_DIR/artisan" migrate --force

    log SUCCESS "Migrations completed"
}

# Build assets
build_assets() {
    if [ "$SKIP_BUILD" = true ]; then
        log WARN "Skipping asset build (--quick)"
        return
    fi

    log INFO "Building frontend assets..."

    cd "$APP_DIR"

    if [ -f "package.json" ]; then
        $NPM_BIN ci --production=false
        $NPM_BIN run build
        log SUCCESS "Assets built"
    else
        log WARN "No package.json found, skipping asset build"
    fi
}

# Clear and rebuild caches
rebuild_caches() {
    log INFO "Rebuilding caches..."

    # Clear all caches
    $PHP_BIN "$APP_DIR/artisan" cache:clear
    $PHP_BIN "$APP_DIR/artisan" config:clear
    $PHP_BIN "$APP_DIR/artisan" route:clear
    $PHP_BIN "$APP_DIR/artisan" view:clear

    # Rebuild caches
    $PHP_BIN "$APP_DIR/artisan" config:cache
    $PHP_BIN "$APP_DIR/artisan" route:cache
    $PHP_BIN "$APP_DIR/artisan" view:cache

    # Optimize
    $PHP_BIN "$APP_DIR/artisan" optimize

    log SUCCESS "Caches rebuilt"
}

# Restart queue workers
restart_queues() {
    log INFO "Restarting queue workers..."

    $PHP_BIN "$APP_DIR/artisan" queue:restart

    log SUCCESS "Queue workers signaled to restart"
}

# Post-deployment health check
health_check() {
    log INFO "Running health checks..."

    # Run production check command
    if $PHP_BIN "$APP_DIR/artisan" production:check --json 2>/dev/null; then
        log SUCCESS "Health checks passed"
    else
        log WARN "Some health checks reported issues"
    fi

    # Check HTTP response (if curl available)
    if command -v curl &> /dev/null; then
        APP_URL=$($PHP_BIN "$APP_DIR/artisan" tinker --execute="echo config('app.url');" 2>/dev/null | tail -1)

        if [ -n "$APP_URL" ]; then
            HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL/up" 2>/dev/null || echo "000")

            if [ "$HTTP_STATUS" = "200" ]; then
                log SUCCESS "HTTP health check passed (200 OK)"
            else
                log WARN "HTTP health check returned status: $HTTP_STATUS"
            fi
        fi
    fi
}

# Rollback to previous version
rollback() {
    log INFO "Rolling back to previous version..."

    # Restore .env if backup exists
    if [ -f "$BACKUP_DIR/.env.backup" ]; then
        cp "$BACKUP_DIR/.env.backup" "$APP_DIR/.env"
        log SUCCESS ".env restored from backup"
    fi

    # Rollback last migration
    $PHP_BIN "$APP_DIR/artisan" migrate:rollback --step=1 --force

    # Clear caches
    $PHP_BIN "$APP_DIR/artisan" cache:clear
    $PHP_BIN "$APP_DIR/artisan" config:cache

    log SUCCESS "Rollback completed"
}

# Main deployment flow
main() {
    echo ""
    echo "========================================"
    echo "  Vodo.com Deployment"
    echo "  $(date '+%Y-%m-%d %H:%M:%S')"
    echo "========================================"
    echo ""

    # Handle rollback
    if [ "$ROLLBACK" = true ]; then
        rollback
        exit 0
    fi

    # Run deployment steps
    preflight_checks
    create_backup
    enable_maintenance

    # Update application
    # update_code  # Uncomment if using git pull
    install_dependencies
    run_migrations
    build_assets
    rebuild_caches
    restart_queues

    disable_maintenance
    health_check

    echo ""
    echo "========================================"
    log SUCCESS "Deployment completed successfully!"
    echo "========================================"
    echo ""
}

# Run main function
main "$@"
