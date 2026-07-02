<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    protected $fillable = [
        'sale_id',
        'amount',
        'payment_method',
        'business_date',
        'received_by',
        'note',
        'day_end_report_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'business_date' => 'date',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function dayEndReport(): BelongsTo
    {
        return $this->belongsTo(DailySalesReport::class, 'day_end_report_id');
    }

    /**
     * Payments received on a given trading day.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('business_date', $date);
    }

    /**
     * Payments not yet rolled into an approved day-end.
     */
    public function scopeUnreconciled(Builder $query): Builder
    {
        return $query->whereNull('day_end_report_id');
    }
}
