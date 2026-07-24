@extends('layouts.app')

@php
    $periodLabels = ['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'all' => 'All time', 'custom' => 'Custom'];
@endphp

@section('content')
<div class="min-h-screen bg-gradient-to-b from-emerald-50 via-gray-50 to-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">{{ $product->name }}</h1>
                <p class="text-gray-500 mt-0.5 text-sm">Stock movement history</p>
            </div>
            <a href="{{ route('stock.index') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">← Back to Stock</a>
        </div>

        <!-- Product summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4">
                <p class="text-xs text-gray-500 mb-1">SKU</p>
                <p class="text-base font-semibold text-gray-900">{{ $product->sku ?? '-' }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4">
                <p class="text-xs text-gray-500 mb-1">Current stock</p>
                <p class="text-base font-semibold text-gray-900 tabular-nums">{{ $product->stock_quantity }} {{ $product->unit_of_measurement }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4 col-span-2 lg:col-span-2 flex items-center">
                @if($product->stock_status === 'out_of_stock')
                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">Out of Stock</span>
                @elseif($product->stock_status === 'low_stock')
                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Low Stock</span>
                @else
                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">In Stock</span>
                @endif
            </div>
        </div>

        <!-- Filter bar -->
        <form method="GET" action="{{ route('stock.history', $product) }}" class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4 sm:p-5 mb-6">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-wrap gap-2">
                    @foreach(['all' => 'All time', 'today' => 'Today', 'week' => 'This week', 'month' => 'This month'] as $key => $label)
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

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <button type="submit" name="period" value="custom"
                    class="px-4 py-2 rounded-lg bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium">Apply</button>

                <a href="{{ route('stock.movements.export', array_merge(request()->query(), ['product_id' => $product->id])) }}"
                    class="px-4 py-2 rounded-lg border border-emerald-600 text-emerald-700 hover:bg-emerald-50 text-sm font-medium inline-flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export CSV
                </a>
            </div>
        </form>

        <!-- Movements -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Movement history</h3>
                <span class="text-xs text-gray-400">{{ $movements->total() }} record{{ $movements->total() === 1 ? '' : 's' }}</span>
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3 text-right">Qty</th>
                            <th class="px-5 py-3 text-right">Before</th>
                            <th class="px-5 py-3 text-right">After</th>
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($movements as $movement)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-3 whitespace-nowrap text-gray-900">{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-5 py-3"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $movement->type_badge_color }}-100 text-{{ $movement->type_badge_color }}-800">{{ $movement->type_label }}</span></td>
                                <td class="px-5 py-3 text-right font-semibold tabular-nums {{ $movement->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                                <td class="px-5 py-3 text-right text-gray-500 tabular-nums">{{ $movement->stock_before }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ $movement->stock_after }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $movement->user->name ?? 'System' }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $movement->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No stock movements match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="sm:hidden divide-y divide-gray-100">
                @forelse($movements as $movement)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $movement->type_badge_color }}-100 text-{{ $movement->type_badge_color }}-800">{{ $movement->type_label }}</span>
                            <span class="font-semibold tabular-nums {{ $movement->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between text-xs text-gray-400">
                            <span>{{ $movement->created_at->format('Y-m-d H:i') }} · {{ $movement->user->name ?? 'System' }}</span>
                            <span class="tabular-nums">{{ $movement->stock_before }} → {{ $movement->stock_after }}</span>
                        </div>
                        @if($movement->notes)
                            <p class="mt-1 text-xs text-gray-500">{{ $movement->notes }}</p>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-gray-400 text-sm">No stock movements match these filters.</div>
                @endforelse
            </div>

            <div class="px-5 py-3 border-t border-gray-100">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
