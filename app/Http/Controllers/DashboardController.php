<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailySalesReport;
use App\Models\DailySalesItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get current month's data (using exact same approach as monthly report)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Get completed reports for this month (exact same query as monthly report)
        $completedReports = DailySalesReport::where('status', 'completed')
            ->whereDate('sale_date', '>=', $startOfMonth)
            ->whereDate('sale_date', '<=', $endOfMonth)
            ->get();

        // Total sales this month (gross sales - same as reports)
        $totalSales = $completedReports->sum('total_sales_value');

        // Total revenue this month (net after deductions)
        $totalRevenue = $completedReports->sum('cash_at_hand');

        // Total sales reports this month
        $totalReports = $completedReports->count();

        // Average daily sales (same calculation as monthly report)
        $averageDailySales = $totalReports > 0 ? $totalSales / $totalReports : 0;

        // Total items sold this month (from completed reports only)
        $totalOrders = DailySalesItem::whereIn('daily_sales_report_id', $completedReports->pluck('id'))
            ->sum('quantity');

        // Last 7 Days Comparison
        $last7DaysStart = Carbon::now()->subDays(6)->startOfDay();
        $last7DaysEnd = Carbon::now()->endOfDay();
        $previous7DaysStart = Carbon::now()->subDays(13)->startOfDay();
        $previous7DaysEnd = Carbon::now()->subDays(7)->endOfDay();

        // Last 7 days data
        $last7DaysSales = DailySalesReport::whereBetween('sale_date', [$last7DaysStart, $last7DaysEnd])
            ->orderBy('sale_date')
            ->get();

        // Previous 7 days data
        $previous7DaysSales = DailySalesReport::whereBetween('sale_date', [$previous7DaysStart, $previous7DaysEnd])
            ->orderBy('sale_date')
            ->get();

        // Prepare chart data
        $chartLabels = [];
        $last7DaysValues = [];
        $previous7DaysValues = [];
        $last7DaysCash = [];
        $previous7DaysCash = [];

        for ($i = 6; $i >= 0; $i--) {
    // Last 7 days
    $date = Carbon::now()->subDays($i);
    $chartLabels[] = $date->format('D, M j');
    
    $report = $last7DaysSales->first(function($item) use ($date) {
        return $item->sale_date->isSameDay($date);
    });
    
    $last7DaysValues[] = $report ? floatval($report->total_sales_value) : 0;
    $last7DaysCash[] = $report ? floatval($report->cash_at_hand) : 0;

    // Previous 7 days
    $prevDate = Carbon::now()->subDays($i + 7);
    
    $prevReport = $previous7DaysSales->first(function($item) use ($prevDate) {
        return $item->sale_date->isSameDay($prevDate);
    });
    
    $previous7DaysValues[] = $prevReport ? floatval($prevReport->total_sales_value) : 0;
    $previous7DaysCash[] = $prevReport ? floatval($prevReport->cash_at_hand) : 0;
}


        // Calculate totals
        $last7DaysTotal = array_sum($last7DaysValues);
        $previous7DaysTotal = array_sum($previous7DaysValues);
        $changePercent = $previous7DaysTotal > 0 ? (($last7DaysTotal - $previous7DaysTotal) / $previous7DaysTotal) * 100 : 0;

        // Top 5 products this month (from completed reports only - same as monthly report)
        $topProducts = DailySalesItem::whereIn('daily_sales_report_id', $completedReports->pluck('id'))
            ->select('product_name', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('product_name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();




        return view('dashboard', [
            'totalSales' => $totalSales,
            'totalRevenue' => $totalRevenue,
            'totalReports' => $totalReports,
            'averageDailySales' => $averageDailySales,
            'totalOrders' => $totalOrders,
            'chartLabels' => json_encode($chartLabels),
            'last7DaysValues' => json_encode($last7DaysValues),
            'previous7DaysValues' => json_encode($previous7DaysValues),
            'last7DaysCash' => json_encode($last7DaysCash),
            'previous7DaysCash' => json_encode($previous7DaysCash),
            'last7DaysTotal' => $last7DaysTotal,
            'previous7DaysTotal' => $previous7DaysTotal,
            'changePercent' => $changePercent,
            'topProducts' => $topProducts,
        ]);
    }
}
