<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kafka Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for Kafka integration.
    |
    */

    // Kafka broker connection string
    'broker' => env('KAFKA_BROKER', 'kafka:29092'),

    // API token for secure access to Kafka endpoints
    // For production, set this in your .env file
    'api_token' => env('KAFKA_API_TOKEN', null),

    // Default topics
    'topics' => [
        'qr_scan_events' => env('KAFKA_TOPIC_QR_SCAN_EVENTS', 'qr-scan-events'),
        'table_orders' => env('KAFKA_TOPIC_TABLE_ORDERS', 'table-orders'),
        'order_status_updates' => env('KAFKA_TOPIC_ORDER_STATUS', 'order-status-updates'),
    ],

    // Consumer group ID
    'consumer_group_id' => env('KAFKA_CONSUMER_GROUP_ID', 'laravel-consumer-group'),
];
