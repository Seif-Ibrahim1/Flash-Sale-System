<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $product_id
 * @property int $quantity
 * @property CarbonImmutable $expires_at
 * @property ?CarbonImmutable $converted_to_order_at
 */
class Hold extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_id',
        'quantity',
        'expires_at',
        'converted_to_order_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expires_at' => 'immutable_datetime',
        'converted_to_order_at' => 'immutable_datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired() && $this->converted_to_order_at === null;
    }
}
