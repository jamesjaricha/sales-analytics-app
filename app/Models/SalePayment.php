<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One tender line of a split-payment invoice: the portion of the total the
 * customer settled through a single method (cash, bank or mobile money).
 */
class SalePayment extends Model
{
    protected $fillable = [
        'method',
        'amount',
    ];

    protected $casts = [
        'method' => PaymentMethod::class,
        'amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
