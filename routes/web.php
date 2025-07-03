<?php

use App\Http\Controllers\KafkaTestController;
use App\Http\Controllers\KafkaController;
use App\Http\Controllers\ReactAppController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TableOrderController;
use App\Http\Controllers\Api\KitchenApiController;
use App\Http\Controllers\Api\TableApiController;
use Illuminate\Http\Request;

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

// Test React route
Route::get('/test-react', [ReactAppController::class, 'testReact']);

// Kafka test UI
Route::get('/kafka/test', [KafkaTestController::class, 'index']);
Route::get('/kafka/test/produce', [KafkaTestController::class, 'testProduce']);

// Direct route for Kafka API - moved to api.php for proper API security
Route::post('/kafka/produce', [KafkaController::class, 'produce']);

// Table ordering system routes
Route::prefix('tables')->group(function () {
    Route::get('/', [TableOrderController::class, 'index'])->name('tables.index');
    Route::get('/{tableId}/menu', [TableOrderController::class, 'tableMenu'])->name('table.order');
    Route::post('/{tableId}/order', [TableOrderController::class, 'placeOrder'])->name('table.place-order');
});

// Direct route for kitchen display
Route::get('/kitchen', [TableOrderController::class, 'kitchenDisplay'])->name('kitchen.display');
Route::get('/react/kitchen', [ReactAppController::class, 'index'])->name('react.kitchen.display');

// Kitchen API routes for testing without Laravel Sanctum authentication
Route::prefix('/api/kitchen')->group(function () {
    Route::get('/orders', [KitchenApiController::class, 'getOrders']);
    Route::post('/orders/{orderId}/status', [KitchenApiController::class, 'updateOrderStatus']);
    Route::get('/events', [KitchenApiController::class, 'streamEvents']);
    Route::get('/statistics', [KitchenApiController::class, 'getKitchenStatistics']);
});

// Routes for React frontend
Route::get('/react/{path?}', [ReactAppController::class, 'index'])->where('path', '.*');

// API routes for tables
Route::prefix('api')->group(function () {
    Route::get('/tables', [TableApiController::class, 'getTables']);
    Route::get('/tables/{id}', [TableApiController::class, 'getTable']);
    Route::get('/menu-items', [TableApiController::class, 'getMenuItems']);
});

// Debug route to check table order functionality
Route::get('/debug/table-order/{tableId}', function ($tableId) {
    return response()->json([
        'message' => 'Debug route is working',
        'tableId' => $tableId,
        'endpoint' => "/tables/{$tableId}/order"
    ]);
});
