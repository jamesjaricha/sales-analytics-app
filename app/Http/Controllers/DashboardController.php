<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailySalesReport;
use App\Models\DailySalesItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Get current month's data (using exact same approach as monthly report)
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Get completed reports for this month (exact same query as monthly report)
            // Cache for 5 minutes to reduce database load on shared hosting
            $cacheKey = 'dashboard_reports_' . $startOfMonth->format('Y-m');
            $completedReports = Cache::remember($cacheKey, 300, function () use ($startOfMonth, $endOfMonth) {
                return DailySalesReport::where('status', 'completed')
                    ->whereDate('sale_date', '>=', $startOfMonth)
                    ->whereDate('sale_date', '<=', $endOfMonth)
                    ->select(['id', 'sale_date', 'total_sales_value', 'cash_at_hand', 'user_id'])
                    ->get();
            });

            // Total sales this month (gross sales - same as reports)
            $totalSales = $completedReports->sum('total_sales_value');

            // Total sales reports this month
            $totalReports = $completedReports->count();

            // Average daily sales (same calculation as monthly report)
            $averageDailySales = $totalReports > 0 ? $totalSales / $totalReports : 0;

            // Total items sold this month (from completed reports only)
            $reportIds = $completedReports->pluck('id');
            $totalOrders = $reportIds->isNotEmpty() 
                ? DailySalesItem::whereIn('daily_sales_report_id', $reportIds)->sum('quantity')
                : 0;

            // Last 7 Days Comparison
            $last7DaysStart = Carbon::now()->subDays(6)->startOfDay();
            $last7DaysEnd = Carbon::now()->endOfDay();
            $previous7DaysStart = Carbon::now()->subDays(13)->startOfDay();
            $previous7DaysEnd = Carbon::now()->subDays(7)->endOfDay();

            // Last 7 days data - optimized query
            $last7DaysSales = DailySalesReport::whereBetween('sale_date', [$last7DaysStart, $last7DaysEnd])
                ->select(['sale_date', 'total_sales_value', 'cash_at_hand'])
                ->orderBy('sale_date')
                ->get();

            // Previous 7 days data - optimized query
            $previous7DaysSales = DailySalesReport::whereBetween('sale_date', [$previous7DaysStart, $previous7DaysEnd])
                ->select(['sale_date', 'total_sales_value', 'cash_at_hand'])
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
            if ($reportIds->isNotEmpty()) {
                $topProducts = DailySalesItem::whereIn('daily_sales_report_id', $reportIds)
                    ->select('product_name', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_revenue'))
                    ->groupBy('product_name')
                    ->orderByDesc('total_quantity')
                    ->limit(5)
                    ->get();
            } else {
                $topProducts = collect();
            }




            return view('dashboard', [
                'totalSales' => $totalSales,
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
        } catch (\Exception $e) {
            Log::error('Dashboard Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Return dashboard with default/empty values on error
            return view('dashboard', [
                'totalSales' => 0,
                'totalReports' => 0,
                'averageDailySales' => 0,
                'totalOrders' => 0,
                'chartLabels' => json_encode([]),
                'last7DaysValues' => json_encode([]),
                'previous7DaysValues' => json_encode([]),
                'last7DaysCash' => json_encode([]),
                'previous7DaysCash' => json_encode([]),
                'last7DaysTotal' => 0,
                'previous7DaysTotal' => 0,
                'changePercent' => 0,
                'topProducts' => collect(),
            ])->with('error', 'Unable to load dashboard data. Please try again later.');
        }
    }
}
