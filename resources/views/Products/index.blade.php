@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-semibold text-gray-900">Products</h1>
                    <p class="text-gray-500 mt-2">Manage your product catalog</p>
                </div>
                <a href="{{ route('products.create') }}" 
                    style="background-color: green !important; color: white !important; padding: 12px 24px !important; border-radius: 8px !important; font-weight: 600 !important; text-decoration: none !important; display: inline-block !important;">
                    + Add Product
                </a>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-gray-300">
                        <tr>
                            <th class="text-center py-4 px-6 text-sm font-semibold text-gray-700 border-r border-gray-200">Product Name</th>
                            <th class="text-center py-4 px-6 text-sm font-semibold text-gray-700 border-r border-gray-200">SKU</th>
                            <th class="text-center py-4 px-6 text-sm font-semibold text-gray-700 border-r border-gray-200">Price</th>
                            <th class="text-center py-4 px-6 text-sm font-semibold text-gray-700 border-r border-gray-200">Stock</th>
                            <th class="text-center py-4 px-6 text-sm font-semibold text-gray-700 border-r border-gray-200">Category</th>
                            <th class="text-center py-4 px-6 text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-4 px-6 text-sm text-gray-900 text-center border-r border-gray-200">
                                    {{ $product->name }}
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600 text-center border-r border-gray-200">
                                    {{ $product->sku }}
                                </td>
                                <td class="py-4 px-6 text-sm font-semibold text-gray-900 text-center border-r border-gray-200">
                                    ZMW {{ number_format($product->price, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm text-center border-r border-gray-200">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $product->stock_quantity > 10 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $product->stock_quantity }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600 text-center border-r border-gray-200">
                                    {{ $product->category ?? '-' }}
                                </td>
                                <td class="py-4 px-6 text-sm text-center">
                                    <div class="flex justify-center gap-3">
                                        <a href="{{ route('products.edit', $product->id) }}" 
                                            style="color: green; font-weight: 600; text-decoration: none;">
                                            Edit
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-500">
                                    <p class="text-lg mb-2">No products yet</p>
                                    <a href="{{ route('products.create') }}" 
                                        style="color: green; font-weight: 600; text-decoration: none;">
                                        Add your first product →
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
