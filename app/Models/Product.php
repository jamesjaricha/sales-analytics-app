<?php

namespace App\Models;

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
        'is_active'
    ];

    public function dailySalesItems()
    {
        return $this->hasMany(DailySalesItem::class);
    }
}
