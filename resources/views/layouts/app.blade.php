<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sales Analytics</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="antialiased bg-gray-50">

    <!-- Apple-style Navigation -->
    <nav class="bg-white/80 backdrop-blur-xl border-b border-gray-200 sticky top-0 z-50">
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
                    <a href="{{ route('pos.create') }}" class="text-sm font-semibold {{ request()->routeIs('pos.*') ? 'text-blue-600' : 'text-gray-900' }} hover:text-blue-600 transition-colors">New Sale</a>
                    <a href="{{ route('sales.create') }}" class="text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">Batch Entry</a>
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
                    <button type="button" id="mobileMenuButton" class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Toggle navigation">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
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
        <div id="mobileMenu" class="md:hidden border-t border-gray-200 bg-white/95 backdrop-blur px-4 py-4 space-y-3 hidden">
            @if(auth()->user()->role === 'admin')
                <a href="/dashboard" class="block text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Dashboard</a>
            @endif
            <a href="{{ route('pos.create') }}" class="block text-sm font-semibold text-gray-900 hover:text-blue-600 transition-colors">New Sale (POS)</a>
            <a href="{{ route('sales.create') }}" class="block text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">Batch Entry</a>
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

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Additional Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');

            if (toggleButton && mobileMenu) {
                toggleButton.addEventListener('click', function () {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
