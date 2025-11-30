<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price_formatted' => number_format((float) $this->price, 2),
            'available_stock' => (int) $this->available_stock,
            'last_updated' => $this->updated_at?->toIso8601String(),
        ];
    }
}
