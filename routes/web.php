<?php

use App\Http\Controllers\AppLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReplenishmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Autentikasi Publik ────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Rute Terproteksi (Login Diperlukan) ────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard: Riwayat Analisis (Semua Role: Admin, Operator, Viewer)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::delete('/dashboard/match-results/{id}', [DashboardController::class, 'destroyMatchResult'])
        ->middleware('role:admin,operator')
        ->name('dashboard.match-results.destroy');
    Route::delete('/dashboard/match-batch', [DashboardController::class, 'destroyMatchBatch'])
        ->middleware('role:admin,operator')
        ->name('dashboard.match-batch.destroy');

    // Halaman utama: Import & Matching
    Route::get('/', [ReplenishmentController::class, 'index'])->name('replenishment.index');

    // Halaman History: Replenishment Report History
    Route::get('/replenishment/report-history', [ReplenishmentController::class, 'reportHistory'])->name('replenishment.report-history');

    // Halaman History: Analysis Replenishment History
    Route::get('/replenishment/analysis-history', [ReplenishmentController::class, 'analysisHistory'])->name('replenishment.analysis-history');

    // Detail Hasil Matching (gabungan Shift 1 & Shift 2 per tanggal & cabang)
    Route::get('/replenishment/match-detail', [ReplenishmentController::class, 'matchDetail'])->name('replenishment.match-detail');

    // Detail Import History
    Route::get('/replenishment/history/{import}', [ReplenishmentController::class, 'showHistory'])->name('replenishment.history.show');

    // Download file import dari direktori lokal
    Route::get('/replenishment/history/{import}/download', [ReplenishmentController::class, 'download'])->name('replenishment.history.download');

    // Proses upload & matching (Admin & Operator saja)
    Route::post('/replenishment/import', [ReplenishmentController::class, 'store'])
        ->middleware('role:admin,operator')
        ->name('replenishment.import');

    // Manajemen Pengguna & Role (Admin saja)
    Route::middleware('role:admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Application Log (Admin saja)
    Route::middleware('role:admin')->prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [AppLogController::class, 'index'])->name('index');
        Route::post('/clear', [AppLogController::class, 'clearActivityLogs'])->name('clear');
    });

    // Master Data Cabang (Admin saja)
    Route::middleware('role:admin')->prefix('master/branches')->name('master.branches.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BranchShiftController::class, 'indexBranches'])->name('index');
        Route::post('/', [\App\Http\Controllers\BranchShiftController::class, 'storeBranch'])->name('store');
        Route::put('/{branch}', [\App\Http\Controllers\BranchShiftController::class, 'updateBranch'])->name('update');
        Route::patch('/{branch}/toggle', [\App\Http\Controllers\BranchShiftController::class, 'toggleBranch'])->name('toggle');
        Route::delete('/{branch}', [\App\Http\Controllers\BranchShiftController::class, 'destroyBranch'])->name('destroy');
    });

    // Master Data Shift Management (Admin saja)
    Route::middleware('role:admin')->prefix('master/shifts')->name('master.shifts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BranchShiftController::class, 'index'])->name('index');
        Route::post('/branch', [\App\Http\Controllers\BranchShiftController::class, 'storeBranch'])->name('branch.store');
        Route::put('/branch/{branch}', [\App\Http\Controllers\BranchShiftController::class, 'updateBranch'])->name('branch.update');
        Route::patch('/branch/{branch}/toggle', [\App\Http\Controllers\BranchShiftController::class, 'toggleBranch'])->name('branch.toggle');
        Route::delete('/branch/{branch}', [\App\Http\Controllers\BranchShiftController::class, 'destroyBranch'])->name('branch.destroy');
        Route::post('/branch/{branch}/shift', [\App\Http\Controllers\BranchShiftController::class, 'storeShift'])->name('shift.store');
        Route::put('/shift/{shift}', [\App\Http\Controllers\BranchShiftController::class, 'updateShift'])->name('shift.update');
        Route::delete('/shift/{shift}', [\App\Http\Controllers\BranchShiftController::class, 'destroyShift'])->name('shift.destroy');
    });
});
