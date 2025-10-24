<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesItem extends Model
{
    protected $fillable = [
        'daily_sales_report_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price'
    ];

    public function dailySalesReport()
    {
        return $this->belongsTo(DailySalesReport::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
