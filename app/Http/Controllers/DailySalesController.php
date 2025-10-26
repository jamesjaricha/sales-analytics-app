<?php

namespace App\Http\Controllers;

use App\Models\DailySalesReport;
use App\Models\SalesReportDraft;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DailySalesController extends Controller
{
    // Show the form to create a new daily sales report
    public function create()
    {
        $products = Product::where('is_active', true)->get();
        return view('sales.create', compact('products'));
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
                    if (!empty($deduction['amount'])) {
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
                    $report->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'],
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_price' => $qty * $price,
                    ]);
                }
            }

            // Save deductions
            if ($request->has('deductions')) {
                foreach ($request->deductions as $deduction) {
                    if (!empty($deduction['description']) && !empty($deduction['amount'])) {
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

            return redirect()->route('sales.index')->with('success', 'Daily sales report saved successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error saving report: ' . $e->getMessage())->withInput();
        }
    }

    // List all daily sales reports
    public function index()
    {
        $reports = DailySalesReport::with('user')
            ->orderBy('sale_date', 'desc')
            ->paginate(15);
        
        return view('sales.index', compact('reports'));
    }

    // Show detailed view of a specific sales report
    public function show($id)
    {
        $report = DailySalesReport::with(['items', 'deductions', 'user'])->findOrFail($id);
        return view('sales.show', compact('report'));
    }

    // Export sales report to PDF
    public function exportPDF($id)
    {
        $report = DailySalesReport::with(['items', 'deductions', 'user'])->findOrFail($id);
        
        $pdf = Pdf::loadView('sales.pdf', compact('report'));
        
        $filename = 'sales-report-' . $report->sale_date->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    // Search products for autocomplete
public function searchProducts(Request $request)
{
    $query = $request->get('q', '');
    
    $products = Product::where('is_active', true)
        ->where(function($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('sku', 'LIKE', "%{$query}%");
        })
        ->limit(10)
        ->get(['id', 'name', 'sku', 'price']);
    
    return response()->json($products);
}

    // Fetch a draft for a given date for the current user
    public function getDraft(Request $request)
    {
        $this->authorize('viewAny', DailySalesReport::class); // or ensure auth middleware

        $date = $request->query('date');
        if (!$date) {
            return response()->json(['success' => false, 'message' => 'date is required'], 422);
        }

        $draft = SalesReportDraft::where('user_id', Auth::id())
            ->whereDate('sale_date', $date)
            ->first();

        if (!$draft) {
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
    $request->validate([
        'name' => 'required|string|max:255|unique:products,name',
        'price' => 'required|numeric|min:0',
        'sku' => 'nullable|string|max:100|unique:products,sku',
    ]);

    $product = Product::create([
        'name' => $request->name,
        'price' => $request->price,
        'sku' => $request->sku,
        'description' => $request->description,
        'is_active' => true,
    ]);

    return response()->json([
        'success' => true,
        'product' => $product
    ]);
}


}

