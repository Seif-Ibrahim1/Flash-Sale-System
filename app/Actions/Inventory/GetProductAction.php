<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetProductAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function handle(string $id): Product
    {
        // We select only necessary fields.
        // The 'available_stock' is the source of truth for the frontend.
        return Product::query()
            ->select(['id', 'name', 'price', 'available_stock'])
            ->findOrFail($id);
    }
}
