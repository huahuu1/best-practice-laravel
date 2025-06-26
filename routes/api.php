<?php

use App\Http\Controllers\Api\KafkaApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KitchenApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Kafka API routes
Route::post('/kafka/produce', [KafkaApiController::class, 'produce']);

// Kitchen display API routes - note that these will be prefixed with /api
Route::prefix('kitchen')->group(function () {
    Route::get('/orders', [KitchenApiController::class, 'getOrders']);
    Route::post('/orders/{orderId}/status', [KitchenApiController::class, 'updateOrderStatus']);
    Route::get('/events', [KitchenApiController::class, 'streamEvents']);
});
