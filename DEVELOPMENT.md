# Restaurant Management System Development Guide

## Overview

This project consists of two main components:
1. Laravel backend (this repository)
2. NestJS chat service (in ../restaurant-chat-service)

## Quick Start

Run the following command to start both services with proper IP configuration:

```bash
./start-all-services.sh
```

This script will:
1. Automatically detect your local IP address
2. Update configuration files in both services
3. Start the Laravel development server
4. Start the chat service

## Configuration

### Environment Variables

Both services use environment variables for configuration. The main variables are:

- `HOST`: Your local IP address (automatically set by the scripts)
- `PORT`: The port for the chat service (default: 3001)
- `KAFKA_BROKER`: Kafka broker address (default: your-ip:9092)
- `BASE_URL`: Laravel application URL (default: http://your-ip:9999)
- `APP_URL`: Same as BASE_URL
- `DB_CONNECTION`: Database connection type (mysql or postgres)
- `DB_HOST`: Database host (use 'mysql' or 'postgres' when running in Docker)
- `DB_PORT`: Database port (3306 for MySQL, 5432 for Postgres)
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password

## Docker Services

The following Docker services are available:

- **Laravel App**: PHP application container
- **Nginx**: Web server for Laravel
- **MySQL**: MySQL database
- **Postgres**: PostgreSQL database (alternative to MySQL)
- **Redis**: Redis cache
- **Zookeeper**: Required for Kafka
- **Kafka**: Message broker (optional)
- **Kafka UI**: Web UI for Kafka (http://your-ip:8082)

## Troubleshooting

### Database Connection Issues

If you see "Access denied" errors:

1. Make sure your `.env` file has the correct database settings:
   ```
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=laravel
   DB_PASSWORD=secret
   ```

2. Run migrations and seeders:
   ```bash
   docker exec -it best-practice-laravel-app-1 php artisan migrate:fresh --seed
   ```

### Chat Service Issues

The chat service can work with or without Kafka. If Kafka is not available, the service will still function but without message persistence.

If you see "Disconnected" in the chat UI:

1. Check if the chat service is running:
   ```bash
   curl -I http://your-ip:3001
   ```

2. Make sure the WebSocket URL is correct in both services:
   - In Laravel: `public/env.js` should have `WS_URL: 'http://your-ip:3001'`
   - In Chat Service: Same setting in its `public/env.js`

3. Run the update script to fix all IP addresses:
   ```bash
   ./update-all-ips.sh
   ```

### Kafka Issues

Kafka is optional for this application. If you see Kafka connection errors in the logs, you can:

1. Restart Kafka and Zookeeper:
   ```bash
   docker-compose restart zookeeper kafka
   ```

2. Or simply ignore the errors - the chat service will work without Kafka

## Utility Scripts

- `start-all-services.sh`: Starts both Laravel and chat service
- `update-all-ips.sh`: Updates IP addresses in all configuration files
- `update-base-url.sh`: Updates only the base URL in Laravel
- `start-dev-auto-ip.sh`: Starts the chat service with auto IP detection
