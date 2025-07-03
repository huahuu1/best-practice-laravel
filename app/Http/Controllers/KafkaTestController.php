<?php

namespace App\Http\Controllers;

use App\Services\Kafka\KafkaProducer;
use App\Services\Kafka\KafkaConsumer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class KafkaTestController extends Controller
{
    /**
     * @var KafkaProducer
     */
    protected $producer;

    /**
     * @var KafkaConsumer
     */
    protected $consumer;

    /**
     * KafkaTestController constructor.
     *
     * @param KafkaProducer $producer
     * @param KafkaConsumer $consumer
     */
    public function __construct(KafkaProducer $producer, KafkaConsumer $consumer)
    {
        $this->producer = $producer;
        $this->consumer = $consumer;
    }

    /**
     * Test sending a message to Kafka.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testProduce(Request $request): JsonResponse
    {
        $topic = $request->get('topic', config('kafka.topics.qr_scan_events', 'qr-scan-events'));
        $message = $request->get('message', 'Test message from Laravel at ' . date('Y-m-d H:i:s'));
        $key = $request->get('key');

        try {
            // Using direct producer injection here is safe since this is an internal test endpoint
            // In production, we'd go through the API with proper authentication
            $this->producer->send($topic, $message, $key);

            return response()->json([
                'success' => true,
                'message' => 'Message sent to Kafka',
                'details' => [
                    'topic' => $topic,
                    'message' => $message,
                    'key' => $key,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send test message to Kafka', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test consuming a message from Kafka.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testConsume(Request $request): JsonResponse
    {
        $topic = $request->input('topic', config('kafka.topics.qr_scan_events', 'qr-scan-events'));
        $groupId = $request->input('group_id', config('kafka.consumer_group_id', 'laravel-consumer-group'));
        $timeout = $request->input('timeout', 10000); // 10 seconds timeout

        try {
            // Create a consumer specifically for this request
            $consumer = new KafkaConsumer($groupId, [$topic]);

            // Consume a single message
            $messages = $consumer->consume($timeout);

            return response()->json([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the Kafka test interface
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $kafkaUiUrl = env('KAFKA_UI_URL', 'http://localhost:8082');
        $kafkaTopics = config('kafka.topics');

        return view('kafka-test', [
            'kafkaUiUrl' => $kafkaUiUrl,
            'kafkaTopics' => $kafkaTopics
        ]);
    }
}
