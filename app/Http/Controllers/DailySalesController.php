<?php

namespace App\Http\Controllers;

use App\Models\DailySalesReport;
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
        $request->validate([
            'sale_date' => 'required|date|unique:daily_sales_reports,sale_date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            // Calculate totals
            $totalSalesValue = 0;
            foreach ($request->items as $item) {
                $totalSalesValue += $item['quantity'] * $item['unit_price'];
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

            // Create daily sales report
            $report = DailySalesReport::create([
                'user_id' => Auth::id(),
                'sale_date' => $request->sale_date,
                'total_sales_value' => $totalSalesValue,
                'total_deductions' => $totalDeductions,
                'cash_at_hand' => $cashAtHand,
                'notes' => $request->notes,
            ]);

            // Save sales items
            foreach ($request->items as $item) {
                $report->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
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

