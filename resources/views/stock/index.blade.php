@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-semibold text-gray-900">Stock Management</h1>
                    <p class="text-gray-500 mt-2">Monitor and manage inventory levels</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('stock.daily-pdf', ['date' => now()->format('Y-m-d')]) }}"
                       class="btn btn-secondary" target="_blank">
                        Daily PDF
                    </a>
                    <a href="{{ route('stock.reports') }}" class="btn btn-secondary">
                        Reports
                    </a>
                </div>
            </div>
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Stock Alerts -->
            @if($outOfStockProducts->count() > 0 || $lowStockProducts->count() > 0)
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($outOfStockProducts->count() > 0)
                        <div class="bg-red-50 border-l-4 border-red-500 p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">
                                        {{ $outOfStockProducts->count() }} product(s) out of stock
                                    </p>
                                    <a href="{{ route('stock.low-stock') }}" class="text-sm underline text-red-700 hover:text-red-900">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($lowStockProducts->count() > 0)
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-yellow-800">
                                        {{ $lowStockProducts->count() }} product(s) low on stock
                                    </p>
                                    <a href="{{ route('stock.low-stock') }}" class="text-sm underline text-yellow-700 hover:text-yellow-900">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Stock Summary Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Stock Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded">
                            <p class="text-sm text-gray-600">Total Stock Value</p>
                            <p class="text-2xl font-bold text-blue-600">ZMW {{ number_format($totalStockValue, 2) }}</p>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded">
                            <p class="text-sm text-gray-600">Low Stock Items</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ $lowStockProducts->count() }}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded">
                            <p class="text-sm text-gray-600">Out of Stock</p>
                            <p class="text-2xl font-bold text-red-600">{{ $outOfStockProducts->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">All Products</h3>
                        <form method="GET" action="{{ route('stock.index') }}" class="flex gap-3">
                            <!-- Search Box -->
                            <input type="text" name="search" id="productSearch" placeholder="Search products..."
                                   value="{{ request('search') }}"
                                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
                            <!-- Stock Filter -->
                            <select name="stock_filter" id="stockFilter" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="all" {{ request('stock_filter') == 'all' || !request('stock_filter') ? 'selected' : '' }}>All Products</option>
                                <option value="in_stock" {{ request('stock_filter') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                <option value="low_stock" {{ request('stock_filter') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                                <option value="out_of_stock" {{ request('stock_filter') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">Search</button>
                            @if(request('search') || request('stock_filter'))
                                <a href="{{ route('stock.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                            @endif
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($products as $product)
                                    <tr class="{{ $product->stock_status === 'out_of_stock' ? 'bg-red-50' : ($product->stock_status === 'low_stock' ? 'bg-yellow-50' : '') }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $product->sku ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">{{ $product->stock_quantity }}</div>
                                            <div class="text-xs text-gray-500">Threshold: {{ $product->low_stock_threshold }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($product->stock_status === 'out_of_stock')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Out of Stock
                                                </span>
                                            @elseif($product->stock_status === 'low_stock')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Low Stock
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    In Stock
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $product->unit_of_measurement }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ZMW {{ number_format($product->stock_quantity * (($product->cost > 0) ? $product->cost : $product->price), 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <a href="{{ route('stock.history', $product) }}" class="text-blue-600 hover:text-blue-900">
                                                    History
                                                </a>
                                                @if(auth()->user()->role === 'admin')
                                                    <span class="text-gray-300">|</span>
                                                    <button type="button" 
                                                        data-product-id="{{ $product->id }}"
                                                        data-product-name="{{ $product->name }}"
                                                        data-stock-quantity="{{ $product->stock_quantity }}"
                                                        data-unit="{{ $product->unit_of_measurement }}"
                                                        class="adjust-stock-btn text-green-600 hover:text-green-900">
                                                        Adjust
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            No products found with stock tracking enabled.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                    @if(request('search') || (request('stock_filter') && request('stock_filter') !== 'all'))
                        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                            <strong>Filtered Results:</strong> Showing {{ $products->total() }} product(s) matching your criteria.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div id="adjustStockModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 p-4">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <form id="adjustStockForm" method="POST" action="{{ route('stock.store') }}">
            @csrf
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="modalProductName">Adjust Stock</h3>
            </div>

            <div class="p-6 space-y-4">
                <input type="hidden" name="product_id" id="modalProductId">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock</label>
                    <div class="text-2xl font-bold text-gray-900" id="modalCurrentStock">-</div>
                </div>

                <div>
                    <label for="modalType" class="block text-sm font-medium text-gray-700 mb-2">Movement Type *</label>
                    <select name="type" id="modalType" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="in">Stock In (Increase)</option>
                        <option value="out">Stock Out (Decrease)</option>
                        <option value="purchase">Purchase (Increase)</option>
                        <option value="return">Return (Increase)</option>
                        <option value="adjustment">Adjustment (Decrease)</option>
                        <option value="initial">Initial Stock (Increase)</option>
                    </select>
                </div>

                <div>
                    <label for="modalQuantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                    <input type="number" name="quantity" id="modalQuantity" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="modalUnitCost" class="block text-sm font-medium text-gray-700 mb-2">Unit Cost (Optional)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-500">ZMW</span>
                        <input type="number" name="unit_cost" id="modalUnitCost" step="0.01" min="0" class="w-full pl-14 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label for="modalNotes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="modalNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" onclick="closeAdjustModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="adjustSubmitBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed inline-flex items-center gap-2">
                    <svg id="adjustSpinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span id="adjustSubmitLabel">Adjust Stock</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Event delegation for adjust stock buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.adjust-stock-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const stockQuantity = this.dataset.stockQuantity;
            const unit = this.dataset.unit;
            openAdjustModal(productId, productName, stockQuantity, unit);
        });
    });

    // Prevent double-submit of a stock adjustment (writes to live inventory)
    const adjustForm = document.getElementById('adjustStockForm');
    adjustForm?.addEventListener('submit', function() {
        const btn = document.getElementById('adjustSubmitBtn');
        if (btn) btn.disabled = true;
        document.getElementById('adjustSpinner')?.classList.remove('hidden');
        const label = document.getElementById('adjustSubmitLabel');
        if (label) label.textContent = 'Adjusting…';
    });
});

function openAdjustModal(productId, productName, currentStock, unit) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('modalProductName').textContent = 'Adjust Stock - ' + productName;
    document.getElementById('modalCurrentStock').textContent = currentStock + ' ' + unit;
    document.getElementById('modalQuantity').value = '';
    document.getElementById('modalUnitCost').value = '';
    document.getElementById('modalNotes').value = '';
    const modal = document.getElementById('adjustStockModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAdjustModal() {
    const modal = document.getElementById('adjustStockModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modal on outside click
document.getElementById('adjustStockModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdjustModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAdjustModal();
    }
});
</script>

@endsection
