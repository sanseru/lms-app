<?php

use Illuminate\Support\Facades\Route;
use Modules\Leads\Http\Controllers\LeadController;
use Modules\Leads\Http\Controllers\TrialController;

Route::prefix('leads')->name('leads.')->group(function () {
    Route::middleware('can:leads.trial')->group(function () {
        Route::get('trials', [TrialController::class, 'index'])->name('trials');
        Route::post('{lead}/trial-result', [TrialController::class, 'result'])->name('result');
    });

    Route::middleware('can:leads.manage')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::get('{lead}', [LeadController::class, 'show'])->name('show');
        Route::put('{lead}', [LeadController::class, 'update'])->name('update');
        Route::post('{lead}/notes', [LeadController::class, 'note'])->name('note');
        Route::post('{lead}/contacted', [LeadController::class, 'contacted'])->name('contacted');
        Route::post('{lead}/schedule-trial', [LeadController::class, 'schedule'])->name('schedule');
        Route::post('{lead}/lost', [LeadController::class, 'lose'])->name('lose');
        Route::post('{lead}/reopen', [LeadController::class, 'reopen'])->name('reopen');
        Route::post('{lead}/convert', [LeadController::class, 'convert'])->name('convert');
    });
});
