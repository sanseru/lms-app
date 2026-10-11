<?php

use Illuminate\Support\Facades\Route;
use Modules\Certificates\Http\Controllers\CertificateController;

Route::get('sertifikat/{code}', [CertificateController::class, 'verify'])
    ->where('code', '[A-Za-z0-9]{6,12}')->middleware('throttle:30,1')->name('certificates.verify');
