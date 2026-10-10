<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\InvoiceController;
use Modules\Finance\Http\Controllers\OverviewController;
use Modules\Finance\Http\Controllers\PaymentController;

Route::prefix('finance')->name('finance.')->group(function () {
    Route::middleware('can:finance.manage')->group(function () {
        Route::get('/', OverviewController::class)->name('overview');
        Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
        Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    });

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
    Route::get('payments/{payment}/proof', [PaymentController::class, 'proof'])->name('payments.proof');
});
