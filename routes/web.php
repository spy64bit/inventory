<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Login;
use App\Http\Controllers\Logout;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Register;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'guest'], function () {
    Route::inertia('/login', 'Login')->name('login');
    Route::inertia('/register', 'Register')->name('register');
    Route::post('/login', Login::class)->name('login.post');
    Route::post('/register', Register::class)->name('register.post');
});

Route::group(['middleware' => 'auth'], function () {
    Route::post('/logout', Logout::class)->name('logout');

    Route::inertia('/', 'Dashboard')->name('dashboard');

    // Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    // products
    Route::delete('/product/bulk-destroy', [ProductController::class, 'bulkDestroy'])->name('product.bulk-destroy');
    Route::resource('product', ProductController::class);
    // category
    Route::delete('/category/bulk-destroy', [CategoryController::class, 'bulkDestroy'])->name('category.bulk-destroy');
    Route::resource('category', CategoryController::class);
    // supplier
    Route::delete('/supplier/bulk-destroy', [SupplierController::class, 'bulkDestroy'])->name('supplier.bulk-destroy');
    Route::resource('supplier', SupplierController::class);

    // stock movement
    Route::get('/stock-movement', [StockMovementController::class, 'index'])->name('stock-movement.index');

    Route::post('/stock-movement/{product}/stock-in', [StockMovementController::class, 'stockIn'])->name('stock-movement.stock-in');
    Route::post('/stock-movement/{product}/stock-out', [StockMovementController::class, 'stockOut'])->name('stock-movement.stock-out');

});
