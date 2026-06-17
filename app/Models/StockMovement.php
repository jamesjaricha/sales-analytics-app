<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_id',
        'reference_type',
        'notes',
        'unit_cost',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Get the product that this movement belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who made this movement
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the polymorphic reference (sales item, purchase, etc.)
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scope for filtering by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        // Ensure dates include full day range
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();

        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Get movement type badge color
     */
    protected function typeBadgeColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->type) {
                'in', 'purchase', 'return' => 'green',
                'out', 'sale' => 'red',
                'adjustment' => 'yellow',
                'initial' => 'blue',
                default => 'gray',
            }
        );
    }

    /**
     * Get movement type label
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->type) {
                'in' => 'Stock In',
                'out' => 'Stock Out',
                'adjustment' => 'Adjustment',
                'sale' => 'Sale',
                'purchase' => 'Purchase',
                'return' => 'Return',
                'initial' => 'Initial Stock',
                default => ucfirst($this->type),
            }
        );
    }
}
