@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900">My Sales Reports</h1>
                <p class="text-gray-500 mt-2">View your recorded daily sales</p>
            </div>
            <a href="{{ route('sales.create') }}"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition shadow-lg hover:shadow-xl">
                + Record New Sale
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Sales Reports Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Date</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Total Sales</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Deductions</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Cash at Hand</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-4 px-6 text-sm text-gray-900">
                                    {{ $report->sale_date->format('D, M d, Y') }}
                                </td>
                                <td class="py-4 px-6 text-sm font-semibold text-gray-900">
                                    ZMW {{ number_format($report->total_sales_value, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm text-red-600">
                                    ZMW {{ number_format($report->total_deductions, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm font-bold text-green-600">
                                    ZMW {{ number_format($report->cash_at_hand, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm">
                                    <div class="flex gap-3">
                                        <a href="{{ route('sales.show', $report->id) }}"
                                            class="text-green-600 hover:text-green-800 font-semibold">
                                            View Details
                                        </a>
                                        <a href="{{ route('sales.pdf', $report->id) }}"
                                            class="text-blue-600 hover:text-blue-800 font-semibold"
                                            target="_blank">
                                            Print PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-500">
                                    <p class="text-lg mb-2">No sales reports yet</p>
                                    <a href="{{ route('sales.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">
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
                {{ $reports->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
