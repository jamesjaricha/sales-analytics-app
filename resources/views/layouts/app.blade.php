<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sales Analytics</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Additional Styles -->
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>
<body class="antialiased bg-gray-50">

    <!-- Apple-style Navigation -->
    <nav x-data="{ mobileOpen: false }" class="bg-white/80 backdrop-blur-xl border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('dashboard') }}" class="flex items-center">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 sm:h-10 w-auto">
                        </a>
                    @else
                        <a href="{{ route('pos.create') }}" class="flex items-center">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 sm:h-10 w-auto">
                        </a>
                    @endif
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex space-x-8">
                    @if(auth()->user()->role === 'admin')
                        <a href="/dashboard" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Dashboard</a>
                    @endif
                    <a href="{{ route('pos.create') }}" class="text-sm font-semibold {{ request()->routeIs('pos.*') ? 'text-brand-600' : 'text-gray-900' }} hover:text-brand-600 transition-colors">New Sale</a>
                    <a href="{{ route('day-end.create') }}" class="text-sm font-medium {{ request()->routeIs('day-end.*') ? 'text-brand-600' : 'text-gray-700' }} hover:text-gray-900 transition-colors">Day-End</a>
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('sales.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">View Sales</a>
                        <a href="{{ route('reports.monthly') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Monthly Report</a>
                        <a href="{{ route('products.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Products</a>
                    @else
                        <a href="{{ route('sales.my-sales') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">My Sales</a>
                    @endif
                    <a href="{{ route('stock.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Stock</a>
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('users.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Users</a>
                    @endif
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <button type="button" x-on:click="mobileOpen = !mobileOpen" x-bind:aria-expanded="mobileOpen" class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-label="Toggle navigation">
                        <svg x-show="!mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors px-4 py-2 rounded-lg hover:bg-gray-100">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div x-show="mobileOpen" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden border-t border-gray-200 bg-white/95 backdrop-blur px-4 py-4 space-y-3">
            @if(auth()->user()->role === 'admin')
                <a href="/dashboard" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Dashboard</a>
            @endif
            <a href="{{ route('pos.create') }}" class="block text-sm font-semibold text-gray-900 hover:text-brand-600 transition-colors">New Sale (POS)</a>
            <a href="{{ route('day-end.create') }}" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Day-End</a>
            @if(auth()->user()->role === 'admin')
            <a href="{{ route('sales.index') }}" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">View Sales</a>
                <a href="{{ route('reports.monthly') }}" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Monthly Report</a>
                <a href="{{ route('products.index') }}" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Products</a>
            @else
                <a href="{{ route('sales.my-sales') }}" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">My Sales</a>
            @endif
            <a href="{{ route('stock.index') }}" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Stock</a>
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('users.index') }}" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Users</a>
            @endif
        </div>
    </nav>

    <!-- Toast notifications -->
    @if(session('success') || session('error'))
        @php($toastType = session('success') ? 'success' : 'error')
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 translate-x-2"
            role="status" aria-live="polite"
            class="fixed top-20 inset-x-4 sm:inset-x-auto sm:right-6 z-[100] sm:max-w-sm">
            <div class="flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg {{ $toastType === 'success' ? 'bg-white border-brand-200' : 'bg-white border-accent-200' }}">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $toastType === 'success' ? 'bg-brand-100 text-brand-700' : 'bg-accent-100 text-accent-700' }}">
                    @if($toastType === 'success')
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    @else
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                    @endif
                </span>
                <p class="text-sm text-gray-800 flex-1">{{ session('success') ?? session('error') }}</p>
                <button type="button" x-on:click="show = false" aria-label="Dismiss" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
