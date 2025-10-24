@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-semibold text-gray-900">Add New Product</h1>
            <p class="text-gray-500 mt-2">Create a new product in your catalog</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('products.store') }}" method="POST">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                
                <!-- Product Name -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="e.g., Clamps">
                </div>

                <!-- SKU -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">SKU (Product Code) *</label>
                    <input type="text" name="sku" value="{{ old('sku') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="e.g., CLMP-001">
                </div>

                <!-- Price and Cost -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price (ZMW) *</label>
                        <input type="number" step="0.01" name="price" value="{{ old('price') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cost Price (ZMW)</label>
                        <input type="number" step="0.01" name="cost" value="{{ old('cost') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="0.00">
                    </div>
                </div>

                <!-- Stock and Category -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                        <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <input type="text" name="category" value="{{ old('category') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="e.g., Electronics">
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Product description (optional)">{{ old('description') }}</textarea>
                </div>

            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('products.index') }}"
                    style="background-color: #6b7280 !important; color: white !important; padding: 12px 24px !important; border-radius: 8px !important; font-weight: 600 !important; text-decoration: none !important; display: inline-block !important;">
                    Cancel
                </a>
                <button type="submit"
                    style="background-color: #16a34a !important; color: white !important; padding: 12px 32px !important; border-radius: 8px !important; font-weight: 600 !important; border: none !important; cursor: pointer !important;">
                    Save Product
                </button>
            </div>

        </form>

    </div>
</div>
@endsection
