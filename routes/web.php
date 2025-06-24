<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KafkaController;
use App\Http\Controllers\KafkaTestController;

// Main application route
Route::get('/', function () {
    return view('welcome');
});

// Kafka API route
Route::post('/kafka/produce', [KafkaController::class, 'produce']);

// Kafka test routes
Route::get('/kafka/test/produce', [KafkaTestController::class, 'testProduce']);
Route::get('/kafka/test', function () {
    return view('kafka-test');
});
