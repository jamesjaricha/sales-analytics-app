@extends('layouts.app')

@section('content')
<!-- Hidden flash message data -->
@if(session('success'))
<div data-flash-success="{{ session('success') }}" class="hidden"></div>
@endif
@if(session('error'))
<div data-flash-error="{{ session('error') }}" class="hidden"></div>
@endif
@if($errors->any())
<div data-validation-errors="{{ json_encode($errors->all()) }}" class="hidden"></div>
@endif

<div class="min-h-screen bg-gray-50 py-8">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

		<!-- Header -->
		<div class="mb-8">
			<div class="flex items-center gap-3 mb-2">
				<span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">DRAFT</span>
				<h1 class="text-3xl font-semibold text-gray-900">Edit Draft Sales Report</h1>
			</div>
			<p class="text-gray-500">Complete or update this draft report</p>
		</div>

		<form action="{{ route('sales.store') }}" method="POST" id="salesForm"
			data-mode="edit"
			data-route-store="{{ route('sales.store') }}"
			data-route-product-search="{{ route('sales.products.search') }}"
			data-route-quick-create="{{ route('sales.products.quick-create') }}">
			@csrf

			<!-- Sale Date -->
			<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
				<label class="block text-sm font-medium text-gray-700 mb-2">Sale Date *</label>
				<input type="date" name="sale_date" value="{{ old('sale_date', $report->sale_date->format('Y-m-d')) }}"
					class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
			</div>

			<!-- Sales Items -->
			<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
				<h2 class="text-lg font-semibold text-gray-900 mb-4">Sales Items *</h2>

				<div class="mb-4">
					<button type="button" id="addItemBtn" class="btn-success">
						+ Add Item
					</button>
				</div>

				<div class="overflow-x-auto">
					<table class="w-full table-fixed">
						<thead>
							<tr class="border-b-2 border-gray-300">
								<th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[35%]">Product Name</th>
								<th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[15%]">Quantity</th>
								<th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[20%]">Unit Price</th>
								<th class="text-right py-3 px-2 text-sm font-semibold text-gray-700 w-[18%]">Total</th>
								<th class="text-center py-3 px-2 text-sm font-semibold text-gray-700 w-[12%]"></th>
							</tr>
						</thead>
						<tbody id="itemsContainer">
							@php $i = 0; @endphp
							@foreach($report->items as $item)
							<tr class="border-b border-gray-100">
								<td class="py-3 px-2">
									<input type="hidden" name="items[{{ $i }}][product_id]" class="product-id-field" value="{{ $item->product_id }}">
									<input type="text" name="items[{{ $i }}][product_name]" class="product-name-field hidden" value="{{ $item->product_name }}">
									<input type="text" class="product-search-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Search product..." autocomplete="off" data-row-index="{{ $i }}" value="{{ $item->product_name }}">
								</td>
								<td class="py-3 px-2">
									<input type="number" name="items[{{ $i }}][quantity]" class="quantity-input w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm" min="1" value="{{ $item->quantity }}">
								</td>
								<td class="py-3 px-2">
									<input type="number" name="items[{{ $i }}][unit_price]" class="price-input w-28 px-3 py-2 border border-gray-300 rounded-lg text-sm" min="0" step="0.01" value="{{ number_format($item->unit_price, 2, '.', '') }}">
								</td>
								<td class="py-3 px-2 text-right">
									<span class="item-total font-semibold">0.00</span>
								</td>
								<td class="py-3 px-2 text-center">
									<button type="button" class="remove-item-btn text-red-600 hover:text-red-800 text-sm font-medium px-2">Remove</button>
								</td>
							</tr>
							@php $i++; @endphp
							@endforeach
							@if($report->items->isEmpty())
							<!-- Ensure at least one row exists -->
							<tr class="border-b border-gray-100">
								<td class="py-3 px-2">
									<input type="hidden" name="items[0][product_id]" class="product-id-field">
									<input type="text" name="items[0][product_name]" class="product-name-field hidden">
									<input type="text" class="product-search-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Search product..." autocomplete="off" data-row-index="0">
								</td>
								<td class="py-3 px-2">
									<input type="number" name="items[0][quantity]" class="quantity-input w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm" min="1" value="1">
								</td>
								<td class="py-3 px-2">
									<input type="number" name="items[0][unit_price]" class="price-input w-28 px-3 py-2 border border-gray-300 rounded-lg text-sm" min="0" step="0.01" value="0">
								</td>
								<td class="py-3 px-2 text-right">
									<span class="item-total font-semibold">0.00</span>
								</td>
								<td class="py-3 px-2 text-center">
									<button type="button" class="remove-item-btn text-red-600 hover:text-red-800 text-sm font-medium px-2">Remove</button>
								</td>
							</tr>
							@endif
						</tbody>
					</table>
				</div>

				<div class="mt-6 pt-4 border-t border-gray-200">
					<div class="flex justify-end">
						<div class="text-right">
							<p class="text-sm text-gray-500">Total Sales Value</p>
							<p class="text-2xl font-bold text-gray-900">ZMW <span id="totalSalesValue">0.00</span></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Deductions -->
			<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
				<div class="flex justify-between items-center mb-4">
					<h2 class="text-lg font-semibold text-gray-900">Deductions</h2>
					<button type="button" id="addDeductionBtn"
						class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
						+ Add Deduction
					</button>
				</div>

				<div id="deductionsContainer" class="space-y-3">
					@php $d = 0; @endphp
					@foreach($report->deductions as $deduction)
					<div class="flex gap-4">
						<input type="text" name="deductions[{{ $d }}][description]" value="{{ $deduction->description }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" placeholder="e.g., Gel Battery Purchase">
						<input type="number" step="0.01" name="deductions[{{ $d }}][amount]" value="{{ number_format($deduction->amount, 2, '.', '') }}" class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500" placeholder="0.00" min="0">
						<button type="button" class="remove-deduction-btn text-red-600 hover:text-red-800 text-sm font-medium px-2">Remove</button>
					</div>
					@php $d++; @endphp
					@endforeach
				</div>

				<div class="mt-6 pt-4 border-t border-gray-200">
					<div class="flex justify-end">
						<div class="text-right">
							<p class="text-sm text-gray-500">Total Deductions</p>
							<p class="text-2xl font-bold text-red-600">ZMW <span id="totalDeductions">0.00</span></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Cash at Hand -->
			<div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200 p-6 mb-6">
				<div class="text-center">
					<p class="text-sm font-medium text-green-700 mb-2">Cash at Hand</p>
					<p class="text-4xl font-bold text-green-900">ZMW <span id="cashAtHand">0.00</span></p>
				</div>
			</div>

			<!-- Submit Buttons -->
			<div class="flex gap-4 mt-6">
				<a href="{{ route('sales.index') }}"
					class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold transition text-center">
					Cancel
				</a>
				<button type="button" id="saveDraftBtn"
					class="flex-1 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold transition">
					Save as Draft
				</button>
				<button type="submit"
					class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-xl font-semibold transition">
					Complete & Submit
				</button>
			</div>

		</form>

	</div>
</div>

<!-- Quick Create Product Modal -->
<div id="createProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4">
	<div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
		<div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-2xl">
			<div class="flex items-center justify-between">
				<h3 class="text-xl font-bold text-gray-900">Create New Product</h3>
				<button type="button" id="modalCloseBtn" class="text-gray-400 hover:text-gray-600">
					<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
					</svg>
				</button>
			</div>
		</div>

		<form id="quickCreateForm" class="p-6">
			<div class="space-y-4">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
					<input type="text" id="newProductName"
						class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
						placeholder="e.g., Luxpower 6kw Inverter" required>
				</div>

				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">Price (ZMW) *</label>
					<input type="number" step="0.01" id="newProductPrice"
						class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
						placeholder="0.00" min="0" required>
				</div>

				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">SKU (Optional)</label>
					<input type="text" id="newProductSku"
						class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
						placeholder="e.g., LUX-6KW-001">
				</div>

				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">Description (Optional)</label>
					<textarea id="newProductDescription" rows="3"
						class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm resize-none"
						placeholder="Brief product description"></textarea>
				</div>

				<div id="modalError" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>
			</div>

			<div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
				<button type="button" id="modalCancelBtn"
					class="flex-1 px-4 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition">
					Cancel
				</button>
				<button type="submit" id="createBtn"
					class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
					Create Product
				</button>
			</div>
		</form>
	</div>
</div>

@push('styles')
@vite('resources/css/sales-form.css')
@endpush

@push('scripts')
@vite('resources/js/sales-form.js')
@endpush
@endsection