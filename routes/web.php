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

    // Dashboard: Riwayat Analisis
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard,view')
        ->name('dashboard');
    Route::delete('/dashboard/match-results/{id}', [DashboardController::class, 'destroyMatchResult'])
        ->middleware('permission:dashboard,delete')
        ->name('dashboard.match-results.destroy');
    Route::delete('/dashboard/match-batch', [DashboardController::class, 'destroyMatchBatch'])
        ->middleware('permission:matching,delete')
        ->name('dashboard.match-batch.destroy');

    // Halaman utama: Import & Matching
    Route::get('/', [ReplenishmentController::class, 'index'])
        ->middleware('permission:matching,view')
        ->name('replenishment.index');

    // Halaman History: Replenishment Report History
    Route::get('/replenishment/report-history', [ReplenishmentController::class, 'reportHistory'])
        ->middleware('permission:report_history,view')
        ->name('replenishment.report-history');

    // Halaman History: Analysis Replenishment History
    Route::get('/replenishment/analysis-history', [ReplenishmentController::class, 'analysisHistory'])
        ->middleware('permission:analysis_history,view')
        ->name('replenishment.analysis-history');

    // Detail Hasil Matching (gabungan Shift 1 & Shift 2 per tanggal & cabang)
    Route::get('/replenishment/match-detail', [ReplenishmentController::class, 'matchDetail'])
        ->middleware('permission:matching,view')
        ->name('replenishment.match-detail');

    // Detail Import History
    Route::get('/replenishment/history/{import}', [ReplenishmentController::class, 'showHistory'])
        ->middleware('permission:matching,view')
        ->name('replenishment.history.show');

    // Download file import dari database
    Route::get('/replenishment/history/{import}/download', [ReplenishmentController::class, 'download'])
        ->name('replenishment.history.download');

    // Proses upload & matching
    Route::post('/replenishment/import', [ReplenishmentController::class, 'store'])
        ->middleware('permission:matching,create')
        ->name('replenishment.import');

    // Manajemen Pengguna & Role
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:users,view')->name('index');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:users,create')->name('store');
        Route::put('/roles/{role}', [UserController::class, 'updateRolePermissions'])->middleware('permission:users,edit')->name('roles.update');
        Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:users,edit')->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('permission:users,delete')->name('destroy');
    });

    // Application Log
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [AppLogController::class, 'index'])->middleware('permission:logs,view')->name('index');
        Route::post('/clear', [AppLogController::class, 'clearActivityLogs'])->middleware('permission:logs,delete')->name('clear');
    });

    // Master Data Cabang
    Route::prefix('master/branches')->name('master.branches.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BranchShiftController::class, 'indexBranches'])->middleware('permission:master_branches,view')->name('index');
        Route::post('/', [\App\Http\Controllers\BranchShiftController::class, 'storeBranch'])->middleware('permission:master_branches,create')->name('store');
        Route::put('/{branch}', [\App\Http\Controllers\BranchShiftController::class, 'updateBranch'])->middleware('permission:master_branches,edit')->name('update');
        Route::patch('/{branch}/toggle', [\App\Http\Controllers\BranchShiftController::class, 'toggleBranch'])->middleware('permission:master_branches,edit')->name('toggle');
        Route::delete('/{branch}', [\App\Http\Controllers\BranchShiftController::class, 'destroyBranch'])->middleware('permission:master_branches,delete')->name('destroy');
    });

    // Master Data Shift Management
    Route::prefix('master/shifts')->name('master.shifts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BranchShiftController::class, 'index'])->middleware('permission:master_shifts,view')->name('index');
        Route::post('/branch', [\App\Http\Controllers\BranchShiftController::class, 'storeBranch'])->middleware('permission:master_shifts,create')->name('branch.store');
        Route::put('/branch/{branch}', [\App\Http\Controllers\BranchShiftController::class, 'updateBranch'])->middleware('permission:master_shifts,edit')->name('branch.update');
        Route::patch('/branch/{branch}/toggle', [\App\Http\Controllers\BranchShiftController::class, 'toggleBranch'])->middleware('permission:master_shifts,edit')->name('branch.toggle');
        Route::delete('/branch/{branch}', [\App\Http\Controllers\BranchShiftController::class, 'destroyBranch'])->middleware('permission:master_shifts,delete')->name('branch.destroy');
        Route::post('/branch/{branch}/shift', [\App\Http\Controllers\BranchShiftController::class, 'storeShift'])->middleware('permission:master_shifts,create')->name('shift.store');
        Route::put('/shift/{shift}', [\App\Http\Controllers\BranchShiftController::class, 'updateShift'])->middleware('permission:master_shifts,edit')->name('shift.update');
        Route::delete('/shift/{shift}', [\App\Http\Controllers\BranchShiftController::class, 'destroyShift'])->middleware('permission:master_shifts,delete')->name('shift.destroy');
    });
});
