<?php

use Illuminate\Support\Facades\Route;
use Modules\Certificates\Http\Controllers\CertificateController;

Route::name('certificates.')->prefix('sertifikat-saya')->group(function () {
    Route::get('/', [CertificateController::class, 'index'])->name('index');
    Route::post('/', [CertificateController::class, 'store'])->name('store');
    Route::get('{certificate}', [CertificateController::class, 'show'])->name('show');
    Route::post('{certificate}/cabut', [CertificateController::class, 'revoke'])->name('revoke');
});
