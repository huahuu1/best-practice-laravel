<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;

class TableApiController extends Controller
{
    /**
     * Get all tables
     *
     * @return JsonResponse
     */
    public function getTables(): JsonResponse
    {
        $tables = Table::all()->map(function ($table) {
            return [
                'id' => $table->id,
                'name' => $table->name,
                'capacity' => $table->capacity,
                'status' => $table->status
            ];
        });

        return response()->json(['tables' => $tables]);
    }

    /**
     * Get a specific table by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getTable(int $id): JsonResponse
    {
        $table = Table::findOrFail($id);
        return response()->json($table);
    }

    /**
     * Get all available menu items
     *
     * @return JsonResponse
     */
    public function getMenuItems(): JsonResponse
    {
        $items = MenuItem::where('available', true)->get();
        return response()->json($items);
    }
}
