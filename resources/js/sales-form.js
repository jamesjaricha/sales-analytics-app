// Add sales item row
function addItem() {
    const container = document.getElementById('itemsContainer');
    if (!container) return;
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-field">
            <input type="text" name="items[${itemIndex}][product_name]" class="product-name-field hidden">
            <input type="text" class="product-search-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Search product..." autocomplete="off" data-row-index="${itemIndex}">
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][quantity]" class="quantity-input w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm" min="1" value="1">
        </td>
        <td>
            <input type="number" name="items[${itemIndex}][unit_price]" class="price-input w-28 px-3 py-2 border border-gray-300 rounded-lg text-sm" min="0" step="0.01" value="0">
        </td>
        <td>
            <span class="item-total font-semibold">0.00</span>
        </td>
        <td>
            <button type="button" class="remove-item-btn text-red-600 hover:text-red-800 text-sm font-medium px-2">Remove</button>
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
let suppressUnload = false;

// CSRF Token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// Routes (read from form data attributes)
let routes = { store: '', productSearch: '', quickCreate: '', getDraft: '' };
let formMode = 'create'; // 'create' | 'edit'

function readRoutesFromForm() {
    const form = document.getElementById('salesForm');
    if (!form) return;
    routes = {
        store: form.dataset.routeStore || routes.store,
        productSearch: form.dataset.routeProductSearch || routes.productSearch,
        quickCreate: form.dataset.routeQuickCreate || routes.quickCreate,
        getDraft: form.dataset.routeGetDraft || routes.getDraft,
    };
    if (form.dataset.mode) {
        formMode = form.dataset.mode;
    }
}

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
        suppressUnload = true;
        formChanged = false;
    });
}

// Warn before leaving if form has unsaved changes
function setupBeforeUnload() {
    window.addEventListener('beforeunload', (e) => {
        if (formChanged && !suppressUnload) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
}

// Toast notifications
function ensureToastContainer() {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.position = 'fixed';
        container.style.top = '16px';
        container.style.right = '16px';
        container.style.zIndex = '99999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
    }
    return container;
}

function showToast(message, type = 'info') {
    const container = ensureToastContainer();
    const toast = document.createElement('div');
    toast.className = 'toast-notification shadow-lg rounded-lg px-4 py-3 text-white text-sm';
    toast.style.opacity = '1';
    toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    if (type === 'success') toast.style.backgroundColor = '#16a34a';
    else if (type === 'error') toast.style.backgroundColor = '#dc2626';
    else if (type === 'warning') toast.style.backgroundColor = '#f59e0b';
    else toast.style.backgroundColor = '#374151';
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(10px)'; }, 3500);
    setTimeout(() => { toast.remove(); }, 4000);
}

// Check for flash messages and show as toast
function checkForFlashMessages() {
    const successMessage = document.querySelector('[data-flash-success]');
    const errorMessage = document.querySelector('[data-flash-error]');
    const validationErrors = document.querySelector('[data-validation-errors]');
    
    if (successMessage) {
        showToast(successMessage.dataset.flashSuccess, 'success');
        try { sessionStorage.removeItem(CONFIG.AUTOSAVE_KEY); } catch (e) {}
        // Only reset form if not a draft save
        if (!successMessage.dataset.flashSuccess.toLowerCase().includes('draft')) {
            resetSalesForm();
            // Clear all input fields (including sale_date, items, deductions)
            const form = document.getElementById('salesForm');
            if (form) {
                form.reset();
            }
        }
    }
// Reset the sales form fields and rows
function resetSalesForm() {
    // Reset sale date to today
    const dateInput = document.querySelector('input[name="sale_date"]');
    if (dateInput) {
        dateInput.value = new Date().toISOString().slice(0, 10);
    }
    // Clear items
    const itemsContainer = document.getElementById('itemsContainer');
    if (itemsContainer) {
        itemsContainer.innerHTML = '';
        itemIndex = 0;
        addItem();
    }
    // Clear deductions
    const dedContainer = document.getElementById('deductionsContainer');
    if (dedContainer) {
        dedContainer.innerHTML = '';
        deductionIndex = 0;
    }
    // Reset totals
    calculateTotals();
}
    if (errorMessage) {
        showToast(errorMessage.dataset.flashError, 'error');
    }
    if (validationErrors) {
        const errors = JSON.parse(validationErrors.dataset.validationErrors);
        errors.forEach((error, index) => {
            setTimeout(() => { showToast(error, 'error'); }, index * 500);
        });
    }
}

// Save as draft
function saveDraft() {
    if (!confirm('Save this sales report as a draft? You can complete it later.')) return;
    try { autosaveNow(); } catch (e) {}
    const form = document.getElementById('salesForm');
    if (!form) return;
    const input = document.createElement('input');
    input.type = 'hidden'; input.name = 'save_as_draft'; input.value = '1';
    form.appendChild(input);
    // Prevent beforeunload prompt
    suppressUnload = true;
    formChanged = false;
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else {
        form.submit();
    }
}

// Remove sales item
function removeItem(btn) {
    const container = document.getElementById('itemsContainer');
    if (!container) return;
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
    if (query.length < 2) { hideAutocomplete(); return; }
    searchTimeout = setTimeout(function() {
        fetch(`${routes.productSearch}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(products => { displaySearchResults(products, input, query); })
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
            item.addEventListener('click', function() { selectProduct(row, product); hideAutocomplete(); });
            resultsDiv.appendChild(item);
        });
    }
    const addNew = document.createElement('div');
    addNew.className = 'autocomplete-add-new';
    addNew.textContent = `+ Create "${query}" as new product`;
    addNew.addEventListener('click', function() { openCreateModal(row, query); hideAutocomplete(); });
    resultsDiv.appendChild(addNew);
    document.body.appendChild(resultsDiv);
    currentAutocomplete = resultsDiv;
}

// Hide autocomplete
function hideAutocomplete() { if (currentAutocomplete) { currentAutocomplete.remove(); currentAutocomplete = null; } }
window.addEventListener('scroll', hideAutocomplete);
window.addEventListener('resize', hideAutocomplete);
document.addEventListener('click', function(e) { if (!e.target.classList.contains('product-search-input') && !e.target.closest('.autocomplete-results')) hideAutocomplete(); });

// Select product from search
function selectProduct(row, product) {
    const searchInput = row.querySelector('.product-search-input');
    const productIdField = row.querySelector('.product-id-field');
    const productNameField = row.querySelector('.product-name-field');
    const priceInput = row.querySelector('.price-input');
    searchInput.value = product.name; productIdField.value = product.id; productNameField.value = product.name; priceInput.value = parseFloat(product.price).toFixed(2);
    calculateTotals();
}

// Open create product modal
function openCreateModal(row, productName) {
    currentRow = row;
    document.getElementById('newProductName').value = productName || '';
    document.getElementById('newProductPrice').value = '';
    document.getElementById('newProductSku').value = '';
    document.getElementById('newProductDescription').value = '';
    document.getElementById('modalError').classList.add('hidden');
    document.getElementById('createProductModal').classList.remove('hidden');
    document.body.classList.add('modal-open');
    setTimeout(() => { document.getElementById('newProductPrice').focus(); }, 100);
}

// Close modal
function closeModal() { document.getElementById('createProductModal').classList.add('hidden'); document.body.classList.remove('modal-open'); currentRow = null; }

// Submit quick create form
function submitQuickCreate(event) {
    event.preventDefault();
    const btn = document.getElementById('createBtn');
    const errorDiv = document.getElementById('modalError');
    btn.disabled = true; btn.textContent = 'Creating...'; errorDiv.classList.add('hidden');
    const formData = { name: document.getElementById('newProductName').value, price: document.getElementById('newProductPrice').value, sku: document.getElementById('newProductSku').value, description: document.getElementById('newProductDescription').value, _token: csrfToken };
    fetch(routes.quickCreate, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(formData) })
        .then(response => response.json())
        .then(data => { if (data.success) { selectProduct(currentRow, data.product); closeModal(); showToast('Product created successfully!', 'success'); } else { throw new Error(data.message || 'Failed to create product'); } })
        .catch(error => { errorDiv.textContent = typeof error.message === 'object' ? 'Please fix the errors and try again.' : error.message; errorDiv.classList.remove('hidden'); })
        .finally(() => { btn.disabled = false; btn.textContent = 'Create Product'; });
}

// Add deduction row
function addDeduction() {
    const container = document.getElementById('deductionsContainer');
    const row = document.createElement('div');
    row.className = 'flex gap-4';
    row.innerHTML = `
        <input type="text" name="deductions[${deductionIndex}][description]" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" placeholder="e.g., Gel Battery Purchase">
        <input type="number" step="0.01" name="deductions[${deductionIndex}][amount]" class="deduction-amount w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" placeholder="0.00" min="0" value="0">
        <button type="button" class="remove-deduction-btn text-red-600 hover:text-red-800 text-sm font-medium px-2">Remove</button>`;
    container.appendChild(row);
    row.querySelector('.deduction-amount').addEventListener('input', calculateTotals);
    row.querySelector('.remove-deduction-btn').addEventListener('click', function() { removeDeduction(this); });
    deductionIndex++;
    calculateTotals();
}

// Remove deduction
function removeDeduction(btn) { btn.parentElement.remove(); calculateTotals(); }

// Calculate totals
function calculateTotals() {
    let totalSales = 0;
    const items = document.querySelectorAll('#itemsContainer tr');
    items.forEach(function(item) {
        const quantity = parseFloat(item.querySelector('.quantity-input')?.value) || 0;
        const price = parseFloat(item.querySelector('.price-input')?.value) || 0;
        const itemTotal = quantity * price;
        const totalCell = item.querySelector('.item-total'); if (totalCell) totalCell.textContent = itemTotal.toFixed(2);
        totalSales += itemTotal;
    });
    let totalDeductions = 0;
    const deductions = document.querySelectorAll('#deductionsContainer input[name*="[amount]"]');
    deductions.forEach(function(deduction) { totalDeductions += parseFloat(deduction.value) || 0; });
    const totalSalesEl = document.getElementById('totalSalesValue'); if (totalSalesEl) totalSalesEl.textContent = totalSales.toFixed(2);
    const totalDeductionsEl = document.getElementById('totalDeductions'); if (totalDeductionsEl) totalDeductionsEl.textContent = totalDeductions.toFixed(2);
    const cashAtHandEl = document.getElementById('cashAtHand'); if (cashAtHandEl) cashAtHandEl.textContent = (totalSales - totalDeductions).toFixed(2);
}

// Set routes from blade template
function setRoutes(newRoutes) { routes = { ...routes, ...newRoutes }; }

// Export functions to global scope
window.salesForm = { addItem, removeItem, addDeduction, removeDeduction, saveDraft, closeModal, submitQuickCreate, setRoutes };

// ===== AUTOSAVE / RESTORE =====
function collectFormData() {
    const data = { sale_date: document.querySelector('input[name="sale_date"]')?.value || '', items: [], deductions: [] };
    document.querySelectorAll('#itemsContainer tr').forEach(tr => { data.items.push({ product_id: tr.querySelector('.product-id-field')?.value || '', product_name: tr.querySelector('.product-name-field')?.value || tr.querySelector('.product-search-input')?.value || '', quantity: tr.querySelector('.quantity-input')?.value || '', unit_price: tr.querySelector('.price-input')?.value || '' }); });
    document.querySelectorAll('#deductionsContainer > div').forEach(row => { data.deductions.push({ description: row.querySelector('input[name*="[description]"]')?.value || '', amount: row.querySelector('input[name*="[amount]"]')?.value || '' }); });
    return data;
}

function restoreFromAutosave() {
    try {
        const raw = sessionStorage.getItem(CONFIG.AUTOSAVE_KEY);
        if (!raw) return false;
        const saved = JSON.parse(raw);
        const dateInput = document.querySelector('input[name="sale_date"]'); if (saved.sale_date && dateInput) dateInput.value = saved.sale_date;
        const itemsContainer = document.getElementById('itemsContainer'); if (itemsContainer) { itemsContainer.innerHTML = ''; itemIndex = 0; (saved.items || []).forEach(it => { addItem(); const row = itemsContainer.lastElementChild; row.querySelector('.product-id-field').value = it.product_id || ''; row.querySelector('.product-name-field').value = it.product_name || ''; row.querySelector('.product-search-input').value = it.product_name || ''; row.querySelector('.quantity-input').value = it.quantity || 1; row.querySelector('.price-input').value = it.unit_price || 0; }); }
        const dedContainer = document.getElementById('deductionsContainer'); if (dedContainer) { dedContainer.innerHTML = ''; deductionIndex = 0; (saved.deductions || []).forEach(d => { addDeduction(); const row = dedContainer.lastElementChild; row.querySelector('input[name*="[description]"]').value = d.description || ''; row.querySelector('input[name*="[amount]"]').value = d.amount || ''; }); }
        calculateTotals();
        return true;
    } catch (e) { console.warn('Failed to restore autosave', e); return false; }
}

// Try fetching a server-side draft by date and restore it
async function fetchAndRestoreDraftByDate(dateStr) {
    if (!routes.getDraft || !dateStr) return false;
    try {
        const resp = await fetch(`${routes.getDraft}?date=${encodeURIComponent(dateStr)}`);
        const data = await resp.json();
        if (!data.success) return false;
        const form_data = data.draft.form_data || {};
        sessionStorage.setItem(CONFIG.AUTOSAVE_KEY, JSON.stringify({ sale_date: form_data.sale_date || dateStr, items: form_data.items || [], deductions: form_data.deductions || [] }));
        return restoreFromAutosave();
    } catch (e) { console.warn('Failed to fetch draft', e); return false; }
}

function autosaveNow() {
    try { const data = collectFormData(); data._savedAt = new Date().toISOString(); sessionStorage.setItem(CONFIG.AUTOSAVE_KEY, JSON.stringify(data)); }
    catch (e) { console.warn('Autosave failed', e); }
}

function setupAutosave() {
    setInterval(autosaveNow, CONFIG.AUTOSAVE_INTERVAL);
    document.getElementById('salesForm')?.addEventListener('input', autosaveNow);
}

// Initialize indices from existing DOM (useful for edit mode)
function initIndicesFromDOM() {
    const itemsContainer = document.getElementById('itemsContainer');
    const deductionsContainer = document.getElementById('deductionsContainer');
    if (itemsContainer) {
        itemIndex = itemsContainer.querySelectorAll('tr').length;
    }
    if (deductionsContainer) {
        deductionIndex = deductionsContainer.children.length;
    }
}

// Bind events to an existing item row
function bindItemRowEvents(row) {
    const searchInput = row.querySelector('.product-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() { searchProduct(this); });
        searchInput.addEventListener('focus', function() { searchProduct(this); });
    }
    const quantityInput = row.querySelector('.quantity-input');
    const priceInput = row.querySelector('.price-input');
    if (quantityInput) quantityInput.addEventListener('input', calculateTotals);
    if (priceInput) priceInput.addEventListener('input', calculateTotals);
    const removeBtn = row.querySelector('.remove-item-btn');
    if (removeBtn) removeBtn.addEventListener('click', function() { removeItem(this); });
}

// Bind events to existing rows (edit mode)
function bindExistingRows() {
    document.querySelectorAll('#itemsContainer tr').forEach(bindItemRowEvents);
    document.querySelectorAll('#deductionsContainer > div').forEach(row => {
        const amt = row.querySelector('input[name*="[amount]"]');
        if (amt) amt.addEventListener('input', calculateTotals);
        const btn = row.querySelector('.remove-deduction-btn');
        if (btn) btn.addEventListener('click', function() { removeDeduction(this); });
    });
}

// Initialize when DOM is ready
window.addEventListener('DOMContentLoaded', function() {
    // Read routes from form data attributes and bind UI events
    readRoutesFromForm();
    document.getElementById('addItemBtn')?.addEventListener('click', addItem);
    document.getElementById('addDeductionBtn')?.addEventListener('click', addDeduction);
    document.getElementById('saveDraftBtn')?.addEventListener('click', saveDraft);
    document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal);
    document.getElementById('modalCancelBtn')?.addEventListener('click', closeModal);
    const modal = document.getElementById('createProductModal');
    if (modal) { modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); }); }
    document.getElementById('quickCreateForm')?.addEventListener('submit', submitQuickCreate);
    // Only clear form fields if no autosave/draft is present
    const restored = restoreFromAutosave();
    if (formMode === 'edit') {
        // Do not reset or auto-add in edit mode; just align indices and totals
        initIndicesFromDOM();
        bindExistingRows();
        calculateTotals();
    } else if (!restored) {
        const form = document.getElementById('salesForm');
        if (form) {
            form.reset();
        }
        const dateInput = document.querySelector('input[name="sale_date"]');
        if (dateInput) {
            fetchAndRestoreDraftByDate(dateInput.value).then(found => { if (!found) addItem(); });
        } else {
            addItem();
        }
    }
    setupFormChangeDetection();
    setupBeforeUnload();
    setupAutosave();
    checkForFlashMessages();
});
