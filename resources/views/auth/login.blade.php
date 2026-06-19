<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Sales Analytics</title>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome back</h1>
            <p class="text-gray-500">Sign in to your Sales Analytics account</p>
        </div>

        <!-- Login Form Card -->
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

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl transition-colors focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                        placeholder="you@example.com">
                </div>

                <!-- Password -->
                <div x-data="{ show: false }">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <input id="password" x-bind:type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-xl transition-colors focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                            placeholder="Enter your password">
                        <button type="button" x-on:click="show = !show" x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-brand-600">
                            <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Remember me</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-brand-600 hover:bg-brand-700 text-white py-3 rounded-xl font-semibold text-base shadow-sm shadow-brand-600/20 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.98]">
                    Sign in
                </button>
            </form>
        </div>

        <!-- Security Notice -->
        <p class="text-center mt-6 text-sm text-gray-500">
            Access is restricted. Contact your administrator for an account.
        </p>
    </div>

    <script>
        sessionStorage.removeItem('sales_form_autosave');
    </script>

</body>
</html>
