@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900">Daily Sales Reports</h1>
                <p class="text-gray-500 mt-2">View all recorded daily sales</p>
            </div>
            <a href="{{ route('sales.create') }}"
                class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 rounded-lg font-medium shadow-sm [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.97]">
                + New Sale
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <form method="GET" action="{{ route('sales.index') }}" class="flex flex-wrap gap-4 items-end">
                <!-- Month Filter -->
                <div class="flex-1 min-w-[200px]">
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-2">Filter by Month</label>
                    <input type="month" name="month" id="month" value="{{ request('month') }}"
                           class="w-full px-4 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>

                <!-- User Filter -->
                <div class="flex-1 min-w-[200px]">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">Filter by User</label>
                    <select name="user_id" id="user_id" class="w-full px-4 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 focus:ring-2 focus:ring-brand-500 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.97]">
                        Apply Filters
                    </button>
                    @if(request('month') || request('user_id'))
                        <a href="{{ route('sales.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            @if(request('month') || request('user_id'))
                <div class="mt-4 p-3 bg-brand-50 border border-brand-200 rounded-lg text-sm text-brand-700">
                    <strong>Filtered Results:</strong> Showing {{ $reports->total() }} report(s)
                    @if(request('month'))
                        for <strong>{{ \Carbon\Carbon::parse(request('month'))->format('F Y') }}</strong>
                    @endif
                    @if(request('user_id'))
                        by <strong>{{ $users->firstWhere('id', request('user_id'))->name }}</strong>
                    @endif
                </div>
            @endif
        </div>

        <!-- Sales Reports — mobile cards -->
        <div class="md:hidden space-y-3">
            @forelse($reports as $report)
                <a href="{{ route('sales.show', $report->id) }}"
                    class="block bg-white rounded-2xl shadow-sm border border-gray-200 p-4 active:scale-[0.99] transition-transform">
                    <div class="flex justify-between items-start mb-3">
                        <p class="font-semibold text-gray-900">{{ $report->sale_date->format('D, M d, Y') }}</p>
                        <p class="text-xs text-gray-500">{{ $report->user->name }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <p class="text-xs text-gray-500">Sales</p>
                            <p class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($report->total_sales_value, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Deductions</p>
                            <p class="text-sm text-red-600 tabular-nums">{{ number_format($report->total_deductions, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Cash</p>
                            <p class="text-sm font-bold text-green-600 tabular-nums">{{ number_format($report->cash_at_hand, 2) }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center text-gray-500">
                    <p class="mb-2">No sales reports yet</p>
                    <a href="{{ route('sales.create') }}" class="text-brand-600 hover:text-brand-700 font-medium">Record your first sale →</a>
                </div>
            @endforelse
        </div>

        <!-- Sales Reports Table (desktop) -->
        <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Date</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Total Sales</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Deductions</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Cash at Hand</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Recorded By</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-4 px-6 text-sm text-gray-900">
                                    {{ $report->sale_date->format('D, M d, Y') }}
                                </td>
                                <td class="py-4 px-6 text-sm font-semibold text-gray-900 tabular-nums">
                                    ZMW {{ number_format($report->total_sales_value, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm text-red-600 tabular-nums">
                                    ZMW {{ number_format($report->total_deductions, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm font-bold text-green-600 tabular-nums">
                                    ZMW {{ number_format($report->cash_at_hand, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600">
                                    {{ $report->user->name }}
                                </td>
                                <td class="py-4 px-6 text-sm text-center">
                                    <a href="{{ route('sales.show', $report->id) }}"
                                        class="inline-flex items-center text-brand-600 hover:text-brand-700 font-semibold">
                                        View Details
                                    </a>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-500">
                                    <p class="text-lg mb-2">No sales reports yet</p>
                                    <a href="{{ route('sales.create') }}" class="text-brand-600 hover:text-brand-700 font-medium">
                                        Record your first sale →
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($reports->hasPages())
            <div class="mt-6">
                {{ $reports->appends(request()->query())->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
