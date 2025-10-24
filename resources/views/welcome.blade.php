<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics - Modern Sales Tracking & Reporting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="fixed w-full bg-white/80 backdrop-blur-lg z-50 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Sales Analytics</span>
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-900 font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 font-medium">Login</a>
                        <a href="{{ route('register') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 opacity-60"></div>
        
        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm font-semibold mb-6">
                    <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                    Modern Sales Management Platform
                </div>
                
                <h1 class="text-5xl md:text-7xl font-bold text-gray-900 mb-6 leading-tight">
                    Track Sales.<br/>
                    <span class="gradient-text">Grow Your Business.</span>
                </h1>
                
                <p class="text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
                    Beautiful, powerful sales tracking and analytics platform. Record daily sales, manage inventory, and generate insights—all in one place.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('login') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:shadow-2xl transition transform hover:-translate-y-1">
                        Login
                    </a>
                   
                </div>
                
              
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Everything You Need</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Powerful features designed to help you manage and grow your business efficiently.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Real-time Analytics</h3>
                    <p class="text-gray-600">Beautiful dashboards with live sales data, trends, and insights at your fingertips.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Daily Sales Entry</h3>
                    <p class="text-gray-600">Quick and easy daily sales recording with smart product search and automatic calculations.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Product Management</h3>
                    <p class="text-gray-600">Organize your inventory with ease. Add, edit, and track all your products in one place.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">PDF Reports</h3>
                    <p class="text-gray-600">Generate professional PDF reports with your branding for clients and accounting.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-pink-600 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Weekly Comparisons</h3>
                    <p class="text-gray-600">Compare performance week-over-week with beautiful charts and percentage changes.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Mobile Friendly</h3>
                    <p class="text-gray-600">Fully responsive design. Track sales on the go from any device, anywhere.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-600 to-purple-600 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full filter blur-3xl animate-float"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full filter blur-3xl animate-float" style="animation-delay: 3s;"></div>
        </div>
        
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">Ready to Transform Your Business?</h2>
            <p class="text-xl text-blue-100 mb-10">
                Join businesses already using Sales Analytics to track, analyze, and grow.
            </p>
            <a href="{{ route('login') }}" class="inline-block bg-white text-blue-600 px-8 py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition transform hover:-translate-y-1">
                Lets Go
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <div class="flex items-center justify-center gap-2 mb-4">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="text-lg font-bold text-white">Sales Analytics</span>
            </div>
            <p class="text-sm mb-4">Modern sales tracking and analytics platform</p>
            <p class="text-sm">© {{ date('Y') }} Sales Analytics. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
