<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - Sales Analytics</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-b from-brand-50 via-gray-50 to-gray-50">

    <div class="w-full max-w-md animate-rise-in">
        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center justify-center mb-4">
                <img src="{{ asset('images/logo.png') }}" alt="Ulwazi Energy" class="h-16 sm:h-20 w-auto">
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Forgot your password?</h1>
            <p class="text-gray-500">Enter your email and we'll send you a link to reset it</p>
        </div>

        <!-- Request Reset Link Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/60 border border-gray-200/80 p-8">

            @if (session('status'))
                <div class="bg-brand-50 border border-brand-200 text-brand-800 px-4 py-3 rounded-xl mb-6 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-accent-50 border border-accent-200 text-accent-700 px-4 py-3 rounded-xl mb-6 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl transition-colors focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                        placeholder="you@example.com">
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-brand-600 hover:bg-brand-700 text-white py-3 rounded-xl font-semibold text-base shadow-sm shadow-brand-600/20 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.98]">
                    Email password reset link
                </button>
            </form>
        </div>

        <!-- Back to Login -->
        <div class="text-center mt-6">
            <a href="{{ route('login.email') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center justify-center gap-1 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to login
            </a>
        </div>
    </div>

</body>
</html>
