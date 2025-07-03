<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Kafka\KafkaProducer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
     * Validate API token
     *
     * @param Request $request
     * @return bool
     */
    protected function validateApiToken(Request $request): bool
    {
        // For internal calls (like from our QR code scanner) we can bypass token validation
        // if the request comes from our own application
        if ($request->hasHeader('Referer') &&
            str_contains($request->header('Referer'), config('app.url'))) {
            return true;
        }

        // For external API calls, require a valid API token
        $apiToken = config('kafka.api_token', env('KAFKA_API_TOKEN'));

        // If no API token is configured, only allow internal requests
        if (empty($apiToken)) {
            return false;
        }

        return $request->header('X-API-Token') === $apiToken;
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
     *         response=401,
     *         description="Unauthorized access"
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
     * @return JsonResponse
     */
    public function produce(Request $request): JsonResponse
    {
        try {
            // For security, validate either internal request or API token
            if (!$this->validateApiToken($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

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
        } catch (\Exception $e) {
            Log::error('Kafka API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message to Kafka',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
