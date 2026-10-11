<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\MonthlyReportController;

Route::prefix('laporan-bulanan')->name('reports.')->middleware('can:reports.view')->group(function () {
    Route::get('/', [MonthlyReportController::class, 'index'])->name('index');
    Route::get('{student}/{month}', [MonthlyReportController::class, 'show'])->name('show');
    Route::put('{student}/{month}/catatan', [MonthlyReportController::class, 'note'])->middleware('can:reports.note')->name('note');
});
