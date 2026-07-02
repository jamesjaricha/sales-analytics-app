<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    protected $fillable = [
        'daily_sales_report_id',
        'description',
        'amount',
        'payment_method',
    ];

    public function dailySalesReport()
    {
        return $this->belongsTo(DailySalesReport::class);
    }
}
