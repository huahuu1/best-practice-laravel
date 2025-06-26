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
    protected $kafkaProducer;

    /**
     * KafkaController constructor.
     *
     * @param KafkaProducer $kafkaProducer
     */
    public function __construct(KafkaProducer $kafkaProducer)
    {
        $this->kafkaProducer = $kafkaProducer;
        $this->middleware('web')->except(['produce']);
    }

    /**
     * Produce a message to Kafka
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function produce(Request $request)
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'message' => 'required|string',
            'key' => 'nullable|string',
        ]);

        try {
            $result = $this->kafkaProducer->send(
                $validated['topic'],
                $validated['message'],
                $validated['key'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'details' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
