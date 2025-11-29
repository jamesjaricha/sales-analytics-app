<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
// RoleMiddleware now registered as alias 'role' in bootstrap/app.php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DailySalesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\StockController;

Route::middleware(['auth', 'role:admin,sales_rep', 'throttle:60,1'])->group(function () {
    // Sales - Recording (both admin and sales_rep)
    Route::get('/sales/create', [DailySalesController::class, 'create'])->name('sales.create');
    Route::post('/sales', [DailySalesController::class, 'store'])->name('sales.store')->middleware('throttle:10,1');
    // My Sales - Sales rep can view their own sales
    Route::get('/my-sales', [DailySalesController::class, 'mySales'])->name('sales.my-sales');
    // Sales helpers (specific routes must come before wildcard {id} routes)
    Route::get('/sales/products/search', [DailySalesController::class, 'searchProducts'])->name('sales.products.search');
    Route::post('/sales/products/quick-create', [DailySalesController::class, 'quickCreateProduct'])->name('sales.products.quick-create')->middleware('throttle:20,1');
    Route::get('/sales/drafts', [DailySalesController::class, 'getDraft'])->name('sales.drafts.get');
    // Sales detail routes (wildcard routes come last)
    Route::get('/sales/{id}', [DailySalesController::class, 'show'])->name('sales.show');
    Route::get('/sales/{id}/pdf', [DailySalesController::class, 'exportPDF'])->name('sales.pdf')->middleware('throttle:20,1');

    // Stock Management - View Only (for both admin and sales_rep)
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/low-stock', [StockController::class, 'lowStock'])->name('stock.low-stock');
    Route::get('/stock/reports', [StockController::class, 'reports'])->name('stock.reports');
    Route::get('/stock/daily-pdf', [StockController::class, 'dailyStockPDF'])->name('stock.daily-pdf')->middleware('throttle:20,1');
    Route::get('/stock/{product}/history', [StockController::class, 'history'])->name('stock.history');
});

// Admin-only routes
Route::middleware(['auth', 'role:admin', 'throttle:60,1'])->group(function () {
    // Dashboard - Admin only
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Sales List - Admin only (view all sales)
    Route::get('/sales', [DailySalesController::class, 'index'])->name('sales.index');

    // Monthly Report - Admin only
    Route::get('/reports/monthly', [MonthlyReportController::class, 'index'])->name('reports.monthly');
    Route::get('/reports/monthly/pdf', [MonthlyReportController::class, 'exportPDF'])->name('reports.monthly.pdf')->middleware('throttle:20,1');

    // Product CRUD - Admin only
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store')->middleware('throttle:20,1');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update')->middleware('throttle:20,1');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')->middleware('throttle:20,1');

    // Stock Adjustments - Admin only
    Route::post('/stock/adjust', [StockController::class, 'store'])->name('stock.store')->middleware('throttle:20,1');
});

// Admin-only User Management
Route::middleware(['auth', 'role:admin', 'throttle:60,1'])->group(function () {
    Route::get('/users', [App\Http\Controllers\UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create', [App\Http\Controllers\UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users', [App\Http\Controllers\UserManagementController::class, 'store'])->name('users.store')->middleware('throttle:5,1');
    Route::get('/users/{user}/edit', [App\Http\Controllers\UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [App\Http\Controllers\UserManagementController::class, 'update'])->name('users.update')->middleware('throttle:10,1');
    Route::delete('/users/{user}', [App\Http\Controllers\UserManagementController::class, 'destroy'])->name('users.destroy')->middleware('throttle:3,1');
});

Route::get('/', function () {
    return redirect()->route('login');
});

// Health check endpoint (no authentication required for monitoring)
Route::get('/health', [App\Http\Controllers\HealthController::class, 'check'])->name('health.check');

// Profile routes...
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// (Removed duplicate route group to avoid conflicts)




require __DIR__ . '/auth.php';
