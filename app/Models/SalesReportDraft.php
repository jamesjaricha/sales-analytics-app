<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReportDraft extends Model
{
    protected $fillable = [
        'user_id',
        'sale_date',
        'form_data',
        'total_sales_value',
        'total_deductions',
        'cash_at_hand',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'form_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
