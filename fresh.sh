#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════════════════════════════╗"
echo "║                         VODO FRESH SETUP                                 ║"
echo "║  This will DROP all tables, run migrations, and seed essential data      ║"
echo "╚══════════════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running. Please start Docker and try again.${NC}"
    exit 1
fi

# Check if the app container is running
if ! docker compose ps app | grep -q "Up"; then
    echo -e "${YELLOW}App container is not running. Starting containers...${NC}"
    docker compose up -d
    
    # Wait for services to be ready
    echo -e "${YELLOW}Waiting for services to be ready...${NC}"
    sleep 10
fi

# Confirm before proceeding (skip if --force flag is passed)
if [[ "$1" != "--force" && "$1" != "-f" ]]; then
    echo -e "${RED}⚠️  WARNING: This will DELETE all existing data!${NC}"
    echo ""
    read -p "Are you sure you want to continue? (y/N): " confirm
    if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
        echo -e "${YELLOW}Aborted.${NC}"
        exit 0
    fi
fi

echo ""
echo -e "${YELLOW}Step 1/3: Running fresh migrations...${NC}"
docker compose exec app php artisan migrate:fresh --force

if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Migration failed. Please check the error above.${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 2/3: Seeding database with essential data...${NC}"
docker compose exec app php artisan db:seed --force

if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Seeding failed. Please check the error above.${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 3/3: Clearing caches...${NC}"
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✓ FRESH SETUP COMPLETED SUCCESSFULLY!                                   ║${NC}"
echo -e "${GREEN}║                                                                          ║${NC}"
echo -e "${GREEN}║  Your database now has:                                                  ║${NC}"
echo -e "${GREEN}║  • All tables created                                                    ║${NC}"
echo -e "${GREEN}║  • Permissions with group_id assigned                                    ║${NC}"
echo -e "${GREEN}║  • Super admin users created                                             ║${NC}"
echo -e "${GREEN}║  • Plugins registered from app/Plugins                                   ║${NC}"
echo -e "${GREEN}║  • View types registered                                                 ║${NC}"
echo -e "${GREEN}║                                                                          ║${NC}"
echo -e "${GREEN}║  Default login: super@vodo.com / password                                ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

