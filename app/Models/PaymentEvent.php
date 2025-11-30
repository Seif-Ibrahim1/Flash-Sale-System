<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'response_summary' => 'array',
    ];
}
