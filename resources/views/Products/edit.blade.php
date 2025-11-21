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
                        <!-- Stock Quantity with Add/Subtract -->
                        <div>
                            <label for="stock_quantity" class="form-label">
                                Stock Quantity
                            </label>
                            <div class="space-y-3">
                                <!-- Current Stock Display -->
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p class="text-sm text-blue-700 mb-1">Current Stock</p>
                                    <p class="text-3xl font-bold text-blue-900" id="currentStock">{{ old('stock_quantity', $product->stock_quantity ?? 0) }}</p>
                                </div>

                                <!-- Add/Subtract Controls -->
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-700 mb-3">Adjust Stock</p>
                                    <div class="flex gap-2 mb-3">
                                        <button type="button" onclick="adjustStock('add')" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                            + Add Stock
                                        </button>
                                        <button type="button" onclick="adjustStock('subtract')" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                            - Remove Stock
                                        </button>
                                    </div>
                                    <input type="number"
                                           id="adjustAmount"
                                           min="1"
                                           value="1"
                                           placeholder="Quantity to add/remove"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <!-- Hidden input for form submission -->
                                <input type="hidden"
                                       name="stock_quantity"
                                       id="stock_quantity"
                                       value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}">
                            </div>
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

@push('scripts')
<script>
function adjustStock(action) {
    const currentStockEl = document.getElementById('currentStock');
    const stockInput = document.getElementById('stock_quantity');
    const adjustAmountInput = document.getElementById('adjustAmount');

    let currentStock = parseInt(stockInput.value) || 0;
    let adjustAmount = parseInt(adjustAmountInput.value) || 0;

    if (adjustAmount <= 0) {
        alert('Please enter a valid quantity to adjust');
        return;
    }

    let newStock = currentStock;

    if (action === 'add') {
        newStock = currentStock + adjustAmount;
    } else if (action === 'subtract') {
        newStock = currentStock - adjustAmount;
        if (newStock < 0) {
            if (!confirm('This will result in negative stock (' + newStock + '). Continue?')) {
                return;
            }
        }
    }

    // Update the display and hidden input
    currentStockEl.textContent = newStock;
    stockInput.value = newStock;

    // Flash effect to show change
    currentStockEl.classList.add('scale-110');
    setTimeout(() => {
        currentStockEl.classList.remove('scale-110');
    }, 200);

    // Reset adjustment amount
    adjustAmountInput.value = 1;
}
</script>
@endpush

@endsection
