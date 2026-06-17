<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Sales Analytics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Access Denied</h1>
            <p class="text-gray-600 mb-8">
                You do not have permission to access this page. This incident has been logged.
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
                @endauth
                
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" 
                            class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                        Logout & Return to Login
                    </button>
                </form>
            </div>
            
            <p class="mt-6 text-sm text-gray-500">
                If you believe this is an error, please contact your administrator.
            </p>
        </div>
    </div>
</body>
</html>
