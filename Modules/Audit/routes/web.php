<?php

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\AuditController;

Route::get('audit', [AuditController::class, 'index'])->middleware('can:audit.view')->name('audit.index');
