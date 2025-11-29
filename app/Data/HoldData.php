<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Hold;
use Carbon\CarbonImmutable;

/**
 * A strictly typed Data Transfer Object for Hold responses.
 * We do not return raw Eloquent models to the controller.
 */
readonly class HoldData
{
    public function __construct(
        public string $hold_id,
        public string $product_id,
        public int $quantity,
        public CarbonImmutable $expires_at,
    ) {}

    public static function fromModel(Hold $hold): self
    {
        return new self(
            hold_id: $hold->id,
            product_id: $hold->product_id,
            quantity: $hold->quantity,
            expires_at: $hold->expires_at,
        );
    }
}
