<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // List all products
    public function index()
    {
        $products = Product::select(['id', 'name', 'sku', 'price', 'stock_quantity', 'category', 'is_active', 'created_at'])
            ->orderBy('name')
            ->paginate(20);
        
        return view('products.index', compact('products'));
    }

    // Show create form
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

    // Delete product (soft delete would be better for business data)
    public function destroy(Product $product)
    {
        // Check if product is used in any sales reports before deletion
        if ($product->dailySalesItems()->exists()) {
            return redirect()->route('products.index')
                ->with('error', 'Cannot delete product that has been used in sales reports. Consider deactivating it instead.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully!');
    }
}
