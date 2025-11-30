<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HoldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'hold_id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => (int) $this->quantity,
            'expires_at' => $this->expires_at->toIso8601String(),
            'expires_in_seconds' => $this->expires_at->diffInSeconds(now(), absolute: false) * -1,
        ];
    }
}
