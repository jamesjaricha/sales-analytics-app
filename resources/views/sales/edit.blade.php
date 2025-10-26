@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">DRAFT</span>
                <h1 class="text-3xl font-semibold text-gray-900">Edit Draft Sales Report</h1>
            </div>
            <p class="text-gray-500">Complete or update this draft report</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-semibold text-red-800">Please fix the following errors:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('sales.update', $report->id) }}" method="POST" id="salesForm">
            @csrf
            @method('PUT')

            <!-- Sale Date -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sale Date *</label>
                <input type="date" name="sale_date" value="{{ old('sale_date', $report->sale_date->format('Y-m-d')) }}" 
                    class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>

            <!-- Sales Items -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Sales Items *</h2>
                
                <div class="mb-4">
                    <button type="button" onclick="addItem()"
                        style="background-color: #16a34a; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none;">
                        + Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead>
                            <tr class="border-b-2 border-gray-300">
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700" style="width: 35%;">Product Name</th>
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700" style="width: 15%;">Quantity</th>
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700" style="width: 20%;">Unit Price</th>
                                <th class="text-right py-3 px-2 text-sm font-semibold text-gray-700" style="width: 18%;">Total</th>
                                <th class="text-center py-3 px-2 text-sm font-semibold text-gray-700" style="width: 12%;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsContainer">
                            <!-- Existing items will be loaded here -->
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
                    <button type="button" onclick="addDeduction()"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        + Add Deduction
                    </button>
                </div>

                <div id="deductionsContainer" class="space-y-3">
                    <!-- Existing deductions will be loaded here -->
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

            <!-- Submit Buttons -->
            <div class="flex gap-4 mt-6">
                <a href="{{ route('sales.index') }}" 
                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold transition text-center">
                    Cancel
                </a>
                <button type="button" onclick="saveDraft()"
                    class="flex-1 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold transition">
                    💾 Save as Draft
                </button>
                <button type="submit" 
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-xl font-semibold transition">
                    ✓ Complete & Submit
                </button>
            </div>

        </form>

    </div>
</div>

<!-- Quick Create Product Modal (same as create.blade.php) -->
<div id="createProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) closeModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Create New Product</h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <form id="quickCreateForm" onsubmit="submitQuickCreate(event)" class="p-6">
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
                <button type="button" onclick="closeModal()" 
                    class="flex-1 px-4 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" id="createBtn"
                    class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                    ✓ Create Product
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Same styles as create.blade.php */
.product-search-wrapper {
    position: relative;
}

.autocomplete-results {
    position: fixed !important;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    max-height: 250px;
    overflow-y: auto;
    z-index: 9999 !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    min-width: 300px;
}

.autocomplete-item {
    padding: 12px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
    font-size: 14px;
}

.autocomplete-item:hover {
    background-color: #f3f4f6;
}

.autocomplete-item strong {
    display: block;
    margin-bottom: 4px;
    color: #111827;
}

.autocomplete-add-new {
    padding: 12px 14px;
    cursor: pointer;
    background-color: #f0fdf4;
    color: #16a34a;
    font-weight: 600;
    border-top: 2px solid #d1fae5;
    font-size: 14px;
}

.autocomplete-add-new:hover {
    background-color: #dcfce7;
}

#itemsContainer td {
    vertical-align: middle;
}

#createProductModal {
    overflow-y: auto;
}

@media (max-width: 640px) {
    #createProductModal > div {
        margin: 0;
        max-width: 100%;
        min-height: 100vh;
        border-radius: 0;
    }
    
    .autocomplete-results {
        min-width: 250px;
        max-width: 90vw;
    }
}

body.modal-open {
    overflow: hidden;
}
</style>

<script>
var itemIndex = 0;
var deductionIndex = 0;
var currentAutocomplete = null;
var currentRow = null;
var formChanged = false;

// Existing report data
var existingItems = @json($report->items);
var existingDeductions = @json($report->deductions);

window.onload = function() {
    // Load existing items
    existingItems.forEach(function(item) {
        addItemWithData(item);
    });
    
    // Load existing deductions
    existingDeductions.forEach(function(deduction) {
        addDeductionWithData(deduction);
    });
    
    // If no items, add one empty
    if (existingItems.length === 0) {
        addItem();
    }
    
    calculateTotals();
    setupFormChangeDetection();
    setupBeforeUnload();
};

function addItemWithData(item) {
    var container = document.getElementById('itemsContainer');
    var row = document.createElement('tr');
    row.className = 'border-b border-gray-100';
    row.dataset.index = itemIndex;
    
    row.innerHTML = `
        <td class="py-3 px-2" style="width: 35%;">
            <div class="product-search-wrapper">
                <input type="text" 
                    class="product-search-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                    value="${item.product_name}"
                    placeholder="Type to search product..." 
                    autocomplete="off"
                    data-row-index="${itemIndex}"
                    oninput="searchProduct(this)"
                    onfocus="searchProduct(this)">
                
                <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-field" value="${item.product_id || ''}">
                <input type="hidden" name="items[${itemIndex}][product_name]" class="product-name-field" value="${item.product_name}" required>
            </div>
        </td>
        <td class="py-3 px-2" style="width: 15%;">
            <input type="number" name="items[${itemIndex}][quantity]" 
                class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                value="${item.quantity}"
                placeholder="0" min="1" required oninput="calculateTotals()">
        </td>
        <td class="py-3 px-2" style="width: 20%;">
            <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" 
                class="price-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                value="${item.unit_price}"
                placeholder="0.00" min="0" required oninput="calculateTotals()">
        </td>
        <td class="py-3 px-2 text-right" style="width: 18%;">
            <span class="text-sm font-semibold text-gray-900 item-total">0.00</span>
        </td>
        <td class="py-3 px-2 text-center" style="width: 12%;">
            <button type="button" onclick="removeItem(this)" 
                class="text-red-600 hover:text-red-800 text-sm font-medium">
                Remove
            </button>
        </td>
    `;
    container.appendChild(row);
    itemIndex++;
}

function addDeductionWithData(deduction) {
    var container = document.getElementById('deductionsContainer');
    var row = document.createElement('div');
    row.className = 'flex gap-4';
    row.innerHTML = `
        <input type="text" name="deductions[${deductionIndex}][description]" 
            value="${deduction.description}"
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" 
            placeholder="e.g., Gel Battery Purchase">
        <input type="number" step="0.01" name="deductions[${deductionIndex}][amount]" 
            value="${deduction.amount}"
            class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" 
            placeholder="0.00" min="0" oninput="calculateTotals()">
        <button type="button" onclick="removeDeduction(this)" 
            class="text-red-600 hover:text-red-800 text-sm font-medium px-2">
            Remove
        </button>
    `;
    container.appendChild(row);
    deductionIndex++;
}

// Same JavaScript functions as create.blade.php (addItem, searchProduct, etc.)
// Copy all the JavaScript from create.blade.php here

function setupFormChangeDetection() {
    const form = document.getElementById('salesForm');
    form.addEventListener('change', () => {
        formChanged = true;
    });
    form.addEventListener('input', () => {
        formChanged = true;
    });
}

function setupBeforeUnload() {
    window.addEventListener('beforeunload', (e) => {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
}

function saveDraft() {
    const form = document.getElementById('salesForm');
    const draftInput = document.createElement('input');
    draftInput.type = 'hidden';
    draftInput.name = 'save_as_draft';
    draftInput.value = '1';
    form.appendChild(draftInput);
    
    formChanged = false;
    form.submit();
}

function addItem() {
    var container = document.getElementById('itemsContainer');
    var row = document.createElement('tr');
    row.className = 'border-b border-gray-100';
    row.dataset.index = itemIndex;
    
    row.innerHTML = `
        <td class="py-3 px-2" style="width: 35%;">
            <div class="product-search-wrapper">
                <input type="text" 
                    class="product-search-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                    placeholder="Type to search product..." 
                    autocomplete="off"
                    data-row-index="${itemIndex}"
                    oninput="searchProduct(this)"
                    onfocus="searchProduct(this)">
                
                <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-field" value="">
                <input type="hidden" name="items[${itemIndex}][product_name]" class="product-name-field" value="" required>
            </div>
        </td>
        <td class="py-3 px-2" style="width: 15%;">
            <input type="number" name="items[${itemIndex}][quantity]" 
                class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                placeholder="0" min="1" value="1" required oninput="calculateTotals()">
        </td>
        <td class="py-3 px-2" style="width: 20%;">
            <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" 
                class="price-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                placeholder="0.00" min="0" value="0" required oninput="calculateTotals()">
        </td>
        <td class="py-3 px-2 text-right" style="width: 18%;">
            <span class="text-sm font-semibold text-gray-900 item-total">0.00</span>
        </td>
        <td class="py-3 px-2 text-center" style="width: 12%;">
            <button type="button" onclick="removeItem(this)" 
                class="text-red-600 hover:text-red-800 text-sm font-medium">
                Remove
            </button>
        </td>
    `;
    container.appendChild(row);
    itemIndex++;
    calculateTotals();
}

var searchTimeout;
function searchProduct(input) {
    clearTimeout(searchTimeout);
    
    var query = input.value.trim();
    
    if (query.length < 2) {
        hideAutocomplete();
        return;
    }
    
    searchTimeout = setTimeout(function() {
        fetch(`{{ route('sales.products.search') }}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(products => {
                displaySearchResults(products, input, query);
            })
            .catch(error => console.error('Error searching products:', error));
    }, 300);
}

function displaySearchResults(products, input, query) {
    hideAutocomplete();
    
    var resultsDiv = document.createElement('div');
    resultsDiv.className = 'autocomplete-results';
    resultsDiv.dataset.inputIndex = input.dataset.rowIndex;
    
    var rect = input.getBoundingClientRect();
    resultsDiv.style.top = (rect.bottom + 2) + 'px';
    resultsDiv.style.left = rect.left + 'px';
    resultsDiv.style.width = Math.max(rect.width, 300) + 'px';
    
    var row = input.closest('tr');
    
    if (products.length === 0) {
        resultsDiv.innerHTML = '<div class="autocomplete-item" style="color: #6b7280;">No products found</div>';
    } else {
        products.forEach(function(product) {
            var item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `
                <strong>${product.name}</strong>
                ${product.sku ? '<span style="color: #6b7280; font-size: 12px;">' + product.sku + '</span>' : ''}
                <div style="color: #16a34a; font-weight: 600; margin-top: 4px;">ZMW ${parseFloat(product.price).toFixed(2)}</div>
            `;
            item.onclick = function() {
                selectProduct(row, product);
                hideAutocomplete();
            };
            resultsDiv.appendChild(item);
        });
    }
    
    var addNew = document.createElement('div');
    addNew.className = 'autocomplete-add-new';
    addNew.innerHTML = `+ Create "${query}" as new product`;
    addNew.onclick = function() {
        openCreateModal(row, query);
        hideAutocomplete();
    };
    resultsDiv.appendChild(addNew);
    
    document.body.appendChild(resultsDiv);
    currentAutocomplete = resultsDiv;
}

function hideAutocomplete() {
    if (currentAutocomplete) {
        currentAutocomplete.remove();
        currentAutocomplete = null;
    }
}

window.addEventListener('scroll', hideAutocomplete);
window.addEventListener('resize', hideAutocomplete);

function selectProduct(row, product) {
    var searchInput = row.querySelector('.product-search-input');
    var productIdField = row.querySelector('.product-id-field');
    var productNameField = row.querySelector('.product-name-field');
    var priceInput = row.querySelector('.price-input');
    
    searchInput.value = product.name;
    productIdField.value = product.id;
    productNameField.value = product.name;
    priceInput.value = parseFloat(product.price).toFixed(2);
    
    calculateTotals();
}

function openCreateModal(row, productName) {
    currentRow = row;
    document.getElementById('newProductName').value = productName;
    document.getElementById('newProductPrice').value = '';
    document.getElementById('newProductSku').value = '';
    document.getElementById('newProductDescription').value = '';
    document.getElementById('modalError').classList.add('hidden');
    document.getElementById('createProductModal').classList.remove('hidden');
    document.body.classList.add('modal-open');
    
    setTimeout(() => {
        document.getElementById('newProductPrice').focus();
    }, 100);
}

function closeModal() {
    document.getElementById('createProductModal').classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentRow = null;
}

function submitQuickCreate(event) {
    event.preventDefault();
    
    var btn = document.getElementById('createBtn');
    var errorDiv = document.getElementById('modalError');
    
    btn.disabled = true;
    btn.textContent = 'Creating...';
    errorDiv.classList.add('hidden');
    
    var formData = {
        name: document.getElementById('newProductName').value,
        price: document.getElementById('newProductPrice').value,
        sku: document.getElementById('newProductSku').value,
        description: document.getElementById('newProductDescription').value,
        _token: '{{ csrf_token() }}'
    };
    
    fetch('{{ route("sales.products.quick-create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            selectProduct(currentRow, data.product);
            closeModal();
        } else {
            throw new Error(data.message || 'Failed to create product');
        }
    })
    .catch(error => {
        errorDiv.textContent = typeof error.message === 'object' ? 'Please fix the errors and try again.' : error.message;
        errorDiv.classList.remove('hidden');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '✓ Create Product';
    });
}

document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('product-search-input') && !e.target.closest('.autocomplete-results')) {
        hideAutocomplete();
    }
});

function removeItem(btn) {
    var container = document.getElementById('itemsContainer');
    if (container.children.length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    } else {
        alert('You must have at least one item');
    }
}

function addDeduction() {
    var container = document.getElementById('deductionsContainer');
    var row = document.createElement('div');
    row.className = 'flex gap-4';
    row.innerHTML = `
        <input type="text" name="deductions[${deductionIndex}][description]" 
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" 
            placeholder="e.g., Gel Battery Purchase">
        <input type="number" step="0.01" name="deductions[${deductionIndex}][amount]" 
            class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" 
            placeholder="0.00" min="0" value="0" oninput="calculateTotals()">
        <button type="button" onclick="removeDeduction(this)" 
            class="text-red-600 hover:text-red-800 text-sm font-medium px-2">
            Remove
        </button>
    `;
    container.appendChild(row);
    deductionIndex++;
    calculateTotals();
}

function removeDeduction(btn) {
    btn.parentElement.remove();
    calculateTotals();
}

function calculateTotals() {
    var totalSales = 0;
    var items = document.querySelectorAll('#itemsContainer tr');
    items.forEach(function(item) {
        var quantity = parseFloat(item.querySelector('.quantity-input').value) || 0;
        var price = parseFloat(item.querySelector('.price-input').value) || 0;
        var itemTotal = quantity * price;
        item.querySelector('.item-total').textContent = itemTotal.toFixed(2);
        totalSales += itemTotal;
    });

    var totalDeductions = 0;
    var deductions = document.querySelectorAll('#deductionsContainer input[name*="[amount]"]');
    deductions.forEach(function(deduction) {
        total
