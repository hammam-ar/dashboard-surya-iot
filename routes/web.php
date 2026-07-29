<?php
//file web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

// 1. Halaman utama langsung diarahkan ke form login (Bisa dibiarkan tetap seperti ini)
Route::get('/', function () {
    return redirect('/dashboard');
});

// =========================================================================
// --- 🔓 JALUR YANG BISA DIAKSES TANPA LOGIN (DIKELUARKAN DARI MIDDLWARE) ---
// =========================================================================

// Halaman Web Visual
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/history', [DashboardController::class, 'history'])->name('history');

// Jalur Data Belakang Layar (Supaya grafik & tabel tetap muncul datanya)
Route::get('/api/data', [DashboardController::class, 'ambilDataTerbaru']);
Route::get('/api/riwayat', [DashboardController::class, 'riwayatData']);
Route::get('/api/history-filter', [DashboardController::class, 'historyFilter']);

// Jalur Ekspor Data
Route::get('/history/export', [DashboardController::class, 'exportCsv']);
Route::get('/history/export/xlsx', [DashboardController::class, 'exportXlsx']);


// =========================================================================
// --- 🔒 JALUR AMAN (TETAP HARUS LOGIN SEBELUM MASUK) ---
// =========================================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // Rute Kontrol Alat (Tombol Relay/On-Off PLTS dibiarkan di sini agar aman dari orang iseng!)
    Route::post('/kontrol', [DashboardController::class, 'kontrolRelay']);

});

// RUTE PROFIL (Bawaan Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Memanggil rute autentikasi bawaan Breeze (Login, Register, Lupa Password)
require __DIR__.'/auth.php';
