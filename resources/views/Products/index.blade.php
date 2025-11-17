@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-semibold text-gray-900">Products</h1>
                    <p class="text-gray-500 mt-2">Manage your product catalog</p>
                </div>
                <a href="{{ route('products.create') }}" class="btn btn-primary">Add Product</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            @if($products->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($products as $product)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $product->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $product->sku }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">ZMW {{ number_format($product->price, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $product->stock_quantity ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $product->category ?: 'Uncategorized' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline-block ml-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($products->hasPages())
                    <div class="px-4 py-3 border-t">{{ $products->links() }}</div>
                @endif
            @else
                <div class="text-center py-12">
                    <h3 class="text-sm font-medium text-gray-900">No products</h3>
                    <p class="text-sm text-gray-500">Get started by creating your first product.</p>
                    <div class="mt-6">
                        <a href="{{ route('products.create') }}" class="btn btn-primary">Add Product</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
