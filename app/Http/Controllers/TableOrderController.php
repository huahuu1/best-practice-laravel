<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Services\Kafka\KafkaProducer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="TableOrders",
 *     description="API endpoints for the table ordering system"
 * )
 */
class TableOrderController extends Controller
{
    /**
     * @var KafkaProducer
     */
    protected $producer;

    /**
     * TableOrderController constructor.
     *
     * @param KafkaProducer $producer
     */
    public function __construct(KafkaProducer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * Show the table QR code page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $tables = Table::all()->map(function ($table) {
            return [
                'id' => $table->id,
                'name' => $table->name,
                'capacity' => $table->capacity,
                'status' => $table->status,
                'qr_code_url' => route('table.order', ['tableId' => $table->id])
            ];
        });

        return view('table-management', ['tables' => $tables]);
    }

    /**
     * Show the menu for a specific table
     *
     * @param int $tableId
     * @return \Illuminate\Contracts\View\View
     */
    public function tableMenu($tableId)
    {
        $table = Table::findOrFail($tableId);
        $menuItems = MenuItem::where('available', true)
            ->orderBy('category')
            ->get();

        return view('table-menu', [
            'tableId' => $tableId,
            'tableName' => $table->name,
            'menuItems' => $menuItems
        ]);
    }

    /**
     * Process an order from a table
     *
     * @OA\Post(
     *     path="/tables/{tableId}/order",
     *     operationId="placeOrder",
     *     tags={"TableOrders"},
     *     summary="Place a new order from a table",
     *     description="Processes a new order from a table and sends it to the kitchen via Kafka",
     *     @OA\Parameter(
     *         name="tableId",
     *         in="path",
     *         required=true,
     *         description="ID of the table placing the order",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"items"},
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"id", "name", "quantity", "price"},
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Pizza Margherita"),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="price", type="number", format="float", example=10.99)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order placed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order placed successfully"),
     *             @OA\Property(property="order_id", type="string", example="ORD-ABCD1234")
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
     *             @OA\Property(property="message", type="string", example="Failed to place order: Error message")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @param int $tableId
     * @return JsonResponse
     */
    public function placeOrder(Request $request, $tableId)
    {
        // Validate the request
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // Find the table
            $table = Table::findOrFail($tableId);

            // Generate a unique order ID
            $orderId = 'ORD-' . strtoupper(Str::random(8));

            // Calculate total
            $total = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $menuItem = MenuItem::findOrFail($item['id']);
                $itemTotal = $menuItem->price * $item['quantity'];
                $total += $itemTotal;

                $orderItems[] = [
                    'id' => $menuItem->id,
                    'name' => $menuItem->name,
                    'quantity' => $item['quantity'],
                    'price' => $menuItem->price,
                ];
            }

            // Create order in database
            DB::beginTransaction();

            $order = Order::create([
                'order_id' => $orderId,
                'table_id' => $tableId,
                'status' => 'received',
                'total' => $total,
            ]);

            // Create order items
            foreach ($validated['items'] as $item) {
                $menuItem = MenuItem::findOrFail($item['id']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $item['quantity'],
                    'price' => $menuItem->price,
                ]);
            }

            // Update table status
            $table->update(['status' => 'occupied']);

            DB::commit();

            // Create the order message for Kafka
            $orderData = [
                'order_id' => $orderId,
                'table_id' => $tableId,
                'items' => $orderItems,
                'status' => 'received',
                'timestamp' => now()->toIso8601String()
            ];

            // Send the order to Kafka
            $this->producer->send('table-orders', $orderData, "table-{$tableId}");

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => $orderId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to place order', [
                'error' => $e->getMessage(),
                'table_id' => $tableId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View orders for kitchen display
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function kitchenDisplay()
    {
        return view('kitchen-display');
    }
}
