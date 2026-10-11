<?php

use Illuminate\Support\Facades\Route;
use Modules\Leads\Http\Controllers\ApplyController;

Route::get('coba-gratis', [ApplyController::class, 'create'])->name('leads.apply');
Route::post('coba-gratis', [ApplyController::class, 'store'])->middleware('throttle:5,1')->name('leads.apply.store');
