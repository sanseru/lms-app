<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\InvoiceController;
use Modules\Finance\Http\Controllers\OverviewController;
use Modules\Finance\Http\Controllers\PackageController;
use Modules\Finance\Http\Controllers\PaymentController;
use Modules\Finance\Http\Controllers\PayrollController;

Route::prefix('finance')->name('finance.')->group(function () {
    Route::middleware('can:finance.manage')->group(function () {
        Route::get('/', OverviewController::class)->name('overview');
        Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
        Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

        Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
        Route::post('packages', [PackageController::class, 'store'])->name('packages.store');
        Route::put('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::post('packages/assign', [PackageController::class, 'assign'])->name('packages.assign');
        Route::post('student-packages/{studentPackage}/cancel', [PackageController::class, 'cancel'])->name('packages.cancel');
    });

    Route::middleware('can:finance.payroll')->group(function () {
        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::post('payroll/{payout}/paid', [PayrollController::class, 'markPaid'])->name('payroll.paid');
        Route::delete('payroll/{payout}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    });

    Route::get('my-fees', [PayrollController::class, 'mine'])->middleware('role:teacher')->name('my-fees');
    Route::get('payroll/{payout}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::get('payroll/{payout}/proof', [PayrollController::class, 'proof'])->name('payroll.proof');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/receipt', [InvoiceController::class, 'receipt'])->name('invoices.receipt');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
    Route::get('payments/{payment}/proof', [PaymentController::class, 'proof'])->name('payments.proof');
});
