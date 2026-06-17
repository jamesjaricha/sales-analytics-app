@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
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
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Locked</span>
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
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200 p-5 text-center">
                    <p class="text-sm text-green-700">Cash at hand</p>
                    <p class="text-4xl font-bold text-green-900 mt-1">ZMW {{ number_format($report->cash_at_hand, 2) }}</p>
                    <p class="text-xs text-green-700 mt-1">Cash {{ number_format($report->total_cash, 2) }} − expenses {{ number_format($report->total_deductions, 2) }}</p>
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

                <!-- Expenses -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">Cash expenses</div>
                    <div class="divide-y divide-gray-100">
                        @forelse($report->deductions as $deduction)
                            <div class="px-5 py-3 flex items-center justify-between text-sm">
                                <span class="text-gray-700">{{ $deduction->description }}</span>
                                <span class="font-medium text-red-600">ZMW {{ number_format($deduction->amount, 2) }}</span>
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
                                <p class="text-xs text-gray-500">{{ $invoice->created_at->format('H:i') }} · {{ $invoice->payment_method->label() }}@if($invoice->customer_name) · {{ $invoice->customer_name }}@endif</p>
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
