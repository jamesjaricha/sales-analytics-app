@extends('layouts.app')

@section('content')
<!-- Hidden flash message data -->
@if(session('success'))
    <div data-flash-success="{{ session('success') }}" class="hidden"></div>
@endif
@if(session('error'))
    <div data-flash-error="{{ session('error') }}" class="hidden"></div>
@endif

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-semibold text-gray-900">Record Daily Sales</h1>
            <p class="text-gray-500 mt-2">Enter today's sales data</p>
        </div>

        <!-- Hidden error data for toast notifications -->
        @if($errors->any())
            <div data-validation-errors="{{ json_encode($errors->all()) }}" class="hidden"></div>
        @endif

        <form action="{{ route('sales.store') }}" method="POST" id="salesForm">
            @csrf

            <!-- Sale Date -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sale Date *</label>
                <input type="date" name="sale_date" value="{{ old('sale_date', date('Y-m-d')) }}" 
                    class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>

            <!-- Sales Items -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Sales Items *</h2>
                
                <div class="mb-4">
                    <button type="button" onclick="window.salesForm.addItem()" class="btn-primary">
                        + Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead>
                            <tr class="border-b-2 border-gray-300">
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[35%]">Product Name</th>
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[15%]">Quantity</th>
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[20%]">Unit Price</th>
                                <th class="text-right py-3 px-2 text-sm font-semibold text-gray-700 w-[18%]">Total</th>
                                <th class="text-center py-3 px-2 text-sm font-semibold text-gray-700 w-[12%]"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsContainer">
                            <!-- Items will be added here dynamically -->
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-end">
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Sales Value</p>
                            <p class="text-2xl font-bold text-gray-900">ZMW <span id="totalSalesValue">0.00</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deductions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Deductions</h2>
                    <button type="button" onclick="window.salesForm.addDeduction()"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        + Add Deduction
                    </button>
                </div>

                <div id="deductionsContainer" class="space-y-3">
                    <!-- Deductions will be added here -->
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-end">
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Deductions</p>
                            <p class="text-2xl font-bold text-red-600">ZMW <span id="totalDeductions">0.00</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cash at Hand -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200 p-6 mb-6">
                <div class="text-center">
                    <p class="text-sm font-medium text-green-700 mb-2">Cash at Hand</p>
                    <p class="text-4xl font-bold text-green-900">ZMW <span id="cashAtHand">0.00</span></p>
                </div>
            </div>

            <!-- Submit & Clear Buttons -->
            <div class="flex gap-4 mt-6">
                <button type="button" onclick="window.salesForm.saveDraft()" class="btn-secondary flex-1">
                    Save as Draft
                </button>
                <button type="submit" class="btn-primary flex-1">
                    Submit Report
                </button>
                <!-- Clear Form button removed -->
            </div>

        </form>

    </div>
</div>

<!-- Quick Create Product Modal -->
<div id="createProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) window.salesForm.closeModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Create New Product</h3>
                <button type="button" onclick="window.salesForm.closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <form id="quickCreateForm" onsubmit="window.salesForm.submitQuickCreate(event)" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
                    <input type="text" id="newProductName" 
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm" 
                        placeholder="e.g., Luxpower 6kw Inverter" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price (ZMW) *</label>
                    <input type="number" step="0.01" id="newProductPrice" 
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm" 
                        placeholder="0.00" min="0" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">SKU (Optional)</label>
                    <input type="text" id="newProductSku" 
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm" 
                        placeholder="e.g., LUX-6KW-001">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description (Optional)</label>
                    <textarea id="newProductDescription" rows="3"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm resize-none" 
                        placeholder="Brief product description"></textarea>
                </div>

                <div id="modalError" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
                <button type="button" onclick="window.salesForm.closeModal()" 
                    class="flex-1 px-4 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" id="createBtn"
                    class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                    Create Product
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
@vite('resources/css/sales-form.css')
@endpush

@push('scripts')
<script>
    // Preload routes before the module script runs (avoids race with Vite type="module")
    window.__salesFormRoutes = {
        store: '{{ route("sales.store") }}',
        productSearch: '{{ route("sales.products.search") }}',
        quickCreate: '{{ route("sales.products.quick-create") }}',
        getDraft: '{{ route("sales.drafts.get") }}'
    };
    // Optional: cleanup after module initializes
    window.addEventListener('DOMContentLoaded', function(){
        setTimeout(() => { try { delete window.__salesFormRoutes; } catch (e) {} }, 0);
    });
    </script>
<script>
  sessionStorage.removeItem('sales_form_autosave');
</script>@vite('resources/js/sales-form.js')
@endpush
@endsection
