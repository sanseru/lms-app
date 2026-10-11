<?php

use Illuminate\Support\Facades\Route;
use Modules\Sessions\Http\Controllers\CalendarController;
use Modules\Sessions\Http\Controllers\SessionController;

Route::get('sessions', [SessionController::class, 'index'])->middleware('role:admin|teacher')->name('sessions.index');
Route::get('sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
Route::put('sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
Route::post('sessions/{session}/reschedule', [SessionController::class, 'reschedule'])->name('sessions.reschedule');
Route::post('sessions/{session}/cancel', [SessionController::class, 'cancel'])->name('sessions.cancel');
Route::post('classes/{class}/sessions/generate', [SessionController::class, 'generate'])->name('classes.sessions.generate');
Route::post('calendar', [CalendarController::class, 'regenerate'])->name('calendar.regenerate');
