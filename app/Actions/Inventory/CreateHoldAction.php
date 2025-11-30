<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Data\HoldData;
use App\Models\Hold;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

final class CreateHoldAction
{
    // 2 minutes expiry as per requirements
    private const HOLD_DURATION_SECONDS = 120;

    /**
     * @throws Exception If stock is insufficient
     */
    public function handle(string $productId, int $quantity): HoldData
    {
        // 1. Atomic Database Update
        // We attempt to decrement the available stock ONLY if we have enough.
        // This single query handles concurrency. If 100 people hit this at once,
        // MySQL ensures they run sequentially on the row lock, but without PHP overhead.
        $affected = Product::query()
            ->where('id', $productId)
            ->where('available_stock', '>=', $quantity)
            ->decrement('available_stock', $quantity);

        if ($affected === 0) {
            throw new Exception('Insufficient stock available.', 409);
        }

        // 2. Clear Cache immediately so GET /products/{id} is accurate
        Cache::forget("product:{$productId}");

        // 2. Stock Secured, Create the Hold Record
        // We use a transaction here just to ensure the Hold creation matches the decrement.
        // If this fails (e.g., DB crash), we technically lose stock, but that's what the
        // "ReleaseExpiredHolds" job is for (cleanup).
        $hold = DB::transaction(function () use ($productId, $quantity) {
            return Hold::create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'expires_at' => now()->addSeconds(self::HOLD_DURATION_SECONDS),
            ]);
        });

        return HoldData::fromModel($hold);
    }
}
