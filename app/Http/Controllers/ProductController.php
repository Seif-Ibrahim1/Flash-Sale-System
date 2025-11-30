<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inventory\CreateHoldAction;
use App\Actions\Inventory\GetProductAction;
use App\Http\Requests\HoldRequest;
use App\Http\Resources\HoldResource;
use App\Http\Resources\ProductResource;
use Exception;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function show(string $id, GetProductAction $action): ProductResource
    {
        // Automatically wrapped in 'data' key by Laravel
        return new ProductResource($action->handle($id));
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
                'data' => new HoldResource($hold),
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
