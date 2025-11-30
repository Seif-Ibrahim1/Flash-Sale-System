<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\CreateOrderAction;
use App\Http\Requests\StoreOrderRequest;
use Exception;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        try {
            $order = $action->handle($request->validated('hold_id'));

            return response()->json([
                'message' => 'Order created successfully. Proceed to payment.',
                'order_id' => $order->id,
                'amount' => $order->total_amount,
            ], 201);

        } catch (Exception $e) {
            // We return 400 Bad Request for business logic errors (expired hold, etc.)
            return response()->json([
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }
}
