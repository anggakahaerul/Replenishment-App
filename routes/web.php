<?php

use App\Http\Controllers\ReplenishmentController;
use Illuminate\Support\Facades\Route;

// Halaman utama: Import & Matching
Route::get('/', [ReplenishmentController::class, 'index'])->name('replenishment.index');

// Endpoint proses upload kedua file
Route::post('/replenishment/import', [ReplenishmentController::class, 'store'])->name('replenishment.import');

// Halaman History: Replenishment Report History
Route::get('/replenishment/report-history', [ReplenishmentController::class, 'reportHistory'])->name('replenishment.report-history');

// Halaman History: Analysis Replenishment History
Route::get('/replenishment/analysis-history', [ReplenishmentController::class, 'analysisHistory'])->name('replenishment.analysis-history');

// Detail Import History
Route::get('/replenishment/history/{import}', [ReplenishmentController::class, 'showHistory'])->name('replenishment.history.show');
