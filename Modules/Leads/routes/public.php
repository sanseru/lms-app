<?php

use Illuminate\Support\Facades\Route;
use Modules\Leads\Http\Controllers\ApplyController;

Route::get('free-trial', [ApplyController::class, 'create'])->name('leads.apply');
Route::post('free-trial', [ApplyController::class, 'store'])->middleware('throttle:5,1')->name('leads.apply.store');
