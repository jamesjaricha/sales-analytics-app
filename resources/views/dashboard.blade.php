@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-10">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Dashboard</h1>
            <p class="text-gray-500">{{ now()->format('l, F j, Y') }}</p>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 animate-rise-in" style="animation-delay: 0ms">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Total Sales</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1 tabular-nums">ZMW {{ number_format($totalSales, 0) }}</p>
                <p class="text-xs text-gray-500">This month</p>
            </div>
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 animate-rise-in" style="animation-delay: 60ms">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Invoices</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1 tabular-nums">{{ $invoiceCount }}</p>
                <p class="text-xs text-gray-500">This month (POS)</p>
            </div>
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 animate-rise-in" style="animation-delay: 120ms">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Avg. Daily Sales</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1 tabular-nums">ZMW {{ number_format($averageDailySales, 2) }}</p>
                <p class="text-xs text-gray-500">Per active day</p>
            </div>
        </div>

        <!-- Settlement breakdown (this month) + Today -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-1">How customers paid</h2>
                <p class="text-sm text-gray-500 mb-6">This month, from POS invoices</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-brand-50 rounded-2xl p-4">
                        <p class="text-xs text-brand-700 font-medium mb-1">Cash</p>
                        <p class="text-xl font-bold text-brand-700 tabular-nums">{{ number_format($settlement['cash'], 0) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <p class="text-xs text-gray-500 font-medium mb-1">Cash @ Bank</p>
                        <p class="text-xl font-bold text-gray-800 tabular-nums">{{ number_format($settlement['bank'], 0) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <p class="text-xs text-gray-500 font-medium mb-1">Mobile Money</p>
                        <p class="text-xl font-bold text-gray-800 tabular-nums">{{ number_format($settlement['mobile_money'], 0) }}</p>
                    </div>
                    <div class="bg-amber-50 rounded-2xl p-4">
                        <p class="text-xs text-amber-700 font-medium mb-1">Outstanding</p>
                        <p class="text-xl font-bold text-amber-700 tabular-nums">{{ number_format($settlement['outstanding'], 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-brand-600 to-brand-700 rounded-3xl shadow-sm p-8 text-white">
                <p class="text-sm text-brand-50">Today's takings</p>
                <p class="text-4xl font-bold mt-1 mb-4 tabular-nums">ZMW {{ number_format($todayTotal, 2) }}</p>
                <div class="space-y-1 text-sm text-brand-50/90">
                    <div class="flex justify-between"><span>Cash</span><span class="tabular-nums">{{ number_format($todaySettlement['cash'], 2) }}</span></div>
                    <div class="flex justify-between"><span>Bank</span><span class="tabular-nums">{{ number_format($todaySettlement['bank'], 2) }}</span></div>
                    <div class="flex justify-between"><span>Mobile</span><span class="tabular-nums">{{ number_format($todaySettlement['mobile_money'], 2) }}</span></div>
                    <div class="flex justify-between"><span>Outstanding</span><span class="tabular-nums">{{ number_format($todaySettlement['outstanding'], 2) }}</span></div>
                </div>
            </div>
        </div>

        <!-- Last 7 Days chart -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 mb-8">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Last 7 Days</h2>
                    <p class="text-sm text-gray-500">Compared to previous 7 days</p>
                </div>
                <p class="text-3xl font-bold tabular-nums {{ $changePercent >= 0 ? 'text-brand-600' : 'text-accent-600' }}">
                    {{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent, 1) }}%
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-brand-50 rounded-2xl p-4">
                    <p class="text-xs text-brand-700 font-medium mb-1">Last 7 Days</p>
                    <p class="text-2xl font-bold text-brand-700 tabular-nums">{{ number_format($last7DaysTotal, 0) }}</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4">
                    <p class="text-xs text-gray-500 font-medium mb-1">Previous 7 Days</p>
                    <p class="text-2xl font-bold text-gray-600 tabular-nums">{{ number_format($previous7DaysTotal, 0) }}</p>
                </div>
            </div>
            <div style="position: relative; height: 280px;">
                <canvas id="weeklyComparisonChart"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 mb-10">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Top Products</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-4 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="text-center py-4 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="text-right py-4 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $index => $product)
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-2">
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-brand-600 text-white text-sm font-bold tabular-nums">{{ $index + 1 }}</span>
                                        <span class="font-medium text-gray-900">{{ $product->product_name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-2 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-700 tabular-nums">{{ $product->total_quantity }}</span>
                                </td>
                                <td class="py-4 px-2 text-right font-bold text-gray-900 tabular-nums">ZMW {{ number_format($product->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-12 text-center text-gray-500">
                                    <p class="text-lg mb-2">No sales data yet</p>
                                    <p class="text-sm">Start recording sales to see insights</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('pos.create') }}" class="bg-gradient-to-br from-brand-500 to-brand-600 rounded-3xl p-8 text-white hover:shadow-2xl transition-shadow duration-300 ease-out" style="text-decoration: none;">
                <h3 class="text-2xl font-bold mb-1">New Sale</h3>
                <p class="text-brand-50">Record a POS invoice</p>
            </a>
            <a href="{{ route('day-end.create') }}" class="bg-gradient-to-br from-brand-600 to-brand-700 rounded-3xl p-8 text-white hover:shadow-2xl transition-shadow duration-300 ease-out" style="text-decoration: none;">
                <h3 class="text-2xl font-bold mb-1">Day-End</h3>
                <p class="text-brand-50">Reconcile &amp; approve today</p>
            </a>
            <a href="{{ route('products.index') }}" class="bg-gradient-to-br from-gray-700 to-gray-800 rounded-3xl p-8 text-white hover:shadow-2xl transition-shadow duration-300 ease-out" style="text-decoration: none;">
                <h3 class="text-2xl font-bold mb-1">Products</h3>
                <p class="text-gray-300">Manage your inventory</p>
            </a>
        </div>

    </div>
</div>

@push('scripts')
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') { console.error('Chart.js is not loaded'); return; }
    const el = document.getElementById('weeklyComparisonChart');
    if (!el) return;

    new Chart(el.getContext('2d'), {
        type: 'bar',
        data: {
            labels: {!! $chartLabels !!},
            datasets: [
                {
                    label: 'Last 7 Days',
                    data: {!! $last7DaysValues !!},
                    backgroundColor: 'rgba(5, 150, 105, 0.85)',
                    borderRadius: 8,
                    borderSkipped: false,
                },
                {
                    label: 'Previous 7 Days',
                    data: {!! $previous7DaysValues !!},
                    backgroundColor: 'rgba(156, 163, 175, 0.5)',
                    borderRadius: 8,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'bottom', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle' } },
                tooltip: {
                    callbacks: {
                        label: function (context) { return context.dataset.label + ': ZMW ' + context.parsed.y.toLocaleString(); }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } }
            }
        }
    });
});
</script>
@endpush
@endsection
