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
        $topic = $request->get('topic', env('KAFKA_TOPIC', 'laravel-topic'));
        $message = $request->get('message', 'Test message from Laravel at ' . date('Y-m-d H:i:s'));
        $key = $request->get('key');

        try {
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
        $topic = $request->input('topic', 'test-topic');
        $groupId = $request->input('group_id', 'test-group');
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
}
