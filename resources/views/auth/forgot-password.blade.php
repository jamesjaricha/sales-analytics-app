<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password - Sales Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center gap-2 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Create New Password</h1>
            <p class="text-gray-600">Enter your new password below</p>
        </div>

        <!-- Reset Password Form Card -->
        <div class="bg-white rounded-3xl shadow-xl border border-gray-200 p-8">
            
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf

                <input type="hidden" name="token" value="{{ request()->route('token') }}">

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', request()->email) }}" required autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-gray-50"
                        placeholder="you@example.com" readonly>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                    <input id="password" type="password" name="password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Minimum 8 characters">
                    <p class="text-xs text-gray-500 mt-1">Use 8+ characters with mix of letters & numbers</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Re-enter your new password">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 rounded-xl font-semibold text-base hover:shadow-lg transition transform hover:-translate-y-0.5">
                    Reset Password
                </button>
            </form>
        </div>

        <!-- Back to Login -->
        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center justify-center gap-1 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to login
            </a>
        </div>
    </div>

</body>
</html>
