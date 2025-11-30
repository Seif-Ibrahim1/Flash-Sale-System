<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;

final class GetProductAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function handle(string $id): Product
    {
        // Cache Key Strategy: product:{id}:stock
        // We cache this for 60 seconds OR until explicitly cleared.
        return Cache::remember("product:{$id}", 60, function () use ($id) {
            return Product::query()
                ->select(['id', 'name', 'price', 'total_stock', 'available_stock', 'updated_at'])
                ->findOrFail($id);
        });
    }
}
