<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

// 1. Halaman utama langsung diarahkan ke form login
Route::get('/', function () {
    return redirect('/login');
});

// 2. RUTE IOT (Hanya bisa diakses jika sudah Login)
Route::middleware(['auth', 'verified'])->group(function () {
    // Halaman Web
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/history', [DashboardController::class, 'history'])->name('history');

    // Rute API & Kontrol (Jalur Belakang Layar)
    Route::get('/api/data', [DashboardController::class, 'ambilDataTerbaru']);
    Route::get('/api/riwayat', [DashboardController::class, 'riwayatData']);
    Route::get('/api/history-filter', [DashboardController::class, 'historyFilter']);
    Route::post('/kontrol', [DashboardController::class, 'kontrolRelay']);
    Route::get('/history/export', [DashboardController::class, 'exportCsv']);
});

// 3. RUTE PROFIL (Bawaan Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Memanggil rute autentikasi bawaan Breeze (Login, Register, Lupa Password)
require __DIR__.'/auth.php';
