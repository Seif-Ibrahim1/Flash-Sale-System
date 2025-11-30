<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Data\HoldData;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\InventoryException;
use Illuminate\Support\Facades\Log;

final readonly class CreateHoldAction
{
    // 2 minutes expiry as per requirements
    private const HOLD_DURATION_SECONDS = 120;

    public function handle(string $productId, int $quantity): Hold
    {
        // WRAP EVERYTHING IN TRANSACTION
        // This ensures "All or Nothing". We never lose stock if the script crashes.
        return DB::transaction(function () use ($productId, $quantity) {
            // 1. Atomic Database Update
            // We attempt to decrement the available stock ONLY if we have enough.
            // This single query handles concurrency. If 100 people hit this at once,
            // MySQL ensures they run sequentially on the row lock, but without PHP overhead.
            $affected = Product::query()
                ->where('id', $productId)
                ->where('available_stock', '>=', $quantity)
                ->decrement('available_stock', $quantity);

            if ($affected === 0) {
                throw InventoryException::insufficientStock($productId);
            }

            // 2. Stock Secured, Create the Hold Record
            // We use a transaction here just to ensure the Hold creation matches the decrement.
            // If this fails (e.g., DB crash), we technically lose stock, but that's what the
            // "ReleaseExpiredHolds" job is for (cleanup).
            try {
                $hold = Hold::create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'expires_at' => now()->addSeconds(self::HOLD_DURATION_SECONDS),
                ]);
            } catch (\Exception $e) {
                throw $e;
            }

            try {
                Cache::forget("product:{$productId}");
            } catch (\Exception $e) {
                // Structured Logging
                Log::warning('Cache clear failed', [
                    'product_id' => $productId,
                    'error' => $e->getMessage()
                ]);
            }

            return $hold;
        });
    }
}
