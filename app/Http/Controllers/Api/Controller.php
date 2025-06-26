<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Laravel Kafka Integration API Documentation",
 *     description="API documentation for Laravel Kafka Integration project, including table ordering system",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT License",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 *
 * @OA\Tag(
 *     name="Kafka",
 *     description="Kafka messaging endpoints"
 * )
 *
 * @OA\Tag(
 *     name="KitchenDisplay",
 *     description="Kitchen display system for table orders"
 * )
 *
 * @OA\Tag(
 *     name="TableOrders",
 *     description="Table ordering system endpoints"
 * )
 */
class Controller extends BaseController
{
    //
}
