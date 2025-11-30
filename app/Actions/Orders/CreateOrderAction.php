<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Hold;
use App\Models\Order;
use App\Exceptions\InventoryException;
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
                throw InventoryException::holdExpired();
            }

            if ($hold->converted_to_order_at !== null) {
                throw InventoryException::holdAlreadyUsed();
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
