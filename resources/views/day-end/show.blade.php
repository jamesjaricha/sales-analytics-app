@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('info'))
            <div class="mb-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-sm">{{ session('info') }}</div>
        @endif

        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Day-End · {{ \Carbon\Carbon::parse($report->sale_date)->format('D, d M Y') }}</h1>
                <p class="text-gray-500 mt-1">
                    @if($report->isApproved())
                        Approved{{ $report->approvedBy ? ' by '.$report->approvedBy->name : '' }} · {{ $report->approved_at->format('d M Y H:i') }}
                    @else
                        Not yet approved
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('day-end.pdf', $report) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Download PDF</a>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Locked</span>
            </div>
        </div>

        <!-- Settlement breakdown -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Gross sales</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($report->total_sales_value, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Cash</p>
                <p class="text-lg font-bold text-green-700">{{ number_format($report->total_cash, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Bank</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($report->total_bank, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Mobile money</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($report->total_mobile_money, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Outstanding</p>
                <p class="text-lg font-bold text-amber-600">{{ number_format($report->total_outstanding, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <!-- Cash at hand -->
                @php($cashExpenses = $report->deductions->where('payment_method', 'cash')->sum('amount'))
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200 p-5 text-center">
                    <p class="text-sm text-green-700">Cash at hand</p>
                    <p class="text-4xl font-bold text-green-900 mt-1 tabular-nums">ZMW {{ number_format($report->cash_at_hand, 2) }}</p>
                    <p class="text-xs text-green-700 mt-1 tabular-nums">
                        B/F {{ number_format((float) ($report->opening_balance ?? 0), 2) }}
                        + cash {{ number_format($report->total_cash, 2) }}
                        − cash expenses {{ number_format((float) $cashExpenses, 2) }}
                    </p>
                </div>

                @if($report->counted_cash !== null)
                    @php($variance = (float) $report->counted_cash - (float) $report->cash_at_hand)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 text-sm space-y-1">
                        <div class="flex justify-between"><span class="text-gray-500">Counted cash</span><span class="font-medium">ZMW {{ number_format($report->counted_cash, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Expected (cash at hand)</span><span class="font-medium">ZMW {{ number_format($report->cash_at_hand, 2) }}</span></div>
                        <div class="flex justify-between border-t border-gray-200 pt-1">
                            <span class="text-gray-700 font-medium">Variance</span>
                            <span class="font-semibold {{ $variance == 0 ? 'text-gray-700' : ($variance > 0 ? 'text-green-600' : 'text-red-600') }}">
                                {{ $variance > 0 ? '+' : '' }}ZMW {{ number_format($variance, 2) }}
                            </span>
                        </div>
                    </div>
                @endif

                @if($report->debtPayments->isNotEmpty())
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 flex items-center justify-between text-sm border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Debt repayments received</span>
                            <span class="font-semibold text-brand-700 tabular-nums">ZMW {{ number_format($report->debtPayments->sum('amount'), 2) }}</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach($report->debtPayments as $payment)
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

                <!-- Expenses -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">Expenses</div>
                    <div class="divide-y divide-gray-100">
                        @forelse($report->deductions as $deduction)
                            <div class="px-5 py-3 flex items-center justify-between gap-2 text-sm">
                                <div class="min-w-0">
                                    <span class="text-gray-700">{{ $deduction->description }}</span>
                                    <span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ ($deduction->payment_method ?? 'cash') === 'cash' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ ['cash' => 'Cash', 'bank' => 'Bank', 'mobile_money' => 'Mobile Money'][$deduction->payment_method ?? 'cash'] ?? $deduction->payment_method }}
                                    </span>
                                </div>
                                <span class="font-medium text-red-600 tabular-nums shrink-0">ZMW {{ number_format($deduction->amount, 2) }}</span>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-sm text-gray-400">No expenses recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Reconciled invoices -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">
                    {{ $report->sales->count() }} reconciled invoice{{ $report->sales->count() === 1 ? '' : 's' }}
                </div>
                <div class="divide-y divide-gray-100 max-h-[28rem] overflow-auto">
                    @forelse($report->sales as $invoice)
                        <div class="px-5 py-3 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $invoice->reference }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $invoice->created_at->format('H:i') }} · {{ $invoice->payment_method->label() }}@if($invoice->customer_name) · {{ $invoice->customer_name }}@endif
                                    @if((float) $invoice->paid_amount > 0)
                                        · paid {{ number_format((float) $invoice->paid_amount, 2) }} ({{ ['cash' => 'cash', 'bank' => 'bank', 'mobile_money' => 'mobile'][$invoice->paid_via] ?? $invoice->paid_via }}) · owing {{ number_format((float) $invoice->amount_due, 2) }}
                                    @endif
                                </p>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 shrink-0">ZMW {{ number_format($invoice->total_amount, 2) }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-sm text-gray-400">No invoices.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('pos.create') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">← Back to Point of Sale</a>
        </div>
    </div>
</div>
@endsection
