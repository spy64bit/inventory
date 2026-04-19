<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Login;
use App\Http\Controllers\Logout;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Register;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'guest'], function () {
    Route::inertia('/', 'Welcome')->name('home');
    Route::inertia('/login', 'Login')->name('login');
    Route::inertia('/register', 'Register')->name('register');
    Route::post('/login', Login::class)->name('login.post');
    Route::post('/register', Register::class)->name('register.post');
});

Route::group(['middleware' => 'auth'], function () {
    Route::post('/logout', Logout::class)->name('logout');

    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::delete('/product/bulk-destroy', [ProductController::class, 'bulkDestroy'])->name('product.bulk-destroy');
    Route::resource('product', ProductController::class);
    Route::delete('/category/bulk-destroy', [CategoryController::class, 'bulkDestroy'])->name('category.bulk-destroy');
    Route::resource('category', CategoryController::class);
    Route::delete('/supplier/bulk-destroy', [SupplierController::class, 'bulkDestroy'])->name('supplier.bulk-destroy');
    Route::resource('supplier', SupplierController::class);

});
