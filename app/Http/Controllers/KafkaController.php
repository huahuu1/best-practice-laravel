<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Kafka\KafkaProducer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class KafkaController extends Controller
{
    /**
     * @var KafkaProducer
     */
    protected $producer;

    /**
     * KafkaController constructor.
     *
     * @param KafkaProducer $producer
     */
    public function __construct(KafkaProducer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * Produce a message to a Kafka topic.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function produce(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'message' => 'required|string',
            'key' => 'nullable|string',
        ]);

        try {
            $this->producer->send(
                $validated['topic'],
                $validated['message'],
                $validated['key'] ?? null
            );

            Log::info('Message sent to Kafka', [
                'topic' => $validated['topic'],
                'message' => $validated['message'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send message to Kafka', [
                'error' => $e->getMessage(),
                'topic' => $validated['topic'],
                'message' => $validated['message'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }
}
