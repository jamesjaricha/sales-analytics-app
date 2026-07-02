@extends('layouts.app')

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 py-8" x-data="debtorsPage">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900">Debtors</h1>
                <p class="text-gray-500 mt-1">Clients with outstanding credit invoices</p>
            </div>
            <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 px-6 py-4 text-white shadow-sm">
                <p class="text-xs text-amber-50/90">Total outstanding · {{ $invoiceCount }} invoice{{ $invoiceCount === 1 ? '' : 's' }}</p>
                <p class="text-2xl font-bold tabular-nums">ZMW {{ number_format($totalOutstanding, 2) }}</p>
            </div>
        </div>

        <!-- Search -->
        <form method="GET" action="{{ route('debtors.index') }}" class="mb-6 flex gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search customer name…"
                class="flex-1 sm:max-w-xs px-4 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
            <button type="submit" class="px-5 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.97]">
                Search
            </button>
            @if($search !== '')
                <a href="{{ route('debtors.index') }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium inline-flex items-center">Clear</a>
            @endif
        </form>

        <!-- Debtor cards -->
        <div class="space-y-4">
            @forelse($debtors as $debtor)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 flex items-center justify-between gap-3 border-b border-gray-100 bg-amber-50/60">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $debtor['name'] }}</p>
                            @php($phone = $debtor['invoices']->first(fn ($i) => $i->customer_phone)?->customer_phone)
                            @if($phone)
                                <p class="text-xs text-gray-500">{{ $phone }}</p>
                            @endif
                        </div>
                        <p class="font-bold text-amber-700 tabular-nums shrink-0">
                            ZMW {{ number_format($debtor['total_due'], 2) }}
                            <span class="font-normal text-xs text-amber-600">owing</span>
                        </p>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach($debtor['invoices'] as $invoice)
                            <div class="px-5 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ $invoice->reference }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $invoice->business_date->format('D, d M Y') }} {{ $invoice->created_at->format('H:i') }}
                                            · recorded by {{ $invoice->user?->name ?? 'unknown' }}
                                            @if($invoice->note) · {{ $invoice->note }}@endif
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-amber-700 tabular-nums">ZMW {{ number_format((float) $invoice->amount_due, 2) }}</p>
                                            <p class="text-xs text-gray-500 tabular-nums">
                                                total {{ number_format((float) $invoice->total_amount, 2) }}@if((float) $invoice->paid_amount > 0) · paid {{ number_format((float) $invoice->paid_amount, 2) }}@endif
                                            </p>
                                        </div>
                                        <button type="button"
                                            x-on:click="openPay({{ $invoice->id }}, @js($invoice->reference), {{ (float) $invoice->amount_due }})"
                                            class="px-3 py-2 rounded-lg bg-brand-600 text-white text-xs font-semibold hover:bg-brand-700 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.96]">
                                            Receive payment
                                        </button>
                                    </div>
                                </div>
                                @if($invoice->payments->isNotEmpty())
                                    <div class="mt-2 pl-3 border-l-2 border-brand-200 space-y-0.5">
                                        @foreach($invoice->payments as $payment)
                                            <p class="text-xs text-gray-500 tabular-nums">
                                                Repaid ZMW {{ number_format((float) $payment->amount, 2) }}
                                                ({{ ['cash' => 'cash', 'bank' => 'bank', 'mobile_money' => 'mobile'][$payment->payment_method] ?? $payment->payment_method }})
                                                · {{ $payment->business_date->format('d M Y') }}
                                                · received by {{ $payment->receivedBy?->name ?? 'unknown' }}
                                                @if($payment->note) · {{ $payment->note }}@endif
                                            </p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
                    @if($search !== '')
                        <p class="text-gray-500">No debtors match "<span class="font-medium text-gray-700">{{ $search }}</span>".</p>
                    @else
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-brand-700 mb-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="text-gray-700 font-medium">No outstanding debts</p>
                        <p class="text-sm text-gray-500 mt-1">Every credit invoice has been settled.</p>
                    @endif
                </div>
            @endforelse
        </div>

    </div>

    <!-- Receive payment modal -->
    <div x-show="pay.open" x-cloak x-on:keydown.escape.window="closePay()"
        class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" x-on:click="closePay()"></div>
        <div x-show="pay.open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-start justify-between mb-1">
                <h2 class="text-lg font-semibold text-gray-900">Receive payment</h2>
                <button type="button" x-on:click="closePay()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                <span x-text="pay.reference"></span> · outstanding <span class="font-semibold text-amber-700 tabular-nums">ZMW <span x-text="pay.due.toFixed(2)"></span></span>
            </p>

            <form method="POST" x-bind:action="'{{ url('/debtors') }}/' + pay.saleId + '/payments'" x-on:submit="submitting = true">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount received (ZMW)</label>
                        <input type="number" name="amount" x-model.number="pay.amount" min="0.01" x-bind:max="pay.due" step="0.01" required
                            class="w-full px-3 py-2.5 text-lg font-semibold text-center tabular-nums border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        <p class="text-xs text-red-600 mt-1" x-show="payTooMuch" x-cloak>Cannot exceed the outstanding balance.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Paid via</label>
                        <select name="payment_method" x-model="pay.method"
                            class="w-full px-3 py-2.5 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="cash">Cash</option>
                            <option value="bank">Cash @ Bank</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                        <input type="text" name="note" x-model="pay.note" maxlength="255" placeholder="e.g. receipt number"
                            class="w-full px-3 py-2.5 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>
                </div>

                <button type="submit" x-bind:disabled="!canPay || submitting"
                    class="mt-6 w-full bg-brand-600 hover:bg-brand-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 rounded-xl font-semibold inline-flex items-center justify-center gap-2 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.98]">
                    <svg x-show="submitting" x-cloak class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="submitting ? 'Recording…' : 'Record payment'"></span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('debtorsPage', () => ({
        submitting: false,
        pay: { open: false, saleId: null, reference: '', due: 0, amount: '', method: 'cash', note: '' },

        openPay(saleId, reference, due) {
            this.pay = { open: true, saleId, reference, due, amount: due, method: 'cash', note: '' };
        },

        closePay() {
            if (!this.submitting) this.pay.open = false;
        },

        get payTooMuch() {
            return (Number(this.pay.amount) || 0) > this.pay.due + 0.005;
        },

        get canPay() {
            const amt = Number(this.pay.amount) || 0;
            return amt > 0 && !this.payTooMuch;
        },
    }));
});
</script>
@endpush
