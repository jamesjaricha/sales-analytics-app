<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display stock overview dashboard
     */
    public function index(Request $request)
    {
        try {
            $lowStockProducts = $this->stockService->getLowStockProducts();
            $outOfStockProducts = $this->stockService->getOutOfStockProducts();
            $totalStockValue = $this->stockService->getTotalStockValue();

            $query = Product::where('track_stock', true)
                ->where('is_active', true);

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%");
                });
            }

            // Apply stock status filter
            if ($request->filled('stock_filter') && $request->stock_filter !== 'all') {
                $stockFilter = $request->stock_filter;

                if ($stockFilter === 'in_stock') {
                    $query->where('stock_quantity', '>', 0)
                        ->whereRaw('stock_quantity > low_stock_threshold');
                } elseif ($stockFilter === 'low_stock') {
                    $query->where('stock_quantity', '>', 0)
                        ->whereRaw('stock_quantity <= low_stock_threshold');
                } elseif ($stockFilter === 'out_of_stock') {
                    $query->where('stock_quantity', '<=', 0);
                }
            }

            $products = $query->orderBy('name')->paginate(30);

            return view('stock.index', compact(
                'products',
                'lowStockProducts',
                'outOfStockProducts',
                'totalStockValue'
            ));
        } catch (\Exception $e) {
            Log::error('Stock Index Error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('error', 'Unable to load stock data. Please try again.');
        }
    }

    /**
     * Process stock adjustment
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'type' => 'required|in:in,out,adjustment,purchase,return,initial',
                'quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string|max:500',
                'unit_cost' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);

            // Calculate adjustment quantity based on type
            $adjustmentQuantity = match ($request->type) {
                'in', 'purchase', 'return', 'initial' => $request->quantity,
                'out', 'adjustment' => -$request->quantity,
                default => $request->quantity,
            };

            $this->stockService->adjustStock(
                product: $product,
                quantity: $adjustmentQuantity,
                type: $request->type,
                userId: Auth::id(),
                notes: $request->notes,
                unitCost: $request->unit_cost
            );

            DB::commit();

            return redirect()
                ->route('stock.index')
                ->with('success', "Stock adjusted successfully. {$product->name} now has {$product->fresh()->stock_quantity} units.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Re-throw validation exceptions
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock Adjustment Error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'product_id' => $request->product_id ?? null,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to adjust stock. Please try again.');
        }
    }

    /**
     * Display stock movement history for a product
     */
    public function history(Product $product, Request $request)
    {
        try {
            [$startDate, $endDate, $period] = $this->resolvePeriod($request, 'all');
            $type = $request->input('type') ?: null;

            $movements = $product->stockMovements()
                ->when($period !== 'all', fn ($q) => $q->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]))
                ->when($type, fn ($q) => $q->where('type', $type))
                ->with('user')
                ->latest()
                ->paginate(50)
                ->appends($request->query());

            return view('stock.history', compact('product', 'movements', 'startDate', 'endDate', 'period', 'type'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Stock History Error', [
                'message' => $e->getMessage(),
                'product_id' => $product->id,
            ]);

            return redirect()->back()->with('error', 'Unable to load stock history. Please try again.');
        }
    }

    /**
     * Display low stock alerts
     */
    public function lowStock()
    {
        $lowStockProducts = $this->stockService->getLowStockProducts();

        return view('stock.low-stock', compact('lowStockProducts'));
    }

    /**
     * Display stock reports
     */
    public function reports(Request $request)
    {
        try {
            [$startDate, $endDate, $period] = $this->resolvePeriod($request);
            $type = $request->input('type') ?: null;
            $productId = $request->input('product_id') ?: null;

            $summary = $this->stockService->getMovementSummary($startDate, $endDate);
            $totalStockValue = $this->stockService->getTotalStockValue();

            $recentMovements = StockMovement::dateRange($startDate, $endDate)
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when($productId, fn ($q) => $q->where('product_id', $productId))
                ->with(['product', 'user'])
                ->latest()
                ->paginate(50)
                ->appends($request->query());

            $products = Product::where('track_stock', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            return view('stock.reports', compact(
                'summary',
                'totalStockValue',
                'recentMovements',
                'startDate',
                'endDate',
                'period',
                'type',
                'productId',
                'products'
            ));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Stock Reports Error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('error', 'Unable to load stock reports. Please try again.');
        }
    }

    /**
     * Resolve a reporting window from a period preset (today/week/month/all)
     * or explicit custom dates. Returns [startDate, endDate, period].
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolvePeriod(Request $request, string $default = 'month'): array
    {
        // Reject malformed filters up front so bad input can never reach
        // Carbon::parse (a 500) or the download filename.
        $request->validate([
            'period' => ['nullable', 'in:today,week,month,all,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(StockMovement::typeOptions()))],
            'product_id' => ['nullable', 'integer'],
        ]);

        $period = $request->input('period', $default);
        $today = Carbon::now();

        return match ($period) {
            'today' => [$today->toDateString(), $today->toDateString(), 'today'],
            'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString(), 'week'],
            'all' => ['2000-01-01', $today->toDateString(), 'all'],
            'custom' => [
                $request->input('start_date', $today->copy()->startOfMonth()->toDateString()),
                $request->input('end_date', $today->toDateString()),
                'custom',
            ],
            default => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString(), 'month'],
        };
    }

    /**
     * Stream the filtered stock movements as a CSV (memory-safe via chunking).
     */
    public function exportMovements(Request $request): StreamedResponse
    {
        [$startDate, $endDate] = $this->resolvePeriod($request);
        $type = $request->input('type') ?: null;
        $productId = $request->input('product_id') ?: null;

        $filename = 'stock-movements-'.$startDate.'-to-'.$endDate.'.csv';

        return response()->streamDownload(function () use ($startDate, $endDate, $type, $productId) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Product', 'SKU', 'Type', 'Quantity', 'Before', 'After', 'User', 'Notes']);

            StockMovement::dateRange($startDate, $endDate)
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when($productId, fn ($q) => $q->where('product_id', $productId))
                ->with(['product', 'user'])
                // Unique ordering: created_at ties across a chunk boundary
                // would skip/duplicate rows with offset pagination.
                ->orderByDesc('id')
                ->chunk(500, function ($movements) use ($out) {
                    foreach ($movements as $m) {
                        fputcsv($out, [
                            $m->created_at->format('Y-m-d H:i'),
                            $m->product->name ?? '-',
                            $m->product->sku ?? '-',
                            $m->type_label,
                            $m->quantity,
                            $m->stock_before,
                            $m->stock_after,
                            $m->user->name ?? 'System',
                            $m->notes ?? '',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Export daily stock report to PDF
     */
    public function dailyStockPDF(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        // Get all products with stock tracking
        $products = Product::where('track_stock', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get stock movements for the day
        $movements = StockMovement::whereDate('created_at', $date)
            ->with(['product', 'user'])
            ->latest()
            ->get();

        // Calculate totals
        $totalStockValue = $this->stockService->getTotalStockValue();
        $lowStockCount = $products->filter(fn ($p) => $p->isLowStock())->count();
        $outOfStockCount = $products->filter(fn ($p) => $p->isOutOfStock())->count();

        // Movement summary for the day
        $movementSummary = [
            'total_movements' => $movements->count(),
            'stock_in' => $movements->where('type', 'in')->sum('quantity') +
                $movements->where('type', 'purchase')->sum('quantity') +
                $movements->where('type', 'return')->sum('quantity'),
            'stock_out' => $movements->where('type', 'out')->sum('quantity') +
                abs($movements->where('type', 'sale')->sum('quantity')) +
                abs($movements->where('type', 'adjustment')->sum('quantity')),
        ];

        $pdf = Pdf::loadView('stock.daily-pdf', compact(
            'date',
            'products',
            'movements',
            'totalStockValue',
            'lowStockCount',
            'outOfStockCount',
            'movementSummary'
        ));

        return $pdf->download('daily-stock-report-'.$date.'.pdf');
    }
}
