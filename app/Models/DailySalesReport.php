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
        'notes',
        'status',
        // Day-end reconciliation (POS settlement breakdown)
        'total_cash',
        'total_bank',
        'total_mobile_money',
        'total_outstanding',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'approved_at' => 'datetime',
        'total_sales_value' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'cash_at_hand' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_bank' => 'decimal:2',
        'total_mobile_money' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
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

    /**
     * POS invoices reconciled into this day-end report.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'day_end_report_id');
    }

    /**
     * Admin who approved the day-end.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * A day-end is approved once it has been signed off (approved_at set).
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Get cumulative monthly totals up to and including the specified date
     *
     * @param  string|\DateTime  $date  The date to calculate totals up to
     * @param  int|null  $userId  Optional user ID to filter by specific user
     * @return array ['total_sales' => float, 'total_deductions' => float, 'cash_at_hand' => float, 'report_count' => int]
     */
    public static function getMonthlyTotalsUpToDate($date, $userId = null)
    {
        $date = $date instanceof \DateTime ? $date : new \DateTime($date);
        $startOfMonth = (clone $date)->modify('first day of this month')->format('Y-m-d');
        $endDate = $date->format('Y-m-d');

        $query = self::where('status', 'completed')
            ->whereDate('sale_date', '>=', $startOfMonth)
            ->whereDate('sale_date', '<=', $endDate);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $reports = $query->get();

        return [
            'total_sales' => $reports->sum('total_sales_value'),
            'total_deductions' => $reports->sum('total_deductions'),
            'cash_at_hand' => $reports->sum('cash_at_hand'),
            'report_count' => $reports->count(),
            'month_name' => $date->format('F Y'),
            'start_date' => $startOfMonth,
            'end_date' => $endDate,
        ];
    }
}
