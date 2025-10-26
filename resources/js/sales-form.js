/**
 * ============================================
 * SALES FORM - ENHANCED JAVASCRIPT
 * ============================================
 */

// Configuration
const CONFIG = {
    AUTOSAVE_KEY: 'sales_form_autosave',
    AUTOSAVE_INTERVAL: 10000, // 10 seconds
    RESTORE_EXPIRY_HOURS: 24,
    SEARCH_DELAY: 300,
};

// Global variables
let itemIndex = 0;
let deductionIndex = 0;
let currentAutocomplete = null;
let currentRow = null;
let formChanged = false;

// CSRF Token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// Routes (these will be set from the blade template)
let routes = {
    store: '',
    productSearch: '',
    quickCreate: ''
};

// Track form changes
function setupFormChangeDetection() {
    const form = document.getElementById('salesForm');
    if (!form) return;
    
    form.addEventListener('change', () => {
        formChanged = true;
    });
    form.addEventListener('input', () => {
        formChanged = true;
    });
    
    // Allow form submission without warning
    form.addEventListener('submit', () => {
        formChanged = false;
    });
}

// Warn before leaving if form has unsaved changes
function setupBeforeUnload() {
    window.addEventListener('beforeunload', (e) => {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
}

// Check for flash messages and show as toast
function checkForFlashMessages() {
    const successMessage = document.querySelector('[data-flash-success]');
    const errorMessage = document.querySelector('[data-flash-error]');
    const validationErrors = document.querySelector('[data-validation-errors]');
    
    if (successMessage) {
        showToast(successMessage.dataset.flashSuccess, 'success');
    }
    if (errorMessage) {
        showToast(errorMessage.dataset.flashError, 'error');
    }
    if (validationErrors) {
        const errors = JSON.parse(validationErrors.dataset.validationErrors);
        // Show each error as a toast
        errors.forEach((error, index) => {
            setTimeout(() => {
                showToast(error, 'error');
            }, index * 500); // Stagger the toasts by 500ms
        });
    }
}

// Save as draft
function saveDraft() {
    if (!confirm('Save this sales report as a draft? You can complete it later.')) {
        return;
    }
    
    const form = document.getElementById('salesForm');
    const draftInput = document.createElement('input');
    draftInput.type = 'hidden';
    draftInput.name = 'save_as_draft';
    draftInput.value = '1';
    form.appendChild(draftInput);
    
    formChanged = false;
    form.submit();
}

// Show toast notification
function showToast(message, type = 'success') {
    // Create a new toast element for each message
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 mb-3`;
    
    const icon = type === 'success' 
        ? '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
        : '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
    
    toast.innerHTML = `${icon}<span>${message}</span>`;
    
    toastContainer.appendChild(toast);
    
    // Auto-remove after duration (longer for errors)
    const duration = type === 'error' ? 6000 : 4000;
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            toast.remove();
            // Remove container if empty
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        }, 300);
    }, duration);
}

// Create toast container if it doesn't exist
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position: fixed; top: 1rem; right: 1rem; z-index: 10000;';
    document.body.appendChild(container);
    return container;
}

// Add sales item row
function addItem() {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-100';
    row.dataset.index = itemIndex;
    
    row.innerHTML = `
        <td class="py-3 px-2 w-[35%]">
            <div class="product-search-wrapper">
                <input type="text" 
                    class="product-search-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                    placeholder="Type to search product..." 
                    autocomplete="off"
                    data-row-index="${itemIndex}">
                
                <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-field" value="">
                <input type="hidden" name="items[${itemIndex}][product_name]" class="product-name-field" value="" required>
            </div>
        </td>
        <td class="py-3 px-2 w-[15%]">
            <input type="number" name="items[${itemIndex}][quantity]" 
                class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                placeholder="0" min="1" value="1" required>
        </td>
        <td class="py-3 px-2 w-[20%]">
            <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" 
                class="price-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" 
                placeholder="0.00" min="0" value="0" required>
        </td>
        <td class="py-3 px-2 text-right w-[18%]">
            <span class="text-sm font-semibold text-gray-900 item-total">0.00</span>
        </td>
        <td class="py-3 px-2 text-center w-[12%]">
            <button type="button" class="text-red-600 hover:text-red-800 text-sm font-medium remove-item-btn">
                Remove
            </button>
        </td>
    `;
    
    container.appendChild(row);
    
    // Attach event listeners
    const searchInput = row.querySelector('.product-search-input');
    searchInput.addEventListener('input', function() { searchProduct(this); });
    searchInput.addEventListener('focus', function() { searchProduct(this); });
    
    const quantityInput = row.querySelector('.quantity-input');
    const priceInput = row.querySelector('.price-input');
    quantityInput.addEventListener('input', calculateTotals);
    priceInput.addEventListener('input', calculateTotals);
    
    const removeBtn = row.querySelector('.remove-item-btn');
    removeBtn.addEventListener('click', function() { removeItem(this); });
    
    itemIndex++;
    calculateTotals();
}

// Remove sales item
function removeItem(btn) {
    const container = document.getElementById('itemsContainer');
    if (container.children.length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    } else {
        alert('You must have at least one item');
    }
}

// Product search with debouncing
let searchTimeout;
function searchProduct(input) {
    clearTimeout(searchTimeout);
    
    const query = input.value.trim();
    
    if (query.length < 2) {
        hideAutocomplete();
        return;
    }
    
    searchTimeout = setTimeout(function() {
        fetch(`${routes.productSearch}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(products => {
                displaySearchResults(products, input, query);
            })
            .catch(error => console.error('Error searching products:', error));
    }, 300);
}

// Display search results
function displaySearchResults(products, input, query) {
    hideAutocomplete();
    
    const resultsDiv = document.createElement('div');
    resultsDiv.className = 'autocomplete-results';
    resultsDiv.dataset.inputIndex = input.dataset.rowIndex;
    
    const rect = input.getBoundingClientRect();
    resultsDiv.style.top = (rect.bottom + 2) + 'px';
    resultsDiv.style.left = rect.left + 'px';
    resultsDiv.style.width = Math.max(rect.width, 300) + 'px';
    
    const row = input.closest('tr');
    
    if (products.length === 0) {
        resultsDiv.innerHTML = '<div class="autocomplete-item text-gray-500">No products found</div>';
    } else {
        products.forEach(function(product) {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `
                <strong>${product.name}</strong>
                ${product.sku ? '<span class="text-gray-500 text-xs">' + product.sku + '</span>' : ''}
                <div class="text-green-600 font-semibold mt-1">ZMW ${parseFloat(product.price).toFixed(2)}</div>
            `;
            item.addEventListener('click', function() {
                selectProduct(row, product);
                hideAutocomplete();
            });
            resultsDiv.appendChild(item);
        });
    }
    
    const addNew = document.createElement('div');
    addNew.className = 'autocomplete-add-new';
    addNew.textContent = `+ Create "${query}" as new product`;
    addNew.addEventListener('click', function() {
        openCreateModal(row, query);
        hideAutocomplete();
    });
    resultsDiv.appendChild(addNew);
    
    document.body.appendChild(resultsDiv);
    currentAutocomplete = resultsDiv;
}

// Hide autocomplete
function hideAutocomplete() {
    if (currentAutocomplete) {
        currentAutocomplete.remove();
        currentAutocomplete = null;
    }
}

window.addEventListener('scroll', hideAutocomplete);
window.addEventListener('resize', hideAutocomplete);

// Close autocomplete when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('product-search-input') && !e.target.closest('.autocomplete-results')) {
        hideAutocomplete();
    }
});

// Select product from search
function selectProduct(row, product) {
    const searchInput = row.querySelector('.product-search-input');
    const productIdField = row.querySelector('.product-id-field');
    const productNameField = row.querySelector('.product-name-field');
    const priceInput = row.querySelector('.price-input');
    
    searchInput.value = product.name;
    productIdField.value = product.id;
    productNameField.value = product.name;
    priceInput.value = parseFloat(product.price).toFixed(2);
    
    calculateTotals();
}

// Open create product modal
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

// Close modal
function closeModal() {
    document.getElementById('createProductModal').classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentRow = null;
}

// Submit quick create form
function submitQuickCreate(event) {
    event.preventDefault();
    
    const btn = document.getElementById('createBtn');
    const errorDiv = document.getElementById('modalError');
    
    btn.disabled = true;
    btn.textContent = 'Creating...';
    errorDiv.classList.add('hidden');
    
    const formData = {
        name: document.getElementById('newProductName').value,
        price: document.getElementById('newProductPrice').value,
        sku: document.getElementById('newProductSku').value,
        description: document.getElementById('newProductDescription').value,
        _token: csrfToken
    };
    
    fetch(routes.quickCreate, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            selectProduct(currentRow, data.product);
            closeModal();
            showToast('Product created successfully!', 'success');
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
        btn.textContent = 'Create Product';
    });
}

// Add deduction row
function addDeduction() {
    const container = document.getElementById('deductionsContainer');
    const row = document.createElement('div');
    row.className = 'flex gap-4';
    row.innerHTML = `
        <input type="text" name="deductions[${deductionIndex}][description]" 
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" 
            placeholder="e.g., Gel Battery Purchase">
        <input type="number" step="0.01" name="deductions[${deductionIndex}][amount]" 
            class="deduction-amount w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" 
            placeholder="0.00" min="0" value="0">
        <button type="button" class="remove-deduction-btn text-red-600 hover:text-red-800 text-sm font-medium px-2">
            Remove
        </button>
    `;
    container.appendChild(row);
    
    // Attach event listeners
    const amountInput = row.querySelector('.deduction-amount');
    amountInput.addEventListener('input', calculateTotals);
    
    const removeBtn = row.querySelector('.remove-deduction-btn');
    removeBtn.addEventListener('click', function() { removeDeduction(this); });
    
    deductionIndex++;
    calculateTotals();
}

// Remove deduction
function removeDeduction(btn) {
    btn.parentElement.remove();
    calculateTotals();
}

// Calculate totals
function calculateTotals() {
    let totalSales = 0;
    const items = document.querySelectorAll('#itemsContainer tr');
    items.forEach(function(item) {
        const quantity = parseFloat(item.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(item.querySelector('.price-input').value) || 0;
        const itemTotal = quantity * price;
        item.querySelector('.item-total').textContent = itemTotal.toFixed(2);
        totalSales += itemTotal;
    });

    let totalDeductions = 0;
    const deductions = document.querySelectorAll('#deductionsContainer input[name*="[amount]"]');
    deductions.forEach(function(deduction) {
        totalDeductions += parseFloat(deduction.value) || 0;
    });

    document.getElementById('totalSalesValue').textContent = totalSales.toFixed(2);
    document.getElementById('totalDeductions').textContent = totalDeductions.toFixed(2);
    document.getElementById('cashAtHand').textContent = (totalSales - totalDeductions).toFixed(2);
}

// Set routes from blade template
function setRoutes(newRoutes) {
    routes = { ...routes, ...newRoutes };
}

// Export functions to global scope
window.salesForm = {
    addItem,
    removeItem,
    addDeduction,
    removeDeduction,
    saveDraft,
    closeModal,
    submitQuickCreate,
    setRoutes
};

// Initialize when DOM is ready
window.addEventListener('DOMContentLoaded', function() {
    console.log('Sales Form JS Loaded - Initializing...');
    addItem();
    setupFormChangeDetection();
    setupBeforeUnload();
    checkForFlashMessages();
    console.log('Sales Form JS Initialized Successfully');
});
