<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockOpnameController;
use Illuminate\Support\Facades\Route;

// Halaman awal diarahkan sesuai role
Route::get('/', function () {
    return redirect()->route(auth()->user()?->isAdmin() ? 'dashboard' : 'assets.index');
});

// Hanya tamu
Route::middleware(['guest', 'no-back'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Wajib login
Route::middleware(['auth', 'no-back'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin dan Pengguna: kelola data aset
    Route::middleware('role:admin,staff')->group(function () {
        Route::resource('aset', AssetController::class)
            ->parameters(['aset' => 'asset'])
            ->names('assets');
    });

    Route::get('peminjam', [LoanController::class, 'index'])->name('loans.index');
    Route::get('peminjam/tambah', [LoanController::class, 'create'])->name('loans.create');
    Route::post('peminjam', [LoanController::class, 'store'])->name('loans.store');
    Route::post('peminjam/{loan}/kembali', [LoanController::class, 'returnLoan'])->name('loans.return');
    Route::get('peminjam/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
    Route::put('peminjam/{loan}', [LoanController::class, 'update'])->name('loans.update');

    // Khusus Admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('kategori', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['kategori' => 'category'])
            ->names('categories');

        Route::resource('lokasi', LocationController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['lokasi' => 'location'])
            ->names('locations');

        Route::get('/riwayat', [HistoryController::class, 'index'])->name('histories.index');
        Route::get('laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('laporan/cetak', [ReportController::class, 'print'])->name('reports.print');
        Route::get('laporan/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('laporan/word', [ReportController::class, 'word'])->name('reports.word');
        Route::get('laporan/excel', [ReportController::class, 'excel'])->name('reports.excel');
        Route::get('laporan/csv', [ReportController::class, 'csv'])->name('reports.csv');
        Route::get('opname', [StockOpnameController::class, 'index'])->name('opnames.index');
        Route::get('opname/buat', [StockOpnameController::class, 'create'])->name('opnames.create');
        Route::post('opname', [StockOpnameController::class, 'store'])->name('opnames.store');
        Route::get('opname/{opname}', [StockOpnameController::class, 'show'])->name('opnames.show');
        Route::put('opname/{opname}/item/{item}', [StockOpnameController::class, 'updateItem'])->name('opnames.items.update');
        Route::get('opname/{opname}/selesai', [StockOpnameController::class, 'finishForm'])->name('opnames.finish.form');
        Route::post('opname/{opname}/selesai', [StockOpnameController::class, 'finish'])->name('opnames.finish');
    });
});