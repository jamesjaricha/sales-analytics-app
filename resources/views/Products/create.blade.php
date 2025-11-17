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
            <h1 class="text-3xl font-semibold text-gray-900">Add New Product</h1>
            <p class="text-gray-500 mt-2">Create a new product in your catalog</p>
        </div>

        @if($errors->any())
            <div class="alert alert-error mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('products.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="form-label">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}"
                               class="form-input @error('name') form-input-error @enderror"
                               placeholder="e.g., Clamps"
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
                               value="{{ old('sku') }}"
                               class="form-input @error('sku') form-input-error @enderror"
                               placeholder="e.g., CLMP-001"
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
                                  class="form-input @error('description') form-input-error @enderror"
                                  placeholder="Optional product description">{{ old('description') }}</textarea>
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
                                   value="{{ old('price') }}"
                                   class="form-input @error('price') form-input-error @enderror"
                                   placeholder="0.00"
                                   required>
                            @error('price')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cost -->
                        <div>
                            <label for="cost" class="form-label">
                                Cost (ZMW) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="cost" 
                                   id="cost" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('cost') }}"
                                   class="form-input @error('cost') form-input-error @enderror"
                                   placeholder="0.00"
                                   required>
                            @error('cost')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Stock Quantity -->
                    <div>
                        <label for="stock_quantity" class="form-label">
                            Stock Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="stock_quantity" 
                               id="stock_quantity" 
                               min="0"
                               value="{{ old('stock_quantity', 0) }}"
                               class="form-input @error('stock_quantity') form-input-error @enderror"
                               required>
                        @error('stock_quantity')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Product
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
