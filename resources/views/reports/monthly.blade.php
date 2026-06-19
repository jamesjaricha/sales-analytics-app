@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900">Monthly Sales Analytics</h1>
                <p class="text-gray-500 mt-2">{{ $analytics['month_name'] }}</p>
            </div>
            <div class="flex gap-4">
                <form action="{{ route('reports.monthly') }}" method="GET" class="flex gap-2">
                    <input type="month" name="month" value="{{ $month }}" 
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                        View
                    </button>
                </form>
                <a href="{{ route('reports.monthly.pdf', ['month' => $month]) }}" 
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition">
                    Export PDF
                </a>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Sales -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">Total Sales</p>
                    <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold">ZMW {{ number_format($analytics['total_sales'], 2) }}</h2>
                <p class="text-xs mt-2 opacity-75">From {{ $analytics['report_count'] }} reports</p>
            </div>

            <!-- Average Daily Sales -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">Avg. Daily Sales</p>
                    <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold">ZMW {{ number_format($analytics['average_daily_sales'], 2) }}</h2>
                <p class="text-xs mt-2 opacity-75">Per report</p>
            </div>

            <!-- Reports Count -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">Reports Filed</p>
                    <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold">{{ $analytics['report_count'] }}</h2>
                <p class="text-xs mt-2 opacity-75">Completed reports</p>
            </div>
        </div>

        <!-- Settlement breakdown -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-1">How customers paid</h2>
            <p class="text-sm text-gray-400 mb-4">POS settlement breakdown for the month</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-green-50 rounded-xl p-4"><p class="text-xs text-green-700 mb-1">Cash</p><p class="text-xl font-bold text-green-700">ZMW {{ number_format($analytics['settlement']['cash'], 2) }}</p></div>
                <div class="bg-gray-50 rounded-xl p-4"><p class="text-xs text-gray-500 mb-1">Cash @ Bank</p><p class="text-xl font-bold text-gray-800">ZMW {{ number_format($analytics['settlement']['bank'], 2) }}</p></div>
                <div class="bg-gray-50 rounded-xl p-4"><p class="text-xs text-gray-500 mb-1">Mobile Money</p><p class="text-xl font-bold text-gray-800">ZMW {{ number_format($analytics['settlement']['mobile_money'], 2) }}</p></div>
                <div class="bg-amber-50 rounded-xl p-4"><p class="text-xs text-amber-700 mb-1">Outstanding</p><p class="text-xl font-bold text-amber-700">ZMW {{ number_format($analytics['settlement']['outstanding'], 2) }}</p></div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Top Performing Products</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-gray-300">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Rank</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Product Name</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Units Sold</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analytics['top_products'] as $index => $product)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-4 text-sm">
                                    @if($index === 0)
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-yellow-800 font-bold">1</span>
                                    @elseif($index === 1)
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-700 font-bold">2</span>
                                    @elseif($index === 2)
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold">3</span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-600">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-sm font-medium text-gray-900">{{ $product->product_name }}</td>
                                <td class="py-3 px-4 text-sm text-center">
                                    <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">
                                        {{ $product->total_quantity }} units
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-sm text-right font-semibold text-gray-900">
                                    ZMW {{ number_format($product->total_revenue, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-500">No product data available for this month</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Best Performing Days -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Top 5 Sales Days</h2>
            <div class="space-y-3">
                @forelse($analytics['best_days'] as $index => $day)
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border border-green-200">
                        <div class="flex items-center gap-4">
                            <span class="text-2xl font-bold text-green-600">{{ $index + 1 }}</span>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $day->sale_date->format('l, F d, Y') }}</p>
                                <p class="text-sm text-gray-600">Recorded by {{ $day->user->name }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-600">ZMW {{ number_format($day->total_sales_value, 2) }}</p>
                            <p class="text-xs text-gray-500">Net: ZMW {{ number_format($day->cash_at_hand, 2) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-4">No sales data available</p>
                @endforelse
            </div>
        </div>

        <!-- Insights -->
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl shadow-sm border-2 border-indigo-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Key Insights</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($analytics['insights'] as $insight)
                    <div class="bg-white rounded-lg p-4 border border-indigo-200 shadow-sm">
                        <h3 class="font-semibold text-gray-900 mb-1">{{ $insight['title'] }}</h3>
                        <p class="text-sm text-gray-600">{{ $insight['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Weekly Performance -->
        @if($analytics['weekly_performance']->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Weekly Breakdown</h2>
                <div class="grid grid-cols-1 md:grid-cols-{{ min($analytics['weekly_performance']->count(), 5) }} gap-4">
                    @foreach($analytics['weekly_performance'] as $week => $data)
                        <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl border-2 border-purple-200">
                            <p class="text-sm font-semibold text-purple-700 mb-2">{{ $week }}</p>
                            <p class="text-3xl font-bold text-purple-600 mb-1">
                                ZMW {{ number_format($data['total_sales'], 0) }}
                            </p>
                            <p class="text-xs text-gray-600">{{ $data['count'] }} report(s)</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
