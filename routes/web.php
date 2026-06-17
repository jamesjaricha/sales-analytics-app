<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
// RoleMiddleware now registered as alias 'role' in bootstrap/app.php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DailySalesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DayEndController;

// Redirect root to login - prevents unnecessary processing
Route::get('/', function () {
    return redirect()->route('login');
});

// Health check endpoint (no authentication required for monitoring)
// Optimized for shared hosting - minimal processing
Route::get('/health', function () {
    try {
        // Quick database check
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return response()->json(['status' => 'ok', 'timestamp' => now()], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Database connection failed'], 500);
    }
})->name('health.check');

// Session keep-alive endpoint (authenticated users only)
Route::get('/session/ping', function () {
    if (!auth()->check()) {
        return response()->json(['status' => 'unauthenticated'], 401);
    }
    return response()->json(['status' => 'ok', 'timestamp' => now()], 200);
})->middleware('auth')->name('session.ping');

// Combined auth routes for both admin and sales_rep
Route::middleware(['auth', 'role:admin,sales_rep', 'throttle:300,1'])->group(function () {
    // POS — point-of-sale invoicing (Module 1: new default record screen)
    Route::get('/pos', [SaleController::class, 'create'])->name('pos.create');
    Route::post('/pos', [SaleController::class, 'store'])->name('pos.store')->middleware('throttle:120,1');
    Route::post('/pos/{sale}/void', [SaleController::class, 'void'])->name('pos.void')->whereNumber('sale')->middleware('throttle:60,1');

    // Sales - Recording (both admin and sales_rep) — legacy batch screen (fallback)
    Route::get('/sales/create', [DailySalesController::class, 'create'])->name('sales.create');
    Route::post('/sales', [DailySalesController::class, 'store'])->name('sales.store');
    // My Sales - Sales rep can view their own sales
    Route::get('/my-sales', [DailySalesController::class, 'mySales'])->name('sales.my-sales');
    // Sales helpers (specific routes must come before wildcard {id} routes)
    Route::get('/sales/products/search', [DailySalesController::class, 'searchProducts'])->name('sales.products.search');
    Route::post('/sales/products/quick-create', [DailySalesController::class, 'quickCreateProduct'])->name('sales.products.quick-create')->middleware('throttle:60,1');
    Route::get('/sales/drafts', [DailySalesController::class, 'getDraft'])->name('sales.drafts.get');
    // Sales detail routes (wildcard routes come last)
    Route::get('/sales/{id}', [DailySalesController::class, 'show'])->name('sales.show')->whereNumber('id');
    Route::get('/sales/{id}/pdf', [DailySalesController::class, 'exportPDF'])->name('sales.pdf')->middleware('throttle:20,1')->whereNumber('id');

    // Stock Management - View Only (for both admin and sales_rep)
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/low-stock', [StockController::class, 'lowStock'])->name('stock.low-stock');
    Route::get('/stock/reports', [StockController::class, 'reports'])->name('stock.reports');
    Route::get('/stock/daily-pdf', [StockController::class, 'dailyStockPDF'])->name('stock.daily-pdf')->middleware('throttle:20,1');
    Route::get('/stock/{product}/history', [StockController::class, 'history'])->name('stock.history');
});

// Admin-only routes
Route::middleware(['auth', 'role:admin', 'throttle:300,1'])->group(function () {
    // Dashboard - Admin only
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Day-End reconciliation (Module 2, admin only)
    Route::get('/day-end', [DayEndController::class, 'create'])->name('day-end.create');
    Route::post('/day-end', [DayEndController::class, 'store'])->name('day-end.store')->middleware('throttle:30,1');
    Route::get('/day-end/{dayEnd}', [DayEndController::class, 'show'])->name('day-end.show')->whereNumber('dayEnd');

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
Route::middleware(['auth', 'role:admin', 'throttle:300,1'])->group(function () {
    Route::get('/users', [App\Http\Controllers\UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create', [App\Http\Controllers\UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users', [App\Http\Controllers\UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [App\Http\Controllers\UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [App\Http\Controllers\UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [App\Http\Controllers\UserManagementController::class, 'destroy'])->name('users.destroy');
});

// Profile routes (all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
