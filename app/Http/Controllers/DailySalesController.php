<?php

namespace App\Http\Controllers;

use App\Models\DailySalesReport;
use App\Models\Product;
use App\Models\SalesReportDraft;
use App\Models\StockMovement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailySalesController extends Controller
{
    // Show the form to create a new daily sales report
    public function create()
    {
        // Get monthly totals for current month up to today
        $today = date('Y-m-d');
        $monthlyTotals = DailySalesReport::getMonthlyTotalsUpToDate($today, null);

        // Pass session lifetime to view for countdown timer (in minutes)
        $sessionLifetime = config('session.lifetime', 600);

        return view('sales.create', compact('monthlyTotals', 'sessionLifetime'));
    }

    // Store the daily sales report
    public function store(Request $request)
    {
        $isDraft = $request->boolean('save_as_draft');

        // Validation: relax rules for drafts
        $rules = [
            'sale_date' => ($isDraft
                ? 'required|date'
                : 'required|date|unique:daily_sales_reports,sale_date'),
            'items' => $isDraft ? 'nullable|array' : 'required|array|min:1',
            'items.*.product_name' => $isDraft ? 'nullable|string' : 'required|string',
            'items.*.quantity' => $isDraft ? 'nullable|integer|min:1' : 'required|integer|min:1',
            'items.*.unit_price' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
        ];

        $messages = [
            'items.*.product_name.required' => 'Please enter a product name for each sales item.',
            'items.*.quantity.required' => 'Please enter a quantity for each sales item.',
            'items.*.unit_price.required' => 'Please enter a unit price for each sales item.',
            'items.required' => 'Please add at least one sales item.',
        ];

        $validated = $request->validate($rules, $messages);

        DB::beginTransaction();

        try {
            // Calculate totals safely
            $totalSalesValue = 0;
            if ($request->filled('items')) {
                foreach ($request->items as $item) {
                    $qty = isset($item['quantity']) ? (int) $item['quantity'] : 0;
                    $price = isset($item['unit_price']) ? (float) $item['unit_price'] : 0.0;
                    $totalSalesValue += $qty * $price;
                }
            }

            $totalDeductions = 0;
            if ($request->has('deductions')) {
                foreach ($request->deductions as $deduction) {
                    if (! empty($deduction['amount'])) {
                        $totalDeductions += $deduction['amount'];
                    }
                }
            }

            $cashAtHand = $totalSalesValue - $totalDeductions;

            // Drafts now live in a separate table
            if ($isDraft) {
                $formData = $request->except(['_token']);
                $draft = SalesReportDraft::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'sale_date' => $request->sale_date,
                    ],
                    [
                        'form_data' => $formData,
                        'total_sales_value' => $totalSalesValue,
                        'total_deductions' => $totalDeductions,
                        'cash_at_hand' => $cashAtHand,
                        'notes' => $request->notes,
                    ]
                );
                DB::commit();

                return redirect()->route('sales.create')
                    ->with('success', 'Draft saved. You can continue editing anytime.');
            }

            // On full submit, remove draft if any and create completed report
            $existingDraft = SalesReportDraft::where('user_id', Auth::id())
                ->whereDate('sale_date', $request->sale_date)
                ->first();

            // Create fresh completed report
            $report = DailySalesReport::create([
                'user_id' => Auth::id(),
                'sale_date' => $request->sale_date,
                'total_sales_value' => $totalSalesValue,
                'total_deductions' => $totalDeductions,
                'cash_at_hand' => $cashAtHand,
                'notes' => $request->notes,
                'status' => 'completed',
            ]);

            // Save sales items
            if ($request->filled('items')) {
                foreach ($request->items as $item) {
                    if (empty($item['product_name'])) {
                        continue; // skip incomplete draft rows
                    }
                    $qty = isset($item['quantity']) ? (int) $item['quantity'] : 0;
                    $price = isset($item['unit_price']) ? (float) $item['unit_price'] : 0.0;

                    $salesItem = $report->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'],
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_price' => $qty * $price,
                    ]);

                    // Reduce stock and create stock movement if product_id exists
                    if (! empty($item['product_id']) && $qty > 0) {
                        $product = Product::find($item['product_id']);
                        if ($product && $product->track_stock) {
                            $stockBefore = $product->stock_quantity;
                            $product->stock_quantity -= $qty;
                            $product->save();

                            // Create stock movement record
                            StockMovement::create([
                                'product_id' => $product->id,
                                'type' => 'out',
                                'quantity' => -$qty,
                                'stock_before' => $stockBefore,
                                'stock_after' => $product->stock_quantity,
                                'notes' => 'Sale - Report #'.$report->id,
                                'user_id' => Auth::id(),
                                'reference_type' => 'App\\Models\\DailySalesReport',
                                'reference_id' => $report->id,
                            ]);
                        }
                    }
                }
            }

            // Save deductions
            if ($request->has('deductions')) {
                foreach ($request->deductions as $deduction) {
                    if (! empty($deduction['description']) && ! empty($deduction['amount'])) {
                        $report->deductions()->create([
                            'description' => $deduction['description'],
                            'amount' => $deduction['amount'],
                        ]);
                    }
                }
            }

            // If there was a draft for this date, delete it now
            if ($existingDraft) {
                $existingDraft->delete();
            }

            DB::commit();

            // Redirect based on user role - with success confirmation
            if (Auth::user()->role === 'admin') {
                return redirect()->route('sales.show', $report->id)
                    ->with('success', 'Daily sales report saved successfully!')
                    ->with('show_success_modal', true);
            } else {
                // Sales reps see their report then can navigate to their sales list
                return redirect()->route('sales.show', $report->id)
                    ->with('success', 'Daily sales report saved successfully!')
                    ->with('show_success_modal', true)
                    ->with('redirect_to_my_sales', true);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return back()->with('error', 'Error saving report: '.$e->getMessage())->withInput();
        }
    }

    // List all daily sales reports
    public function index(Request $request)
    {
        $query = DailySalesReport::with('user');

        // Filter by month
        if ($request->filled('month')) {
            $month = $request->month;
            $query->whereYear('sale_date', substr($month, 0, 4))
                ->whereMonth('sale_date', substr($month, 5, 2));
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $reports = $query->orderBy('sale_date', 'desc')->paginate(15);

        // Get all users for filter dropdown
        $users = User::orderBy('name')->get();

        return view('sales.index', compact('reports', 'users'));
    }

    // List sales for the logged-in user (sales_rep)
    public function mySales()
    {
        $reports = DailySalesReport::with('user')
            ->where('user_id', Auth::id())
            ->orderBy('sale_date', 'desc')
            ->paginate(15);

        return view('sales.my-sales', compact('reports'));
    }

    // Show detailed view of a specific sales report
    public function show($id)
    {
        $report = DailySalesReport::with(['items', 'deductions', 'user', 'sales.items'])->findOrFail($id);

        $lineItems = $this->resolveLineItems($report);

        // Get monthly totals up to this report's date
        // Get monthly totals for ALL users (company-wide), not just this user
        $monthlyTotals = DailySalesReport::getMonthlyTotalsUpToDate($report->sale_date, null);

        return view('sales.show', compact('report', 'monthlyTotals', 'lineItems'));
    }

    // Export sales report to PDF
    public function exportPDF($id)
    {
        $report = DailySalesReport::with(['items', 'deductions', 'user', 'sales.items'])->findOrFail($id);

        $lineItems = $this->resolveLineItems($report);

        // Get monthly totals up to this report's date for PDF
        // Get monthly totals for ALL users (company-wide) for PDF
        $monthlyTotals = DailySalesReport::getMonthlyTotalsUpToDate($report->sale_date, null);

        $pdf = Pdf::loadView('sales.pdf', compact('report', 'monthlyTotals', 'lineItems'));

        $filename = 'sales-report-'.$report->sale_date->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Resolve a report's line items: legacy daily_sales_items when present,
     * otherwise the POS sale_items from the report's linked invoices (day-end).
     */
    private function resolveLineItems(DailySalesReport $report)
    {
        return $report->items->isNotEmpty()
            ? $report->items
            : $report->sales->flatMap(fn ($sale) => $sale->items);
    }

    // Search products for autocomplete
    public function searchProducts(Request $request)
    {
        $query = $request->get('q', '');

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('sku', 'LIKE', "%{$query}%");
            })
            ->where(function ($q) {
                // Exclude out-of-stock items
                $q->where('track_stock', false)
                    ->orWhere('stock_quantity', '>', 0);
            })
            ->limit(10)
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'track_stock']);

        return response()->json($products);
    }

    // Fetch a draft for a given date for the current user
    public function getDraft(Request $request)
    {
        // Authorization handled by middleware

        $date = $request->query('date');
        if (! $date) {
            return response()->json(['success' => false, 'message' => 'date is required'], 422);
        }

        $draft = SalesReportDraft::where('user_id', Auth::id())
            ->whereDate('sale_date', $date)
            ->first();

        if (! $draft) {
            return response()->json(['success' => false]);
        }

        return response()->json([
            'success' => true,
            'draft' => [
                'sale_date' => $draft->sale_date->format('Y-m-d'),
                'form_data' => $draft->form_data,
                'totals' => [
                    'total_sales_value' => $draft->total_sales_value,
                    'total_deductions' => $draft->total_deductions,
                    'cash_at_hand' => $draft->cash_at_hand,
                ],
            ],
        ]);
    }

    public function quickCreateProduct(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:products,name',
                'price' => 'required|numeric|min:0',
                'sku' => 'nullable|string|max:100|unique:products,sku',
                'description' => 'nullable|string|max:1000',
            ]);

            $product = Product::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'sku' => $validated['sku'] ?? 'SKU-'.time(),
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'product' => $product,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: '.$e->getMessage(),
            ], 500);
        }
    }
}
