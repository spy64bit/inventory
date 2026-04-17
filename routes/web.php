<?php

use App\Http\Controllers\Login;
use App\Http\Controllers\Logout;
use App\Http\Controllers\Register;
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
});
