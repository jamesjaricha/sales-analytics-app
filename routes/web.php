<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DailySalesController;
use App\Http\Controllers\ProductController;

Route::middleware(['auth', RoleMiddleware::class.':admin,sales_rep'])->group(function () {
    // Sales
    Route::get('/sales', [DailySalesController::class, 'index'])->name('sales.index');
    Route::get('/sales/create', [DailySalesController::class, 'create'])->name('sales.create');
    Route::post('/sales', [DailySalesController::class, 'store'])->name('sales.store');
    Route::get('/sales/{id}', [DailySalesController::class, 'show'])->name('sales.show');
    Route::get('/sales/{id}/pdf', [DailySalesController::class, 'exportPDF'])->name('sales.pdf');
    // Sales helpers
    Route::get('/sales/products/search', [DailySalesController::class, 'searchProducts'])->name('sales.products.search');
    Route::post('/sales/products/quick-create', [DailySalesController::class, 'quickCreateProduct'])->name('sales.products.quick-create');
    Route::get('/sales/drafts', [DailySalesController::class, 'getDraft'])->name('sales.drafts.get');

    // Product CRUD
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
});


Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(RoleMiddleware::class.':admin,sales_rep')
    ->name('dashboard');



Route::get('/', function () {
    return view('welcome');
});

// Profile routes...
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// (Removed duplicate route group to avoid conflicts)




require __DIR__.'/auth.php';
