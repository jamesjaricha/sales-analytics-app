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

        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" action="{{ route('products.index') }}" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search by name, SKU, or category..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Search
                    </button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        @if(request('search') || request('status'))
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-800">
                    <strong>{{ $products->total() }}</strong> {{ Str::plural('result', $products->total()) }} found
                    @if(request('search'))
                        for "<strong>{{ request('search') }}</strong>"
                    @endif
                    @if(request('status'))
                        ({{ request('status') == 'active' ? 'Active' : 'Inactive' }} only)
                    @endif
                </p>
            </div>
        @endif

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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline-block ml-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn {{ $product->is_active ? 'btn-danger' : 'bg-green-600 hover:bg-green-700 text-white' }} btn-sm"
                                                onclick="return confirm('{{ $product->is_active ? 'Deactivate' : 'Activate' }} this product?')">
                                                {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
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
