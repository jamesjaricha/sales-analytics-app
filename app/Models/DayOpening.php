<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayOpening extends Model
{
    protected $fillable = [
        'business_date',
        'opening_balance',
        'opened_by',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opening_balance' => 'decimal:2',
    ];

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * The opening for a trading day, if one has been captured.
     */
    public static function forDate(string $businessDate): ?self
    {
        return self::whereDate('business_date', $businessDate)->first();
    }
}
