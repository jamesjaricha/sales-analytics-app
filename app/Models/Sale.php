<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'user_id',
        'business_date',
        'payment_method',
        'total_amount',
        'amount_due',
        'paid_amount',
        'paid_via',
        'customer_name',
        'note',
        'status',
        'day_end_report_id',
    ];

    protected $casts = [
        'business_date' => 'date',
        'payment_method' => PaymentMethod::class,
        'total_amount' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function dayEndReport(): BelongsTo
    {
        return $this->belongsTo(DailySalesReport::class, 'day_end_report_id');
    }

    /**
     * Completed (non-void) invoices.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Invoices for a given trading day.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('business_date', $date);
    }

    /**
     * Invoices not yet rolled into an approved day-end.
     */
    public function scopeUnreconciled(Builder $query): Builder
    {
        return $query->whereNull('day_end_report_id');
    }

    /**
     * Once linked to an approved day-end, the invoice is locked.
     */
    public function isLocked(): bool
    {
        return $this->day_end_report_id !== null;
    }
}
