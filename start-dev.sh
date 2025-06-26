#!/bin/bash

# Stop any running containers
docker-compose down

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env file from .env.example"
fi

# Make sure Redis client is set to predis
grep -q "REDIS_CLIENT=predis" .env || echo "REDIS_CLIENT=predis" >> .env

# Set Redis host
grep -q "REDIS_HOST=redis" .env || sed -i '' 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g' .env || echo "REDIS_HOST=redis" >> .env

# Build and start the containers
docker-compose build
docker-compose up -d

# Wait for services to be ready
echo "Waiting for services to be ready..."
sleep 5

# Install dependencies
docker-compose exec app composer install

# Clear caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear

# Generate application key if needed
docker-compose exec app php artisan key:generate --no-interaction

# Run migrations
docker-compose exec app php artisan migrate --no-interaction

echo "Environment is ready!"
echo "Access the application at: http://localhost:9999"
echo "Access Kafka UI at: http://localhost:8081"
