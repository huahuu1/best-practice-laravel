<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kafka Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Kafka routes for your application.
| These routes are loaded directly without any middleware.
|
*/

// Direct route for Kafka API without CSRF
Route::post('/kafka/produce', function (\Illuminate\Http\Request $request) {
    $producer = app(\App\Services\Kafka\KafkaProducer::class);

    $validated = $request->validate([
        'topic' => 'required|string',
        'message' => 'required|string',
        'key' => 'nullable|string',
    ]);

    $topic = $validated['topic'];
    $message = $validated['message'];
    $key = $validated['key'] ?? null;

    $result = $producer->send($topic, $message, $key);

    return response()->json([
        'success' => true,
        'message' => 'Message sent to Kafka',
        'data' => [
            'topic' => $topic,
            'message' => $message,
            'key' => $key,
        ]
    ]);
});
