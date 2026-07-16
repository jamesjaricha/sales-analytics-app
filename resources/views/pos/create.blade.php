@extends('layouts.app')

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-b from-emerald-50 via-gray-50 to-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 shadow-sm shadow-emerald-600/30">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Point of Sale</h1>
                <p class="text-gray-500 mt-0.5 text-sm">Trading day · {{ \Carbon\Carbon::parse($businessDate)->format('D, d M Y') }}</p>
            </div>
        </div>

        <!-- Validation errors -->
        @if($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="pos">

            <!-- Invoice builder -->
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('pos.store') }}" x-on:submit="submitting = true">
                    @csrf
                    <input type="hidden" name="business_date" value="{{ $businessDate }}">

                    <!-- Product search -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-5">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"></path></svg>
                            Add product
                        </label>
                        <div class="relative">
                            <input type="text" x-ref="searchInput" x-model="search" x-on:input.debounce.300ms="lookup()" x-on:focus="lookup()"
                                x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
                                x-on:keydown.enter.prevent="enterKey()" x-on:keydown.escape="results = []"
                                placeholder="Search by name or SKU…" autocomplete="off"
                                class="w-full px-4 py-2.5 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <div x-show="results.length" x-cloak x-on:click.outside="results = []"
                                class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-auto">
                                <template x-for="(p, idx) in results" :key="p.id">
                                    <button type="button" x-on:click="addProduct(p)" x-on:mouseenter="highlighted = idx"
                                        x-bind:class="idx === highlighted ? 'bg-emerald-50' : ''"
                                        class="w-full text-left px-4 py-2.5 flex items-center justify-between gap-3">
                                        <span class="min-w-0">
                                            <span class="font-medium text-gray-900" x-text="p.name"></span>
                                            <span class="text-xs text-gray-400" x-text="p.sku ? ' · ' + p.sku : ''"></span>
                                        </span>
                                        <span class="text-sm text-gray-500 shrink-0">
                                            ZMW <span x-text="Number(p.price).toFixed(2)"></span>
                                            <template x-if="p.track_stock">
                                                <span class="ml-2 text-xs text-gray-400">(<span x-text="p.stock_quantity"></span> left)</span>
                                            </template>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <button type="button" x-on:click="addBlank()" class="mt-3 text-sm text-emerald-600 hover:text-emerald-700 font-medium">+ Add custom item</button>
                    </div>

                    <!-- Line items -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-5 mt-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Items</h2>

                        <div x-show="!items.length" class="text-sm text-gray-400 py-6 text-center">
                            No items yet — search or add a custom item above.
                        </div>

                        <div x-show="items.length" class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b">
                                        <th class="py-2 pr-2">Product</th>
                                        <th class="py-2 px-2 w-20">Qty</th>
                                        <th class="py-2 px-2 w-32">Unit (ZMW)</th>
                                        <th class="py-2 px-2 w-28 text-right">Total</th>
                                        <th class="py-2 pl-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, i) in items" :key="i">
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2 pr-2">
                                                <input type="text" x-bind:name="'items['+i+'][product_name]'" x-model="item.product_name"
                                                    placeholder="Item name" required
                                                    class="w-full px-2 py-1.5 border border-gray-200 rounded focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                            </td>
                                            <td class="py-2 px-2">
                                                <input type="number" min="1" x-bind:max="item.max" x-bind:name="'items['+i+'][quantity]'" x-model.number="item.quantity" required
                                                    class="w-full px-2 py-1.5 border border-gray-200 rounded focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                                <input type="hidden" x-bind:name="'items['+i+'][product_id]'" x-bind:value="item.product_id ?? ''">
                                            </td>
                                            <td class="py-2 px-2">
                                                <input type="number" min="0" step="0.01" x-bind:name="'items['+i+'][unit_price]'" x-model.number="item.unit_price" required
                                                    class="w-full px-2 py-1.5 border border-gray-200 rounded focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                            </td>
                                            <td class="py-2 px-2 text-right font-medium text-gray-900" x-text="((Number(item.quantity)||0)*(Number(item.unit_price)||0)).toFixed(2)"></td>
                                            <td class="py-2 pl-2 text-right">
                                                <button type="button" x-on:click="removeItem(i)" class="text-red-500 hover:text-red-700" aria-label="Remove">✕</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end mt-4 pt-4 border-t border-gray-200">
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Invoice total</p>
                                <p class="text-2xl font-bold text-emerald-700">ZMW <span x-text="total.toFixed(2)"></span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment modal: opens once the invoice is built -->
                    <div x-show="payModal" x-cloak x-on:keydown.escape.window="payModal = false"
                        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
                        <div class="absolute inset-0 bg-gray-900/50" x-on:click="payModal = false"></div>
                        <div class="relative bg-white w-full sm:max-w-2xl rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto p-5"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">How is the customer paying?</h2>
                                <p class="text-sm text-gray-500 mt-0.5">Invoice total · <span class="font-bold text-emerald-700 tabular-nums" x-text="'ZMW ' + total.toFixed(2)"></span></p>
                            </div>
                            <button type="button" x-on:click="payModal = false" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">✕</button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <template x-for="m in methods" :key="m.value">
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method" x-bind:value="m.value" x-model="payment_method" class="peer sr-only">
                                    <div class="px-3 py-3 text-center text-sm rounded-xl border-2 border-gray-200 text-gray-600 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 peer-checked:font-medium transition">
                                        <span x-text="m.label"></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <template x-if="payment_method === 'credit'">
                        <div class="mt-4 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer name <span class="text-red-500">*</span></label>
                                    <input type="text" name="customer_name" x-model="customer_name" x-bind:required="payment_method === 'credit'"
                                        class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone number <span class="text-red-500">*</span></label>
                                    <input type="tel" name="customer_phone" x-model="customer_phone" x-bind:required="payment_method === 'credit'"
                                        inputmode="tel" placeholder="e.g. 0977 123 456"
                                        class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                                    <input type="text" name="note" x-model="note" placeholder="e.g. due date"
                                        class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                </div>
                            </div>
                            <!-- Partial payment: what the customer pays now vs what stays outstanding -->
                            <div class="rounded-xl bg-amber-50 border border-amber-200 p-3">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount paid now (ZMW)</label>
                                        <input type="number" min="0" step="0.01" name="paid_amount" x-model.number="paid_amount" placeholder="0.00"
                                            class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Paid via</label>
                                        <select name="paid_via" x-model="paid_via"
                                            class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                            <option value="cash">Cash</option>
                                            <option value="bank">Cash @ Bank</option>
                                            <option value="mobile_money">Mobile Money</option>
                                        </select>
                                    </div>
                                    <div class="flex flex-col justify-end">
                                        <p class="text-xs text-amber-700">Outstanding debt</p>
                                        <p class="text-lg font-bold text-amber-700 tabular-nums">ZMW <span x-text="outstanding.toFixed(2)"></span></p>
                                    </div>
                                </div>
                                <p x-show="paidTooMuch" x-cloak class="mt-2 text-xs text-red-600">
                                    The paid amount must be less than the invoice total — for a fully paid invoice choose Cash, Bank or Mobile Money instead of Credit.
                                </p>
                            </div>
                        </div>
                        </template>

                        <!-- Split payment: several tender lines settle one invoice -->
                        <template x-if="payment_method === 'split'">
                        <div class="mt-4 space-y-3">
                            <div class="rounded-xl bg-emerald-50/60 border border-emerald-200 p-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-700">How is the customer paying?</p>
                                    <button type="button" x-on:click="addTender()" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">+ Add line</button>
                                </div>
                                <template x-for="(t, i) in tenders" :key="i">
                                    <div class="flex gap-2">
                                        <select x-bind:name="'tenders['+i+'][method]'" x-model="t.method"
                                            class="w-40 px-2 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                            <option value="cash">Cash</option>
                                            <option value="bank">Cash @ Bank</option>
                                            <option value="mobile_money">Mobile Money</option>
                                        </select>
                                        <input type="number" min="0.01" step="0.01" x-bind:name="'tenders['+i+'][amount]'" x-model.number="t.amount" placeholder="0.00"
                                            class="flex-1 px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        <button type="button" x-on:click="removeTender(i)" class="text-red-500 hover:text-red-700 px-2" aria-label="Remove line">✕</button>
                                    </div>
                                </template>
                                <div class="flex items-center justify-between pt-2 border-t border-emerald-200 text-sm">
                                    <span class="text-gray-500">Paid now: <span class="font-semibold text-gray-900 tabular-nums" x-text="'ZMW ' + tendered.toFixed(2)"></span></span>
                                    <span x-show="!splitSettled" class="text-amber-700">Outstanding: <span class="font-bold tabular-nums" x-text="'ZMW ' + splitRemainder.toFixed(2)"></span></span>
                                    <span x-show="splitSettled && !splitOver" x-cloak class="text-emerald-700 font-medium">Fully paid ✓</span>
                                </div>
                                <p x-show="splitOver" x-cloak class="text-xs text-red-600">
                                    The lines add up to more than the invoice total — reduce one of the amounts.
                                </p>
                            </div>

                            <div x-show="!splitSettled" x-cloak class="rounded-xl bg-amber-50 border border-amber-200 p-3">
                                <p class="text-xs text-amber-700 mb-2">The unpaid balance becomes an outstanding debt — customer details are required.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer name <span class="text-red-500">*</span></label>
                                        <input type="text" name="customer_name" x-model="customer_name" x-bind:required="!splitSettled"
                                            class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone number <span class="text-red-500">*</span></label>
                                        <input type="tel" name="customer_phone" x-model="customer_phone" x-bind:required="!splitSettled"
                                            inputmode="tel" placeholder="e.g. 0977 123 456"
                                            class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                                        <input type="text" name="note" x-model="note" placeholder="e.g. due date"
                                            class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                        </div>
                        </template>

                        <button type="submit" x-bind:disabled="!canSubmit || submitting"
                            class="mt-5 w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold py-3.5 rounded-xl shadow-sm shadow-emerald-600/20 inline-flex items-center justify-center gap-2 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.99]">
                            <svg x-show="submitting" x-cloak class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="submitting ? 'Recording…' : 'Record Invoice'"></span>
                        </button>
                        </div>
                    </div>

                    <button type="button" x-on:click="openPay()" x-bind:disabled="!itemsOk"
                        class="mt-6 w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold py-3.5 rounded-xl shadow-sm shadow-emerald-600/20 inline-flex items-center justify-center gap-2 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.99]">
                        Continue to Payment
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </button>
                </form>
            </div>

            <!-- Today's invoices -->
            <div class="space-y-4">
                <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow-sm shadow-emerald-600/20">
                    <p class="text-sm text-emerald-50/90">Today's takings · {{ $invoices->count() }} invoice{{ $invoices->count() === 1 ? '' : 's' }}</p>
                    <p class="text-3xl font-bold mt-1 tabular-nums">ZMW {{ number_format($invoices->sum('total_amount'), 2) }}</p>
                    @if($opening)
                        <p class="text-xs text-emerald-50/80 mt-2 tabular-nums">
                            Balance b/f: ZMW {{ number_format((float) $opening->opening_balance, 2) }}
                            · <a href="{{ route('day.open') }}" class="underline hover:text-white">change</a>
                        </p>
                    @endif
                </div>

                <a href="{{ route('day-end.create') }}"
                    class="flex items-center justify-center gap-2 w-full bg-white border-2 border-emerald-600 text-emerald-700 hover:bg-emerald-50 font-semibold py-3 rounded-2xl transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Submit Day-End
                </a>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
                    <div class="px-5 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">Today's invoices</div>
                    <div class="divide-y divide-gray-100 max-h-[60vh] overflow-auto">
                        @forelse($invoices as $invoice)
                            <div class="px-5 py-3 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $invoice->reference }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $invoice->created_at->format('H:i') }} · {{ $invoice->payment_method->label() }}@if($invoice->settlementSummary()) ({{ $invoice->settlementSummary() }})@endif @if($invoice->customer_name) · {{ $invoice->customer_name }}@endif
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-gray-900">ZMW {{ number_format($invoice->total_amount, 2) }}</p>
                                    <form method="POST" action="{{ route('pos.void', $invoice) }}" onsubmit="return confirm('Void {{ $invoice->reference }}?');">
                                        @csrf
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Void</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center text-sm text-gray-400">No invoices recorded yet today.</div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pos', () => ({
        search: '',
        results: [],
        highlighted: -1,
        items: [],
        submitting: false,
        payModal: false,
        payment_method: 'cash',
        customer_name: '',
        customer_phone: '',
        paid_amount: '',
        paid_via: 'cash',
        tenders: [],
        note: '',
        methods: @json($paymentMethods),

        init() {
            // Selecting Split should always present at least one tender line
            this.$watch('payment_method', (m) => {
                if (m === 'split' && !this.tenders.length) this.addTender();
            });
        },

        async lookup() {
            const q = this.search.trim();
            if (q.length < 1) { this.results = []; this.highlighted = -1; return; }
            try {
                const res = await fetch(`{{ route('sales.products.search') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                this.results = await res.json();
                this.highlighted = this.results.length ? 0 : -1;
            } catch (e) {
                this.results = [];
                this.highlighted = -1;
            }
        },

        move(dir) {
            if (!this.results.length) return;
            this.highlighted = (this.highlighted + dir + this.results.length) % this.results.length;
        },

        // Enter adds the highlighted result — lets a barcode scanner (types SKU + Enter) add instantly
        enterKey() {
            if (this.results.length && this.highlighted >= 0) {
                this.addProduct(this.results[this.highlighted]);
            }
        },

        addProduct(p) {
            const existing = this.items.find(it => it.product_id === p.id);
            if (existing) {
                existing.quantity++;
            } else {
                this.items.push({
                    product_id: p.id,
                    product_name: p.name,
                    quantity: 1,
                    unit_price: Number(p.price),
                    max: p.track_stock ? p.stock_quantity : null,
                });
            }
            this.search = '';
            this.results = [];
            this.highlighted = -1;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        addBlank() {
            this.items.push({ product_id: null, product_name: '', quantity: 1, unit_price: 0, max: null });
        },

        removeItem(i) {
            this.items.splice(i, 1);
        },

        get total() {
            return this.items.reduce((s, it) => s + (Number(it.quantity) || 0) * (Number(it.unit_price) || 0), 0);
        },

        get outstanding() {
            if (this.payment_method !== 'credit') return 0;
            return Math.max(this.total - (Number(this.paid_amount) || 0), 0);
        },

        get paidTooMuch() {
            return this.payment_method === 'credit' && (Number(this.paid_amount) || 0) >= this.total && this.total > 0;
        },

        get itemsOk() {
            return this.items.length > 0
                && this.items.every(it => it.product_name && Number(it.quantity) > 0);
        },

        openPay() {
            if (this.itemsOk) this.payModal = true;
        },

        addTender() {
            this.tenders.push({ method: this.tenders.length ? 'mobile_money' : 'cash', amount: '' });
        },

        removeTender(i) {
            this.tenders.splice(i, 1);
        },

        get tendered() {
            return this.tenders.reduce((s, t) => s + (Number(t.amount) || 0), 0);
        },

        get splitRemainder() {
            return Math.max(this.total - this.tendered, 0);
        },

        get splitOver() {
            return this.payment_method === 'split' && this.tendered > this.total + 0.004;
        },

        get splitSettled() {
            return this.total - this.tendered <= 0.004;
        },

        get splitOk() {
            return this.tenders.length > 0
                && this.tenders.every(t => (Number(t.amount) || 0) > 0)
                && !this.splitOver
                && (this.splitSettled || (this.customer_name.trim() !== '' && this.customer_phone.trim() !== ''));
        },

        get canSubmit() {
            if (!this.itemsOk) return false;
            if (this.payment_method === 'credit') return !this.paidTooMuch;
            if (this.payment_method === 'split') return this.splitOk;
            return true;
        },
    }));
});
</script>
@endpush
