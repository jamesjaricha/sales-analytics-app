<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

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
    }

    /**
     * Process stock adjustment
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out,adjustment,purchase,return,initial',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

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

        return redirect()
            ->route('stock.index')
            ->with('success', "Stock adjusted successfully. {$product->name} now has {$product->fresh()->stock_quantity} units.");
    }

    /**
     * Display stock movement history for a product
     */
    public function history(Product $product)
    {
        $movements = $product->stockMovements()
            ->with('user')
            ->paginate(50);

        return view('stock.history', compact('product', 'movements'));
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
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $summary = $this->stockService->getMovementSummary($startDate, $endDate);
        $totalStockValue = $this->stockService->getTotalStockValue();

        $recentMovements = StockMovement::dateRange($startDate, $endDate)
            ->with(['product', 'user'])
            ->latest()
            ->limit(100)
            ->get();

        return view('stock.reports', compact(
            'summary',
            'totalStockValue',
            'recentMovements',
            'startDate',
            'endDate'
        ));
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
        $lowStockCount = $products->filter(fn($p) => $p->isLowStock())->count();
        $outOfStockCount = $products->filter(fn($p) => $p->isOutOfStock())->count();

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

        return $pdf->download('daily-stock-report-' . $date . '.pdf');
    }
}
