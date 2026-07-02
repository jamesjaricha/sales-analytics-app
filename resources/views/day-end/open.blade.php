@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-brand-50 via-gray-50 to-gray-50 py-10">
    <div class="max-w-md mx-auto px-4 sm:px-6">

        <div class="text-center mb-8 animate-rise-in">
            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 shadow-sm shadow-brand-600/30 mb-4">
                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">{{ $opening ? 'Update balance brought forward' : 'Start the day' }}</h1>
            <p class="text-gray-500 mt-1">{{ \Carbon\Carbon::parse($businessDate)->format('l, d F Y') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/60 border border-gray-200/80 p-8 animate-rise-in" style="animation-delay: 60ms">
            @if($errors->any())
                <div class="mb-4 rounded-xl bg-accent-50 border border-accent-200 text-accent-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('day.open.store') }}" x-data="{ submitting: false }" x-on:submit="submitting = true">
                @csrf

                <label for="opening_balance" class="block text-sm font-semibold text-gray-700 mb-2">Balance brought forward (ZMW)</label>
                <input id="opening_balance" type="number" min="0" step="0.01" name="opening_balance" required autofocus
                    value="{{ old('opening_balance', $opening?->opening_balance) }}"
                    inputmode="decimal" placeholder="0.00"
                    class="w-full px-4 py-3 text-2xl font-semibold text-center tabular-nums border border-gray-300 rounded-xl transition-colors focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <p class="mt-2 text-xs text-gray-500 text-center">Count the cash already in the drawer before the first sale. Enter 0 if the drawer is empty.</p>

                <button type="submit" x-bind:disabled="submitting"
                    class="mt-6 w-full bg-brand-600 hover:bg-brand-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 rounded-xl font-semibold text-base shadow-sm shadow-brand-600/20 inline-flex items-center justify-center gap-2 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.98]">
                    <svg x-show="submitting" x-cloak class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="submitting ? 'Opening…' : '{{ $opening ? 'Update & continue' : 'Open the day' }}'"></span>
                </button>
            </form>
        </div>

        @if($opening)
            <p class="text-center mt-6 text-sm text-gray-500">
                Opened by {{ $opening->openedBy?->name ?? 'unknown' }} · <a href="{{ route('pos.create') }}" class="text-brand-600 hover:text-brand-700 font-medium">Back to POS</a>
            </p>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush
