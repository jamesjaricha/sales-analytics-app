<?php

namespace App\Http\Controllers;

use App\Models\DailySalesItem;
use App\Models\DailySalesReport;
use App\Services\ReportingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonthlyReportController extends Controller
{
    public function __construct(private readonly ReportingService $reporting) {}

    public function index(Request $request)
    {
        try {
            // Default to current month if not specified
            $month = $request->get('month', now()->format('Y-m'));
            $date = Carbon::parse($month.'-01');

            $analytics = $this->getMonthlyAnalytics($date);

            return view('reports.monthly', compact('analytics', 'month'));
        } catch (\Exception $e) {
            Log::error('Monthly Report Index Error', [
                'message' => $e->getMessage(),
                'month' => $request->get('month'),
            ]);

            return redirect()->back()->with('error', 'Unable to load monthly report. Please try again.');
        }
    }

    public function exportPDF(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $date = Carbon::parse($month.'-01');

            $analytics = $this->getMonthlyAnalytics($date);

            $pdf = Pdf::loadView('reports.monthly-pdf', compact('analytics'))
                ->setPaper('a4', 'portrait');

            $filename = 'monthly-sales-report-'.$date->format('Y-m').'.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Monthly Report PDF Error', [
                'message' => $e->getMessage(),
                'month' => $request->get('month'),
            ]);

            return redirect()->back()->with('error', 'Unable to generate PDF. Please try again.');
        }
    }

    private function getMonthlyAnalytics($date)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $monthName = $date->format('F Y');

        // Get all completed reports for the month
        $reports = DailySalesReport::where('status', 'completed')
            ->whereDate('sale_date', '>=', $startOfMonth)
            ->whereDate('sale_date', '<=', $endOfMonth)
            ->with(['items', 'deductions', 'user'])
            ->orderBy('sale_date')
            ->get();

        // Total Sales Summary
        $totalSales = $reports->sum('total_sales_value');
        $totalDeductions = $reports->sum('total_deductions');
        $netRevenue = $reports->sum('cash_at_hand');
        $reportCount = $reports->count();
        $averageDailySales = $reportCount > 0 ? $totalSales / $reportCount : 0;

        // Top Performing Products (by quantity sold)
        $topProducts = DailySalesItem::whereIn('daily_sales_report_id', $reports->pluck('id'))
            ->select('product_name', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Best Performing Days
        $bestDays = $reports->sortByDesc('total_sales_value')->take(5)->values();

        // Sales by Day of Week
        $salesByDayOfWeek = $reports->groupBy(function ($report) {
            return $report->sale_date->format('l'); // Monday, Tuesday, etc.
        })->map(function ($dayReports) {
            return [
                'count' => $dayReports->count(),
                'total_sales' => $dayReports->sum('total_sales_value'),
                'average_sales' => $dayReports->avg('total_sales_value'),
            ];
        });

        // Sort by day order
        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $salesByDayOfWeek = collect($dayOrder)->mapWithKeys(function ($day) use ($salesByDayOfWeek) {
            return [$day => $salesByDayOfWeek->get($day, ['count' => 0, 'total_sales' => 0, 'average_sales' => 0])];
        });

        // Weekly Performance
        $weeklyPerformance = $reports->groupBy(function ($report) {
            return 'Week '.$report->sale_date->weekOfMonth;
        })->map(function ($weekReports) {
            return [
                'count' => $weekReports->count(),
                'total_sales' => $weekReports->sum('total_sales_value'),
            ];
        });

        // Sales Trend (day by day)
        $salesTrend = $reports->map(function ($report) {
            return [
                'date' => $report->sale_date->format('M d'),
                'sales' => $report->total_sales_value,
            ];
        });

        // Customer Behavior Insights
        $insights = $this->generateInsights($reports, $topProducts, $salesByDayOfWeek, $averageDailySales);

        // Settlement breakdown (POS era — how customers paid)
        $settlement = $this->reporting->settlementBreakdown($startOfMonth, $endOfMonth);

        return [
            'settlement' => $settlement,
            'month_name' => $monthName,
            'start_date' => $startOfMonth->format('M d, Y'),
            'end_date' => $endOfMonth->format('M d, Y'),
            'total_sales' => $totalSales,
            'total_deductions' => $totalDeductions,
            'net_revenue' => $netRevenue,
            'report_count' => $reportCount,
            'average_daily_sales' => $averageDailySales,
            'top_products' => $topProducts,
            'best_days' => $bestDays,
            'sales_by_day_of_week' => $salesByDayOfWeek,
            'weekly_performance' => $weeklyPerformance,
            'sales_trend' => $salesTrend,
            'insights' => $insights,
        ];
    }

    private function generateInsights($reports, $topProducts, $salesByDayOfWeek, $averageDailySales)
    {
        $insights = collect([]);

        // Best selling product
        if ($topProducts->isNotEmpty()) {
            $bestProduct = $topProducts->first();
            $insights->push([
                'title' => 'Best Seller',
                'description' => "{$bestProduct->product_name} was your top product with {$bestProduct->total_quantity} units sold.",
            ]);
        }

        // Best day of the week
        $bestDayOfWeek = $salesByDayOfWeek->filter(function ($data) {
            return $data['count'] > 0;
        })->sortByDesc('average_sales')->first();

        if ($bestDayOfWeek) {
            $dayName = $salesByDayOfWeek->search($bestDayOfWeek);
            $insights->push([
                'title' => 'Peak Day',
                'description' => "{$dayName} is your strongest sales day with an average of ZMW ".number_format($bestDayOfWeek['average_sales'], 2).' per report.',
            ]);
        }

        // Sales consistency
        if ($reports->count() > 1) {
            $variance = $this->calculateVariance($reports->pluck('total_sales_value')->toArray());
            $consistencyLevel = $variance < 1000000 ? 'high' : ($variance < 5000000 ? 'moderate' : 'variable');

            $insights->push([
                'title' => 'Sales Consistency',
                'description' => "Your sales show {$consistencyLevel} consistency this month. ".
                    ($consistencyLevel === 'high' ? 'Great steady performance!' :
                    ($consistencyLevel === 'moderate' ? 'Fairly stable with some variation.' :
                    'Consider analyzing what drives high-performing days.')),
            ]);
        }

        // Product diversity
        $uniqueProducts = $topProducts->count();
        $insights->push([
            'title' => 'Product Range',
            'description' => "You sold {$uniqueProducts} different products this month, ".
                ($uniqueProducts > 5 ? 'showing good product diversity.' : 'focus on expanding your range for better customer reach.'),
        ]);

        // Performance trend
        if ($reports->count() >= 3) {
            $firstHalf = $reports->take(ceil($reports->count() / 2))->avg('total_sales_value');
            $secondHalf = $reports->slice(ceil($reports->count() / 2))->avg('total_sales_value');

            if ($secondHalf > $firstHalf * 1.1) {
                $insights->push([
                    'title' => 'Growth Trend',
                    'description' => 'Sales improved in the second half of the month - momentum is building!',
                ]);
            } elseif ($firstHalf > $secondHalf * 1.1) {
                $insights->push([
                    'title' => 'Attention Needed',
                    'description' => 'Sales dipped in the second half. Consider promotional strategies to boost performance.',
                ]);
            } else {
                $insights->push([
                    'title' => 'Steady Performance',
                    'description' => 'Sales remained consistent throughout the month - reliable performance!',
                ]);
            }
        }

        return $insights;
    }

    private function calculateVariance($numbers)
    {
        $count = count($numbers);
        if ($count === 0) {
            return 0;
        }

        $mean = array_sum($numbers) / $count;
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $numbers)) / $count;

        return $variance;
    }
}
