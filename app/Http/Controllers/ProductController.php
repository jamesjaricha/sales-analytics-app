<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // List all products
    public function index(Request $request)
    {
        $query = Product::query();

        // Search functionality
        if ($search = $request->get('search')) {
            $searchTerm = trim($search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('sku', 'like', '%' . $searchTerm . '%')
                    ->orWhere('category', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '' && $request->status !== null) {
            $query->where('is_active', $request->status == 'active');
        }

        $query->select(['id', 'name', 'sku', 'price', 'stock_quantity', 'category', 'is_active', 'created_at']);

        $products = $query->orderByDesc('is_active') // Show active products first
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString(); // Preserve query parameters in pagination

        return view('products.index', compact('products'));
    }    // Show create form
    public function create()
    {
        return view('products.create');
    }

    // Store new product
    public function store(StoreProductRequest $request)
    {
        // Validation is handled by StoreProductRequest
        Product::create($request->validated());

        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    // Show edit form
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    // Update product
    public function update(UpdateProductRequest $request, Product $product)
    {
        // Track stock quantity changes
        $oldStock = $product->stock_quantity;
        $newStock = $request->validated()['stock_quantity'] ?? $oldStock;

        // Validation is handled by UpdateProductRequest
        $product->update($request->validated());

        // Create stock movement if quantity changed
        if ($product->track_stock && $oldStock != $newStock) {
            $difference = $newStock - $oldStock;
            $type = $difference > 0 ? 'adjustment' : 'adjustment';

            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $difference,
                'stock_before' => $oldStock,
                'stock_after' => $newStock,
                'user_id' => Auth::id(),
                'notes' => 'Stock adjusted via product edit',
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    // Toggle product active status (instead of deletion for audit trail)
    public function destroy(Product $product)
    {
        $newStatus = !$product->is_active;
        $product->update(['is_active' => $newStatus]);

        $message = $newStatus
            ? 'Product activated successfully!'
            : 'Product deactivated successfully!';

        return redirect()->route('products.index')->with('success', $message);
    }
}
