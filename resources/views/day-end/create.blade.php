@extends('layouts.app')

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Day-End Reconciliation</h1>
            <p class="text-gray-500 mt-1">Trading day: {{ \Carbon\Carbon::parse($summary['business_date'])->format('D, d M Y') }}</p>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($summary['invoice_count'] === 0 && $summary['debt_payments']->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center">
                <p class="text-gray-500">No sales or debt repayments have been recorded yet today — there is nothing to reconcile.</p>
                <a href="{{ route('pos.create') }}" class="inline-block mt-4 text-brand-600 hover:text-brand-700 font-medium">Go to Point of Sale</a>
            </div>
        @else
        <div x-data="dayEnd" x-cloak>
            <!-- Step indicator -->
            <div class="flex items-center justify-between mb-6 text-sm">
                <template x-for="(label, n) in steps" :key="n">
                    <div class="flex-1 flex items-center">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center font-semibold"
                                x-bind:class="step >= n + 1 ? 'bg-brand-600 text-white' : 'bg-gray-200 text-gray-500'"
                                x-text="n + 1"></span>
                            <span class="hidden sm:inline" x-bind:class="step === n + 1 ? 'text-gray-900 font-semibold' : 'text-gray-400'" x-text="label"></span>
                        </div>
                        <div x-show="n < steps.length - 1" class="flex-1 h-px bg-gray-200 mx-2"></div>
                    </div>
                </template>
            </div>

            <form method="POST" action="{{ route('day-end.store') }}" x-on:submit="submitting = true">
                @csrf

                <!-- STEP 1 — Review -->
                <div x-show="step === 1" class="space-y-6">
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-500">Gross sales</p>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($summary['gross_sales'], 2) }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-500">Cash</p>
                            <p class="text-lg font-bold text-green-700">{{ number_format($summary['total_cash'], 2) }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-500">Bank</p>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($summary['total_bank'], 2) }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-500">Mobile money</p>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($summary['total_mobile_money'], 2) }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-500">Outstanding</p>
                            <p class="text-lg font-bold text-amber-600">{{ number_format($summary['total_outstanding'], 2) }}</p>
                        </div>
                    </div>

                    @if($summary['debt_payments']->isNotEmpty())
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 flex items-center justify-between text-sm border-b border-gray-100">
                                <span class="font-semibold text-gray-700">{{ $summary['debt_payments']->count() }} debt repayment{{ $summary['debt_payments']->count() === 1 ? '' : 's' }} received today</span>
                                <span class="font-semibold text-brand-700 tabular-nums">ZMW {{ number_format($summary['debt_payments_total'], 2) }}</span>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach($summary['debt_payments'] as $payment)
                                    <div class="px-5 py-3 flex items-center justify-between gap-2 text-sm">
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 truncate">{{ $payment->sale?->reference }} · {{ $payment->sale?->customer_name }}</p>
                                            <p class="text-xs text-gray-500">{{ ['cash' => 'Cash', 'bank' => 'Bank', 'mobile_money' => 'Mobile Money'][$payment->payment_method] ?? $payment->payment_method }} · received by {{ $payment->receivedBy?->name ?? 'unknown' }}</p>
                                        </div>
                                        <p class="font-semibold text-gray-900 tabular-nums shrink-0">ZMW {{ number_format((float) $payment->amount, 2) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">
                            {{ $summary['invoice_count'] }} invoice{{ $summary['invoice_count'] === 1 ? '' : 's' }} to reconcile
                        </div>
                        <div class="divide-y divide-gray-100 max-h-72 overflow-auto">
                            @foreach($summary['sales'] as $invoice)
                                <div class="px-5 py-3 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $invoice->reference }}</p>
                                        <p class="text-xs text-gray-500">{{ $invoice->created_at->format('H:i') }} · {{ $invoice->payment_method->label() }}@if($invoice->customer_name) · {{ $invoice->customer_name }}@endif</p>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 shrink-0">ZMW {{ number_format($invoice->total_amount, 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- STEP 2 — Cash expenses -->
                <div x-show="step === 2" class="space-y-4">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Cash expenses</h2>
                            <button type="button" x-on:click="addExpense()" class="text-sm text-brand-600 hover:text-brand-700 font-medium">+ Add expense</button>
                        </div>
                        <p class="text-sm text-gray-500 mb-3">Money paid out today (transport, airtime, supplier payments, etc.) — from cash, bank or mobile money. Optional.</p>

                        <template x-if="!expenses.length">
                            <p class="text-sm text-gray-500 py-4 text-center">No expenses added.</p>
                        </template>

                        <div class="space-y-2">
                            <template x-for="(e, i) in expenses" :key="i">
                                <div class="flex flex-wrap sm:flex-nowrap gap-2">
                                    <input type="text" x-bind:name="'expenses['+i+'][description]'" x-model="e.description" placeholder="Description"
                                        class="flex-1 min-w-[10rem] px-3 py-2 text-base border border-gray-200 rounded-lg">
                                    <input type="number" min="0" step="0.01" x-bind:name="'expenses['+i+'][amount]'" x-model.number="e.amount" placeholder="0.00"
                                        class="w-28 px-3 py-2 text-base border border-gray-200 rounded-lg">
                                    <select x-bind:name="'expenses['+i+'][payment_method]'" x-model="e.payment_method"
                                        class="w-36 px-2 py-2 text-base border border-gray-200 rounded-lg">
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank</option>
                                        <option value="mobile_money">Mobile Money</option>
                                    </select>
                                    <button type="button" x-on:click="removeExpense(i)" aria-label="Remove expense" class="text-red-500 hover:text-red-700 px-2">✕</button>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-200 text-sm space-y-1">
                            <div class="flex justify-end gap-2">
                                <span class="text-gray-500">Cash expenses (leave the drawer):</span>
                                <span class="font-semibold text-red-600 tabular-nums">ZMW <span x-text="fmt(cashExpensesTotal)"></span></span>
                            </div>
                            <div class="flex justify-end gap-2">
                                <span class="text-gray-500">Total expenses:</span>
                                <span class="font-semibold text-red-600 tabular-nums">ZMW <span x-text="fmt(expensesTotal)"></span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3 — Count cash -->
                <div x-show="step === 3" class="space-y-4">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <h2 class="text-lg font-semibold text-gray-900 mb-1">Count the cash drawer</h2>
                        <p class="text-sm text-gray-500 mb-4">Enter the physical cash counted, to check against what's expected.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div class="rounded-lg bg-gray-50 border border-gray-200 px-3 py-2.5">
                                    <p class="text-xs text-gray-500">Balance brought forward (set at sign-in)</p>
                                    <p class="text-base font-semibold text-gray-900 tabular-nums">
                                        ZMW {{ number_format($summary['opening_balance'] ?? 0, 2) }}
                                        <a href="{{ route('day.open') }}" class="ml-1 text-xs font-medium text-brand-600 hover:text-brand-700">change</a>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Counted cash (ZMW)</label>
                                    <input type="number" min="0" step="0.01" name="counted_cash" x-model.number="counted_cash" placeholder="0.00"
                                        class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-sm space-y-1">
                                <div class="flex justify-between"><span class="text-gray-500">Cash sales</span><span class="font-medium tabular-nums">ZMW <span x-text="fmt(totalCash)"></span></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Less cash expenses</span><span class="font-medium text-red-600 tabular-nums">− ZMW <span x-text="fmt(cashExpensesTotal)"></span></span></div>
                                <div class="flex justify-between border-t border-gray-200 pt-1"><span class="text-gray-700 font-medium">Cash at hand (takings)</span><span class="font-semibold tabular-nums">ZMW <span x-text="fmt(cashAtHand)"></span></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Balance b/f (float)</span><span class="font-medium tabular-nums">+ ZMW <span x-text="fmt(openingBalanceNum)"></span></span></div>
                                <div class="flex justify-between border-t border-gray-200 pt-1"><span class="text-gray-700 font-medium">Expected in drawer</span><span class="font-semibold tabular-nums">ZMW <span x-text="fmt(expectedDrawer)"></span></span></div>
                                <template x-if="variance !== null">
                                    <div class="flex justify-between pt-1">
                                        <span class="text-gray-700 font-medium">Variance</span>
                                        <span class="font-semibold" x-bind:class="variance === 0 ? 'text-gray-700' : (variance > 0 ? 'text-green-600' : 'text-red-600')">
                                            <span x-text="variance > 0 ? '+' : ''"></span>ZMW <span x-text="fmt(variance)"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4 — Confirm -->
                <div x-show="step === 4" class="space-y-4">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-2 text-sm">
                        <h2 class="text-lg font-semibold text-gray-900 mb-1">Money held (after expenses)</h2>
                        <p class="text-xs text-gray-400 mb-2">Each channel is net of the expenses paid through it.</p>
                        <div class="flex justify-between"><span class="text-gray-500">Cash at hand (takings)</span><span class="font-medium tabular-nums">ZMW <span x-text="fmt(cashAtHand)"></span></span></div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Cash @ Bank <template x-if="bankExpensesTotal > 0"><span class="text-xs text-red-500">(− <span x-text="fmt(bankExpensesTotal)"></span> exp)</span></template></span>
                            <span class="font-medium tabular-nums">ZMW <span x-text="fmt(netBank)"></span></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Mobile money <template x-if="mobileExpensesTotal > 0"><span class="text-xs text-red-500">(− <span x-text="fmt(mobileExpensesTotal)"></span> exp)</span></template></span>
                            <span class="font-medium tabular-nums">ZMW <span x-text="fmt(netMobile)"></span></span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-900">
                            <span>Total money held</span><span class="tabular-nums">ZMW <span x-text="fmt(totalHeld)"></span></span>
                        </div>
                        <div class="flex justify-between pt-1"><span class="text-gray-500">Outstanding debt (owed to you)</span><span class="font-medium text-amber-600 tabular-nums">ZMW {{ number_format($summary['total_outstanding'], 2) }}</span></div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-2 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Summary</p>
                        <div class="flex justify-between"><span class="text-gray-500">Gross sales invoiced</span><span class="font-medium tabular-nums">ZMW {{ number_format($summary['gross_sales'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Balance b/f (float, not in cash at hand)</span><span class="font-medium tabular-nums">ZMW <span x-text="fmt(openingBalanceNum)"></span></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Expected in drawer (b/f + cash at hand)</span><span class="font-medium tabular-nums">ZMW <span x-text="fmt(expectedDrawer)"></span></span></div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200 p-5 text-center">
                        <p class="text-sm text-green-700">Cash at hand (today's takings)</p>
                        <p class="text-4xl font-bold text-green-900 mt-1 tabular-nums">ZMW <span x-text="fmt(cashAtHand)"></span></p>
                        <p class="text-xs text-green-700 mt-1 tabular-nums">Cash <span x-text="fmt(totalCash)"></span> − cash expenses <span x-text="fmt(cashExpensesTotal)"></span></p>
                    </div>

                    <p class="text-xs text-gray-400 text-center">Approving locks today's {{ $summary['invoice_count'] }} invoice{{ $summary['invoice_count'] === 1 ? '' : 's' }} — they can no longer be edited or voided.</p>
                </div>

                <!-- Wizard controls -->
                <div class="flex items-center justify-between mt-6">
                    <button type="button" x-on:click="prev()" x-show="step > 1"
                        class="px-5 py-2.5 rounded-xl border-2 border-gray-200 text-gray-600 hover:bg-gray-50 font-medium">Back</button>
                    <span x-show="step === 1"></span>

                    <button type="button" x-on:click="next()" x-show="step < 4"
                        class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.97]">Next</button>
                    <button type="submit" x-show="step === 4" x-bind:disabled="submitting"
                        class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold inline-flex items-center gap-2 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.97]">
                        <svg x-show="submitting" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="submitting ? 'Approving…' : 'Approve Day-End'"></span>
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dayEnd', () => ({
        step: 1,
        steps: ['Review', 'Expenses', 'Count cash', 'Confirm'],
        submitting: false,
        expenses: [],
        counted_cash: '',
        opening_balance: @json($summary['opening_balance'] ?? 0),
        totalCash: @json($summary['total_cash'] ?? 0),
        totalBank: @json($summary['total_bank'] ?? 0),
        totalMobile: @json($summary['total_mobile_money'] ?? 0),

        addExpense() { this.expenses.push({ description: '', amount: '', payment_method: 'cash' }); },
        removeExpense(i) { this.expenses.splice(i, 1); },
        next() { if (this.step < 4) this.step++; },
        prev() { if (this.step > 1) this.step--; },

        get expensesTotal() {
            return this.expenses.reduce((s, e) => s + (Number(e.amount) || 0), 0);
        },
        channelExpenses(method) {
            return this.expenses
                .filter(e => (e.payment_method || 'cash') === method)
                .reduce((s, e) => s + (Number(e.amount) || 0), 0);
        },
        get cashExpensesTotal() {
            return this.channelExpenses('cash');
        },
        get bankExpensesTotal() {
            return this.channelExpenses('bank');
        },
        get mobileExpensesTotal() {
            return this.channelExpenses('mobile_money');
        },
        get netBank() {
            return this.totalBank - this.bankExpensesTotal;
        },
        get netMobile() {
            return this.totalMobile - this.mobileExpensesTotal;
        },
        get openingBalanceNum() {
            return Number(this.opening_balance) || 0;
        },
        // Today's cash takings after cash expenses — b/f NOT included
        get cashAtHand() {
            return this.totalCash - this.cashExpensesTotal;
        },
        // What should physically be in the drawer when counted
        get expectedDrawer() {
            return this.openingBalanceNum + this.cashAtHand;
        },
        get totalHeld() {
            return this.cashAtHand + this.netBank + this.netMobile;
        },
        get variance() {
            return this.counted_cash === '' || this.counted_cash === null ? null : (Number(this.counted_cash) - this.expectedDrawer);
        },
        fmt(n) {
            return Number(n || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    }));
});
</script>
@endpush
