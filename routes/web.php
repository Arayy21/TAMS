<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Hanya untuk tamu (belum login)
Route::middleware(['guest', 'no-back'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Wajib login
Route::middleware(['auth', 'no-back'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('aset', AssetController::class)
        ->parameters(['aset' => 'asset'])
        ->names('assets');

    Route::resource('kategori', CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['kategori' => 'category'])
        ->names('categories');

    Route::resource('lokasi', LocationController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['lokasi' => 'location'])
        ->names('locations');

    Route::get('/riwayat', [HistoryController::class, 'index'])->name('histories.index');
});