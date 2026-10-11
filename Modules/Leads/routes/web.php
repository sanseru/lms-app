<?php

use Illuminate\Support\Facades\Route;
use Modules\Leads\Http\Controllers\LeadController;
use Modules\Leads\Http\Controllers\TrialController;

Route::prefix('calon-siswa')->name('leads.')->group(function () {
    Route::middleware('can:leads.trial')->group(function () {
        Route::get('trial', [TrialController::class, 'index'])->name('trials');
        Route::post('{lead}/hasil-trial', [TrialController::class, 'result'])->name('result');
    });

    Route::middleware('can:leads.manage')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::get('{lead}', [LeadController::class, 'show'])->name('show');
        Route::put('{lead}', [LeadController::class, 'update'])->name('update');
        Route::post('{lead}/catatan', [LeadController::class, 'note'])->name('note');
        Route::post('{lead}/dihubungi', [LeadController::class, 'contacted'])->name('contacted');
        Route::post('{lead}/jadwal-trial', [LeadController::class, 'schedule'])->name('schedule');
        Route::post('{lead}/tidak-lanjut', [LeadController::class, 'lose'])->name('lose');
        Route::post('{lead}/buka-lagi', [LeadController::class, 'reopen'])->name('reopen');
        Route::post('{lead}/jadikan-siswa', [LeadController::class, 'convert'])->name('convert');
    });
});
