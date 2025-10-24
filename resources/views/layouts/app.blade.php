<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50">
    
    <!-- Apple-style Navigation -->
    <nav class="bg-white/80 backdrop-blur-xl border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-xl font-semibold text-gray-900">Sales Analytics</a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex space-x-8">
                    <a href="/dashboard" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Dashboard</a>
                    <a href="{{ route('sales.create') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Record Sales</a>
<a href="{{ route('sales.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">View Sales</a>
                   <a href="{{ route('products.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">Products</a>

            
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                   
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors px-4 py-2 rounded-lg hover:bg-gray-100">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

</body>
</html>
