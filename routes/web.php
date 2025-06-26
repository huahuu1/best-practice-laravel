<?php

use App\Http\Controllers\KafkaTestController;
use App\Http\Controllers\KafkaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TableOrderController;
use App\Http\Controllers\Api\KitchenApiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Kafka test UI
Route::get('/kafka/test', [KafkaTestController::class, 'index']);
Route::get('/kafka/test/produce', [KafkaTestController::class, 'testProduce']);

// Direct route for Kafka API without CSRF
Route::post('/kafka/produce', [KafkaController::class, 'produce']);

// Table ordering system routes
Route::prefix('tables')->group(function () {
    Route::get('/', [TableOrderController::class, 'index'])->name('tables.index');
    Route::get('/{tableId}/menu', [TableOrderController::class, 'tableMenu'])->name('table.order');
    Route::post('/{tableId}/order', [TableOrderController::class, 'placeOrder'])->name('table.place-order');
    Route::get('/kitchen', [TableOrderController::class, 'kitchenDisplay'])->name('kitchen.display');
});

// Kitchen API routes for testing without Laravel Sanctum authentication
Route::prefix('/api/kitchen')->group(function () {
    Route::get('/orders', [KitchenApiController::class, 'getOrders']);
    Route::post('/orders/{orderId}/status', [KitchenApiController::class, 'updateOrderStatus']);
    Route::get('/events', [KitchenApiController::class, 'streamEvents']);
});
