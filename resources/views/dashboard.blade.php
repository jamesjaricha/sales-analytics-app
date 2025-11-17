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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            
            <!-- Total Sales Card (matches reports) -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Total Sales</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1">ZMW {{ number_format($totalSales, 0) }}</p>
                <p class="text-xs text-gray-400">This month</p>
            </div>

            <!-- Total Reports Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Sales Reports</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1">{{ $totalReports }}</p>
                <p class="text-xs text-gray-400">This month</p>
            </div>

            <!-- Avg Daily Sales Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Avg. Daily Sales</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1">ZMW {{ number_format($averageDailySales, 2) }}</p>
                <p class="text-xs text-gray-400">Per report</p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
            
            <!-- Last 7 Days Sales -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-1">Last 7 Days</h2>
                        <p class="text-sm text-gray-400">Compared to previous 7 days</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold {{ $changePercent >= 0 ? 'text-green-500' : 'text-red-500' }}">
                            {{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent, 1) }}%
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-2xl p-4">
                        <p class="text-xs text-blue-600 font-medium mb-1">Last 7 Days</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($last7DaysTotal, 0) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <p class="text-xs text-gray-500 font-medium mb-1">Previous 7 Days</p>
                        <p class="text-2xl font-bold text-gray-600">{{ number_format($previous7DaysTotal, 0) }}</p>
                    </div>
                </div>
                <div style="position: relative; height: 280px;">
                    <canvas id="weeklyComparisonChart"></canvas>
                </div>
            </div>

            <!-- Cash at Hand -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Cash at Hand</h2>
                    <p class="text-sm text-gray-400">Last 7 days comparison</p>
                </div>
                <div style="position: relative; height: 340px;">
                    <canvas id="cashWeeklyChart"></canvas>
                </div>
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
                                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white text-sm font-bold">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="font-medium text-gray-900">{{ $product->product_name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-2 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-700">
                                        {{ $product->total_quantity }}
                                    </span>
                                </td>
                                <td class="py-4 px-2 text-right font-bold text-gray-900">
                                    ZMW {{ number_format($product->total_revenue, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-12 text-center">
                                    <div class="text-gray-400">
                                        <p class="text-lg mb-2">No sales data yet</p>
                                        <p class="text-sm">Start recording sales to see insights</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="{{ route('sales.create') }}" class="group bg-gradient-to-br from-blue-500 to-blue-600 rounded-3xl p-8 text-white hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1" style="text-decoration: none;">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold">Record Sales</h3>
                </div>
                <p class="text-blue-100">Enter today's sales data</p>
            </a>
            
            <a href="{{ route('products.index') }}" class="group bg-gradient-to-br from-green-500 to-green-600 rounded-3xl p-8 text-white hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1" style="text-decoration: none;">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold">Products</h3>
                </div>
                <p class="text-green-100">Manage your inventory</p>
            </a>
        </div>

    </div>
</div>

<script>
// Last 7 Days Sales Chart
const weeklyComparisonCtx = document.getElementById('weeklyComparisonChart').getContext('2d');
new Chart(weeklyComparisonCtx, {
    type: 'bar',
    data: {
        labels: {!! $chartLabels !!},
        datasets: [
            {
                label: 'Last 7 Days',
                data: {!! $last7DaysValues !!},
                backgroundColor: 'rgba(59, 130, 246, 0.9)',
                borderRadius: 8,
                borderSkipped: false,
            },
            {
                label: 'Previous 7 Days',
                data: {!! $previous7DaysValues !!},
                backgroundColor: 'rgba(209, 213, 219, 0.6)',
                borderRadius: 8,
                borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 13,
                        weight: '500'
                    },
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                borderRadius: 8,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ZMW ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11,
                        weight: '500'
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});

// Cash at Hand Chart
const cashWeeklyCtx = document.getElementById('cashWeeklyChart').getContext('2d');
new Chart(cashWeeklyCtx, {
    type: 'line',
    data: {
        labels: {!! $chartLabels !!},
        datasets: [
            {
                label: 'Last 7 Days',
                data: {!! $last7DaysCash !!},
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 6,
                pointHoverRadius: 8,
                borderWidth: 3,
                pointBackgroundColor: 'rgb(34, 197, 94)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            },
            {
                label: 'Previous 7 Days',
                data: {!! $previous7DaysCash !!},
                borderColor: 'rgb(209, 213, 219)',
                backgroundColor: 'rgba(209, 213, 219, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 6,
                pointHoverRadius: 8,
                borderWidth: 3,
                pointBackgroundColor: 'rgb(209, 213, 219)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                borderDash: [5, 5]
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 13,
                        weight: '500'
                    },
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                borderRadius: 8,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ZMW ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11,
                        weight: '500'
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});
</script>
@endsection
