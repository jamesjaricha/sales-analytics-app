@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900">Sales Report Details</h1>
                <p class="text-gray-500 mt-2">{{ $report->sale_date->format('l, F d, Y') }}</p>
            </div>
            <div class="flex gap-6">
                <a href="{{ route('sales.pdf', $report->id) }}"
                    style="background-color: #dc2626 !important; color: white !important; padding: 12px 24px !important; border-radius: 8px !important; font-weight: 600 !important; text-decoration: none !important; display: inline-block !important;">
                    Download PDF
                </a>
                <a href="{{ route('sales.index') }}"
                    style="background-color: #6b7280 !important; color: white !important; padding: 12px 24px !important; border-radius: 8px !important; font-weight: 600 !important; text-decoration: none !important; display: inline-block !important;">
                    ← Back to Sales
                </a>
            </div>
        </div>



        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Sales Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Sales Value</p>
                <h2 class="text-3xl font-bold text-gray-900">ZMW {{ number_format($report->total_sales_value, 2) }}</h2>
            </div>

            <!-- Deductions Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Deductions</p>
                <h2 class="text-3xl font-bold text-red-600">ZMW {{ number_format($report->total_deductions, 2) }}</h2>
            </div>

            <!-- Cash at Hand Card -->
            @php
                $cashExpenses = (float) $report->deductions->where('payment_method', 'cash')->sum('amount');
                $bankExpenses = (float) $report->deductions->where('payment_method', 'bank')->sum('amount');
                $mobileExpenses = (float) $report->deductions->where('payment_method', 'mobile_money')->sum('amount');
                $netBank = (float) $report->total_bank - $bankExpenses;
                $netMobile = (float) $report->total_mobile_money - $mobileExpenses;
                $totalHeld = (float) $report->cash_at_hand + $netBank + $netMobile;
            @endphp
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200 p-6">
                <p class="text-sm font-medium text-green-700 mb-1">Cash at Hand (drawer)</p>
                <h2 class="text-3xl font-bold text-green-900 tabular-nums">ZMW {{ number_format($report->cash_at_hand, 2) }}</h2>
                <p class="text-xs text-green-700 mt-1 tabular-nums">
                    B/F {{ number_format((float) ($report->opening_balance ?? 0), 2) }}
                    + cash {{ number_format($report->total_cash, 2) }}
                    − cash expenses {{ number_format($cashExpenses, 2) }}
                </p>
            </div>
        </div>

        @if($report->isApproved())
        <!-- Settlement Breakdown (day-end) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-1">Settlement Breakdown</h2>
            <p class="text-sm text-gray-400 mb-4">Each channel is net of the expenses paid through it.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-green-50 rounded-xl p-4">
                    <p class="text-xs text-green-700 mb-1">Cash Received</p>
                    <p class="text-xl font-bold text-green-700 tabular-nums">ZMW {{ number_format($report->total_cash, 2) }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-1">Cash @ Bank</p>
                    <p class="text-xl font-bold text-gray-800 tabular-nums">ZMW {{ number_format($netBank, 2) }}</p>
                    @if($bankExpenses > 0)<p class="text-xs text-red-500 tabular-nums">− {{ number_format($bankExpenses, 2) }} exp</p>@endif
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-1">Mobile Money</p>
                    <p class="text-xl font-bold text-gray-800 tabular-nums">ZMW {{ number_format($netMobile, 2) }}</p>
                    @if($mobileExpenses > 0)<p class="text-xs text-red-500 tabular-nums">− {{ number_format($mobileExpenses, 2) }} exp</p>@endif
                </div>
                <div class="bg-amber-50 rounded-xl p-4">
                    <p class="text-xs text-amber-700 mb-1">Outstanding Debt</p>
                    <p class="text-xl font-bold text-amber-700 tabular-nums">ZMW {{ number_format($report->total_outstanding, 2) }}</p>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between rounded-xl bg-brand-50 border border-brand-200 px-4 py-3">
                <p class="text-sm font-medium text-brand-800">Total money held (cash at hand + bank + mobile)</p>
                <p class="text-lg font-bold text-brand-800 tabular-nums">ZMW {{ number_format($totalHeld, 2) }}</p>
            </div>
            @if($report->debtPayments->isNotEmpty())
                <p class="text-xs text-gray-500 mt-2">
                    Includes ZMW {{ number_format($report->debtPayments->sum('amount'), 2) }} in debt repayments received this day (collected against earlier invoices — not part of this day's sales value).
                </p>
            @endif
            @if($report->counted_cash !== null)
                @php($variance = (float) $report->counted_cash - (float) $report->cash_at_hand)
                <p class="text-sm text-gray-500 mt-4">
                    Counted cash: <span class="font-semibold text-gray-800">ZMW {{ number_format($report->counted_cash, 2) }}</span>
                    · Variance: <span class="font-semibold {{ $variance == 0 ? 'text-gray-700' : ($variance > 0 ? 'text-green-600' : 'text-red-600') }}">{{ $variance > 0 ? '+' : '' }}ZMW {{ number_format($variance, 2) }}</span>
                </p>
            @endif
        </div>
        @endif


        {{-- Monthly Cumulative Sales - Admin Only --}}
        @if(auth()->user()->role === 'admin')
        <div class="bg-gradient-to-br from-green-50 via-yellow-50 to-red-50 rounded-2xl border-2 border-orange-300 p-6 mb-8 shadow-lg">
            <div class="text-center">
                <div class="flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 mr-2 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold bg-gradient-to-r from-green-700 via-orange-600 to-red-700 bg-clip-text text-transparent">
                        Total Monthly Sales ({{ $monthlyTotals['month_name'] }})
                    </h3>
                </div>
                <p class="text-5xl font-bold bg-gradient-to-r from-green-600 via-orange-500 to-red-600 bg-clip-text text-transparent mb-2">
                    ZMW {{ number_format($monthlyTotals['total_sales'], 2) }}
                </p>
                <p class="text-sm text-orange-700 font-medium">
                    Cumulative from {{ \Carbon\Carbon::parse($monthlyTotals['start_date'])->format('M d') }} to {{ \Carbon\Carbon::parse($monthlyTotals['end_date'])->format('M d, Y') }} ({{ $monthlyTotals['report_count'] }} report(s))
                </p>
            </div>
        </div>
        @endif
        <!-- Sales Items -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Sales Items</h2>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-gray-300">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 border-r border-gray-200">Product</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 border-r border-gray-200">Quantity</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 border-r border-gray-200">Unit Price</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lineItems as $item)
                        <tr class="border-b border-gray-200">
                            <td class="py-3 px-4 text-sm text-gray-900 border-r border-gray-200">
                                {{ $item->product_name }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-900 text-center border-r border-gray-200">
                                {{ $item->quantity }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-900 text-center border-r border-gray-200">
                                ZMW {{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="py-3 px-4 text-sm font-semibold text-gray-900 text-center">
                                ZMW {{ number_format($item->total_price, 2) }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-50">
                            <td colspan="3" class="py-3 px-4 text-sm font-semibold text-gray-900 text-right">
                                Subtotal:
                            </td>
                            <td class="py-3 px-4 text-sm font-bold text-gray-900 text-center">
                                ZMW {{ number_format($report->total_sales_value, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Deductions -->
        @if($report->deductions->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Deductions</h2>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-gray-300">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 border-r border-gray-200">Description</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 border-r border-gray-200">Paid From</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report->deductions as $deduction)
                        <tr class="border-b border-gray-200">
                            <td class="py-3 px-4 text-sm text-gray-900 border-r border-gray-200">
                                {{ $deduction->description }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-700 text-center border-r border-gray-200">
                                {{ ['cash' => 'Cash', 'bank' => 'Bank', 'mobile_money' => 'Mobile Money'][$deduction->payment_method ?? 'cash'] ?? $deduction->payment_method }}
                            </td>
                            <td class="py-3 px-4 text-sm font-semibold text-red-600 text-center tabular-nums">
                                ZMW {{ number_format($deduction->amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-50">
                            <td colspan="2" class="py-3 px-4 text-sm font-semibold text-gray-900 text-right">
                                Total Deductions:
                            </td>
                            <td class="py-3 px-4 text-sm font-bold text-red-600 text-center tabular-nums">
                                ZMW {{ number_format($report->total_deductions, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Notes -->
        @if($report->notes)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-3">Notes</h2>
            <p class="text-gray-700">{{ $report->notes }}</p>
        </div>
        @endif

        <!-- Report Info -->
        <div class="bg-gray-50 rounded-2xl border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Recorded By</p>
                    <p class="text-sm font-medium text-gray-900">{{ $report->user->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date Recorded</p>
                    <p class="text-sm font-medium text-gray-900">{{ $report->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Success Modal -->
@if(session('show_success_modal'))
<div id="successModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all" style="animation: slideIn 0.3s ease-out;">
        <div class="p-8 text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Success Message -->
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Report Saved Successfully!</h3>
            <p class="text-gray-600 mb-6">Your daily sales report for {{ $report->sale_date->format('M d, Y') }} has been saved.</p>

            <!-- Summary -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600">Total Sales:</span>
                    <span class="text-sm font-semibold text-gray-900">ZMW {{ number_format($report->total_sales_value, 2) }}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600">Deductions:</span>
                    <span class="text-sm font-semibold text-red-600">ZMW {{ number_format($report->total_deductions, 2) }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-gray-200">
                    <span class="text-sm font-medium text-gray-900">Cash at Hand:</span>
                    <span class="text-sm font-bold text-green-600">ZMW {{ number_format($report->cash_at_hand, 2) }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                @if(session('redirect_to_my_sales'))
                <!-- Sales Rep Buttons -->
                <a href="{{ route('sales.my-sales') }}"
                    class="w-full inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    View My Sales Reports
                </a>
                <a href="{{ route('sales.create') }}"
                    class="w-full inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    Record Another Sale
                </a>
                @else
                <!-- Admin Buttons -->
                <a href="{{ route('sales.index') }}"
                    class="w-full inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    View All Sales Reports
                </a>
                <a href="{{ route('dashboard') }}"
                    class="w-full inline-block bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    Go to Dashboard
                </a>
                @endif

                <button onclick="document.getElementById('successModal').style.display='none'"
                    class="w-full inline-block bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-lg transition duration-200">
                    Stay on This Page
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endif

@endsection