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
            📄 Download PDF
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
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200 p-6">
                <p class="text-sm font-medium text-green-700 mb-1">Cash at Hand</p>
                <h2 class="text-3xl font-bold text-green-900">ZMW {{ number_format($report->cash_at_hand, 2) }}</h2>
            </div>
        </div>

        <!-- Monthly Cumulative Sales -->
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
        </div>        <!-- Sales Items -->
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
                        @foreach($report->items as $item)
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
                                <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->deductions as $deduction)
                                <tr class="border-b border-gray-200">
                                    <td class="py-3 px-4 text-sm text-gray-900 border-r border-gray-200">
                                        {{ $deduction->description }}
                                    </td>
                                    <td class="py-3 px-4 text-sm font-semibold text-red-600 text-center">
                                        ZMW {{ number_format($deduction->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50">
                                <td class="py-3 px-4 text-sm font-semibold text-gray-900 text-right">
                                    Total Deductions:
                                </td>
                                <td class="py-3 px-4 text-sm font-bold text-red-600 text-center">
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
@endsection
