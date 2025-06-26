# API Documentation Guide

This project uses [Swagger/OpenAPI](https://swagger.io/) for API documentation through the [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) package. This guide explains how to view, use, and extend the API documentation.

## Viewing the Documentation

The API documentation is available at:

```
http://localhost:9999/api/documentation
```

This interactive interface allows you to:

1. Browse all available API endpoints
2. See request parameters, headers, and body schemas
3. Test API calls directly from the browser
4. View response schemas and examples

## Understanding the Documentation Structure

The API documentation is organized by tags:

- **Kafka**: Endpoints related to Kafka messaging
- **TableOrders**: Endpoints related to the table ordering system
- **KitchenDisplay**: Endpoints related to the kitchen display system

## Testing Endpoints

To test an endpoint:

1. Click on the endpoint you want to test
2. Click the "Try it out" button
3. Fill in the required parameters
4. Click "Execute"
5. View the response

## Extending the Documentation

### Adding a New Endpoint

To document a new API endpoint, add OpenAPI annotations to your controller method:

```php
/**
 * @OA\Post(
 *     path="/api/your/endpoint",
 *     summary="Short summary of what the endpoint does",
 *     description="Detailed description of the endpoint",
 *     tags={"YourTag"},
 *     @OA\RequestBody(...),
 *     @OA\Response(...)
 * )
 */
public function yourMethod()
{
    // Your code here
}
```

### Common Annotation Types

- `@OA\Info`: API information
- `@OA\Server`: Server information
- `@OA\Tag`: Tag for grouping operations
- `@OA\Get`, `@OA\Post`, `@OA\Put`, `@OA\Delete`: HTTP methods
- `@OA\Parameter`: URL parameters
- `@OA\RequestBody`: Request body schema
- `@OA\Response`: Response schema
- `@OA\Property`: Schema property
- `@OA\Schema`: Data schema

### Regenerating Documentation

After adding or modifying annotations, regenerate the documentation with:

```bash
php artisan l5-swagger:generate
```

Or if using Docker:

```bash
docker-compose exec app php artisan l5-swagger:generate
```

## Best Practices

1. **Be descriptive**: Write clear summaries and descriptions
2. **Provide examples**: Add examples for requests and responses
3. **Use proper tags**: Organize endpoints under appropriate tags
4. **Document all responses**: Include success and error responses
5. **Keep documentation updated**: Regenerate docs after API changes

## Troubleshooting

If you encounter issues with the documentation:

1. Check syntax of your annotations
2. Ensure all referenced models and schemas exist
3. Clear Laravel cache and regenerate docs:

```bash
php artisan cache:clear
php artisan config:clear
php artisan l5-swagger:generate
```

## Resources

- [OpenAPI Specification](https://spec.openapis.org/oas/latest.html)
- [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger/wiki) 
