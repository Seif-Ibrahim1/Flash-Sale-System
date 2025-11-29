<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property float $price
 * @property int $total_stock
 * @property int $available_stock
 */
class Product extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'price',
        'total_stock',
        'available_stock',
    ];

    protected $casts = [
        'price' => 'float',
        'total_stock' => 'integer',
        'available_stock' => 'integer',
    ];

    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }
}
