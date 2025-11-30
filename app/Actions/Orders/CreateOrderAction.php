<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Hold;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;

final readonly class CreateOrderAction
{
    /**
     * Converts a Hold into a Pending Order.
     * @throws Exception
     */
    public function handle(string $holdId): Order
    {
        return DB::transaction(function () use ($holdId) {
            // 1. Lock the hold to prevent race conditions (Double Usage)
            $hold = Hold::lockForUpdate()->find($holdId);

            // 2. Validation Checks
            if (! $hold) {
                throw new Exception('Hold not found.', 404);
            }

            if ($hold->isExpired()) {
                throw new Exception('Hold has expired. Please reserve again.', 400);
            }

            if ($hold->converted_to_order_at !== null) {
                throw new Exception('This hold has already been used for an order.', 409);
            }

            // 3. Create the Order
            $order = Order::create([
                'hold_id' => $hold->id,
                'product_id' => $hold->product_id,
                'status' => OrderStatus::PENDING,
                'total_amount' => $hold->product->price * $hold->quantity,
            ]);

            // 4. Mark Hold as Used
            $hold->update(['converted_to_order_at' => now()]);

            return $order;
        });
    }
}
