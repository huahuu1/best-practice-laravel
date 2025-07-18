<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Kafka\KafkaProducer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KitchenApiController extends Controller
{
    /**
     * @var KafkaProducer
     */
    protected $producer;

    /**
     * KitchenApiController constructor.
     *
     * @param KafkaProducer $producer
     */
    public function __construct(KafkaProducer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * Get all kitchen orders
     *
     * @OA\Get(
     *     path="/api/kitchen/orders",
     *     operationId="getOrders",
     *     tags={"KitchenDisplay"},
     *     summary="Get all kitchen orders",
     *     description="Returns a list of all orders with their current status",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="orders",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="order_id", type="string", example="ORD-ABCD1234"),
     *                     @OA\Property(property="table_id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="items",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Pizza Margherita"),
     *                             @OA\Property(property="quantity", type="integer", example=2),
     *                             @OA\Property(property="price", type="number", format="float", example=10.99)
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="status",
     *                         type="string",
     *                         enum={"received", "processing", "ready", "completed"},
     *                         example="received"
     *                     ),
     *                     @OA\Property(property="timestamp", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     * @return JsonResponse
     */
    public function getOrders(): JsonResponse
    {
        try {
            // Fetch active orders and recently completed orders (last 24 hours)
            // Using single quotes for PostgreSQL enum values
            $orders = Order::whereIn('status', ['received', 'processing', 'ready'])
                ->orWhere(function ($query) {
                    $query->where('status', 'completed')
                          ->where('updated_at', '>=', now()->subHours(24));
                })
                ->with(['items.menuItem'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    $items = $order->items->map(function ($item) {
                        return [
                            'id' => $item->menu_item_id,
                            'name' => $item->menuItem->name,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'special_instructions' => $item->special_instructions
                        ];
                    });

                    return [
                        'order_id' => $order->order_id,
                        'table_id' => $order->table_id,
                        'items' => $items,
                        'status' => $order->status,
                        'timestamp' => $order->created_at->toIso8601String(),
                        'updated_at' => $order->updated_at->toIso8601String(),
                        'total' => $order->total
                    ];
                });

            return response()->json([
                'success' => true,
                'orders' => $orders
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch kitchen orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status
     *
     * @OA\Post(
     *     path="/api/kitchen/orders/{orderId}/status",
     *     operationId="updateOrderStatus",
     *     tags={"KitchenDisplay"},
     *     summary="Update order status",
     *     description="Updates the status of an order and sends a message to Kafka",
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="ID of the order to update",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"received", "processing", "ready", "completed"},
     *                 example="processing"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order ORD-ABCD1234 status updated to processing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update order status: Error message")
     *         )
     *     )
     * )
     * @param Request $request
     * @param string $orderId
     * @return JsonResponse
     */
    public function updateOrderStatus(Request $request, string $orderId): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'status' => 'required|string|in:received,processing,ready,completed',
        ]);

        $status = $validated['status'];

        try {
            // Find the order by order_id
            $order = Order::where('order_id', $orderId)->firstOrFail();

            // Update the order status
            $order->status = $status;
            $order->save();

            // If order is completed, update the table status back to available
            if ($status === 'completed') {
                $table = $order->table;
                $table->status = 'available';
                $table->save();
            }

            // Send status update to Kafka
            $this->producer->send('order-status-updates', [
                'order_id' => $orderId,
                'table_id' => $order->table_id,
                'status' => $status,
                'updated_at' => now()->toIso8601String()
            ], $orderId);

            return response()->json([
                'success' => true,
                'message' => "Order {$orderId} status updated to {$status}"
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update order status', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'status' => $status
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream events for real-time updates
     *
     * @OA\Get(
     *     path="/api/kitchen/events",
     *     operationId="streamEvents",
     *     tags={"KitchenDisplay"},
     *     summary="Stream kitchen events",
     *     description="Server-sent events stream for real-time kitchen updates",
     *     @OA\Response(
     *         response=200,
     *         description="SSE stream started"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     * @return StreamedResponse
     */
    public function streamEvents(): StreamedResponse
    {
        $response = new StreamedResponse(function() {
            // Set headers for event stream
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');

            // In a real app, this would connect to Kafka and stream messages
            // For this demo, we'll keep the connection open without generating fake orders

            $counter = 0;
            while (true) {
                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }

                // Comment out the fake order generation
                /*
                if ($counter % 5 === 0) {
                    // Every 10 seconds, send a fake order update
                    $orderId = 'ORD-' . strtoupper(substr(md5(rand()), 0, 8));
                    $event = [
                        'type' => 'new_order',
                        'order' => [
                            'order_id' => $orderId,
                            'table_id' => rand(1, 10),
                            'items' => [
                                [
                                    'id' => rand(1, 6),
                                    'name' => 'Sample Item ' . rand(1, 10),
                                    'quantity' => rand(1, 3),
                                    'price' => 9.99
                                ]
                            ],
                            'status' => 'received',
                            'timestamp' => now()->toIso8601String()
                        ]
                    ];

                    echo "data: " . json_encode($event) . "\n\n";
                    flush();
                }
                */

                $counter++;
                sleep(2); // Sleep for 2 seconds
            }
        });

        return $response;
    }

    /**
     * Get kitchen order statistics
     *
     * @OA\Get(
     *     path="/api/kitchen/statistics",
     *     operationId="getKitchenStatistics",
     *     tags={"KitchenDisplay"},
     *     summary="Get kitchen order statistics",
     *     description="Returns statistics for kitchen orders like counts by status and average preparation times",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="statistics",
     *                 type="object",
     *                 @OA\Property(property="received_count", type="integer", example=5),
     *                 @OA\Property(property="processing_count", type="integer", example=3),
     *                 @OA\Property(property="ready_count", type="integer", example=2),
     *                 @OA\Property(property="completed_count", type="integer", example=10),
     *                 @OA\Property(property="avg_preparation_time", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     * @return JsonResponse
     */
    public function getKitchenStatistics(): JsonResponse
    {
        try {
            $today = now()->startOfDay();

            // Count orders by status for today
            $receivedCount = Order::where('status', 'received')
                ->where('created_at', '>=', $today)
                ->count();

            $processingCount = Order::where('status', 'processing')
                ->where('created_at', '>=', $today)
                ->count();

            $readyCount = Order::where('status', 'ready')
                ->where('created_at', '>=', $today)
                ->count();

            $completedCount = Order::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->count();

            // Calculate average preparation time for completed orders today
            // (time between 'received' and 'ready' statuses)
            $completedOrders = Order::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->get();

            $totalPreparationTime = 0;
            $ordersWithPrepTime = 0;

            foreach ($completedOrders as $order) {
                // For this demo, we'll just use a simple calculation based on created_at and updated_at
                // In a real system, you'd track timestamp of each status change
                $preparationTime = $order->updated_at->diffInMinutes($order->created_at);
                $totalPreparationTime += $preparationTime;
                $ordersWithPrepTime++;
            }

            $avgPreparationTime = $ordersWithPrepTime > 0
                ? round($totalPreparationTime / $ordersWithPrepTime)
                : 0;

            return response()->json([
                'success' => true,
                'statistics' => [
                    'received_count' => $receivedCount,
                    'processing_count' => $processingCount,
                    'ready_count' => $readyCount,
                    'completed_count' => $completedCount,
                    'avg_preparation_time' => $avgPreparationTime,
                    'total_orders_today' => $receivedCount + $processingCount + $readyCount + $completedCount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch kitchen statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kitchen statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
