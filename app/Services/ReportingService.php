<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\DailySalesReport;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reporting aggregations that UNION the two eras without double-counting:
 *   - POS era: granular rows in `sales` / `sale_items`.
 *   - Legacy/batch era: `daily_sales_reports` (approved_at IS NULL) + `daily_sales_items`.
 *
 * Day-end reports (approved_at IS NOT NULL) are NEVER summed for totals, because
 * the invoices they reconcile already live in `sales`.
 */
class ReportingService
{
    /**
     * Total sales value for the period (POS invoices + legacy batch reports).
     */
    public function totalSales(Carbon $start, Carbon $end): float
    {
        $pos = (float) Sale::completed()
            ->whereBetween('business_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum('total_amount');

        $legacy = (float) DailySalesReport::whereNull('approved_at')
            ->whereBetween('sale_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum('total_sales_value');

        return $pos + $legacy;
    }

    /**
     * Number of POS invoices in the period.
     */
    public function invoiceCount(Carbon $start, Carbon $end): int
    {
        return Sale::completed()
            ->whereBetween('business_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count();
    }

    /**
     * Settlement breakdown (POS era only — legacy batch sales had no payment method).
     *
     * @return array{cash: float, bank: float, mobile_money: float, outstanding: float, total: float}
     */
    public function settlementBreakdown(Carbon $start, Carbon $end): array
    {
        $sales = Sale::completed()
            ->whereBetween('business_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get(['payment_method', 'total_amount', 'amount_due']);

        $byMethod = fn (PaymentMethod $m): float => (float) $sales
            ->filter(fn (Sale $s) => $s->payment_method === $m)
            ->sum(fn (Sale $s) => (float) $s->total_amount);

        $cash = $byMethod(PaymentMethod::Cash);
        $bank = $byMethod(PaymentMethod::Bank);
        $mobile = $byMethod(PaymentMethod::MobileMoney);
        $outstanding = (float) $sales
            ->filter(fn (Sale $s) => $s->payment_method === PaymentMethod::Credit)
            ->sum(fn (Sale $s) => (float) $s->amount_due);

        return [
            'cash' => $cash,
            'bank' => $bank,
            'mobile_money' => $mobile,
            'outstanding' => $outstanding,
            'total' => $cash + $bank + $mobile + $outstanding,
        ];
    }

    /**
     * Top products by quantity, unioning POS sale_items + legacy daily_sales_items.
     */
    public function topProducts(Carbon $start, Carbon $end, int $limit = 5): Collection
    {
        $pos = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.business_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('sale_items.product_name')
            ->select(
                'sale_items.product_name as product_name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.total_price) as revenue'),
            )
            ->get();

        $legacy = DB::table('daily_sales_items')
            ->join('daily_sales_reports', 'daily_sales_reports.id', '=', 'daily_sales_items.daily_sales_report_id')
            ->whereNull('daily_sales_reports.approved_at')
            ->whereBetween('daily_sales_reports.sale_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('daily_sales_items.product_name')
            ->select(
                'daily_sales_items.product_name as product_name',
                DB::raw('SUM(daily_sales_items.quantity) as qty'),
                DB::raw('SUM(daily_sales_items.total_price) as revenue'),
            )
            ->get();

        return $pos->concat($legacy)
            ->groupBy('product_name')
            ->map(fn (Collection $rows, string $name) => (object) [
                'product_name' => $name,
                'total_quantity' => (int) $rows->sum('qty'),
                'total_revenue' => (float) $rows->sum('revenue'),
            ])
            ->sortByDesc('total_quantity')
            ->take($limit)
            ->values();
    }

    /**
     * Per-day sales totals for the range (both eras), keyed by Y-m-d, every day present.
     *
     * @return array<string, float>
     */
    public function dailyTotals(Carbon $start, Carbon $end): array
    {
        $totals = [];
        for ($day = $start->copy()->startOfDay(); $day <= $end; $day->addDay()) {
            $totals[$day->toDateString()] = 0.0;
        }

        Sale::completed()
            ->whereBetween('business_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get(['business_date', 'total_amount'])
            ->each(function (Sale $s) use (&$totals): void {
                $key = $s->business_date->toDateString();
                $totals[$key] = ($totals[$key] ?? 0.0) + (float) $s->total_amount;
            });

        DailySalesReport::whereNull('approved_at')
            ->whereBetween('sale_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get(['sale_date', 'total_sales_value'])
            ->each(function (DailySalesReport $r) use (&$totals): void {
                $key = $r->sale_date->toDateString();
                $totals[$key] = ($totals[$key] ?? 0.0) + (float) $r->total_sales_value;
            });

        return $totals;
    }
}
