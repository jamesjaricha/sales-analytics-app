@extends('layouts.app')

@section('content')
<!-- Hidden flash message data -->
@if(session('success'))
<div data-flash-success="{{ session('success') }}" class="hidden"></div>
@endif
@if(session('error'))
<div data-flash-error="{{ session('error') }}" class="hidden"></div>
@endif

{{-- Session Countdown Timer --}}
<div id="sessionTimerBar" class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-green-500 via-blue-500 to-green-500 shadow-md transition-all duration-1000" style="height: 4px;"></div>
<div id="sessionTimerText" class="fixed top-4 right-4 bg-white shadow-lg rounded-full px-4 py-2 text-sm font-semibold text-gray-700 z-50 border-2 border-green-500 hidden">
    <span class="text-green-600">⏱️</span> <span id="timeRemaining">10:00:00</span>
</div>

{{-- Session Warning Modal --}}
<div id="sessionWarningModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center transform scale-95 transition-transform">
        <div class="text-7xl mb-4 animate-bounce">⚠️</div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">Still Working?</h3>
        <p class="text-gray-600 mb-2">Your session will expire soon!</p>
        <p class="text-lg mb-6">Time remaining: <span id="warningCountdown" class="font-bold text-red-600 text-2xl">15:00</span></p>
        <button onclick="window.SessionManager.refreshSession()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-lg mb-3">
            ✓ Yes, I'm Still Here!
        </button>
        <p class="text-xs text-gray-500">Your work is auto-saved every 10 seconds</p>
    </div>
</div>

<div class="min-h-screen bg-gray-50 py-8" data-keep-session-alive>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-semibold text-gray-900">Record Daily Sales</h1>
            <p class="text-gray-500 mt-2">Enter today's sales data</p>
        </div>

        <!-- Hidden error data for toast notifications -->
        @if($errors->any())
        <div data-validation-errors="{{ json_encode($errors->all()) }}" class="hidden"></div>
        @endif

        <form action="{{ route('sales.store') }}" method="POST" id="salesForm"
            data-route-store="{{ route('sales.store') }}"
            data-route-product-search="{{ route('sales.products.search') }}"
            data-route-quick-create="{{ route('sales.products.quick-create') }}"
            data-route-get-draft="{{ route('sales.drafts.get') }}">
            @csrf

            <!-- Sale Date -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sale Date *</label>
                <input type="date" name="sale_date" value="{{ old('sale_date', date('Y-m-d')) }}"
                    class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>

            <!-- Sales Items -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Sales Items *</h2>

                <div class="mb-4">
                    <button type="button" id="addItemBtn" class="btn-primary">
                        + Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead>
                            <tr class="border-b-2 border-gray-300">
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[35%]">Product Name</th>
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[15%]">Quantity</th>
                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-700 w-[20%]">Unit Price</th>
                                <th class="text-right py-3 px-2 text-sm font-semibold text-gray-700 w-[18%]">Total</th>
                                <th class="text-center py-3 px-2 text-sm font-semibold text-gray-700 w-[12%]"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsContainer">
                            <!-- Items will be added here dynamically -->
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-end">
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Sales Value</p>
                            <p class="text-2xl font-bold text-gray-900">ZMW <span id="totalSalesValue">0.00</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deductions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Deductions</h2>
                    <button type="button" id="addDeductionBtn"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        + Add Deduction
                    </button>
                </div>

                <div id="deductionsContainer" class="space-y-3">
                    <!-- Deductions will be added here -->
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-end">
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Deductions</p>
                            <p class="text-2xl font-bold text-red-600">ZMW <span id="totalDeductions">0.00</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cash at Hand -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200 p-6 mb-6">
                <div class="text-center">
                    <p class="text-sm font-medium text-green-700 mb-2">Cash at Hand</p>
                    <p class="text-4xl font-bold text-green-900">ZMW <span id="cashAtHand">0.00</span></p>
                </div>
            </div>

            <!-- Submit & Clear Buttons -->
            <div class="flex gap-4 mt-6">
                <button type="button" id="saveDraftBtn" class="btn-secondary flex-1">
                    Save as Draft
                </button>
                <button type="submit" class="btn-primary flex-1">
                    Submit Report
                </button>
                <!-- Clear Form button removed -->
            </div>

        </form>

    </div>
</div>

<!-- Quick Create Product Modal -->
<div id="createProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Create New Product</h3>
                <button type="button" id="modalCloseBtn" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="quickCreateForm" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
                    <input type="text" id="newProductName"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
                        placeholder="e.g., Luxpower 6kw Inverter" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price (ZMW) *</label>
                    <input type="number" step="0.01" id="newProductPrice"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
                        placeholder="0.00" min="0" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">SKU (Optional)</label>
                    <input type="text" id="newProductSku"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
                        placeholder="e.g., LUX-6KW-001">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description (Optional)</label>
                    <textarea id="newProductDescription" rows="3"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm resize-none"
                        placeholder="Brief product description"></textarea>
                </div>

                <div id="modalError" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
                <button type="button" id="modalCancelBtn"
                    class="flex-1 px-4 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" id="createBtn"
                    class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                    Create Product
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
@vite('resources/css/sales-form.css')
@endpush

@push('scripts')
@vite('resources/js/sales-form.js')
{{-- Enhanced Session Manager with Countdown & Warnings --}}
<script type="module">
    const SessionManager = {
        config: {
            sessionLifetimeMinutes: {
                {
                    $sessionLifetime ?? 600
                }
            }, // From backend
            warningMinutes: 15, // Show warning 15 min before expiry
            pingInterval: 5 * 60 * 1000, // Ping every 5 minutes
            activityTimeout: 2 * 60 * 1000, // Consider inactive after 2 min
            pingUrl: '/session/ping',
        },

        state: {
            sessionExpiresAt: null,
            lastActivityTime: Date.now(),
            timerInterval: null,
            pingTimer: null,
            warningShown: false,
            isActive: false,
        },

        init() {
            if (!document.querySelector('[data-keep-session-alive]')) return;

            this.resetSessionTimer();
            this.startCountdown();
            this.startActivityTracking();
            this.startPeriodicPing();
            this.state.isActive = true;

            console.log('[SessionManager] Initialized - Session lifetime:', this.config.sessionLifetimeMinutes, 'minutes');
        },

        resetSessionTimer() {
            const now = Date.now();
            this.state.sessionExpiresAt = now + (this.config.sessionLifetimeMinutes * 60 * 1000);
            this.state.warningShown = false;
            this.hideWarning();

            // Show timer display
            const timerText = document.getElementById('sessionTimerText');
            if (timerText) timerText.classList.remove('hidden');
        },

        startCountdown() {
            this.state.timerInterval = setInterval(() => {
                this.updateCountdown();
            }, 1000); // Update every second
        },

        updateCountdown() {
            const now = Date.now();
            const remaining = this.state.sessionExpiresAt - now;

            if (remaining <= 0) {
                this.handleSessionExpired();
                return;
            }

            const totalSeconds = Math.floor(remaining / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            // Update time display
            const timeRemaining = document.getElementById('timeRemaining');
            if (timeRemaining) {
                timeRemaining.textContent = `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }

            // Update progress bar
            const progressBar = document.getElementById('sessionTimerBar');
            if (progressBar) {
                const totalTime = this.config.sessionLifetimeMinutes * 60 * 1000;
                const percentage = (remaining / totalTime) * 100;
                progressBar.style.width = percentage + '%';

                // Change color based on time remaining
                if (percentage < 10) {
                    progressBar.className = 'fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-red-600 to-red-500 shadow-md transition-all duration-1000';
                } else if (percentage < 25) {
                    progressBar.className = 'fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-orange-500 to-yellow-500 shadow-md transition-all duration-1000';
                } else {
                    progressBar.className = 'fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-green-500 via-blue-500 to-green-500 shadow-md transition-all duration-1000';
                }
            }

            // Show warning at configured time
            const warningThreshold = this.config.warningMinutes * 60 * 1000;
            if (remaining <= warningThreshold && !this.state.warningShown) {
                this.showWarning();
            }

            // Update warning countdown if modal is visible
            if (!this.state.warningShown === false) {
                const warningCountdown = document.getElementById('warningCountdown');
                if (warningCountdown) {
                    const mins = Math.floor(totalSeconds / 60);
                    const secs = totalSeconds % 60;
                    warningCountdown.textContent = `${mins}:${String(secs).padStart(2, '0')}`;
                }
            }
        },

        showWarning() {
            this.state.warningShown = true;
            const modal = document.getElementById('sessionWarningModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.querySelector('.bg-white').classList.add('scale-100');
                modal.querySelector('.bg-white').classList.remove('scale-95');
            }
            console.log('[SessionManager] Warning shown - Session expiring soon');
        },

        hideWarning() {
            const modal = document.getElementById('sessionWarningModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.querySelector('.bg-white').classList.remove('scale-100');
                modal.querySelector('.bg-white').classList.add('scale-95');
            }
        },

        async refreshSession() {
            try {
                const response = await fetch(this.config.pingUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });

                if (response.ok) {
                    this.resetSessionTimer();
                    console.log('[SessionManager] Session refreshed successfully');

                    // Show success feedback
                    const timeRemaining = document.getElementById('timeRemaining');
                    if (timeRemaining) {
                        timeRemaining.parentElement.classList.add('animate-pulse');
                        setTimeout(() => {
                            timeRemaining.parentElement.classList.remove('animate-pulse');
                        }, 1000);
                    }

                    return true;
                } else {
                    console.warn('[SessionManager] Refresh failed:', response.status);
                    return false;
                }
            } catch (error) {
                console.error('[SessionManager] Refresh error:', error);
                return false;
            }
        },

        startActivityTracking() {
            const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
            events.forEach(event => {
                document.addEventListener(event, () => {
                    this.state.lastActivityTime = Date.now();
                }, {
                    passive: true
                });
            });
        },

        isUserActive() {
            const timeSinceActivity = Date.now() - this.state.lastActivityTime;
            return timeSinceActivity < this.config.activityTimeout;
        },

        startPeriodicPing() {
            this.state.pingTimer = setInterval(async () => {
                if (this.isUserActive()) {
                    await this.refreshSession();
                } else {
                    console.log('[SessionManager] User inactive, skipping ping');
                }
            }, this.config.pingInterval);
        },

        handleSessionExpired() {
            console.warn('[SessionManager] Session expired!');
            clearInterval(this.state.timerInterval);
            clearInterval(this.state.pingTimer);

            // Try to auto-save if possible
            if (typeof autosaveNow === 'function') {
                try {
                    autosaveNow();
                    console.log('[SessionManager] Auto-saved before expiry');
                } catch (e) {
                    console.error('[SessionManager] Auto-save failed:', e);
                }
            }

            alert('Your session has expired. Please save your work and refresh the page.');
        }
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => SessionManager.init());
    } else {
        SessionManager.init();
    }

    // Export for manual use
    window.SessionManager = SessionManager;
</script>
@endpush
@endsection