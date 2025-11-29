<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inventory\CreateHoldAction;
use App\Actions\Inventory\GetProductAction;
use App\Http\Requests\HoldRequest;
use Exception;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function show(string $id, GetProductAction $action): JsonResponse
    {
        $product = $action->handle($id);

        return response()->json([
            'data' => $product,
        ]);
    }

    public function reserve(HoldRequest $request, CreateHoldAction $action): JsonResponse
    {
        // 1. Extract validated strict data
        $productId = $request->validated('product_id');
        $qty = (int) $request->validated('qty');

        try {
            // 2. Delegate to Business Logic (Action)
            $hold = $action->handle($productId, $qty);

            return response()->json([
                'message' => 'Hold created successfully',
                'data' => $hold,
            ], 201); // 201 Created

        } catch (Exception $e) {
            // 3. Handle Business Failures (e.g., Stock ran out)
            // We return 409 Conflict for concurrency/state issues.
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 409);
        }
    }
}
