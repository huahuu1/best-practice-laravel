<?php

// Direct API endpoint for Kafka without Laravel routing
header('Content-Type: application/json');

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
    ]);
    exit();
}

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validate the request
if (!$data || !isset($data['topic']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Required fields: topic, message',
    ]);
    exit();
}

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get the Kafka producer service
$producer = $app->make(\App\Services\Kafka\KafkaProducer::class);

try {
    // Send the message to Kafka
    $result = $producer->send(
        $data['topic'],
        $data['message'],
        $data['key'] ?? null
    );

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Message sent to Kafka',
        'data' => [
            'topic' => $data['topic'],
            'message' => $data['message'],
            'key' => $data['key'] ?? null,
        ]
    ]);
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message to Kafka',
        'error' => $e->getMessage(),
    ]);
}
