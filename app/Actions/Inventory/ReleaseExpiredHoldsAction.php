<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ReleaseExpiredHoldsAction
{
    public function handle(): int
    {
        // 1. Transaction is mandatory to prevent "Double Release" race conditions
        return DB::transaction(function () {
            // 2. Lock and fetch expired holds to prevent other jobs from picking them up
            // "forUpdate()" locks these rows until the transaction finishes.
            $expiredHolds = Hold::query()
                ->where('expires_at', '<=', now())
                ->whereNull('converted_to_order_at')
                ->lockForUpdate()
                ->get();

            if ($expiredHolds->isEmpty()) {
                return 0;
            }

            // 3. Group by Product to do 1 update query per product (Bulk Optimization)
            $restorationMap = $expiredHolds->groupBy('product_id');

            foreach ($restorationMap as $productId => $holds) {
                $qtyToRestore = $holds->sum('quantity');

                Product::query()
                    ->where('id', $productId)
                    ->increment('available_stock', $qtyToRestore);

                Log::info("Restored {$qtyToRestore} stock for Product {$productId}");
            }

            // 4. Delete the holds (or soft delete if you need history)
            // We use the ID list to be precise.
            Hold::whereIn('id', $expiredHolds->pluck('id'))->delete();

            return $expiredHolds->count();
        });
    }
}
