# Environment Variables

This application uses the following environment variables for configuration. Add these to your `.env` file:

## Application Configuration
```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:9999
```

## Logging Configuration
```
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

## Database Configuration
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

## Redis Configuration
```
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Kafka Configuration
```
KAFKA_BROKER=kafka:29092
KAFKA_TOPIC=laravel-topic
KAFKA_GROUP_ID=laravel-consumer-group
KAFKA_UI_URL=http://localhost:8082
```

## Swagger/OpenAPI Configuration
```
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_BASE_PATH=http://localhost:9999
L5_SWAGGER_CONST_HOST=http://localhost:9999/api
```

## Usage

The `KAFKA_UI_URL` environment variable is used to define the URL for the Kafka UI monitoring interface. This is used in the application to provide links to the Kafka UI from the testing interface.

In development environments with Docker, this is typically set to:
```
KAFKA_UI_URL=http://localhost:8082
```

The Swagger configuration variables control the API documentation:
- `L5_SWAGGER_GENERATE_ALWAYS`: When true, API documentation is automatically regenerated on each request (useful in development)
- `L5_SWAGGER_BASE_PATH`: The base URL of your API for documentation purposes
- `L5_SWAGGER_CONST_HOST`: The full API host URL used in the documentation

For production deployments, you would set these to your actual production endpoints. 
