#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Running Laravel migrations inside Docker container...${NC}"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running. Please start Docker and try again.${NC}"
    exit 1
fi

# Check if the app container is running
if ! docker compose ps app | grep -q "Up"; then
    echo -e "${YELLOW}App container is not running. Starting containers...${NC}"
    docker compose up -d
    
    # Wait for MySQL to be ready
    echo -e "${YELLOW}Waiting for MySQL to be ready...${NC}"
    sleep 5
fi

# Run migrations
echo -e "${GREEN}Executing: php artisan migrate${NC}"
docker compose exec app php artisan migrate

# Check if migration was successful
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed successfully!${NC}"
else
    echo -e "${RED}✗ Migration failed. Please check the error above.${NC}"
    exit 1
fi
