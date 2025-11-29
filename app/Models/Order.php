<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $hold_id
 * @property OrderStatus $status
 * @property float $total_amount
 */
class Order extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'hold_id',
        'product_id',
        'status',
        'total_amount',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'total_amount' => 'float',
    ];

    public function hold(): BelongsTo
    {
        return $this->belongsTo(Hold::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
