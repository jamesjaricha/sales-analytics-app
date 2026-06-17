<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - Sales Analytics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-6xl font-bold text-gray-900 mb-4">500</h1>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Server Error</h2>
            <p class="text-gray-600 mb-8">
                Something went wrong on our end. We've been notified and are working to fix it.
            </p>
            
            <div class="space-y-3">
                @auth
                    @if(auth()->user()->role === 'sales_rep')
                        <a href="{{ route('sales.create') }}" 
                           class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                            Go to Record Sales
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" 
                           class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                            Go to Dashboard
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" 
                       class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                        Go to Login
                    </a>
                @endauth
            </div>
            
            <p class="mt-6 text-sm text-gray-500">
                Error reference: {{ uniqid() }}
            </p>
        </div>
    </div>
</body>
</html>
