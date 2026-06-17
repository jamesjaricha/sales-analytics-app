<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'cost',
        'stock_quantity',
        'category',
        'is_active',
        'low_stock_threshold',
        'reorder_level',
        'reorder_quantity',
        'unit_of_measurement',
        'track_stock',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function dailySalesItems()
    {
        return $this->hasMany(DailySalesItem::class);
    }

    /**
     * Get all stock movements for this product
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    /**
     * Check if product is low on stock
     */
    public function isLowStock()
    {
        if (! $this->track_stock) {
            return false;
        }

        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock()
    {
        if (! $this->track_stock) {
            return false;
        }

        return $this->stock_quantity <= 0;
    }

    /**
     * Get stock status
     */
    protected function stockStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->track_stock) {
                    return 'not_tracked';
                }

                if ($this->stock_quantity <= 0) {
                    return 'out_of_stock';
                }

                if ($this->stock_quantity <= $this->low_stock_threshold) {
                    return 'low_stock';
                }

                return 'in_stock';
            }
        );
    }

    /**
     * Get stock status badge color
     */
    protected function stockStatusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->stock_status) {
                'out_of_stock' => 'red',
                'low_stock' => 'yellow',
                'in_stock' => 'green',
                'not_tracked' => 'gray',
                default => 'gray',
            }
        );
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
            ->whereRaw('stock_quantity <= low_stock_threshold');
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_stock', true)
            ->where('stock_quantity', '<=', 0);
    }
}
