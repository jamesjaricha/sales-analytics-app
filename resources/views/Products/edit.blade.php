@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-4 mb-4">
                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Products
                </a>
            </div>
            <h1 class="text-3xl font-semibold text-gray-900">Edit Product</h1>
            <p class="text-gray-500 mt-2">Update {{ $product->name }}</p>
        </div>

        <!-- Form -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div>
                        <label for="name" class="form-label">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name', $product->name) }}"
                               class="form-input @error('name') form-input-error @enderror"
                               required>
                        @error('name')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SKU -->
                    <div>
                        <label for="sku" class="form-label">
                            SKU (Stock Keeping Unit) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="sku" 
                               id="sku" 
                               value="{{ old('sku', $product->sku) }}"
                               class="form-input @error('sku') form-input-error @enderror"
                               required>
                        @error('sku')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="form-label">
                            Description
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="3"
                                  class="form-input @error('description') form-input-error @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Price -->
                        <div>
                            <label for="price" class="form-label">
                                Price (ZMW) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="price" 
                                   id="price" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('price', $product->price) }}"
                                   class="form-input @error('price') form-input-error @enderror"
                                   required>
                            @error('price')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cost -->
                        <div>
                            <label for="cost" class="form-label">
                                Cost (ZMW)
                            </label>
                            <input type="number" 
                                   name="cost" 
                                   id="cost" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('cost', $product->cost) }}"
                                   class="form-input @error('cost') form-input-error @enderror">
                            @error('cost')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Stock Quantity -->
                        <div>
                            <label for="stock_quantity" class="form-label">
                                Stock Quantity
                            </label>
                            <input type="number" 
                                   name="stock_quantity" 
                                   id="stock_quantity" 
                                   min="0"
                                   value="{{ old('stock_quantity', $product->stock_quantity) }}"
                                   class="form-input @error('stock_quantity') form-input-error @enderror">
                            @error('stock_quantity')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="form-label">
                                Category
                            </label>
                            <input type="text" 
                                   name="category" 
                                   id="category" 
                                   value="{{ old('category', $product->category) }}"
                                   class="form-input @error('category') form-input-error @enderror">
                            @error('category')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-4 pt-6">
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection