<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesReport extends Model
{
    protected $fillable = [
        'user_id',           // Add this if missing
        'sale_date',
        'total_sales_value',
        'total_deductions',
        'cash_at_hand',
        'notes'
    ];

    protected $casts = [
        'sale_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(DailySalesItem::class);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }
}
