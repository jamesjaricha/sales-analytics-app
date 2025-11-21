<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;

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
        // Validation is handled by UpdateProductRequest
        $product->update($request->validated());

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
