<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportingService $reporting) {}

    public function index()
    {
        try {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $totalSales = $this->reporting->totalSales($startOfMonth, $endOfMonth);
            $invoiceCount = $this->reporting->invoiceCount($startOfMonth, $endOfMonth);
            $settlement = $this->reporting->settlementBreakdown($startOfMonth, $endOfMonth);
            $topProducts = $this->reporting->topProducts($startOfMonth, $endOfMonth, 5);

            // Today (live)
            $todayStart = Carbon::now()->startOfDay();
            $todayEnd = Carbon::now()->endOfDay();
            $todayTotal = $this->reporting->totalSales($todayStart, $todayEnd);
            $todaySettlement = $this->reporting->settlementBreakdown($todayStart, $todayEnd);

            // Average per active day
            $monthDaily = $this->reporting->dailyTotals($startOfMonth, $endOfMonth);
            $activeDays = collect($monthDaily)->filter(fn ($v) => $v > 0)->count();
            $averageDailySales = $activeDays > 0 ? $totalSales / $activeDays : 0;

            // Last 7 days vs previous 7 days
            $last7 = $this->reporting->dailyTotals(Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay());
            $prev7 = $this->reporting->dailyTotals(Carbon::now()->subDays(13)->startOfDay(), Carbon::now()->subDays(7)->endOfDay());

            $chartLabels = array_map(fn ($d) => Carbon::parse($d)->format('D, M j'), array_keys($last7));
            $last7Values = array_map('floatval', array_values($last7));
            $prev7Values = array_map('floatval', array_values($prev7));

            $last7Total = array_sum($last7Values);
            $prev7Total = array_sum($prev7Values);
            $changePercent = $prev7Total > 0 ? (($last7Total - $prev7Total) / $prev7Total) * 100 : 0;

            return view('dashboard', [
                'totalSales' => $totalSales,
                'invoiceCount' => $invoiceCount,
                'averageDailySales' => $averageDailySales,
                'settlement' => $settlement,
                'todayTotal' => $todayTotal,
                'todaySettlement' => $todaySettlement,
                'topProducts' => $topProducts,
                'chartLabels' => json_encode($chartLabels),
                'last7DaysValues' => json_encode($last7Values),
                'previous7DaysValues' => json_encode($prev7Values),
                'last7DaysTotal' => $last7Total,
                'previous7DaysTotal' => $prev7Total,
                'changePercent' => $changePercent,
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return view('dashboard', [
                'totalSales' => 0,
                'invoiceCount' => 0,
                'averageDailySales' => 0,
                'settlement' => ['cash' => 0, 'bank' => 0, 'mobile_money' => 0, 'outstanding' => 0, 'total' => 0],
                'todayTotal' => 0,
                'todaySettlement' => ['cash' => 0, 'bank' => 0, 'mobile_money' => 0, 'outstanding' => 0, 'total' => 0],
                'topProducts' => collect(),
                'chartLabels' => json_encode([]),
                'last7DaysValues' => json_encode([]),
                'previous7DaysValues' => json_encode([]),
                'last7DaysTotal' => 0,
                'previous7DaysTotal' => 0,
                'changePercent' => 0,
            ])->with('error', 'Unable to load dashboard data. Please try again later.');
        }
    }
}
