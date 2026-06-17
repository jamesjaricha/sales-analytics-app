<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // List all products
    public function index(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Product Index Error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return redirect()->back()->with('error', 'Unable to load products. Please try again.');
        }
    }    // Show create form
    public function create()
    {
        return view('products.create');
    }

    // Store new product
    public function store(StoreProductRequest $request)
    {
        try {
            // Validation is handled by StoreProductRequest
            Product::create($request->validated());

            return redirect()->route('products.index')->with('success', 'Product created successfully!');
        } catch (\Exception $e) {
            Log::error('Product Store Error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $request->validated(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create product. Please try again.');
        }
    }

    // Show edit form
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    // Update product
    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            DB::beginTransaction();

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

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product Update Error', [
                'message' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => Auth::id(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update product. Please try again.');
        }
    }

    // Toggle product active status (instead of deletion for audit trail)
    public function destroy(Product $product)
    {
        try {
            $newStatus = !$product->is_active;
            $product->update(['is_active' => $newStatus]);

            $message = $newStatus
                ? 'Product activated successfully!'
                : 'Product deactivated successfully!';

            return redirect()->route('products.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Product Toggle Status Error', [
                'message' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => Auth::id(),
            ]);
            return redirect()->back()->with('error', 'Failed to update product status. Please try again.');
        }
    }
}
