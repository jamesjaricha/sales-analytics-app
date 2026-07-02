<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in - Sales Analytics</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-b from-brand-50 via-gray-50 to-gray-50">

    <div class="w-full max-w-md animate-rise-in" x-data="pinLogin">
        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="Ulwazi Energy" class="inline-block h-16 sm:h-20 w-auto mb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Till sign in</h1>
            <p class="text-gray-500" x-text="selected ? 'Enter your PIN, ' + selectedName : 'Tap your name to continue'"></p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/60 border border-gray-200/80 p-8">

            @if ($errors->any())
                <div class="bg-accent-50 border border-accent-200 text-accent-700 px-4 py-3 rounded-xl mb-6 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($reps->isEmpty())
                <p class="text-sm text-gray-500 text-center py-4">
                    No till operators have a PIN yet. An admin can set PINs under Users,
                    or <a href="{{ route('login.email') }}" class="text-brand-600 hover:text-brand-700 font-medium">sign in with email</a>.
                </p>
            @else
                <!-- Step 1: pick a name -->
                <div x-show="!selected" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($reps as $rep)
                        <button type="button" x-on:click="pick({{ $rep->id }}, @js($rep->name))"
                            class="px-4 py-4 rounded-xl border-2 border-gray-200 text-gray-800 font-semibold hover:border-brand-500 hover:bg-brand-50 text-center [transition:background-color_160ms_var(--ease-out),border-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.97]">
                            {{ $rep->name }}
                        </button>
                    @endforeach
                </div>

                <!-- Step 2: PIN pad -->
                <form x-show="selected" x-cloak method="POST" action="{{ route('login.pin') }}" x-on:submit="submitting = true">
                    @csrf
                    <input type="hidden" name="user_id" x-bind:value="selected">
                    <input type="hidden" name="pin" x-bind:value="pin">

                    <!-- PIN dots -->
                    <div class="flex justify-center gap-3 mb-6 h-4" aria-label="PIN entered">
                        <template x-for="i in 6" :key="i">
                            <span class="w-3.5 h-3.5 rounded-full border-2"
                                x-bind:class="i <= pin.length ? 'bg-brand-600 border-brand-600' : 'border-gray-300'"
                                x-show="i <= Math.max(pin.length, 4)"></span>
                        </template>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <template x-for="d in [1,2,3,4,5,6,7,8,9]" :key="d">
                            <button type="button" x-on:click="press(d)"
                                class="py-4 rounded-xl bg-gray-50 border border-gray-200 text-xl font-semibold text-gray-800 hover:bg-brand-50 hover:border-brand-300 [transition:background-color_120ms_var(--ease-out),transform_120ms_var(--ease-out)] active:scale-[0.95]"
                                x-text="d"></button>
                        </template>
                        <button type="button" x-on:click="back()" aria-label="Back to names"
                            class="py-4 rounded-xl bg-gray-50 border border-gray-200 text-sm font-medium text-gray-500 hover:bg-gray-100 active:scale-[0.95] [transition:background-color_120ms_var(--ease-out),transform_120ms_var(--ease-out)]">
                            Back
                        </button>
                        <button type="button" x-on:click="press(0)"
                            class="py-4 rounded-xl bg-gray-50 border border-gray-200 text-xl font-semibold text-gray-800 hover:bg-brand-50 hover:border-brand-300 [transition:background-color_120ms_var(--ease-out),transform_120ms_var(--ease-out)] active:scale-[0.95]">
                            0
                        </button>
                        <button type="button" x-on:click="pin = pin.slice(0, -1)" aria-label="Delete last digit"
                            class="py-4 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 active:scale-[0.95] [transition:background-color_120ms_var(--ease-out),transform_120ms_var(--ease-out)]">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"></path></svg>
                        </button>
                    </div>

                    <button type="submit" x-bind:disabled="pin.length < 4 || submitting"
                        class="w-full bg-brand-600 hover:bg-brand-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 rounded-xl font-semibold text-base shadow-sm shadow-brand-600/20 inline-flex items-center justify-center gap-2 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)] active:scale-[0.98]">
                        <svg x-show="submitting" x-cloak class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="submitting ? 'Signing in…' : 'Sign in'"></span>
                    </button>
                </form>
            @endif
        </div>

        <p class="text-center mt-6 text-sm text-gray-500">
            Admin? <a href="{{ route('login.email') }}" class="text-brand-600 hover:text-brand-700 font-medium">Sign in with email</a>
        </p>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pinLogin', () => ({
                selected: null,
                selectedName: '',
                pin: '',
                submitting: false,

                pick(id, name) {
                    this.selected = id;
                    this.selectedName = name;
                    this.pin = '';
                },

                press(d) {
                    if (this.pin.length < 6) this.pin += String(d);
                },

                back() {
                    this.selected = null;
                    this.selectedName = '';
                    this.pin = '';
                },

                init() {
                    // Physical keyboard support for the PIN pad
                    window.addEventListener('keydown', (e) => {
                        if (!this.selected || this.submitting) return;
                        if (/^[0-9]$/.test(e.key)) this.press(e.key);
                        if (e.key === 'Backspace') this.pin = this.pin.slice(0, -1);
                        if (e.key === 'Escape') this.back();
                    });
                },
            }));
        });
        sessionStorage.removeItem('sales_form_autosave');
    </script>

</body>
</html>
