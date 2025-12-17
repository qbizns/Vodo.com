#!/bin/bash

# Script to setup .env file for Docker

ENV_FILE=".env"

echo "Setting up .env file for Docker..."

# Check if .env exists
if [ ! -f "$ENV_FILE" ]; then
    echo "Creating .env file from .env.example..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        echo "Creating new .env file..."
        touch .env
    fi
fi

# Update database configuration for Docker
echo "Updating database configuration..."

# Use sed to update or add DB settings
if grep -q "DB_CONNECTION=" "$ENV_FILE"; then
    sed -i '' 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' "$ENV_FILE"
else
    echo "DB_CONNECTION=mysql" >> "$ENV_FILE"
fi

if grep -q "DB_HOST=" "$ENV_FILE"; then
    sed -i '' 's/^DB_HOST=.*/DB_HOST=mysql/' "$ENV_FILE"
else
    echo "DB_HOST=mysql" >> "$ENV_FILE"
fi

if grep -q "DB_PORT=" "$ENV_FILE"; then
    sed -i '' 's/^DB_PORT=.*/DB_PORT=3306/' "$ENV_FILE"
else
    echo "DB_PORT=3306" >> "$ENV_FILE"
fi

if grep -q "DB_DATABASE=" "$ENV_FILE"; then
    sed -i '' 's/^DB_DATABASE=.*/DB_DATABASE=saas/' "$ENV_FILE"
else
    echo "DB_DATABASE=saas" >> "$ENV_FILE"
fi

if grep -q "DB_USERNAME=" "$ENV_FILE"; then
    sed -i '' 's/^DB_USERNAME=.*/DB_USERNAME=laravel/' "$ENV_FILE"
else
    echo "DB_USERNAME=laravel" >> "$ENV_FILE"
fi

if grep -q "DB_PASSWORD=" "$ENV_FILE"; then
    sed -i '' 's/^DB_PASSWORD=.*/DB_PASSWORD=laravel/' "$ENV_FILE"
else
    echo "DB_PASSWORD=laravel" >> "$ENV_FILE"
fi

echo "✅ .env file updated for Docker!"
echo ""
echo "Database settings:"
echo "  DB_CONNECTION=mysql"
echo "  DB_HOST=mysql (Docker container name)"
echo "  DB_PORT=3306"
echo "  DB_DATABASE=saas"
echo "  DB_USERNAME=laravel"
echo "  DB_PASSWORD=laravel"
echo ""
echo "phpMyAdmin will be available at: http://localhost:8080"
echo "  Username: root"
echo "  Password: root"
