@extends('layouts.app')

@php
    $periodLabels = ['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'all' => 'All time', 'custom' => 'Custom'];
@endphp

@section('content')
<div class="min-h-screen bg-gradient-to-b from-emerald-50 via-gray-50 to-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 shadow-sm shadow-emerald-600/30">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h13M9 5h13M4 5h.01M4 11h.01M4 17h.01"></path></svg>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Stock Movements</h1>
                    <p class="text-gray-500 mt-0.5 text-sm">Analytics &amp; movement history</p>
                </div>
            </div>
            <a href="{{ route('stock.index') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">← Back to Stock</a>
        </div>

        <!-- Filter bar -->
        <form method="GET" action="{{ route('stock.reports') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4 sm:p-5 mb-6">
            <div class="flex flex-wrap items-end gap-4">
                <!-- Quick presets (each is its own submit, so no JS needed) -->
                <div class="flex flex-wrap gap-2">
                    @foreach(['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'all' => 'All time'] as $key => $label)
                        <button type="submit" name="period" value="{{ $key }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium border transition {{ $period === $key ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="min-w-[9rem]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">All types</option>
                        @foreach(\App\Models\StockMovement::typeOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-[10rem]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Product</label>
                    <select name="product_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">All products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected((string) $productId === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input type="date" name="start_date" value="{{ $period === 'all' ? '' : $startDate }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input type="date" name="end_date" value="{{ $period === 'all' ? '' : $endDate }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <button type="submit" name="period" value="custom"
                    class="px-4 py-2 rounded-lg bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium">Apply</button>

                <a href="{{ route('stock.movements.export', request()->query()) }}"
                    class="px-4 py-2 rounded-lg border border-emerald-600 text-emerald-700 hover:bg-emerald-50 text-sm font-medium inline-flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export CSV
                </a>
            </div>
            <p class="text-xs text-gray-400 mt-3">
                Showing <span class="font-medium text-gray-600">{{ $periodLabels[$period] ?? $period }}</span>
                ({{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }})
            </p>
        </form>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4">
                <p class="text-xs text-gray-500 mb-1">Total stock value</p>
                <p class="text-xl sm:text-2xl font-bold text-emerald-700 tabular-nums">ZMW {{ number_format($totalStockValue, 2) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4">
                <p class="text-xs text-gray-500 mb-1">Movements</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 tabular-nums">{{ $summary['total_movements'] }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4">
                <p class="text-xs text-gray-500 mb-1">Stock in</p>
                <p class="text-xl sm:text-2xl font-bold text-green-600 tabular-nums">+{{ $summary['stock_in'] }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4">
                <p class="text-xs text-gray-500 mb-1">Stock out</p>
                <p class="text-xl sm:text-2xl font-bold text-red-600 tabular-nums">{{ $summary['stock_out'] }}</p>
            </div>
        </div>

        <!-- Movements table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Movements</h3>
                <span class="text-xs text-gray-400">{{ $recentMovements->total() }} record{{ $recentMovements->total() === 1 ? '' : 's' }}</span>
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3 text-right">Qty</th>
                            <th class="px-5 py-3 text-right">Before</th>
                            <th class="px-5 py-3 text-right">After</th>
                            <th class="px-5 py-3">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentMovements as $movement)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-3 whitespace-nowrap text-gray-900">{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-gray-900">{{ $movement->product->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-400">{{ $movement->product->sku ?? '' }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $movement->type_badge_color }}-100 text-{{ $movement->type_badge_color }}-800">{{ $movement->type_label }}</span>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold tabular-nums {{ $movement->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                                <td class="px-5 py-3 text-right text-gray-500 tabular-nums">{{ $movement->stock_before }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ $movement->stock_after }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $movement->user->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No stock movements match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="sm:hidden divide-y divide-gray-100">
                @forelse($recentMovements as $movement)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ $movement->product->name ?? '-' }}</p>
                                <p class="text-xs text-gray-400">{{ $movement->created_at->format('Y-m-d H:i') }} · {{ $movement->user->name ?? 'System' }}</p>
                            </div>
                            <span class="font-semibold tabular-nums shrink-0 {{ $movement->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between text-xs">
                            <span class="px-2 inline-flex leading-5 font-semibold rounded-full bg-{{ $movement->type_badge_color }}-100 text-{{ $movement->type_badge_color }}-800">{{ $movement->type_label }}</span>
                            <span class="text-gray-400 tabular-nums">{{ $movement->stock_before }} → {{ $movement->stock_after }}</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-gray-400 text-sm">No stock movements match these filters.</div>
                @endforelse
            </div>

            <div class="px-5 py-3 border-t border-gray-100">
                {{ $recentMovements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
