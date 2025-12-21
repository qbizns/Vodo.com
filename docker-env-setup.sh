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
    sed -i '' 's/^DB_DATABASE=.*/DB_DATABASE=vodo/' "$ENV_FILE"
else
    echo "DB_DATABASE=vodo" >> "$ENV_FILE"
fi

if grep -q "DB_USERNAME=" "$ENV_FILE"; then
    sed -i '' 's/^DB_USERNAME=.*/DB_USERNAME=vodo/' "$ENV_FILE"
else
    echo "DB_USERNAME=vodo" >> "$ENV_FILE"
fi

if grep -q "DB_PASSWORD=" "$ENV_FILE"; then
    sed -i '' 's/^DB_PASSWORD=.*/DB_PASSWORD=laravel/' "$ENV_FILE"
else
    echo "DB_PASSWORD=laravel" >> "$ENV_FILE"
fi

# Add Docker Compose required variables
echo "Updating Docker Compose environment variables..."

# Ensure the file ends with a newline before adding new variables
[ -n "$(tail -c1 "$ENV_FILE")" ] && echo "" >> "$ENV_FILE"

if grep -q "MYSQL_ROOT_PASSWORD=" "$ENV_FILE"; then
    sed -i '' 's/^MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=root/' "$ENV_FILE"
else
    echo "MYSQL_ROOT_PASSWORD=root" >> "$ENV_FILE"
fi

if grep -q "MYSQL_DATABASE=" "$ENV_FILE"; then
    sed -i '' 's/^MYSQL_DATABASE=.*/MYSQL_DATABASE=vodo/' "$ENV_FILE"
else
    echo "MYSQL_DATABASE=vodo" >> "$ENV_FILE"
fi

if grep -q "MYSQL_USER=" "$ENV_FILE"; then
    sed -i '' 's/^MYSQL_USER=.*/MYSQL_USER=vodo/' "$ENV_FILE"
else
    echo "MYSQL_USER=vodo" >> "$ENV_FILE"
fi

if grep -q "MYSQL_PASSWORD=" "$ENV_FILE"; then
    sed -i '' 's/^MYSQL_PASSWORD=.*/MYSQL_PASSWORD=laravel/' "$ENV_FILE"
else
    echo "MYSQL_PASSWORD=laravel" >> "$ENV_FILE"
fi

# Session settings for HTTP development (Docker typically uses HTTP)
echo "Updating session settings for HTTP development..."

if grep -q "SESSION_SECURE_COOKIE=" "$ENV_FILE"; then
    sed -i '' 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=false/' "$ENV_FILE"
else
    echo "SESSION_SECURE_COOKIE=false" >> "$ENV_FILE"
fi

echo "✅ .env file updated for Docker!"
echo ""
echo "Laravel Database settings:"
echo "  DB_CONNECTION=mysql"
echo "  DB_HOST=mysql (Docker container name)"
echo "  DB_PORT=3306"
echo "  DB_DATABASE=vodo"
echo "  DB_USERNAME=vodo"
echo "  DB_PASSWORD=laravel"
echo ""
echo "Docker Compose settings:"
echo "  MYSQL_ROOT_PASSWORD=root"
echo "  MYSQL_DATABASE=vodo"
echo "  MYSQL_USER=vodo"
echo "  MYSQL_PASSWORD=laravel"
echo ""
echo "phpMyAdmin will be available at: http://localhost:8080"
echo "  Username: root"
echo "  Password: root"
