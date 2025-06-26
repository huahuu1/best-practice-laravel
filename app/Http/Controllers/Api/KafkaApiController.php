<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Kafka\KafkaProducer;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Kafka",
 *     description="API endpoints for Kafka integration"
 * )
 */
class KafkaApiController extends Controller
{
    /**
     * @var KafkaProducer
     */
    protected $kafkaProducer;

    /**
     * KafkaApiController constructor.
     *
     * @param KafkaProducer $kafkaProducer
     */
    public function __construct(KafkaProducer $kafkaProducer)
    {
        $this->kafkaProducer = $kafkaProducer;
    }

    /**
     * Produce a message to Kafka
     *
     * @OA\Post(
     *     path="/api/kafka/produce",
     *     operationId="produceMessage",
     *     tags={"Kafka"},
     *     summary="Send a message to a Kafka topic",
     *     description="Sends a message to the specified Kafka topic with an optional key",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"topic","message"},
     *             @OA\Property(property="topic", type="string", example="test-topic"),
     *             @OA\Property(property="message", type="string", example="Hello Kafka from Laravel!"),
     *             @OA\Property(property="key", type="string", example="test-key")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Message sent to Kafka"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="topic", type="string", example="test-topic"),
     *                 @OA\Property(property="message", type="string", example="Hello Kafka from Laravel!"),
     *                 @OA\Property(property="key", type="string", example="test-key")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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

        $topic = $validated['topic'];
        $message = $validated['message'];
        $key = $validated['key'] ?? null;

        $result = $this->kafkaProducer->send($topic, $message, $key);

        return response()->json([
            'success' => true,
            'message' => 'Message sent to Kafka',
            'data' => [
                'topic' => $topic,
                'message' => $message,
                'key' => $key,
            ]
        ]);
    }
}
