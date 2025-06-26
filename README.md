<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Laravel Kafka Integration

This project demonstrates the integration of Apache Kafka with Laravel using the rdkafka PHP extension.

## Setup

1. Make sure you have Docker and Docker Compose installed
2. Clone the repository
3. Run `docker-compose up -d` to start the containers
4. Access the Laravel application at http://localhost:9999
5. Access the Kafka UI at http://localhost:8082

## Kafka Integration Features

The project includes a complete Kafka integration with:

- Producer service for sending messages to Kafka topics
- Consumer service for receiving and processing messages
- Artisan command for consuming messages via CLI
- HTTP endpoints for producing messages via API
- Test controller for simple message testing
- Table ordering system with QR codes for restaurants
- API documentation with Swagger/OpenAPI

### Table Ordering System

The project includes a complete QR code-based table ordering system for restaurants:

- Table management system with QR code generation
- Mobile-friendly digital menu for customers to order from their devices
- Real-time kitchen display system showing orders as they come in
- Order status tracking from received to completed
- Kafka integration for real-time messaging between components

#### How It Works

1. Restaurant staff can access the table management system at `/tables`
2. Each table has a unique QR code that customers can scan
3. Customers scan the QR code to access the digital menu on their own devices
4. Orders are placed directly through the customer's device
5. Orders are published to Kafka and displayed on the kitchen display in real-time
6. Kitchen staff can update order status (received → processing → ready → completed)
7. Waitstaff are notified when orders are ready to be served

#### Accessing the System

- Table Management: http://localhost:9999/tables
- Kitchen Display: http://localhost:9999/tables/kitchen
- Customer Menu: http://localhost:9999/tables/{table_id}/menu (accessed via QR code)

### Kafka Configuration

The Kafka configuration is set in your `.env` file:

```
KAFKA_BROKER=kafka:29092
KAFKA_TOPIC=laravel-topic
KAFKA_GROUP_ID=laravel-consumer-group
KAFKA_UI_URL=http://localhost:8082
```

### Producing Messages

#### Via API

To produce a message to Kafka, make a POST request to `/kafka/produce` with the following JSON payload:

```json
{
  "topic": "your-topic-name",
  "message": "Your message content",
  "key": "optional-message-key"
}
```

Example with curl:

```bash
curl -X POST http://localhost:9999/kafka/produce \
  -H "Content-Type: application/json" \
  -d '{"topic": "test-topic", "message": "Hello Kafka!", "key": "test-key"}'
```

#### Via Test Endpoint

For quick testing, visit `/kafka/test/produce` with query parameters:

```
/kafka/test/produce?topic=test-topic&message=Hello&key=test-key
```

### Consuming Messages

To consume messages from Kafka, run the artisan command:

```bash
# Run inside the Docker container
docker-compose exec app php artisan kafka:consume

# With specific topic and group
docker-compose exec app php artisan kafka:consume --topic=topic-name --group=group-id
```

### Using the Kafka Services in Code

#### Producer Example

```php
use App\Services\Kafka\KafkaProducer;

class SomeService
{
    protected $producer;

    public function __construct(KafkaProducer $producer)
    {
        $this->producer = $producer;
    }

    public function sendNotification($data)
    {
        $this->producer->send('notifications', $data);
    }
}
```

#### Consumer Example

```php
use App\Services\Kafka\KafkaConsumer;

class NotificationProcessor
{
    public function process()
    {
        $consumer = new KafkaConsumer('notification-group', ['notifications']);
        
        $consumer->consume(function($message) {
            $value = $message['value'];
            // Process the message
            
            return true; // Commit the offset
        });
    }
}
```

## Kafka UI

The project includes a web-based Kafka UI accessible at http://localhost:8080. This UI allows you to:

- View all Kafka topics
- Create new topics
- Browse messages in topics
- View consumer groups and their offsets
- Monitor Kafka brokers

### API Documentation

The project includes full API documentation using Swagger/OpenAPI. You can access the interactive documentation at:

```
http://localhost:9999/api/documentation
```

This provides:

- Interactive documentation for all API endpoints
- Request and response schema information
- Testing interface to try out API calls directly from the browser
- Sample request bodies and responses

To regenerate the Swagger documentation after making changes to the API:

```bash
docker-compose exec app php artisan l5-swagger:generate
```
